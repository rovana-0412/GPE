<?php

namespace Tests\Feature;

use App\Models\Bien;
use App\Models\Acquisition;
use App\Models\SortiePatrimoine;
use App\Models\Categorie;
use App\Models\Ministere;
use App\Models\Service;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PatrimoineApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => str_repeat('t', 32)]);
    }

    public function test_patrimoine_routes_reject_requests_without_a_token(): void
    {
        foreach ([
            '/api/biens',
            '/api/categories',
            '/api/ministeres',
            '/api/services',
            '/api/stats',
            '/api/audit',
        ] as $uri) {
            $this->getJson($uri)->assertUnauthorized();
        }
    }

    public function test_biens_are_paginated_filtered_and_soft_deleted(): void
    {
        [$service, $categorie] = $this->createReferenceData();
        $bien = Bien::create([
            'reference' => 'GPE-TEST-001',
            'designation' => 'Ordinateur de test',
            'categorie_id' => $categorie->id,
            'service_id' => $service->id,
            'etat' => 'bon_etat',
            'valeur_acquisition' => 100000,
        ]);
        Bien::create([
            'reference' => 'GPE-TEST-002',
            'designation' => 'Véhicule de test',
            'categorie_id' => $categorie->id,
            'service_id' => $service->id,
            'etat' => 'a_reparer',
            'valeur_acquisition' => 200000,
        ]);

        $this->withToken($this->token())
            ->getJson('/api/biens?categorie_id='.$categorie->id.'&etat=bon_etat&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.reference', 'GPE-TEST-001')
            ->assertJsonPath('per_page', 1)
            ->assertJsonPath('total', 1);
        $this->withToken($this->token())
            ->getJson('/api/biens?'.http_build_query(['ids' => [$bien->id]]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $bien->id)
            ->assertJsonPath('total', 1);
        $this->withToken($this->token())
            ->getJson('/api/biens?q=ordinateur')
            ->assertOk()
            ->assertJsonPath('data.0.id', $bien->id);

        $bien = Bien::where('reference', 'GPE-TEST-001')->firstOrFail();

        $this->withToken($this->token())
            ->deleteJson('/api/biens/'.$bien->id)
            ->assertNoContent();

        $this->assertSoftDeleted('biens', ['id' => $bien->id]);
    }

    public function test_resource_crud_and_statistics_use_their_own_database(): void
    {
        $token = $this->token();

        $ministereId = $this->withToken($token)
            ->postJson('/api/ministeres', [
                'nom' => 'Ministère test',
                'sigle' => 'MT',
            ])
            ->assertCreated()
            ->json('data.id');

        $serviceId = $this->withToken($token)
            ->postJson('/api/services', [
                'ministere_id' => $ministereId,
                'nom' => 'Service test',
                'sigle' => 'ST',
            ])
            ->assertCreated()
            ->json('data.id');

        $categorieId = $this->withToken($token)
            ->postJson('/api/categories', [
                'nom' => 'Catégorie test',
                'type' => 'mobilier',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->withToken($token)
            ->postJson('/api/biens', [
                'reference' => 'GPE-API-001',
                'designation' => 'Bien créé par API',
                'categorie_id' => $categorieId,
                'service_id' => $serviceId,
                'valeur_acquisition' => 250000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.reference', 'GPE-API-001');

        $this->withToken($token)
            ->getJson('/api/stats')
            ->assertOk()
            ->assertJsonPath('data.biens', 1)
            ->assertJsonPath('data.valeur_totale', 250000)
            ->assertJsonPath('data.acquisitions_en_cours', 0)
            ->assertJsonPath('data.sorties_en_cours', 0)
            ->assertJsonPath('data.biens_par_ministere.0.biens_count', 1);

        $this->withToken($token)
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.biens_count', 1);
        $this->withToken($token)
            ->getJson('/api/categories?q=Catégorie')
            ->assertOk()
            ->assertJsonPath('data.0.nom', 'Catégorie test');
        $this->withToken($token)
            ->getJson('/api/ministeres?q=Ministère')
            ->assertOk()
            ->assertJsonPath('data.0.nom', 'Ministère test');
        $this->withToken($token)
            ->getJson('/api/services?q=Service')
            ->assertOk()
            ->assertJsonPath('data.0.nom', 'Service test');
    }

    public function test_demo_seeders_are_idempotent(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('ministeres', 4);
        $this->assertDatabaseCount('services', 9);
        $this->assertDatabaseCount('categories', 10);
        $this->assertDatabaseCount('biens', 6);
    }

    public function test_reference_updates_are_validated_and_used_records_cannot_be_deleted(): void
    {
        [$service, $categorie] = $this->createReferenceData();
        $bien = Bien::create([
            'reference' => 'GPE-REF-001',
            'designation' => 'Bien de référence',
            'categorie_id' => $categorie->id,
            'service_id' => $service->id,
        ]);
        $token = $this->token();

        $this->withToken($token)
            ->putJson('/api/services/'.$service->id, ['nom' => 'Direction mise à jour'])
            ->assertOk()
            ->assertJsonPath('data.nom', 'Direction mise à jour');

        $this->withToken($token)
            ->deleteJson('/api/categories/'.$categorie->id)
            ->assertConflict();

        $this->assertDatabaseHas('biens', ['id' => $bien->id, 'categorie_id' => $categorie->id]);
    }

    public function test_acquisition_workflow_creates_a_well_only_after_receipt_and_validation(): void
    {
        [$service, $categorie] = $this->createReferenceData();
        $token = $this->token();

        $acquisitionId = $this->withToken($token)
            ->postJson('/api/acquisitions', [
                'reference' => 'GPE-ACQ-001',
                'designation' => 'Véhicule administratif',
                'categorie_id' => $categorie->id,
                'service_id' => $service->id,
                'fournisseur' => 'Fournisseur local',
                'cout' => 14500000,
                'date_acquisition' => '2026-11-01',
                'mode_acquisition' => 'achat',
                'reference_facture' => 'FAC-2026-001',
                'reference_bon_commande' => 'BC-2026-004',
            ])
            ->assertCreated()
            ->assertJsonPath('data.statut', 'brouillon')
            ->json('data.id');

        $this->assertDatabaseCount('biens', 0);

        $this->withToken($token)
            ->patchJson('/api/acquisitions/'.$acquisitionId.'/statut', ['statut' => 'en_attente'])
            ->assertOk()
            ->assertJsonPath('data.statut', 'en_attente');

        $this->withToken($token)
            ->patchJson('/api/acquisitions/'.$acquisitionId.'/statut', ['statut' => 'receptionnee'])
            ->assertOk()
            ->assertJsonPath('data.statut', 'receptionnee');

        $this->withToken($token)
            ->patchJson('/api/acquisitions/'.$acquisitionId.'/statut', ['statut' => 'validee'])
            ->assertOk()
            ->assertJsonPath('data.statut', 'validee')
            ->assertJsonPath('data.bien.reference', 'GPE-ACQ-001')
            ->assertJsonPath('data.bien.valeur_acquisition', '14500000.00');

        $this->assertDatabaseCount('biens', 1);
        $this->assertDatabaseHas('acquisitions', [
            'id' => $acquisitionId,
            'statut' => 'validee',
            'fournisseur' => 'Fournisseur local',
            'reference_facture' => 'FAC-2026-001',
        ]);
        $this->assertNotNull(Acquisition::findOrFail($acquisitionId)->bien_id);
        $this->assertDatabaseHas('journal_actions', [
            'ressource' => 'acquisition',
            'ressource_id' => $acquisitionId,
            'action' => 'acquisition.statut_modifie',
            'utilisateur_id' => 42,
        ]);

        $this->withToken($token)
            ->patchJson('/api/acquisitions/'.$acquisitionId.'/statut', ['statut' => 'validee'])
            ->assertUnprocessable();
        $this->assertDatabaseCount('biens', 1);
    }

    public function test_acquisition_decisions_notify_the_requester_through_mouvements_service(): void
    {
        Http::fake(['*' => Http::response(['data' => ['id' => 1]], 201)]);
        [$service, $categorie] = $this->createReferenceData();
        $requester = $this->tokenForUser(42);
        $reviewer = $this->tokenForUser(77);
        $acquisitionId = $this->withToken($requester)->postJson('/api/acquisitions', [
            'reference' => 'GPE-ACQ-NOTIF',
            'designation' => 'Équipement notifié',
            'categorie_id' => $categorie->id,
            'service_id' => $service->id,
            'fournisseur' => 'Fournisseur local',
            'cout' => 150000,
            'date_acquisition' => '2026-11-01',
            'mode_acquisition' => 'achat',
        ])->assertCreated()->json('data.id');

        $this->withToken($requester)->patchJson('/api/acquisitions/'.$acquisitionId.'/statut', [
            'statut' => 'en_attente',
        ])->assertOk();
        $this->withToken($reviewer)->patchJson('/api/acquisitions/'.$acquisitionId.'/statut', [
            'statut' => 'receptionnee',
        ])->assertOk();
        $this->withToken($reviewer)->patchJson('/api/acquisitions/'.$acquisitionId.'/statut', [
            'statut' => 'validee',
        ])->assertOk();

        Http::assertSentCount(2);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://mouvements-service:8000/api/notifications'
            && $request['utilisateur_id'] === 42
            && $request['type'] === 'acquisition.validee'
            && $request['donnees']['acquisition_id'] === $acquisitionId);
        Http::assertSent(fn (ClientRequest $request) => $request['type'] === 'acquisition.receptionnee');
    }

    public function test_acquisition_cannot_be_validated_before_receipt(): void
    {
        [$service, $categorie] = $this->createReferenceData();
        $acquisition = Acquisition::create([
            'reference' => 'GPE-ACQ-002',
            'designation' => 'Ordinateur administratif',
            'categorie_id' => $categorie->id,
            'service_id' => $service->id,
            'fournisseur' => 'Fournisseur local',
            'cout' => 500000,
            'date_acquisition' => '2026-11-01',
            'mode_acquisition' => 'achat',
        ]);

        $this->withToken($this->token())
            ->patchJson('/api/acquisitions/'.$acquisition->id.'/statut', ['statut' => 'validee'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('biens', 0);
        $this->assertDatabaseHas('acquisitions', ['id' => $acquisition->id, 'statut' => 'brouillon']);
    }

    public function test_exit_workflow_archives_asset_without_deleting_it(): void
    {
        [$service, $categorie] = $this->createReferenceData();
        $bien = Bien::create([
            'reference' => 'GPE-SORTIE-001',
            'designation' => 'Véhicule à réformer',
            'categorie_id' => $categorie->id,
            'service_id' => $service->id,
            'actif' => true,
        ]);
        $token = $this->token();

        $sortieId = $this->withToken($token)
            ->postJson('/api/sorties', [
                'bien_id' => $bien->id,
                'type_sortie' => 'mise_au_rebut',
                'motif' => 'Bien irréparable après expertise.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.statut', 'demandee')
            ->json('data.id');

        $this->withToken($token)
            ->postJson('/api/sorties', [
                'bien_id' => $bien->id,
                'type_sortie' => 'cession',
                'motif' => 'Demande en doublon.',
            ])
            ->assertConflict();

        $this->withToken($token)
            ->patchJson('/api/sorties/'.$sortieId.'/statut', ['statut' => 'executee'])
            ->assertUnprocessable();
        $this->withToken($token)
            ->patchJson('/api/sorties/'.$sortieId.'/statut', ['statut' => 'approuvee'])
            ->assertOk();
        $this->withToken($token)
            ->patchJson('/api/sorties/'.$sortieId.'/statut', ['statut' => 'executee'])
            ->assertOk()
            ->assertJsonPath('data.statut', 'executee')
            ->assertJsonPath('data.bien.actif', false)
            ->assertJsonPath('data.bien.statut', 'sorti');

        $this->assertDatabaseHas('biens', [
            'id' => $bien->id,
            'reference' => 'GPE-SORTIE-001',
            'actif' => false,
            'statut' => 'sorti',
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('sorties_patrimoine', [
            'id' => $sortieId,
            'statut' => 'executee',
            'date_execution' => now()->toDateString(),
        ]);
        $this->withToken($token)->getJson('/api/audit')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'sortie.statut_modifie')
            ->assertJsonPath('data.0.bien_id', $bien->id);
        $this->assertInstanceOf(SortiePatrimoine::class, SortiePatrimoine::findOrFail($sortieId));
    }

    public function test_exit_decisions_notify_the_requester(): void
    {
        Http::fake(['*' => Http::response(['data' => ['id' => 1]], 201)]);
        [$service, $categorie] = $this->createReferenceData();
        $bien = Bien::create([
            'reference' => 'GPE-SORTIE-NOTIF',
            'designation' => 'Matériel à réformer',
            'categorie_id' => $categorie->id,
            'service_id' => $service->id,
            'actif' => true,
        ]);
        $requester = $this->tokenForUser(42);
        $reviewer = $this->tokenForUser(77);
        $sortieId = $this->withToken($requester)->postJson('/api/sorties', [
            'bien_id' => $bien->id,
            'type_sortie' => 'mise_au_rebut',
            'motif' => 'Matériel hors d’usage.',
        ])->assertCreated()->json('data.id');

        $this->withToken($reviewer)->patchJson('/api/sorties/'.$sortieId.'/statut', [
            'statut' => 'approuvee',
        ])->assertOk();
        $this->withToken($reviewer)->patchJson('/api/sorties/'.$sortieId.'/statut', [
            'statut' => 'executee',
        ])->assertOk();

        Http::assertSentCount(2);
        Http::assertSent(fn (ClientRequest $request) => $request['utilisateur_id'] === 42
            && $request['type'] === 'sortie.executee'
            && $request['donnees']['sortie_id'] === $sortieId);
    }

    private function createReferenceData(): array
    {
        $ministere = Ministere::create([
            'nom' => 'Ministère de test',
            'sigle' => 'MTEST',
        ]);
        $service = Service::create([
            'ministere_id' => $ministere->id,
            'nom' => 'Service de test',
            'sigle' => 'STEST',
        ]);
        $categorie = Categorie::create([
            'nom' => 'Catégorie de test',
            'type' => 'mobilier',
        ]);

        return [$service, $categorie];
    }

    private function token(): string
    {
        return $this->tokenForUser(42);
    }

    private function tokenForUser(int $userId): string
    {
        return JWT::encode([
            'sub' => (string) $userId,
            'role' => 'administrateur-general',
            'service_id' => null,
            'iat' => now()->timestamp,
            'exp' => now()->addHours(2)->timestamp,
        ], config('jwt.secret'), 'HS256');
    }
}
