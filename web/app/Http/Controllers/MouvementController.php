<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MouvementController extends Controller
{
    public function affectations(MicroserviceClient $client): View
    {
        $biens = $this->liste($client, 'patrimoine', '/api/biens');
        $services = $this->liste($client, 'patrimoine', '/api/services');

        return view('mouvements.affectations.index', [
            'affectations' => $client->request('mouvements', 'GET', '/api/affectations', [
                'query' => ['per_page' => 100],
            ])->json('data', []),
            'biens' => $biens,
            'services' => $services,
            'biensParId' => collect($biens)->keyBy('id')->all(),
            'servicesParId' => collect($services)->keyBy('id')->all(),
        ]);
    }

    public function creerAffectation(Request $request, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'bien_id' => ['required', 'integer', 'min:1'],
            'service_id' => ['required', 'integer', 'min:1'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'motif' => ['nullable', 'string', 'max:500'],
            'observation' => ['nullable', 'string', 'max:5000'],
        ]);

        $response = $client->request('mouvements', 'POST', '/api/affectations', ['json' => $data]);

        if (! $response->created()) {
            return $this->erreurApi($response, 'L’affectation n’a pas pu être enregistrée.');
        }

        return redirect()->route('affectations.index')->with('success', 'L’affectation a été enregistrée.');
    }

    public function modifierAffectation(Request $request, string $id, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate(['statut' => ['required', 'in:terminee,annulee']]);
        $response = $client->request('mouvements', 'PATCH', '/api/affectations/'.$id, ['json' => $data]);

        if (! $response->successful()) {
            return $this->erreurApi($response, 'L’affectation n’a pas pu être mise à jour.');
        }

        return redirect()->route('affectations.index')->with('success', 'Le statut de l’affectation a été mis à jour.');
    }

    public function transferts(Request $request, MicroserviceClient $client): View
    {
        $filters = $request->only(['statut', 'service_origine_id', 'service_destination_id']);
        $biens = $this->liste($client, 'patrimoine', '/api/biens');
        $services = $this->liste($client, 'patrimoine', '/api/services');

        return view('mouvements.transferts.index', [
            'transferts' => $client->request('mouvements', 'GET', '/api/transferts', [
                'query' => array_merge($filters, ['per_page' => 100]),
            ])->json('data', []),
            'biens' => $biens,
            'services' => $services,
            'biensParId' => collect($biens)->keyBy('id')->all(),
            'servicesParId' => collect($services)->keyBy('id')->all(),
            'filtres' => $filters,
        ]);
    }

    public function creerTransfert(Request $request, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'bien_id' => ['required', 'integer', 'min:1'],
            'service_destination_id' => ['required', 'integer', 'min:1'],
            'motif' => ['required', 'string', 'max:2000'],
            'observation' => ['nullable', 'string', 'max:5000'],
        ]);
        $response = $client->request('mouvements', 'POST', '/api/transferts', ['json' => $data]);

        if (! $response->created()) {
            return $this->erreurApi($response, 'La demande de transfert n’a pas pu être enregistrée.');
        }

        return redirect()->route('transferts.index')->with('success', 'La demande de transfert a été créée en brouillon.');
    }

    public function changerStatutTransfert(Request $request, string $id, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'in:en_attente,approuvé,effectué,rejeté'],
            'motif_refus' => ['required_if:statut,rejeté', 'nullable', 'string', 'max:2000'],
        ]);
        $response = $client->request('mouvements', 'PATCH', '/api/transferts/'.$id.'/statut', ['json' => $data]);

        if (! $response->successful()) {
            return $this->erreurApi($response, 'Le statut du transfert n’a pas pu être modifié.');
        }

        return redirect()->route('transferts.index')->with('success', 'Le statut du transfert a été mis à jour.');
    }

    public function maintenances(MicroserviceClient $client): View
    {
        $biens = $this->liste($client, 'patrimoine', '/api/biens');

        return view('mouvements.maintenances.index', [
            'maintenances' => $client->request('mouvements', 'GET', '/api/maintenances', [
                'query' => ['per_page' => 100],
            ])->json('data', []),
            'biens' => $biens,
            'biensParId' => collect($biens)->keyBy('id')->all(),
        ]);
    }

    public function creerMaintenance(Request $request, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'bien_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:5000'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'cout' => ['nullable', 'numeric', 'min:0'],
            'prestataire' => ['nullable', 'string', 'max:255'],
        ]);
        $response = $client->request('mouvements', 'POST', '/api/maintenances', ['json' => $data]);

        if (! $response->created()) {
            return $this->erreurApi($response, 'La maintenance n’a pas pu être signalée.');
        }

        return redirect()->route('maintenances.index')->with('success', 'La maintenance a été signalée.');
    }

    public function modifierMaintenance(Request $request, string $id, MicroserviceClient $client): RedirectResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'in:signalee,planifiee,en_cours,terminee,annulee'],
            'cout' => ['nullable', 'numeric', 'min:0'],
        ]);
        $response = $client->request('mouvements', 'PATCH', '/api/maintenances/'.$id, ['json' => $data]);

        if (! $response->successful()) {
            return $this->erreurApi($response, 'La maintenance n’a pas pu être mise à jour.');
        }

        return redirect()->route('maintenances.index')->with('success', 'La maintenance a été mise à jour.');
    }

    public function historique(Request $request, MicroserviceClient $client): View
    {
        $operations = $client->request('mouvements', 'GET', '/api/historique', [
            'query' => $request->only(['bien_id', 'utilisateur_id', 'action', 'page']) + ['per_page' => 30],
        ])->json('data', []);

        return view('mouvements.historique.index', ['operations' => $operations]);
    }

    private function liste(MicroserviceClient $client, string $service, string $path): array
    {
        return $client->request($service, 'GET', $path, [
            'query' => ['per_page' => 100],
        ])->json('data', []);
    }

    private function erreurApi(Response $response, string $fallback): RedirectResponse
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
