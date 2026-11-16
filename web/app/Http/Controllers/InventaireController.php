<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventaireController extends Controller
{
    public function index(Request $request, MicroserviceClient $client): View
    {
        $filters = $request->validate([
            'service_id' => ['nullable', 'integer', 'min:1'],
            'statut' => ['nullable', 'in:brouillon,en_cours,a_valider,valide'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $inventaires = $client->request('mouvements', 'GET', '/api/inventaires', [
            'query' => $filters + ['per_page' => 15],
        ]);
        abort_unless($inventaires->successful(), $inventaires->status());
        $services = $client->request('patrimoine', 'GET', '/api/services', [
            'query' => ['per_page' => 100],
        ]);
        abort_unless($services->successful(), $services->status());

        return view('mouvements.inventaires.index', [
            'inventaires' => $inventaires->json('data', []),
            'pagination' => $inventaires->json(),
            'services' => $services->json('data', []),
            'servicesParId' => collect($services->json('data', []))->keyBy('id')->all(),
            'filters' => $filters,
        ]);
    }

    public function create(MicroserviceClient $client): View
    {
        $services = $client->request('patrimoine', 'GET', '/api/services', [
            'query' => ['per_page' => 100],
        ]);
        abort_unless($services->successful(), $services->status());

        return view('mouvements.inventaires.form', ['services' => $services->json('data', [])]);
    }

    public function store(Request $request, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'titre' => ['required', 'string', 'max:180'],
            'service_id' => ['nullable', 'integer', 'min:1'],
            'observation' => ['nullable', 'string', 'max:5000'],
        ]);
        $response = $client->request('mouvements', 'POST', '/api/inventaires', ['json' => $data]);

        if (! $response->created()) {
            return $this->apiError($response, 'La campagne d’inventaire n’a pas pu être créée.');
        }

        return redirect()->route('inventaires.index')->with('success', 'La campagne d’inventaire a été créée.');
    }

    public function show(Request $request, string $id, MicroserviceClient $client): View
    {
        $filters = $request->validate([
            'bien_q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'biens_non_controles_page' => ['nullable', 'integer', 'min:1'],
        ]);
        $response = $client->request('mouvements', 'GET', '/api/inventaires/'.$id, [
            'query' => ['biens_non_controles_page' => $filters['biens_non_controles_page'] ?? 1],
        ]);
        abort_unless($response->successful(), $response->status());
        $data = $response->json('data', []);
        $lines = $data['lignes'] ?? [];
        $bienIds = collect($lines)->pluck('bien_id')->unique()->values()->all();
        $biens = [];

        if ($bienIds !== []) {
            $biensResponse = $client->request('patrimoine', 'GET', '/api/biens', [
                'query' => ['ids' => $bienIds, 'per_page' => 100],
            ]);
            abort_unless($biensResponse->successful(), $biensResponse->status());
            $biens = collect($biensResponse->json('data', []))->keyBy('id')->all();
        }

        $candidates = ['data' => [], 'current_page' => 1, 'last_page' => 1];

        if (! empty($filters['bien_q'])) {
            $candidatesResponse = $client->request('patrimoine', 'GET', '/api/biens', [
                'query' => array_filter([
                    'service_id' => $data['service_id'] ?? null,
                    'q' => $filters['bien_q'],
                    'page' => $filters['page'] ?? 1,
                    'per_page' => 25,
                ], static fn ($value) => $value !== null),
            ]);
            abort_unless($candidatesResponse->successful(), $candidatesResponse->status());
            $candidates = $candidatesResponse->json();
        }

        return view('mouvements.inventaires.show', [
            'inventaire' => $data,
            'rapport' => $response->json('rapport', []),
            'lignes' => $lines,
            'biensParId' => $biens,
            'biensDisponibles' => $candidates['data'] ?? [],
            'candidatesPagination' => $candidates,
            'bienSearch' => $filters['bien_q'] ?? '',
        ]);
    }

    public function addLine(Request $request, string $id, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'bien_id' => ['required', 'integer', 'min:1'],
            'constat' => ['required', 'in:present,absent,deteriore'],
            'localisation_physique' => ['nullable', 'string', 'max:255'],
            'observation' => ['nullable', 'string', 'max:5000'],
        ]);
        $response = $client->request('mouvements', 'POST', '/api/inventaires/'.$id.'/lignes', ['json' => $data]);

        if (! $response->created()) {
            return $this->apiError($response, 'Le constat n’a pas pu être enregistré.');
        }

        return redirect()->route('inventaires.show', $id)->with('success', 'Le constat physique a été enregistré.');
    }

    public function updateLine(Request $request, string $id, string $lineId, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'constat' => ['required', 'in:present,absent,deteriore'],
            'localisation_physique' => ['nullable', 'string', 'max:255'],
            'observation' => ['nullable', 'string', 'max:5000'],
        ]);
        $response = $client->request('mouvements', 'PATCH', '/api/inventaires/'.$id.'/lignes/'.$lineId, [
            'json' => $data,
        ]);

        if (! $response->successful()) {
            return $this->apiError($response, 'Le constat n’a pas pu être modifié.');
        }

        return redirect()->route('inventaires.show', $id)->with('success', 'Le constat physique a été mis à jour.');
    }

    public function transition(Request $request, string $id, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate(['statut' => ['required', 'in:en_cours,a_valider,valide']]);
        $response = $client->request('mouvements', 'PATCH', '/api/inventaires/'.$id.'/statut', ['json' => $data]);

        if (! $response->successful()) {
            return $this->apiError($response, 'Le statut de la campagne n’a pas pu être modifié.');
        }

        return redirect()->route('inventaires.show', $id)->with('success', 'Le statut de la campagne a été mis à jour.');
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
