<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SortieController extends Controller
{
    public function index(Request $request, MicroserviceClient $client): View
    {
        $filters = $request->validate([
            'statut' => ['nullable', 'in:demandee,approuvee,rejetee,executee'],
            'type_sortie' => ['nullable', 'in:cession,restitution,mise_au_rebut,declassement,autre'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $response = $client->request('patrimoine', 'GET', '/api/sorties', [
            'query' => $filters + ['per_page' => 15],
        ]);
        abort_unless($response->successful(), $response->status());

        return view('patrimoine.sorties.index', [
            'sorties' => $response->json('data', []),
            'pagination' => $response->json(),
            'filters' => $filters,
        ]);
    }

    public function create(MicroserviceClient $client): View
    {
        $response = $client->request('patrimoine', 'GET', '/api/biens', [
            'query' => ['actif' => 1, 'per_page' => 100],
        ]);
        abort_unless($response->successful(), $response->status());

        return view('patrimoine.sorties.form', ['biens' => $response->json('data', [])]);
    }

    public function store(Request $request, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'bien_id' => ['required', 'integer', 'min:1'],
            'type_sortie' => ['required', 'in:cession,restitution,mise_au_rebut,declassement,autre'],
            'motif' => ['required', 'string', 'max:5000'],
            'observation' => ['nullable', 'string', 'max:5000'],
        ]);
        $response = $client->request('patrimoine', 'POST', '/api/sorties', ['json' => $data]);

        if (! $response->created()) {
            return $this->apiError($response, 'La demande de sortie n’a pas pu être créée.');
        }

        return redirect()->route('sorties.index')->with('success', 'La demande de sortie a été créée.');
    }

    public function transition(Request $request, string $id, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'in:approuvee,rejetee,executee'],
            'motif_rejet' => ['required_if:statut,rejetee', 'nullable', 'string', 'max:2000'],
        ]);
        $response = $client->request('patrimoine', 'PATCH', '/api/sorties/'.$id.'/statut', ['json' => $data]);

        if (! $response->successful()) {
            return $this->apiError($response, 'Le statut de la demande n’a pas pu être modifié.');
        }

        return redirect()->route('sorties.index')->with('success', 'Le statut de la demande a été mis à jour.');
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
