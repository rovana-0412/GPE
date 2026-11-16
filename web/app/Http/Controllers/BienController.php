<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BienController extends Controller
{
    public function index(Request $request, MicroserviceClient $client): View
    {
        $filters = $request->only(['q', 'categorie_id', 'service_id', 'etat', 'page']);
        $biens = $client->request('patrimoine', 'GET', '/api/biens', [
            'query' => array_merge($filters, ['per_page' => 15]),
        ])->json();

        return view('patrimoine.biens.index', [
            'biens' => $biens['data'] ?? [],
            'pagination' => $biens,
            'categories' => $this->referenceList($client, 'categories'),
            'services' => $this->referenceList($client, 'services'),
        ]);
    }

    public function create(MicroserviceClient $client): View
    {
        return view('patrimoine.biens.form', [
            'bien' => [],
            'categories' => $this->referenceList($client, 'categories'),
            'services' => $this->referenceList($client, 'services'),
            'mode' => 'creation',
        ]);
    }

    public function store(Request $request, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $response = $client->request('patrimoine', 'POST', '/api/biens', ['json' => $data]);

        if ($response->status() === 422) {
            return $this->apiValidationError($response);
        }

        if (! $response->created()) {
            return $this->apiValidationError($response, 'Le bien n’a pas pu être enregistré.');
        }

        return redirect()->route('biens.index')->with('success', 'Le bien a été enregistré.');
    }

    public function show(string $id, MicroserviceClient $client): View
    {
        $response = $client->request('patrimoine', 'GET', '/api/biens/'.$id);

        abort_unless($response->successful(), $response->notFound() ? 404 : 500);

        $documents = $client->request('mouvements', 'GET', '/api/documents', [
            'query' => [
                'cible_type' => 'bien',
                'cible_id' => $id,
                'per_page' => 100,
            ],
        ]);
        abort_unless($documents->successful(), 502);

        return view('patrimoine.biens.show', [
            'bien' => $response->json('data'),
            'documents' => $documents->json('data', []),
            'documentsPagination' => $documents->json(),
            'typesDocument' => config('documents.types'),
        ]);
    }

    public function edit(string $id, MicroserviceClient $client): View
    {
        $response = $client->request('patrimoine', 'GET', '/api/biens/'.$id);

        abort_unless($response->successful(), $response->notFound() ? 404 : 500);

        return view('patrimoine.biens.form', [
            'bien' => $response->json('data'),
            'categories' => $this->referenceList($client, 'categories'),
            'services' => $this->referenceList($client, 'services'),
            'mode' => 'modification',
        ]);
    }

    public function update(Request $request, string $id, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $response = $client->request('patrimoine', 'PUT', '/api/biens/'.$id, ['json' => $data]);

        if ($response->status() === 422) {
            return $this->apiValidationError($response);
        }

        if (! $response->successful()) {
            return $this->apiValidationError($response, 'Le bien n’a pas pu être modifié.');
        }

        return redirect()->route('biens.show', $id)->with('success', 'Les informations du bien ont été mises à jour.');
    }

    public function destroy(string $id, MicroserviceClient $client): RedirectResponse
    {
        $response = $client->request('patrimoine', 'DELETE', '/api/biens/'.$id);

        if (! $response->noContent()) {
            return $this->apiValidationError($response, 'Le bien n’a pas pu être archivé.');
        }

        return redirect()->route('biens.index')->with('success', 'Le bien a été archivé.');
    }

    private function referenceList(MicroserviceClient $client, string $resource): array
    {
        return $client->request('patrimoine', 'GET', '/api/'.$resource, [
            'query' => ['per_page' => 100],
        ])->json('data', []);
    }

    private function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:50'],
            'designation' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'categorie_id' => ['required', 'integer'],
            'service_id' => ['required', 'integer'],
            'numero_serie' => ['nullable', 'string', 'max:100'],
            'date_acquisition' => ['nullable', 'date'],
            'valeur_acquisition' => ['nullable', 'numeric', 'min:0'],
            'etat' => ['required', 'string', 'max:30'],
            'statut' => ['required', 'string', 'max:30'],
            'localisation' => ['nullable', 'string', 'max:255'],
            'actif' => ['required', 'boolean'],
        ];
    }

    private function apiValidationError(Response $response, string $fallback = 'Les données saisies sont invalides.'): RedirectResponse
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
