<?php

namespace Tests\Feature;

use App\Models\Affectation;
use App\Models\HistoriqueOperation;
use App\Models\Inventaire;
use App\Models\Maintenance;
use App\Models\Transfert;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Vérifie le cloisonnement des lectures mouvements par périmètre (F3)
 * et l'interdiction d'auto-validation des transferts (F4).
 */
class PerimetreLectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'jwt.secret' => str_repeat('t', 32),
            'services.patrimoine.url' => 'http://patrimoine-service:8000',
        ]);

        $this->fakePatrimoinePerimetre();

        Affectation::create([
            'bien_id' => 1, 'service_id' => 10, 'utilisateur_id' => 1,
            'date_debut' => '2026-01-01', 'statut' => 'active',
        ]);
        Affectation::create([
            'bien_id' => 3, 'service_id' => 20, 'utilisateur_id' => 1,
            'date_debut' => '2026-01-01', 'statut' => 'active',
        ]);

        Transfert::create([
            'bien_id' => 1, 'service_origine_id' => 10, 'service_destination_id' => 20,
            'demandeur_id' => 1, 'date_demande' => '2026-01-01',
            'motif' => 'Transfert A', 'statut' => 'en_attente',
        ]);
        Transfert::create([
            'bien_id' => 4, 'service_origine_id' => 30, 'service_destination_id' => 30,
            'demandeur_id' => 1, 'date_demande' => '2026-01-01',
            'motif' => 'Transfert hors périmètre', 'statut' => 'brouillon',
        ]);

        Maintenance::create([
            'bien_id' => 1, 'utilisateur_id' => 1, 'type' => 'revision',
            'description' => 'Maintenance dans le périmètre', 'date_debut' => '2026-01-10',
            'statut' => 'signalee', 'cout' => 50000,
        ]);
        Maintenance::create([
            'bien_id' => 4, 'utilisateur_id' => 1, 'type' => 'revision',
            'description' => 'Maintenance hors périmètre', 'date_debut' => '2026-01-10',
            'statut' => 'signalee', 'cout' => 70000,
        ]);

        Inventaire::create([
            'titre' => 'Inventaire service 10', 'service_id' => 10,
            'statut' => 'en_cours', 'createur_id' => 1, 'date_debut' => '2026-01-01',
        ]);
        Inventaire::create([
            'titre' => 'Inventaire service 30', 'service_id' => 30,
            'statut' => 'en_cours', 'createur_id' => 1, 'date_debut' => '2026-01-01',
        ]);

        HistoriqueOperation::create([
            'utilisateur_id' => 1, 'bien_id' => 1,
            'action' => 'test.dans_perimetre', 'description' => 'Opération service 10',
        ]);
        HistoriqueOperation::create([
            'utilisateur_id' => 1, 'bien_id' => 4,
            'action' => 'test.hors_perimetre', 'description' => 'Opération service 30',
        ]);
    }

    private function fakePatrimoinePerimetre(): void
    {
        Http::fake(function (ClientRequest $request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            // Services 10 et 30 → ministère 100 ; service 20 → ministère 200
            if (preg_match('~/api/services/(\d+)$~', (string) $path, $m)) {
                $id = (int) $m[1];
                $ministere = $id === 20 ? 200 : 100;

                return Http::response(['data' => ['id' => $id, 'ministere_id' => $ministere]], 200);
            }

            if ($path === '/api/services') {
                return Http::response(['data' => [
                    ['id' => 10, 'ministere_id' => 100],
                    ['id' => 30, 'ministere_id' => 100],
                    ['id' => 20, 'ministere_id' => 200],
                ]], 200);
            }

            if ($path === '/api/biens') {
                // Biens par service : 10 → [1,2], 20 → [3], 30 → [4]
                $map = [10 => [1, 2], 20 => [3], 30 => [4]];
                $serviceId = (int) ($query['service_id'] ?? 0);
                $data = array_map(
                    fn (int $id) => ['id' => $id, 'service_id' => $serviceId, 'actif' => true],
                    $map[$serviceId] ?? []
                );

                return Http::response([
                    'data' => $data,
                    'current_page' => 1,
                    'last_page' => 1,
                ], 200);
            }

            if (preg_match('~/api/biens/(\d+)$~', (string) $path, $m)) {
                return Http::response(['data' => ['id' => (int) $m[1], 'service_id' => 10]], 200);
            }

            return Http::response(['data' => ['id' => 1]], 200);
        });
    }

    private function tokenForUser(int $userId, string $role, ?int $serviceId = null): string
    {
        return JWT::encode([
            'sub' => (string) $userId,
            'role' => $role,
            'service_id' => $serviceId,
            'iat' => now()->timestamp,
            'exp' => now()->addHours(2)->timestamp,
        ], config('jwt.secret'), 'HS256');
    }

    private function ids(array $json): array
    {
        return collect($json['data'] ?? [])->pluck('id')->all();
    }

    public function test_agent_ne_voit_que_les_affectations_de_son_service(): void
    {
        $agent10 = $this->tokenForUser(1, 'agent-gestionnaire', 10);
        $agent20 = $this->tokenForUser(2, 'agent-gestionnaire', 20);

        $this->withToken($agent10)->getJson('/api/affectations?per_page=100')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $response = $this->withToken($agent20)->getJson('/api/affectations?per_page=100')->assertOk();
        $this->assertSame(20, $response->json('data.0.service_id'));
    }

    public function test_agent_ne_peut_pas_lire_une_affectation_hors_perimetre(): void
    {
        $affectation = Affectation::where('service_id', 20)->firstOrFail();

        $this->withToken($this->tokenForUser(1, 'agent-gestionnaire', 10))
            ->getJson('/api/affectations/'.$affectation->id)
            ->assertForbidden();
    }

    public function test_agent_ne_voit_que_les_transferts_de_son_perimetre(): void
    {
        $agent10 = $this->tokenForUser(1, 'agent-gestionnaire', 10);

        // Transfert 10→20 visible (origine ET destination dans le périmètre agent 10 ? non :
        // agent 10 voit les transferts dont origine OU destination = 10 → le 10→20 oui)
        $response = $this->withToken($agent10)->getJson('/api/transferts?per_page=100')->assertOk();
        $this->assertCount(1, $this->ids($response->json()));

        // Le transfert 30→30 est invisible
        $this->withToken($agent10)->getJson('/api/transferts?per_page=100')
            ->assertJsonMissing(['motif' => 'Transfert hors périmètre']);
    }

    public function test_agent_ne_peut_pas_lire_un_transfert_hors_perimetre(): void
    {
        $transfert = Transfert::where('service_origine_id', 30)->firstOrFail();

        $this->withToken($this->tokenForUser(1, 'agent-gestionnaire', 10))
            ->getJson('/api/transferts/'.$transfert->id)
            ->assertForbidden();

        // L'agent du service 20 voit le transfert 10→20 car il est destinataire
        $transfertVisible = Transfert::where('service_origine_id', 10)->firstOrFail();
        $this->withToken($this->tokenForUser(2, 'agent-gestionnaire', 20))
            ->getJson('/api/transferts/'.$transfertVisible->id)
            ->assertOk();
    }

    public function test_maintenances_historique_et_inventaires_sont_cloisonnes(): void
    {
        $agent10 = $this->tokenForUser(1, 'agent-gestionnaire', 10);

        $this->withToken($agent10)->getJson('/api/maintenances?per_page=100')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $maintenanceHors = Maintenance::where('bien_id', 4)->firstOrFail();
        $this->withToken($agent10)->getJson('/api/maintenances/'.$maintenanceHors->id)
            ->assertForbidden();

        $this->withToken($agent10)->getJson('/api/historique?per_page=100')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($agent10)->getJson('/api/inventaires?per_page=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titre', 'Inventaire service 10');

        $inventaireHors = Inventaire::where('service_id', 30)->firstOrFail();
        $this->withToken($agent10)->getJson('/api/inventaires/'.$inventaireHors->id)
            ->assertForbidden();
    }

    public function test_statistiques_respectent_le_perimetre(): void
    {
        $agent10 = $this->tokenForUser(1, 'agent-gestionnaire', 10);
        $admin = $this->tokenForUser(99, 'administrateur-general');

        $statsAgent = $this->withToken($agent10)->getJson('/api/stats')->assertOk()->json('data');
        $statsAdmin = $this->withToken($admin)->getJson('/api/stats')->assertOk()->json('data');

        // Agent 10 : 1 affectation active (service 10), maintenances du bien 1 uniquement
        $this->assertSame(1, $statsAgent['affectations_actives']);
        $this->assertSame(50000.0, (float) $statsAgent['cout_total_maintenances']);
        $this->assertCount(1, $statsAgent['operations_recentes']);

        // Admin : vue globale (2 affectations, 2 maintenances)
        $this->assertSame(2, $statsAdmin['affectations_actives']);
        $this->assertSame(120000.0, (float) $statsAdmin['cout_total_maintenances']);
        $this->assertCount(2, $statsAdmin['operations_recentes']);
    }

    public function test_responsable_ministeriel_voit_tout_son_ministere(): void
    {
        // Service 30 appartient au ministère 100 comme le service 10
        $responsable = $this->tokenForUser(5, 'responsable-ministeriel', 10);

        // Affectations des services 10 et 20 ? Non : 20 est ministère 200.
        // Services du ministère 100 = {10, 30} → affectations service 10 uniquement
        $response = $this->withToken($responsable)->getJson('/api/affectations?per_page=100')->assertOk();
        $this->assertCount(1, $this->ids($response->json()));

        // Inventaires des services 10 et 30 → les deux
        $response = $this->withToken($responsable)->getJson('/api/inventaires?per_page=100')->assertOk();
        $this->assertCount(2, $response->json('data'));

        // Transferts 10→20 (origine dans le ministère) et 30→30 (services du ministère)
        $response = $this->withToken($responsable)->getJson('/api/transferts?per_page=100')->assertOk();
        $this->assertCount(2, $this->ids($response->json()));
    }

    public function test_auditeur_a_un_acces_lecture_global(): void
    {
        $auditeur = $this->tokenForUser(7, 'auditeur');

        $this->withToken($auditeur)->getJson('/api/affectations?per_page=100')
            ->assertOk()->assertJsonCount(2, 'data');
        $this->withToken($auditeur)->getJson('/api/maintenances?per_page=100')
            ->assertOk()->assertJsonCount(2, 'data');
        $this->withToken($auditeur)->getJson('/api/inventaires?per_page=100')
            ->assertOk()->assertJsonCount(2, 'data');
        $this->withToken($auditeur)->getJson('/api/stats')->assertOk();
    }

    public function test_demandeur_ne_peut_pas_approuver_son_propre_transfert(): void
    {
        $gestionnaire = $this->tokenForUser(42, 'gestionnaire-patrimoine');
        $validateur = $this->tokenForUser(77, 'gestionnaire-patrimoine');

        // Transfert existant : demandeur_id = 1 → un token sub=1 tente d'approuver
        $transfert = Transfert::where('service_origine_id', 10)->firstOrFail();

        $this->withToken($this->tokenForUser(1, 'gestionnaire-patrimoine'))
            ->patchJson('/api/transferts/'.$transfert->id.'/statut', ['statut' => 'approuvé'])
            ->assertForbidden();

        // Un autre utilisateur peut approuver
        $this->withToken($validateur)
            ->patchJson('/api/transferts/'.$transfert->id.'/statut', ['statut' => 'approuvé'])
            ->assertOk();
    }
}
