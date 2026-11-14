<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurer les URLs pour les tests
        config(['services.auth.url' => 'http://auth-service:9000']);
    }

    public function test_login_stores_jwt_in_session()
    {
        // Fake la réponse de l'auth-service
        Http::fake([
            'http://auth-service:9000/api/login' => Http::response([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'token' => 'fake_jwt_token_12345',
                    'user' => [
                        'id' => 1,
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                        'role_id' => 1,
                        'service_id' => null,
                    ],
                ],
            ], 200),
        ]);

        // Simuler la connexion
        $response = $this->post('/connexion', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/tableau-de-bord');

        // Vérifier que le JWT est stocké en session
        $this->assertSessionHas('jwt_token', 'fake_jwt_token_12345');
        $this->assertSessionHas('user');
    }

    public function test_login_with_invalid_credentials_redirects_back()
    {
        // Fake une réponse d'erreur de l'auth-service
        Http::fake([
            'http://auth-service:9000/api/login' => Http::response([
                'success' => false,
                'message' => 'Identifiants invalides',
            ], 401),
        ]);

        $response = $this->post('/connexion', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    }

    public function test_dashboard_accessible_with_jwt()
    {
        // Fake la réponse de l'auth-service
        Http::fake([
            'http://auth-service:9000/api/login' => Http::response([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'token' => 'fake_jwt_token_12345',
                    'user' => [
                        'id' => 1,
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                        'role_id' => 1,
                        'service_id' => null,
                    ],
                ],
            ], 200),
        ]);

        // Se connecter
        $this->post('/connexion', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Accéder au dashboard
        $response = $this->get('/tableau-de-bord');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
    }

    public function test_dashboard_redirects_to_login_without_jwt()
    {
        $response = $this->get('/tableau-de-bord');

        $response->assertRedirect('/connexion');
    }
}
