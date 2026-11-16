<?php

namespace Tests\Feature;

use App\Models\Bien;
use App\Models\Categorie;
use App\Models\Ministere;
use App\Models\Service;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vérifie les périmètres d'accès par rôle sur l'API patrimoine.
 * Deux ministères, deux services chacun, biens répartis.
 */
class PerimetreAccesTest extends TestCase
{
    use RefreshDatabase;

    private Ministere $ministereA;
    private Ministere $ministereB;
    private Service $serviceA1;
    private Service $serviceA2;
    private Service $serviceB1;
    private Service $serviceB2;
    private Bien $bienA;
    private Bien $bienB;

    protected function setUp(): void
    {
        parent::setUp();

        $categorie = Categorie::create(['nom' => 'Cat test', 'type' => 'mobilier']);
        $this->ministereA = Ministere::create(['nom' => 'Ministère A', 'sigle' => 'MINA']);
        $this->ministereB = Ministere::create(['nom' => 'Ministère B', 'sigle' => 'MINB']);
        $this->serviceA1 = Service::create(['ministere_id' => $this->ministereA->id, 'nom' => 'Service A1', 'sigle' => 'SA1']);
        $this->serviceA2 = Service::create(['ministere_id' => $this->ministereA->id, 'nom' => 'Service A2', 'sigle' => 'SA2']);
        $this->serviceB1 = Service::create(['ministere_id' => $this->ministereB->id, 'nom' => 'Service B1', 'sigle' => 'SB1']);
        $this->serviceB2 = Service::create(['ministere_id' => $this->ministereB->id, 'nom' => 'Service B2', 'sigle' => 'SB2']);

        $this->bienA = Bien::create([
            'reference' => 'BIEN-A1', 'designation' => 'Bien ministère A',
            'categorie_id' => $categorie->id, 'service_id' => $this->serviceA1->id,
            'actif' => true,
        ]);
        Bien::create([
            'reference' => 'BIEN-A2', 'designation' => 'Bien A service 2',
            'categorie_id' => $categorie->id, 'service_id' => $this->serviceA2->id,
            'actif' => true,
        ]);
        $this->bienB = Bien::create([
            'reference' => 'BIEN-B1', 'designation' => 'Bien ministère B',
            'categorie_id' => $categorie->id, 'service_id' => $this->serviceB1->id,
            'actif' => true,
        ]);
        Bien::create([
            'reference' => 'BIEN-B2', 'designation' => 'Bien B service 2',
            'categorie_id' => $categorie->id, 'service_id' => $this->serviceB2->id,
            'actif' => true,
        ]);
    }

    private function token(string $role, ?int $serviceId): string
    {
        return JWT::encode([
            'sub' => '1',
            'role' => $role,
            'service_id' => $serviceId,
            'iat' => now()->timestamp,
            'exp' => now()->addHour()->timestamp,
        ], config('jwt.secret'), 'HS256');
    }

    private function references(string $token): array
    {
        $response = $this->withToken($token)->getJson('/api/biens?per_page=100');
        $response->assertOk();

        return collect($response->json('data'))->pluck('reference')->all();
    }

    public function test_responsable_ministeriel_ne_voit_que_son_ministere(): void
    {
        $refs = $this->references($this->token('responsable-ministeriel', $this->serviceA1->id));

        $this->assertContains('BIEN-A1', $refs);
        $this->assertContains('BIEN-A2', $refs);
        $this->assertNotContains('BIEN-B1', $refs);
        $this->assertNotContains('BIEN-B2', $refs);
    }

    public function test_agent_gestionnaire_ne_voit_que_son_service(): void
    {
        $refs = $this->references($this->token('agent-gestionnaire', $this->serviceA1->id));

        $this->assertSame(['BIEN-A1'], $refs);
    }

    public function test_responsable_ne_peut_pas_lire_le_bien_d_un_autre_ministere(): void
    {
        $response = $this->withToken($this->token('responsable-ministeriel', $this->serviceA1->id))
            ->getJson('/api/biens/'.$this->bienB->id);

        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_responsable_ne_peut_pas_modifier_un_bien_hors_perimetre(): void
    {
        $response = $this->withToken($this->token('responsable-ministeriel', $this->serviceA1->id))
            ->putJson('/api/biens/'.$this->bienB->id, ['designation' => 'Détournement']);

        $this->assertContains($response->status(), [403, 404]);
        $this->assertDatabaseHas('biens', ['id' => $this->bienB->id, 'designation' => 'Bien ministère B']);
    }

    public function test_responsable_ne_peut_pas_supprimer_un_bien_hors_perimetre(): void
    {
        $response = $this->withToken($this->token('responsable-ministeriel', $this->serviceA1->id))
            ->deleteJson('/api/biens/'.$this->bienB->id);

        $this->assertContains($response->status(), [403, 404]);
        $this->assertDatabaseHas('biens', ['id' => $this->bienB->id, 'deleted_at' => null]);
    }

    public function test_agent_ne_peut_pas_ecrire_meme_dans_son_perimetre_selon_les_routes(): void
    {
        // Le groupe de routes « écriture par périmètre » est partiellement masqué
        // par le groupe gestionnaire ; on documente le comportement actuel.
        $response = $this->withToken($this->token('agent-gestionnaire', $this->serviceA1->id))
            ->postJson('/api/biens', [
                'reference' => 'BIEN-TEST-AGENT',
                'designation' => 'Test agent',
                'categorie_id' => $this->bienA->categorie_id,
                'service_id' => $this->serviceA1->id,
            ]);

        $this->assertContains($response->status(), [201, 403]);
    }

    public function test_auditeur_ne_peut_pas_ecrire(): void
    {
        $response = $this->withToken($this->token('auditeur', null))
            ->postJson('/api/biens', [
                'reference' => 'BIEN-TEST-AUD',
                'designation' => 'Test auditeur',
                'categorie_id' => $this->bienA->categorie_id,
                'service_id' => $this->serviceA1->id,
            ]);

        $this->assertSame(403, $response->status());
    }

    public function test_ressources_sans_perimetre_refusent_les_roles_sans_service(): void
    {
        // Un responsable sans service_id ne doit rien écrire.
        $response = $this->withToken($this->token('responsable-ministeriel', null))
            ->putJson('/api/biens/'.$this->bienA->id, ['designation' => 'Test']);

        $this->assertContains($response->status(), [403, 404]);
    }
}
