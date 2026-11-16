<?php

namespace App\Http\Controllers;

use App\Services\MicroserviceClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GlobalSearchController extends Controller
{
    public function index(Request $request, MicroserviceClient $client): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'min:2', 'max:100']]);
        $term = trim($filters['q'] ?? '');
        $results = [
            'biens' => [],
            'categories' => [],
            'ministeres' => [],
            'services' => [],
            'affectations' => [],
            'transferts' => [],
            'maintenances' => [],
            'documents' => [],
            'operations' => [],
            'utilisateurs' => [],
        ];
        $role = Str::slug((string) data_get(session('user', []), 'role_slug', data_get(session('user', []), 'role', '')));
        $peutVoirUtilisateurs = $role === 'administrateur-general';

        if ($term !== '') {
            $query = ['q' => $term, 'per_page' => 10];
            $results['biens'] = $this->liste($client, 'patrimoine', '/api/biens', $query);
            $results['categories'] = $this->liste($client, 'patrimoine', '/api/categories', $query);
            $results['ministeres'] = $this->liste($client, 'patrimoine', '/api/ministeres', $query);
            $results['services'] = $this->liste($client, 'patrimoine', '/api/services', $query);
            $bienIds = collect($results['biens'])->pluck('id')->map(fn ($id): int => (int) $id)->all();

            if ($bienIds !== []) {
                $movementQuery = ['bien_ids' => $bienIds, 'per_page' => 10];
                $results['affectations'] = $this->liste($client, 'mouvements', '/api/affectations', $movementQuery);
                $results['transferts'] = $this->liste($client, 'mouvements', '/api/transferts', $movementQuery);
                $results['maintenances'] = $this->liste($client, 'mouvements', '/api/maintenances', $movementQuery);
            }

            $results['documents'] = $this->liste($client, 'mouvements', '/api/documents', ['q' => $term, 'per_page' => 10]);
            $results['operations'] = $this->liste($client, 'mouvements', '/api/historique', ['q' => $term, 'per_page' => 10]);

            if ($peutVoirUtilisateurs) {
                $results['utilisateurs'] = $this->liste($client, 'auth', '/api/admin/users', $query);
            }
        }

        return view('search.index', compact('term', 'results', 'peutVoirUtilisateurs'));
    }

    private function liste(MicroserviceClient $client, string $service, string $path, array $query): array
    {
        $response = $client->request($service, 'GET', $path, ['query' => $query]);
        abort_unless($response->successful(), $response->status());

        return $response->json('data', []);
    }
}
