<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Affectation;
use App\Models\DocumentJustificatif;
use App\Models\Maintenance;
use App\Models\Transfert;
use App\Services\HistoriqueWriter;
use App\Services\DocumentAccessService;
use App\Services\PatrimoineClient;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function index(
        Request $request,
        DocumentAccessService $access,
        PatrimoineClient $patrimoine,
    ): JsonResponse
    {
        $filters = $request->validate([
            'cible_type' => ['nullable', 'required_with:cible_id', 'in:bien,acquisition,affectation,transfert,maintenance'],
            'cible_id' => ['nullable', 'required_with:cible_type', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100', 'required_without:cible_type'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $scope = $access->userScope(
            $request->attributes->get('jwt_payload', []),
            (string) $request->bearerToken(),
            $patrimoine,
        );
        $documents = DocumentJustificatif::query()
            ->when(isset($filters['cible_type']), fn ($query) => $query->where('cible_type', $filters['cible_type']))
            ->when(isset($filters['cible_id']), fn ($query) => $query->where('cible_id', $filters['cible_id']))
            ->when(isset($filters['q']), fn ($query) => $query->where(fn ($search) => $search
                ->where('titre', 'ilike', '%'.$filters['q'].'%')
                ->orWhere('reference', 'ilike', '%'.$filters['q'].'%')
                ->orWhere('description', 'ilike', '%'.$filters['q'].'%')))
            ->when(! $scope['global'], fn ($query) => $access->applyScope($query, $scope))
            ->latest('id')
            ->paginate($filters['per_page'] ?? 50)
            ->withQueryString();

        return response()->json($documents);
    }

    public function store(
        StoreDocumentRequest $request,
        DocumentAccessService $access,
        PatrimoineClient $patrimoine,
        HistoriqueWriter $historique,
    ): JsonResponse {
        $data = $request->validated();
        $context = $access->resolveTarget(
            $data['cible_type'],
            (int) $data['cible_id'],
            (string) $request->bearerToken(),
            $patrimoine,
        );

        $file = $request->file('fichier');
        $extension = $file->guessExtension();

        if (! is_string($extension) || $extension === '') {
            throw ValidationException::withMessages([
                'fichier' => 'Le format du fichier ne peut pas être vérifié.',
            ]);
        }

        $originalName = str_replace('\\', '/', $file->getClientOriginalName());
        $originalName = basename($originalName);
        $originalName = preg_replace('/[\x00-\x1F\x7F]/u', '', $originalName) ?: 'document';
        $originalName = trim($originalName);

        if ($originalName === '' || $originalName === '.' || $originalName === '..') {
            $originalName = 'document.'.$extension;
        }

        $storedName = Str::uuid().'.'.$extension;
        $path = Storage::disk('local')->putFileAs('documents', $file, $storedName);

        if (! is_string($path)) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Le fichier justificatif n’a pas pu être stocké.');
        }

        $payload = $request->attributes->get('jwt_payload', []);
        try {
            $document = DB::transaction(function () use ($data, $originalName, $path, $file, $payload, $context, $historique): DocumentJustificatif {
                $document = DocumentJustificatif::create([
                    'cible_type' => $data['cible_type'],
                    'cible_id' => (int) $data['cible_id'],
                    'type_document' => $data['type_document'],
                    'titre' => $data['titre'],
                    'reference' => $data['reference'] ?? null,
                    'description' => $data['description'] ?? null,
                    'date_document' => $data['date_document'] ?? null,
                    'nom_original' => Str::limit($originalName, 255, ''),
                    'chemin' => $path,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'taille_octets' => $file->getSize(),
                    'utilisateur_id' => (int) $payload['sub'],
                ]);
                $document->perimetres()->createMany($context['perimetres']);
                $historique->enregistrer('document.justificatif.ajoute', $context['bien_id'], null, [
                    'document_id' => $document->id,
                    'cible_type' => $document->cible_type,
                    'cible_id' => $document->cible_id,
                    'type_document' => $document->type_document,
                    'titre' => $document->titre,
                ]);

                return $document;
            });
        } catch (QueryException $exception) {
            if (! Storage::disk('local')->delete($path)) {
                abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Le fichier justificatif temporaire n’a pas pu être nettoyé.');
            }

            throw $exception;
        }

        return response()->json(['data' => $document], Response::HTTP_CREATED);
    }

    public function download(
        Request $request,
        DocumentAccessService $access,
        PatrimoineClient $patrimoine,
        string $cibleType,
        string $cibleId,
        DocumentJustificatif $document,
    ): StreamedResponse {
        $this->assertDocumentBelongsTo($document, $cibleType, $cibleId);
        $scope = $access->userScope(
            $request->attributes->get('jwt_payload', []),
            (string) $request->bearerToken(),
            $patrimoine,
        );
        abort_unless($access->canView($document, $scope), Response::HTTP_NOT_FOUND);

        if (! Storage::disk('local')->exists($document->chemin)) {
            abort(Response::HTTP_NOT_FOUND, 'Le fichier justificatif est introuvable.');
        }

        return Storage::disk('local')->download($document->chemin, $document->nom_original, [
            'Content-Type' => $document->mime_type,
            'X-Document-Name' => rawurlencode($document->nom_original),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(
        string $cibleType,
        string $cibleId,
        DocumentJustificatif $document,
        HistoriqueWriter $historique,
    ): Response {
        $this->assertDocumentBelongsTo($document, $cibleType, $cibleId);
        $bienId = $this->resolveBienIdFromStoredTarget($document);

        if (! Storage::disk('local')->exists($document->chemin)) {
            abort(Response::HTTP_NOT_FOUND, 'Le fichier justificatif est introuvable.');
        }

        if (! Storage::disk('local')->delete($document->chemin)) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Le fichier justificatif n’a pas pu être supprimé.');
        }

        DB::transaction(function () use ($document, $bienId, $historique): void {
            $historique->enregistrer('document.justificatif.supprime', $bienId, [
                'document_id' => $document->id,
                'cible_type' => $document->cible_type,
                'cible_id' => $document->cible_id,
                'type_document' => $document->type_document,
                'titre' => $document->titre,
            ], null);
            $document->delete();
        });

        return response()->noContent();
    }

    private function assertDocumentBelongsTo(
        DocumentJustificatif $document,
        string $cibleType,
        string $cibleId,
    ): void {
        abort_unless(
            $document->cible_type === $cibleType
            && (int) $document->cible_id === (int) $cibleId,
            Response::HTTP_NOT_FOUND,
        );
    }

    private function resolveBienIdFromStoredTarget(DocumentJustificatif $document): ?int
    {
        $bienId = match ($document->cible_type) {
            'bien' => (int) $document->cible_id,
            'affectation' => Affectation::query()->whereKey($document->cible_id)->value('bien_id'),
            'transfert' => Transfert::query()->whereKey($document->cible_id)->value('bien_id'),
            'maintenance' => Maintenance::query()->whereKey($document->cible_id)->value('bien_id'),
            default => null,
        };

        return is_numeric($bienId) ? (int) $bienId : null;
    }
}
