<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Firebase\JWT\JWT;
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

    public function test_root_returns_service_status(): void
    {
        $this->getJson('/')
            ->assertOk()
            ->assertExactJson([
                'service' => 'auth-service',
                'status' => 'ok',
            ]);
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

    public function test_login_attempts_are_audited_without_storing_credentials(): void
    {
        $role = Role::create(['nom' => 'Agent gestionnaire']);
        $user = User::create([
            'name' => 'Agent',
            'email' => 'agent@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $this->assertDatabaseHas('audit_logs', [
            'acteur_id' => null,
            'sujet_id' => $user->id,
            'action' => 'authentification.echouee',
            'nouvelle_valeur' => null,
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'acteur_id' => $user->id,
            'sujet_id' => $user->id,
            'action' => 'authentification.reussie',
            'nouvelle_valeur' => null,
        ]);
    }

    public function test_login_normalizes_accents_in_role_slug(): void
    {
        $role = Role::create([
            'nom' => 'Administrateur général',
            'description' => 'Administration complète',
        ]);

        User::create([
            'name' => 'Administrateur',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'service_id' => null,
        ]);

        $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.role_slug', 'administrateur-general');
    }

    public function test_login_maps_manager_role_to_the_permission_slug(): void
    {
        $role = Role::create([
            'nom' => 'Gestionnaire du patrimoine',
            'description' => 'Gestion du patrimoine',
        ]);

        User::create([
            'name' => 'Gestionnaire',
            'email' => 'gestionnaire@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);

        $this->postJson('/api/login', [
            'email' => 'gestionnaire@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.role_slug', 'gestionnaire-patrimoine');
    }

    public function test_disabled_accounts_cannot_log_in_or_use_existing_tokens(): void
    {
        $role = Role::create(['nom' => 'Agent gestionnaire']);
        $user = User::create([
            'name' => 'Agent',
            'email' => 'agent@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'actif' => false,
        ]);

        $this->postJson('/api/login', [
            'email' => 'agent@example.com',
            'password' => 'password123',
        ])->assertUnauthorized();

        $this->withToken($this->tokenFor($user))
            ->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Compte désactivé.');
    }

    public function test_admin_user_management_is_role_protected_and_prevents_self_escalation(): void
    {
        $adminRole = Role::create(['nom' => 'Administrateur général']);
        $agentRole = Role::create(['nom' => 'Agent gestionnaire']);
        $agent = User::create([
            'name' => 'Agent',
            'email' => 'agent@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $agentRole->id,
        ]);
        $admin = User::create([
            'name' => 'Administrateur',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
        ]);

        $this->withToken($this->tokenFor($agent))
            ->getJson('/api/admin/users')
            ->assertForbidden();

        $adminToken = $this->tokenFor($admin);
        $newUserId = $this->withToken($adminToken)
            ->postJson('/api/admin/users', [
                'name' => 'Nouvel agent',
                'email' => 'nouvel-agent@example.com',
                'password' => 'MotDePasse!2026',
                'role_id' => $agentRole->id,
                'service_id' => 12,
            ])
            ->assertCreated()
            ->assertJsonMissingPath('data.password')
            ->json('data.id');

        $this->withToken($adminToken)
            ->putJson('/api/admin/users/'.$admin->id, ['role_id' => $agentRole->id])
            ->assertForbidden();

        $this->withToken($adminToken)
            ->patchJson('/api/admin/users/'.$admin->id.'/status', ['actif' => false])
            ->assertForbidden();

        $this->withToken($adminToken)
            ->patchJson('/api/admin/users/'.$newUserId.'/status', ['actif' => false])
            ->assertOk()
            ->assertJsonPath('data.actif', false);

        $this->assertDatabaseCount('audit_logs', 2);
        $this->assertDatabaseHas('audit_logs', [
            'sujet_id' => $newUserId,
            'action' => 'utilisateur.desactive',
        ]);
        $this->withToken($adminToken)->getJson('/api/audit')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'utilisateur.desactive')
            ->assertJsonMissingPath('data.0.nouvelle_valeur.password');
        $this->withToken($this->tokenFor($agent))->getJson('/api/audit')->assertForbidden();

        $this->postJson('/api/login', [
            'email' => 'nouvel-agent@example.com',
            'password' => 'MotDePasse!2026',
        ])->assertUnauthorized();
    }

    public function test_platform_settings_are_admin_only_persisted_and_audited(): void
    {
        $adminRole = Role::create(['nom' => 'Administrateur général']);
        $agentRole = Role::create(['nom' => 'Agent gestionnaire']);
        $admin = User::create([
            'name' => 'Administrateur',
            'email' => 'settings-admin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
        ]);
        $agent = User::create([
            'name' => 'Agent',
            'email' => 'settings-agent@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $agentRole->id,
        ]);
        $settings = [
            'nom_plateforme' => 'GPE National',
            'nom_institution' => 'République togolaise',
            'contact_support' => 'support@example.com',
        ];

        $this->withToken($this->tokenFor($agent))->putJson('/api/admin/settings', $settings)
            ->assertForbidden();
        $this->withToken($this->tokenFor($admin))->getJson('/api/admin/settings')
            ->assertOk()
            ->assertJsonPath('data.nom_plateforme', 'GPE')
            ->assertJsonPath('data.nom_institution', 'République togolaise');
        $this->withToken($this->tokenFor($admin))->putJson('/api/admin/settings', $settings)
            ->assertOk()
            ->assertJsonPath('data.nom_plateforme', 'GPE National')
            ->assertJsonPath('data.contact_support', 'support@example.com');
        $this->withToken($this->tokenFor($agent))->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('data.nom_plateforme', 'GPE National');

        $this->assertDatabaseHas('parametres_plateforme', [
            'cle' => 'nom_plateforme',
            'valeur' => 'GPE National',
            'modifie_par' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'acteur_id' => $admin->id,
            'action' => 'parametres.modifies',
        ]);
    }

    private function tokenFor(User $user): string
    {
        return JWT::encode([
            'sub' => $user->id,
            'role' => $user->role->slug(),
            'role_id' => $user->role_id,
            'service_id' => $user->service_id,
            'iat' => time(),
            'exp' => time() + 7200,
        ], config('jwt.secret'), 'HS256');
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

    public function test_login_attempts_are_rate_limited(): void
    {
        $credentials = [
            'email' => 'limited@example.com',
            'password' => 'wrongpassword',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', $credentials)->assertUnauthorized();
        }

        $this->postJson('/api/login', $credentials)->assertTooManyRequests();
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
