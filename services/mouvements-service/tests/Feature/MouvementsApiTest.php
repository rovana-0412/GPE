<?php

namespace Tests\Feature;

use App\Models\HistoriqueOperation;
use App\Models\Maintenance;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MouvementsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'jwt.secret' => str_repeat('t', 32),
            'services.patrimoine.url' => 'http://patrimoine-service:8000',
        ]);
    }

    public function test_all_movement_routes_reject_requests_without_a_token(): void
    {
        foreach ([
            '/api/affectations',
            '/api/transferts',
            '/api/maintenances',
            '/api/historique',
            '/api/stats',
        ] as $uri) {
            $this->getJson($uri)->assertUnauthorized();
        }

        $this->postJson('/api/transferts', [])->assertUnauthorized();
        $this->withToken('jeton-invalide')->getJson('/api/historique')->assertUnauthorized();
    }

    public function test_affectations_check_the_asset_and_allow_only_one_active_assignment(): void
    {
        $this->fakePatrimoine();
        $token = $this->token();

        $this->withToken($token)->postJson('/api/affectations', [
            'bien_id' => 1,
            'service_id' => 20,
            'date_debut' => '2026-01-15',
        ])->assertCreated();

        $this->withToken($token)->postJson('/api/affectations', [
            'bien_id' => 1,
            'service_id' => 20,
            'date_debut' => '2026-01-16',
        ])->assertConflict();

        $this->assertDatabaseCount('affectations', 1);
        $this->assertDatabaseCount('historique_operations', 1);
    }

    public function test_an_operation_is_not_saved_when_the_asset_does_not_exist(): void
    {
        Http::fake(fn (ClientRequest $request) => Http::response([], $request->method() === 'GET' ? 404 : 500));

        $this->withToken($this->token())->postJson('/api/maintenances', [
            'bien_id' => 404,
            'type' => 'preventive',
            'description' => 'Révision annuelle',
            'date_debut' => '2026-01-15',
            'cout' => 25000,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('bien_id');

        $this->assertDatabaseCount('maintenances', 0);
        $this->assertDatabaseCount('historique_operations', 0);
    }

    public function test_maintenance_creation_and_update_write_auditable_values(): void
    {
        $this->fakePatrimoine();
        $token = $this->token();

        $maintenanceId = $this->withToken($token)->postJson('/api/maintenances', [
            'bien_id' => 1,
            'type' => 'preventive',
            'description' => 'Révision annuelle',
            'date_debut' => '2026-01-15',
            'cout' => 25000,
        ])->assertCreated()
            ->json('data.id');

        $this->withToken($token)->patchJson('/api/maintenances/'.$maintenanceId, [
            'statut' => 'terminee',
        ])->assertOk()
            ->assertJsonPath('data.statut', 'terminee');

        $this->assertDatabaseCount('historique_operations', 2);
        $this->assertDatabaseHas('historique_operations', [
            'action' => 'maintenance.modifiee',
            'utilisateur_id' => 42,
            'bien_id' => 1,
        ]);
        $this->assertInstanceOf(Maintenance::class, Maintenance::find($maintenanceId));
        $this->assertNotNull(HistoriqueOperation::firstOrFail()->nouvelle_valeur);
        $this->withToken($token)->getJson('/api/historique')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'maintenance.modifiee');
        $this->withToken($token)->getJson('/api/stats')
            ->assertOk()
            ->assertJsonPath('data.maintenances_en_cours', 0);
    }

    public function test_transfer_transition_must_follow_the_allowed_sequence(): void
    {
        $this->fakePatrimoine();
        $token = $this->token();
        $transfertId = $this->createTransfer($token);

        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'approuvé',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('transferts', [
            'id' => $transfertId,
            'statut' => 'brouillon',
        ]);
        $this->assertDatabaseCount('historique_operations', 1);
    }

    public function test_transfer_keeps_approved_status_when_patrimoine_update_fails(): void
    {
        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'PATCH') {
                return Http::response(['message' => 'Erreur distante'], 500);
            }

            if (str_ends_with($request->url(), '/api/services/20')) {
                return Http::response(['data' => ['id' => 20]], 200);
            }

            return Http::response(['data' => ['id' => 1, 'service_id' => 10]], 200);
        });

        $token = $this->token();
        $transfertId = $this->createTransfer($token);
        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'en_attente',
        ])->assertOk();
        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'approuvé',
        ])->assertOk();

        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'effectué',
        ])->assertServiceUnavailable()
            ->assertJsonPath('message', 'Le bien n’a pas pu être mis à jour; le transfert reste approuvé.');

        $this->assertDatabaseHas('transferts', [
            'id' => $transfertId,
            'statut' => 'approuvé',
        ]);
        $this->assertDatabaseCount('historique_operations', 3);
    }

    public function test_transfer_updates_patrimoine_before_recording_completion(): void
    {
        $this->fakePatrimoine();
        $token = $this->token();
        $transfertId = $this->createTransfer($token);
        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'en_attente',
        ])->assertOk();
        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'approuvé',
        ])->assertOk();

        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'effectué',
        ])->assertOk()
            ->assertJsonPath('data.statut', 'effectué');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'PATCH'
            && str_ends_with($request->url(), '/api/biens/1')
            && $request['service_id'] === 20);
        $this->assertDatabaseHas('transferts', ['id' => $transfertId, 'statut' => 'effectué']);
        $this->assertDatabaseHas('historique_operations', [
            'action' => 'transfert.statut_modifie',
            'nouvelle_valeur->statut' => 'effectué',
        ]);
    }

    private function createTransfer(string $token): int
    {
        return $this->withToken($token)->postJson('/api/transferts', [
            'bien_id' => 1,
            'service_destination_id' => 20,
            'date_demande' => '2026-01-15',
            'motif' => 'Réorganisation des services',
        ])->assertCreated()->json('data.id');
    }

    private function fakePatrimoine(): void
    {
        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/api/services/20')) {
                return Http::response(['data' => ['id' => 20]], 200);
            }

            if (str_ends_with($request->url(), '/api/biens/404')) {
                return Http::response([], 404);
            }

            return Http::response(['data' => ['id' => 1, 'service_id' => 10]], 200);
        });
    }

    private function token(): string
    {
        return JWT::encode([
            'sub' => '42',
            'role' => 'administrateur-general',
            'service_id' => null,
            'iat' => now()->timestamp,
            'exp' => now()->addHours(2)->timestamp,
        ], config('jwt.secret'), 'HS256');
    }
}
