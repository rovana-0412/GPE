<?php

namespace Tests\Feature;

use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Parcours complet d'inventaire : constats multiples, totaux,
 * validation sans modification des données patrimoniales.
 */
class InventaireParcoursTest extends TestCase
{
    use RefreshDatabase;

    private function token(int $userId = 42): string
    {
        return JWT::encode([
            'sub' => (string) $userId,
            'role' => 'gestionnaire-patrimoine',
            'service_id' => null,
            'iat' => now()->timestamp,
            'exp' => now()->addHour()->timestamp,
        ], config('jwt.secret'), 'HS256');
    }

    private function fakePatrimoine(): void
    {
        $biens = [
            ['id' => 1, 'reference' => 'GPE-001', 'designation' => 'Ordinateur', 'service_id' => 10, 'actif' => true],
            ['id' => 2, 'reference' => 'GPE-002', 'designation' => 'Imprimante', 'service_id' => 10, 'actif' => true],
            ['id' => 3, 'reference' => 'GPE-003', 'designation' => 'Bureau', 'service_id' => 10, 'actif' => true],
            ['id' => 4, 'reference' => 'GPE-004', 'designation' => 'Climatiseur', 'service_id' => 10, 'actif' => true],
            ['id' => 5, 'reference' => 'GPE-005', 'designation' => 'Projeté', 'service_id' => 10, 'actif' => true],
        ];

        Http::fake(function ($request) use ($biens) {
            $path = parse_url($request->url(), PHP_URL_PATH);

            if ($path === '/api/biens') {
                return Http::response(['data' => $biens, 'current_page' => 1, 'last_page' => 1], 200);
            }
            if (preg_match('~/api/biens/(\d+)$~', $path, $m)) {
                $id = (int) $m[1];
                $bien = collect($biens)->firstWhere('id', $id);

                return $bien ? Http::response(['data' => $bien], 200) : Http::response([], 404);
            }
            if (preg_match('~/api/services/(\d+)$~', $path, $m)) {
                return Http::response(['data' => ['id' => (int) $m[1], 'ministere_id' => 100]], 200);
            }

            return Http::response(['data' => ['id' => 1]], 200);
        });
    }

    public function test_parcours_complet_avec_totaux_et_sans_modification_patrimoniale(): void
    {
        $this->fakePatrimoine();
        $token = $this->token();

        // 1. Création campagne
        $inventoryId = $this->withToken($token)->postJson('/api/inventaires', [
            'titre' => 'Inventaire annuel service 10',
            'service_id' => 10,
        ])->assertCreated()->assertJsonPath('data.statut', 'brouillon')->json('data.id');

        // 2. Démarrage
        $this->withToken($token)->patchJson("/api/inventaires/$inventoryId/statut", ['statut' => 'en_cours'])
            ->assertOk()->assertJsonPath('data.statut', 'en_cours');

        // 3. Constatations : 1 présent, 1 absent, 1 détérioré (bien 5 non contrôlé)
        $this->withToken($token)->postJson("/api/inventaires/$inventoryId/lignes", [
            'bien_id' => 1, 'constat' => 'present', 'localisation_physique' => 'Bureau A',
        ])->assertCreated();
        $this->withToken($token)->postJson("/api/inventaires/$inventoryId/lignes", [
            'bien_id' => 2, 'constat' => 'absent', 'observation' => 'Introuvable',
        ])->assertCreated();
        $this->withToken($token)->postJson("/api/inventaires/$inventoryId/lignes", [
            'bien_id' => 3, 'constat' => 'deteriore', 'observation' => 'Écran fissuré',
        ])->assertCreated();

        // 4. Rapport intermédiaire : 5 théoriques, 3 contrôlés, 2 non contrôlés (biens 4 et 5)
        $this->withToken($token)->getJson("/api/inventaires/$inventoryId")
            ->assertOk()
            ->assertJsonPath('rapport.biens_theoriques', 5)
            ->assertJsonPath('rapport.biens_controles', 3)
            ->assertJsonPath('rapport.presents', 1)
            ->assertJsonPath('rapport.absents', 1)
            ->assertJsonPath('rapport.deteriores', 1)
            ->assertJsonPath('rapport.non_controles', 2)
            ->assertJsonPath('rapport.ecarts', 4); // 1 absent + 1 détérioré + 2 non contrôlés

        // 5. Soumission puis validation par un autre utilisateur
        $this->withToken($token)->patchJson("/api/inventaires/$inventoryId/statut", ['statut' => 'a_valider'])
            ->assertOk()->assertJsonPath('data.statut', 'a_valider');
        $this->withToken($this->token(77))->patchJson("/api/inventaires/$inventoryId/statut", ['statut' => 'valide'])
            ->assertOk()->assertJsonPath('data.statut', 'valide');

        // 6. La validation ne doit PAS modifier les biens côté patrimoine (aucun PUT/PATCH/DELETE)
        Http::assertNotSent(fn ($request) => in_array($request->method(), ['PUT', 'PATCH', 'DELETE'], true));

        // 7. Historique et notification au créateur
        $this->assertDatabaseHas('historique_operations', ['action' => 'inventaire.statut_modifie']);
        $this->assertDatabaseHas('notifications_metier', ['utilisateur_id' => 42, 'type' => 'inventaire.valide']);

        // 8. Campagne verrouillée : plus de constat possible
        $this->withToken($token)->postJson("/api/inventaires/$inventoryId/lignes", [
            'bien_id' => 4, 'constat' => 'present',
        ])->assertConflict();
    }
}
