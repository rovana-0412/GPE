<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcquisitionController extends Controller
{
    public function index(Request $request, MicroserviceClient $client): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'statut' => ['nullable', 'in:brouillon,en_attente,receptionnee,validee,rejetee'],
            'service_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $response = $client->request('patrimoine', 'GET', '/api/acquisitions', [
            'query' => $filters + ['per_page' => 15],
        ]);
        abort_unless($response->successful(), $response->status());

        return view('patrimoine.acquisitions.index', [
            'acquisitions' => $response->json('data', []),
            'pagination' => $response->json(),
            'filters' => $filters,
        ]);
    }

    public function create(MicroserviceClient $client): View
    {
        return view('patrimoine.acquisitions.form', [
            'categories' => $this->referenceList($client, 'categories'),
            'services' => $this->referenceList($client, 'services'),
        ]);
    }

    public function store(Request $request, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:50'],
            'designation' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:10000'],
            'categorie_id' => ['required', 'integer', 'min:1'],
            'service_id' => ['required', 'integer', 'min:1'],
            'numero_serie' => ['nullable', 'string', 'max:100'],
            'localisation' => ['nullable', 'string', 'max:255'],
            'fournisseur' => ['required', 'string', 'max:180'],
            'cout' => ['required', 'numeric', 'min:0'],
            'date_acquisition' => ['required', 'date'],
            'mode_acquisition' => ['required', 'in:achat,don,dotation,transfert,autre'],
            'reference_facture' => ['nullable', 'string', 'max:100'],
            'reference_bon_commande' => ['nullable', 'string', 'max:100'],
        ]);
        $response = $client->request('patrimoine', 'POST', '/api/acquisitions', ['json' => $data]);

        if (! $response->created()) {
            return $this->apiError($response, 'L’acquisition n’a pas pu être enregistrée.');
        }

        return redirect()->route('acquisitions.index')->with('success', 'L’acquisition a été créée en brouillon.');
    }

    public function transition(Request $request, string $id, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'in:en_attente,receptionnee,validee,rejetee'],
            'motif_rejet' => ['required_if:statut,rejetee', 'nullable', 'string', 'max:2000'],
        ]);
        $response = $client->request('patrimoine', 'PATCH', '/api/acquisitions/'.$id.'/statut', ['json' => $data]);

        if (! $response->successful()) {
            return $this->apiError($response, 'Le statut de l’acquisition n’a pas pu être modifié.');
        }

        return redirect()->route('acquisitions.index')->with('success', 'Le statut de l’acquisition a été mis à jour.');
    }

    private function referenceList(MicroserviceClient $client, string $resource): array
    {
        $response = $client->request('patrimoine', 'GET', '/api/'.$resource, [
            'query' => ['per_page' => 100],
        ]);
        abort_unless($response->successful(), $response->status());

        return $response->json('data', []);
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
