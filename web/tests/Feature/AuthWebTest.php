<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthWebTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configurer les URLs pour les tests
        config(['services.auth.url' => 'http://auth-service:8000']);
        config(['services.patrimoine.url' => 'http://patrimoine-service:8000']);
    }

    public function test_login_stores_jwt_in_session()
    {
        // Fake la réponse de l'auth-service
        Http::fake([
            'http://auth-service:8000/api/login' => Http::response([
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
        $response->assertSessionHas('jwt_token', 'fake_jwt_token_12345');
        $response->assertSessionHas('user');
    }

    public function test_login_with_invalid_credentials_redirects_back()
    {
        // Fake une réponse d'erreur de l'auth-service
        Http::fake([
            'http://auth-service:8000/api/login' => Http::response([
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
        Http::fake([
            'http://auth-service:8000/api/login' => Http::response([
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
            'http://patrimoine-service:8000/api/stats' => Http::response([
                'data' => [
                    'biens' => 0,
                    'categories' => 0,
                    'ministeres' => 0,
                    'services' => 0,
                    'valeur_totale' => 0,
                    'biens_par_type' => [],
                    'biens_par_categorie' => [],
                    'biens_par_ministere' => [],
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

    public function test_service_unauthorized_response_logs_out_the_user(): void
    {
        Http::fake([
            'http://patrimoine-service:8000/api/stats' => Http::response([], 401),
        ]);

        $response = $this->withSession([
            'jwt_token' => 'expired-token',
            'user' => ['id' => 1, 'name' => 'Utilisateur test'],
        ])->get('/tableau-de-bord');

        $response->assertRedirect('/connexion')
            ->assertSessionMissing('jwt_token')
            ->assertSessionHasErrors('email');
    }

    public function test_unavailable_service_displays_a_french_error_page(): void
    {
        Http::fake([
            'http://patrimoine-service:8000/api/stats' => Http::response([], 503),
        ]);

        $this->withSession([
            'jwt_token' => 'valid-looking-token',
            'user' => ['id' => 1, 'name' => 'Utilisateur test'],
        ])->get('/tableau-de-bord')
            ->assertStatus(503)
            ->assertSee('Service momentanément indisponible')
            ->assertSee('ne répond pas actuellement');
    }

    public function test_bien_list_and_creation_form_use_patrimoine_service(): void
    {
        Http::fake([
            'http://patrimoine-service:8000/api/biens*' => Http::response([
                'data' => [[
                    'id' => 1,
                    'reference' => 'GPE-TEST-001',
                    'designation' => 'Ordinateur de test',
                    'etat' => 'bon_etat',
                    'valeur_acquisition' => '100000.00',
                    'categorie' => ['nom' => 'Matériel informatique'],
                    'service' => ['nom' => 'Direction du Budget'],
                ]],
                'current_page' => 1,
                'last_page' => 1,
            ]),
            'http://patrimoine-service:8000/api/categories*' => Http::response([
                'data' => [['id' => 1, 'nom' => 'Matériel informatique']],
            ]),
            'http://patrimoine-service:8000/api/services*' => Http::response([
                'data' => [['id' => 1, 'nom' => 'Direction du Budget', 'ministere' => ['nom' => 'MEF']]],
            ]),
        ]);
        $session = [
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Utilisateur test'],
        ];

        $this->withSession($session)->get('/biens')
            ->assertOk()
            ->assertSee('GPE-TEST-001')
            ->assertSee('Ordinateur de test');

        $this->withSession($session)->get('/biens/creer')
            ->assertOk()
            ->assertSee('Enregistrer un bien')
            ->assertSee('Matériel informatique')
            ->assertSee('Direction du Budget');
    }

    public function test_reference_management_page_renders(): void
    {
        Http::fake([
            'http://patrimoine-service:8000/api/categories*' => Http::response([
                'data' => [['id' => 1, 'nom' => 'Matériel informatique', 'type' => 'mobilier', 'biens_count' => 2]],
                'current_page' => 1,
                'last_page' => 1,
            ]),
        ]);

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Utilisateur test'],
        ])->get('/categories')
            ->assertOk()
            ->assertSee('Catégories enregistrés')
            ->assertSee('Matériel informatique');
    }

    public function test_dashboard_redirects_to_login_without_jwt()
    {
        $response = $this->get('/tableau-de-bord');

        $response->assertRedirect('/connexion');
    }
}
