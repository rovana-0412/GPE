<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    private const TARGETS = [
        'bien' => ['service' => 'patrimoine', 'resource' => 'biens', 'label' => 'Bien'],
        'acquisition' => ['service' => 'patrimoine', 'resource' => 'acquisitions', 'label' => 'Acquisition'],
        'affectation' => ['service' => 'mouvements', 'resource' => 'affectations', 'label' => 'Affectation'],
        'transfert' => ['service' => 'mouvements', 'resource' => 'transferts', 'label' => 'Transfert'],
        'maintenance' => ['service' => 'mouvements', 'resource' => 'maintenances', 'label' => 'Maintenance'],
    ];

    public function index(
        Request $request,
        string $cibleType,
        string $cibleId,
        MicroserviceClient $client,
    ): View {
        abort_unless(isset(self::TARGETS[$cibleType]), 404);
        abort_unless(ctype_digit($cibleId) && (int) $cibleId > 0, 404);

        $targetConfig = self::TARGETS[$cibleType];
        $targetResponse = $client->request(
            $targetConfig['service'],
            'GET',
            '/api/'.$targetConfig['resource'].'/'.$cibleId,
        );
        abort_unless($targetResponse->successful(), $targetResponse->notFound() ? 404 : 502);

        $documentsResponse = $client->request('mouvements', 'GET', '/api/documents', [
            'query' => [
                'cible_type' => $cibleType,
                'cible_id' => $cibleId,
                'per_page' => 100,
                'page' => $request->integer('page', 1),
            ],
        ]);
        abort_unless($documentsResponse->successful(), 502);

        $target = $targetResponse->json('data', []);

        return view('documents.index', [
            'cibleType' => $cibleType,
            'cibleId' => (int) $cibleId,
            'returnUrl' => $this->returnUrl($cibleType, (int) $cibleId),
            'targetLabel' => self::TARGETS[$cibleType]['label'],
            'targetTitle' => $this->targetTitle($cibleType, $target),
            'documents' => $documentsResponse->json('data', []),
            'pagination' => $documentsResponse->json(),
            'typesDocument' => config('documents.types'),
        ]);
    }

    public function store(
        Request $request,
        string $cibleType,
        string $cibleId,
        MicroserviceClient $client,
    ): RedirectResponse {
        abort_unless(isset(self::TARGETS[$cibleType]), 404);
        abort_unless(ctype_digit($cibleId) && (int) $cibleId > 0, 404);

        $data = $request->validate([
            'type_document' => ['required', 'in:'.implode(',', array_keys(config('documents.types')))],
            'titre' => ['required', 'string', 'max:180'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'date_document' => ['nullable', 'date'],
            'fichier' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
                'mimetypes:application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ]);

        $fields = $data;
        unset($fields['fichier']);

        $response = $client->upload('mouvements', '/api/documents', $request->file('fichier'), [
            ...$fields,
            'cible_type' => $cibleType,
            'cible_id' => (int) $cibleId,
        ]);

        if (! $response->created()) {
            return $this->apiError($response, 'Le document n’a pas pu être ajouté.');
        }

        return redirect()->route('documents.index', [$cibleType, $cibleId])
            ->with('success', 'Le document justificatif a été ajouté.');
    }

    public function download(
        string $cibleType,
        string $cibleId,
        string $documentId,
        MicroserviceClient $client,
    ): StreamedResponse {
        abort_unless(isset(self::TARGETS[$cibleType]), 404);
        abort_unless(ctype_digit($cibleId) && (int) $cibleId > 0, 404);
        abort_unless(ctype_digit($documentId) && (int) $documentId > 0, 404);

        $response = $client->request(
            'mouvements',
            'GET',
            '/api/documents/'.$cibleType.'/'.$cibleId.'/'.$documentId.'/telecharger',
            ['stream' => true, '_timeout' => 30],
        );
        abort_unless($response->successful(), $response->notFound() ? 404 : 502);

        $filename = rawurldecode($response->header('X-Document-Name', ''));
        $filename = basename(str_replace('\\', '/', $filename));
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename) ?: 'document-'.$documentId;

        if ($filename === '.' || $filename === '..') {
            $filename = 'document-'.$documentId;
        }

        $stream = $response->toPsrResponse()->getBody();

        return response()->streamDownload(
            static function () use ($stream): void {
                while (! $stream->eof()) {
                    echo $stream->read(8192);
                }
            },
            $filename,
            [
                'Content-Type' => $response->header('Content-Type', 'application/octet-stream'),
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function destroy(
        string $cibleType,
        string $cibleId,
        string $documentId,
        MicroserviceClient $client,
    ): RedirectResponse {
        abort_unless(isset(self::TARGETS[$cibleType]), 404);
        abort_unless(ctype_digit($cibleId) && (int) $cibleId > 0, 404);
        abort_unless(ctype_digit($documentId) && (int) $documentId > 0, 404);

        $response = $client->request(
            'mouvements',
            'DELETE',
            '/api/documents/'.$cibleType.'/'.$cibleId.'/'.$documentId,
        );

        if (! $response->noContent()) {
            return $this->apiError($response, 'Le document n’a pas pu être supprimé.');
        }

        return redirect()->route('documents.index', [$cibleType, $cibleId])
            ->with('success', 'Le document justificatif a été supprimé.');
    }

    private function targetTitle(string $type, array $target): string
    {
        return match ($type) {
            'bien' => ($target['reference'] ?? 'Bien').' — '.($target['designation'] ?? ''),
            'acquisition' => ($target['reference'] ?? 'Acquisition').' — '.($target['designation'] ?? ''),
            default => self::TARGETS[$type]['label'].' #'.($target['id'] ?? ''),
        };
    }

    private function returnUrl(string $type, int $id): string
    {
        return match ($type) {
            'bien' => route('biens.show', $id),
            'acquisition' => route('acquisitions.index'),
            'affectation' => route('affectations.index'),
            'transfert' => route('transferts.index'),
            'maintenance' => route('maintenances.index'),
        };
    }

    private function apiError(Response $response, string $fallback): RedirectResponse
    {
        $errors = $response->json('errors', []);

        if (is_array($errors) && $errors !== []) {
            return back()->withErrors($errors)->withInput();
        }

        return back()->withErrors([
            'service' => $response->json('message', $fallback),
        ])->withInput();
    }
}
