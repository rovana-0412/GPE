<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => 'test_secret_key_for_jwt_at_least_32_chars']);
    }

    public function test_login_returns_valid_jwt_token()
    {
        // Créer un rôle et un utilisateur de test
        $role = Role::create([
            'nom' => 'Test Role',
            'description' => 'Role de test',
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'service_id' => null,
        ]);

        // Appeler l'endpoint login
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Connexion réussie',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role_id',
                        'service_id',
                    ],
                ],
            ]);

        // Vérifier que le token est présent
        $this->assertArrayHasKey('token', $response->json()['data']);
    }

    public function test_login_with_invalid_credentials_returns_401()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Identifiants invalides',
            ]);
    }

    public function test_me_without_token_returns_401()
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token manquant',
            ]);
    }

    public function test_me_with_valid_token_returns_user_data()
    {
        // Créer un rôle et un utilisateur de test
        $role = Role::create([
            'nom' => 'Test Role',
            'description' => 'Role de test',
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'service_id' => null,
        ]);

        // Login pour obtenir le token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json()['data']['token'];

        // Appeler /api/me avec le token
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ],
            ]);
    }
}
