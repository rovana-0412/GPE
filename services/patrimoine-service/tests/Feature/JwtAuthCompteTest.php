<?php

namespace Tests\Feature;

use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * F6 : le JWT seul ne suffit pas — le statut actif du compte est
 * revérifié auprès de l'auth-service à chaque requête API.
 */
class JwtAuthCompteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.auth.url' => 'http://auth.test',
            'services.auth.verify_account' => true,
        ]);
    }

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

    public function test_compte_actif_accede_a_l_api(): void
    {
        Http::fake(['*/api/me' => Http::response(['success' => true], 200)]);

        $this->withToken($this->token())->getJson('/api/biens')->assertOk();
    }

    public function test_compte_desactive_est_refuse(): void
    {
        Http::fake(['*/api/me' => Http::response(['success' => false, 'message' => 'Compte désactivé.'], 401)]);

        $this->withToken($this->token())->getJson('/api/biens')->assertUnauthorized();
    }

    public function test_reactivation_rend_l_acces_apres_le_cache(): void
    {
        // Compte désactivé puis réactivé : le cache court (30 s) retarde
        // au maximum la réactivation, jamais l'inverse.
        $actif = false;
        Http::fake(function () use (&$actif) {
            return $actif
                ? Http::response(['success' => true], 200)
                : Http::response(['success' => false], 401);
        });

        $this->withToken($this->token())->getJson('/api/biens')->assertUnauthorized();

        $this->withToken($this->token())->getJson('/api/biens')->assertUnauthorized();

        \Illuminate\Support\Facades\Cache::flush();
        $actif = true;

        $this->withToken($this->token())->getJson('/api/biens')->assertOk();
    }

    public function test_auth_service_indisponible_refuse_l_acces(): void
    {
        Http::fake(['*/api/me' => Http::response(['message' => 'Erreur serveur'], 500)]);

        $this->withToken($this->token())->getJson('/api/biens')->assertStatus(503);
    }

    public function test_timeout_auth_service_refuse_l_acces(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'));

        $this->withToken($this->token())->getJson('/api/biens')->assertStatus(503);
    }

    public function test_le_statut_est_mis_en_cache_pour_eviter_les_appels_repetes(): void
    {
        Http::fake(['*/api/me' => Http::response(['success' => true], 200)]);

        $this->withToken($this->token())->getJson('/api/biens')->assertOk();
        $this->withToken($this->token())->getJson('/api/biens')->assertOk();

        Http::assertSentCount(1);
    }

    public function test_jeton_expire_reste_refuse_avant_toute_verification(): void
    {
        Http::fake(['*/api/me' => Http::response(['success' => true], 200)]);

        $expired = JWT::encode([
            'sub' => '42',
            'role' => 'gestionnaire-patrimoine',
            'service_id' => null,
            'iat' => now()->subHours(3)->timestamp,
            'exp' => now()->subHour()->timestamp,
        ], config('jwt.secret'), 'HS256');

        $this->withToken($expired)->getJson('/api/biens')->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_sans_token_aucun_appel_auth(): void
    {
        Http::fake(['*/api/me' => Http::response(['success' => true], 200)]);

        $this->getJson('/api/biens')->assertUnauthorized();
        Http::assertNothingSent();
    }
}
