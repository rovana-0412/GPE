<?php

namespace Tests\Feature;

use App\Models\Bien;
use App\Models\Categorie;
use App\Models\Ministere;
use App\Models\Service;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ] as $uri) {
            $this->getJson($uri)->assertUnauthorized();
        }
    }

    public function test_biens_are_paginated_filtered_and_soft_deleted(): void
    {
        [$service, $categorie] = $this->createReferenceData();
        Bien::create([
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
            ->assertJsonPath('data.biens_par_ministere.0.biens_count', 1);

        $this->withToken($token)
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.biens_count', 1);
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
        return JWT::encode([
            'sub' => '42',
            'role' => 'administrateur-general',
            'service_id' => null,
            'iat' => now()->timestamp,
            'exp' => now()->addHours(2)->timestamp,
        ], config('jwt.secret'), 'HS256');
    }
}
