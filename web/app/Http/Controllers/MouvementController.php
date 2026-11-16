<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class MouvementController extends Controller
{
    public function affectations(Request $request, MicroserviceClient $client): View
    {
        $filters = $request->validate(['bien_id' => ['nullable', 'integer', 'min:1']]);
        $biens = $this->liste($client, 'patrimoine', '/api/biens');
        $services = $this->liste($client, 'patrimoine', '/api/services');

        return view('mouvements.affectations.index', [
            'affectations' => $client->request('mouvements', 'GET', '/api/affectations', [
                'query' => $filters + ['per_page' => 100],
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
        $filters = $request->only(['statut', 'service_origine_id', 'service_destination_id', 'bien_id']);
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

    public function maintenances(Request $request, MicroserviceClient $client): View
    {
        $filters = $request->validate([
            'bien_id' => ['nullable', 'integer', 'min:1'],
            'statut' => ['nullable', 'in:signalee,planifiee,en_cours,terminee,annulee'],
        ]);
        $biens = $this->liste($client, 'patrimoine', '/api/biens');

        return view('mouvements.maintenances.index', [
            'maintenances' => $client->request('mouvements', 'GET', '/api/maintenances', [
                'query' => $filters + ['per_page' => 100],
            ])->json('data', []),
            'biens' => $biens,
            'biensParId' => collect($biens)->keyBy('id')->all(),
            'filtres' => $filters,
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
        $filters = $request->validate([
            'source' => ['nullable', 'in:mouvements,patrimoine,auth'],
            'bien_id' => ['nullable', 'integer', 'min:1'],
            'utilisateur_id' => ['nullable', 'integer', 'min:1'],
            'action' => ['nullable', 'string', 'max:120'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $source = $filters['source'] ?? 'mouvements';
        $role = Str::slug((string) data_get(session('user', []), 'role_slug', data_get(session('user', []), 'role', '')));
        $canViewAdminAudit = in_array($role, ['administrateur-general', 'auditeur'], true);

        if ($source !== 'mouvements' && ! $canViewAdminAudit) {
            abort(403);
        }

        $service = $source === 'auth' ? 'auth' : $source;
        $path = $source === 'mouvements' ? '/api/historique' : '/api/audit';
        $query = $filters + ['per_page' => 30];
        unset($query['source']);
        $response = $client->request($service, 'GET', $path, ['query' => $query]);
        abort_unless($response->successful(), $response->status());
        $operations = collect($response->json('data', []))->map(function (array $log) use ($source): array {
            return [
                'created_at' => $log['created_at'],
                'utilisateur_id' => $source === 'auth' ? ($log['acteur_id'] ?? null) : ($log['utilisateur_id'] ?? null),
                'bien_id' => $log['bien_id'] ?? null,
                'action' => $log['action'],
                'ancienne_valeur' => $log['ancienne_valeur'] ?? null,
                'nouvelle_valeur' => $log['nouvelle_valeur'] ?? null,
            ];
        })->all();

        return view('mouvements.historique.index', [
            'operations' => $operations,
            'pagination' => $response->json(),
            'source' => $source,
            'canViewAdminAudit' => $canViewAdminAudit,
        ]);
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
