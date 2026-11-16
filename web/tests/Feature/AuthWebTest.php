<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AuthWebTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configurer les URLs pour les tests
        config(['services.auth.url' => 'http://auth-service:8000']);
        config(['services.patrimoine.url' => 'http://patrimoine-service:8000']);
        config(['services.mouvements.url' => 'http://mouvements-service:8000']);
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
        $response->assertSessionHas('user')
            ->assertSessionHas('success', 'Connexion réussie. Bienvenue dans votre espace.');
    }

    public function test_login_page_has_accessible_password_toggle_and_loading_state(): void
    {
        $this->get('/connexion')
            ->assertOk()
            ->assertSee('Afficher')
            ->assertSee('aria-label="Afficher le mot de passe"', false)
            ->assertSee('Connexion en cours');
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
            'http://mouvements-service:8000/api/stats' => Http::response(['data' => [
                'transferts_en_attente' => 0,
                'maintenances_en_cours' => 0,
                'operations_recentes' => [],
            ]], 200),
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
        $response->assertSee('Connexion réussie. Bienvenue dans votre espace.');
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
            'http://mouvements-service:8000/api/stats' => Http::response(['data' => ['transferts_en_attente' => 0]], 200),
        ]);
        $session = [
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Utilisateur test', 'role_slug' => 'administrateur-général'],
        ];

        $this->withSession($session)->get('/biens')
            ->assertOk()
            ->assertSee('GPE-TEST-001')
            ->assertSee('Ordinateur de test')
            ->assertSee('Enregistrer un bien')
            ->assertDontSee('Consultation seule');

        $this->withSession($session)->get('/biens/creer')
            ->assertOk()
            ->assertSee('Enregistrer un bien')
            ->assertSee('Matériel informatique')
            ->assertSee('Direction du Budget');
    }

    public function test_bien_detail_displays_its_private_justificatifs_section(): void
    {
        Http::fake([
            'http://patrimoine-service:8000/api/biens/1' => Http::response(['data' => [
                'id' => 1,
                'reference' => 'GPE-TEST-001',
                'designation' => 'Ordinateur de test',
                'description' => 'Ordinateur portable',
                'etat' => 'bon_etat',
                'statut' => 'disponible',
                'numero_serie' => null,
                'date_acquisition' => null,
                'valeur_acquisition' => null,
                'localisation' => null,
                'categorie' => ['nom' => 'Informatique'],
                'service' => ['nom' => 'Direction du Budget', 'ministere' => ['nom' => 'MEF']],
            ]], 200),
            'http://mouvements-service:8000/api/documents*' => Http::response([
                'data' => [[
                    'id' => 7,
                    'type_document' => 'facture_acquisition',
                    'titre' => 'Facture ordinateur',
                    'reference' => 'FAC-001',
                    'date_document' => '2026-01-15',
                    'nom_original' => 'facture.pdf',
                    'taille_octets' => 2048,
                ]],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 1,
            ], 200),
            'http://mouvements-service:8000/api/stats' => Http::response(['data' => ['transferts_en_attente' => 0]], 200),
        ]);

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Gestionnaire', 'role_slug' => 'administrateur-general'],
        ])->get('/biens/1')
            ->assertOk()
            ->assertSee('Pièces justificatives')
            ->assertSee('Facture ordinateur')
            ->assertSee('Ajouter le justificatif')
            ->assertSee('facture.pdf');
    }

    public function test_document_page_and_upload_use_the_authenticated_movements_api(): void
    {
        Http::fake(function ($request) {
            if ($request->method() === 'POST' && $request->url() === 'http://mouvements-service:8000/api/documents') {
                return Http::response(['data' => ['id' => 1]], 201);
            }

            if ($request->url() === 'http://mouvements-service:8000/api/maintenances/9') {
                return Http::response(['data' => ['id' => 9, 'bien_id' => 1]], 200);
            }

            if ($request->method() === 'GET' && str_starts_with($request->url(), 'http://mouvements-service:8000/api/documents?')) {
                return Http::response([
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                ], 200);
            }

            if ($request->url() === 'http://mouvements-service:8000/api/stats') {
                return Http::response(['data' => ['transferts_en_attente' => 0]], 200);
            }

            return Http::response([], 404);
        });
        $session = [
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 42, 'name' => 'Gestionnaire', 'role_slug' => 'gestionnaire-patrimoine'],
        ];

        $this->withSession($session)->get('/documents/maintenance/9')
            ->assertOk()
            ->assertSee('Maintenance #9')
            ->assertSee('Fichier (PDF');

        $this->withSession($session)->post('/documents/maintenance/9', [
            'type_document' => 'document_maintenance',
            'titre' => 'Rapport de maintenance',
            'reference' => 'RAP-09',
            'fichier' => UploadedFile::fake()->createWithContent('rapport.pdf', "%PDF-1.4\nrapport"),
        ])->assertRedirect('/documents/maintenance/9')
            ->assertSessionHas('success', 'Le document justificatif a été ajouté.');

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'http://mouvements-service:8000/api/documents');
    }

    public function test_document_download_is_streamed_through_the_authenticated_web_session(): void
    {
        Http::fake([
            'http://mouvements-service:8000/api/documents/bien/1/7/telecharger' => Http::response(
                "%PDF-1.4\njustificatif",
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="facture.pdf"',
                    'X-Document-Name' => 'facture.pdf',
                ],
            ),
            'http://mouvements-service:8000/api/stats' => Http::response(['data' => ['transferts_en_attente' => 0]], 200),
        ]);

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 7, 'name' => 'Auditeur', 'role_slug' => 'auditeur'],
        ])->get('/documents/bien/1/7/telecharger')
            ->assertOk()
            ->assertDownload('facture.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertStreamedContent("%PDF-1.4\njustificatif");
    }

    public function test_reference_management_page_renders(): void
    {
        Http::fake([
            'http://patrimoine-service:8000/api/categories*' => Http::response([
                'data' => [['id' => 1, 'nom' => 'Matériel informatique', 'type' => 'mobilier', 'biens_count' => 2]],
                'current_page' => 1,
                'last_page' => 1,
            ]),
            'http://mouvements-service:8000/api/stats' => Http::response(['data' => ['transferts_en_attente' => 0]], 200),
        ]);

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Utilisateur test'],
        ])->get('/categories')
            ->assertOk()
            ->assertSee('Catégories enregistrées')
            ->assertSee('Matériel informatique');
    }

    public function test_movement_pages_render_data_from_the_movements_service(): void
    {
        Http::fake([
            'http://mouvements-service:8000/api/stats' => Http::response(['data' => [
                'transferts_en_attente' => 1,
                'maintenances_en_cours' => 0,
                'operations_recentes' => [],
            ]], 200),
            'http://mouvements-service:8000/api/affectations*' => Http::response(['data' => []], 200),
            'http://mouvements-service:8000/api/transferts*' => Http::response(['data' => [[
                'id' => 1,
                'bien_id' => 10,
                'service_origine_id' => 2,
                'service_destination_id' => 3,
                'date_demande' => '2026-01-15',
                'motif' => 'Réorganisation',
                'statut' => 'en_attente',
            ]]], 200),
            'http://mouvements-service:8000/api/maintenances*' => Http::response(['data' => [[
                'id' => 1,
                'bien_id' => 10,
                'type' => 'preventive',
                'description' => 'Révision annuelle',
                'date_debut' => '2026-01-15',
                'cout' => '25000.00',
                'statut' => 'signalee',
            ]]], 200),
            'http://mouvements-service:8000/api/historique*' => Http::response(['data' => [[
                'id' => 1,
                'utilisateur_id' => 42,
                'bien_id' => 10,
                'action' => 'maintenance.creee',
                'ancienne_valeur' => null,
                'nouvelle_valeur' => ['statut' => 'signalee'],
                'created_at' => '2026-01-15T10:00:00.000000Z',
            ]]], 200),
            'http://patrimoine-service:8000/api/biens*' => Http::response(['data' => [[
                'id' => 10,
                'reference' => 'GPE-TEST-010',
                'designation' => 'Ordinateur de test',
            ]]], 200),
            'http://patrimoine-service:8000/api/services*' => Http::response(['data' => [
                ['id' => 2, 'nom' => 'Direction d’origine'],
                ['id' => 3, 'nom' => 'Direction destinataire'],
            ]], 200),
        ]);
        $session = [
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 42, 'name' => 'Utilisateur test', 'role_slug' => 'administrateur-general'],
        ];

        $this->withSession($session)->get('/affectations')
            ->assertOk()
            ->assertSee('Nouvelle affectation');
        $this->withSession($session)->get('/transferts')
            ->assertOk()
            ->assertSee('Réorganisation')
            ->assertSee('Approuver');
        $this->withSession($session)->get('/maintenances')
            ->assertOk()
            ->assertSee('Révision annuelle')
            ->assertSee('25 000');
        $this->withSession($session)->get('/historique')
            ->assertOk()
            ->assertSee('maintenance · creee');

        Http::assertSent(fn ($request) => $request->url() === 'http://mouvements-service:8000/api/stats');
    }

    public function test_auditeur_sees_reference_data_without_write_actions(): void
    {
        Http::fake([
            'http://patrimoine-service:8000/api/categories*' => Http::response([
                'data' => [['id' => 1, 'nom' => 'Matériel informatique', 'type' => 'mobilier', 'biens_count' => 2]],
                'current_page' => 1,
                'last_page' => 1,
            ]),
            'http://mouvements-service:8000/api/stats' => Http::response(['data' => ['transferts_en_attente' => 0]], 200),
        ]);

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 7, 'name' => 'Auditeur', 'role_slug' => 'auditeur'],
        ])->get('/categories')
            ->assertOk()
            ->assertSee('Consultation seule')
            ->assertSee('Matériel informatique')
            ->assertDontSee(route('categories.create'))
            ->assertDontSee('Supprimer');
    }

    public function test_dashboard_redirects_to_login_without_jwt()
    {
        $response = $this->get('/tableau-de-bord');

        $response->assertRedirect('/connexion');
    }

    public function test_user_administration_is_available_to_admin_only_and_uses_auth_service(): void
    {
        Http::fake([
            'http://auth-service:8000/api/admin/users*' => Http::response([
                'data' => [[
                    'id' => 3,
                    'name' => 'Agent du patrimoine',
                    'email' => 'agent@example.com',
                    'role_id' => 4,
                    'service_id' => 12,
                    'actif' => true,
                    'role' => ['nom' => 'Agent gestionnaire'],
                ]],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 1,
            ]),
            'http://auth-service:8000/api/admin/roles' => Http::response(['data' => [
                ['id' => 1, 'nom' => 'Administrateur général'],
                ['id' => 4, 'nom' => 'Agent gestionnaire'],
            ]]),
            'http://mouvements-service:8000/api/stats' => Http::response(['data' => ['transferts_en_attente' => 0]]),
        ]);

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Administrateur', 'role_slug' => 'administrateur-general'],
        ])->get('/administration/utilisateurs')
            ->assertOk()
            ->assertSee('Agent du patrimoine')
            ->assertSee('agent@example.com')
            ->assertSee('Créer un compte');

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 2, 'name' => 'Agent', 'role_slug' => 'agent-gestionnaire'],
        ])->get('/administration/utilisateurs')
            ->assertForbidden();
    }

    public function test_platform_settings_are_admin_only_persisted_and_used_by_the_layout(): void
    {
        Http::fake(function ($request) {
            if ($request->url() === 'http://auth-service:8000/api/admin/settings') {
                return Http::response([
                    'data' => [
                        'nom_plateforme' => $request->method() === 'PUT' ? 'GPE National' : 'GPE',
                        'nom_institution' => 'République togolaise',
                        'contact_support' => $request->method() === 'PUT' ? 'support@example.com' : null,
                    ],
                ]);
            }

            if ($request->url() === 'http://mouvements-service:8000/api/stats') {
                return Http::response(['data' => ['transferts_en_attente' => 0]]);
            }

            return Http::response([], 404);
        });
        $adminSession = [
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Administrateur', 'role_slug' => 'administrateur-general'],
        ];

        $this->withSession($adminSession)->get('/administration/parametres')
            ->assertOk()
            ->assertSee('Paramètres de la plateforme')
            ->assertSee('GPE')
            ->assertSee('République togolaise');

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 2, 'name' => 'Agent', 'role_slug' => 'agent-gestionnaire'],
        ])->get('/administration/parametres')->assertForbidden();

        $this->withSession($adminSession)->put('/administration/parametres', [
            'nom_plateforme' => 'GPE National',
            'nom_institution' => 'République togolaise',
            'contact_support' => 'support@example.com',
        ])->assertRedirect('/administration/parametres')
            ->assertSessionHas('success')
            ->assertSessionHas('platform_settings.nom_plateforme', 'GPE National');
        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && $request->url() === 'http://auth-service:8000/api/admin/settings'
            && $request['nom_plateforme'] === 'GPE National');
    }

    public function test_acquisition_pages_use_live_patrimoine_references_and_hide_write_controls_for_auditeurs(): void
    {
        Http::fake([
            'http://patrimoine-service:8000/api/acquisitions*' => Http::response([
                'data' => [[
                    'id' => 1,
                    'reference' => 'GPE-ACQ-001',
                    'designation' => 'Véhicule administratif',
                    'fournisseur' => 'Fournisseur local',
                    'cout' => '14500000.00',
                    'date_acquisition' => '2026-11-01',
                    'statut' => 'en_attente',
                ]],
                'current_page' => 1,
                'last_page' => 1,
            ]),
            'http://patrimoine-service:8000/api/categories*' => Http::response(['data' => [
                ['id' => 1, 'nom' => 'Véhicules'],
            ]]),
            'http://patrimoine-service:8000/api/services*' => Http::response(['data' => [
                ['id' => 1, 'nom' => 'Garage central', 'ministere' => ['nom' => 'Ministère test']],
            ]]),
            'http://mouvements-service:8000/api/stats' => Http::response(['data' => ['transferts_en_attente' => 0]]),
        ]);

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Gestionnaire', 'role_slug' => 'administrateur-general'],
        ])->get('/acquisitions')
            ->assertOk()
            ->assertSee('GPE-ACQ-001')
            ->assertSee('14 500 000')
            ->assertSee('Confirmer la réception')
            ->assertSee('Nouvelle acquisition');

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Gestionnaire', 'role_slug' => 'administrateur-general'],
        ])->get('/acquisitions/creer')
            ->assertOk()
            ->assertSee('Fournisseur / donateur')
            ->assertSee('Référence de facture')
            ->assertSee('Véhicules')
            ->assertSee('Garage central');

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 7, 'name' => 'Auditeur', 'role_slug' => 'auditeur'],
        ])->get('/acquisitions')
            ->assertOk()
            ->assertDontSee('Nouvelle acquisition')
            ->assertDontSee('Confirmer la réception');
    }

    public function test_inventory_pages_show_campaign_findings_and_search_scoped_assets(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, '/api/stats')) {
                return Http::response(['data' => ['transferts_en_attente' => 0]]);
            }
            if (str_contains($url, '/api/inventaires/5')) {
                return Http::response([
                    'data' => [
                        'id' => 5,
                        'titre' => 'Inventaire du service',
                        'service_id' => 2,
                        'statut' => 'en_cours',
                        'lignes' => [[
                            'id' => 10,
                            'bien_id' => 1,
                            'constat' => 'present',
                            'localisation_physique' => 'Bureau 12',
                            'observation' => null,
                            'controle_le' => '2026-11-16T10:00:00Z',
                        ]],
                    ],
                    'rapport' => [
                        'biens_controles' => 1,
                        'biens_theoriques' => 2,
                        'non_controles' => 1,
                        'ecarts' => 1,
                        'presents' => 1,
                        'absents' => 0,
                        'deteriores' => 0,
                        'biens_non_controles' => [[
                            'id' => 2,
                            'reference' => 'GPE-TEST-002',
                            'designation' => 'Imprimante non vérifiée',
                        ]],
                        'pagination_non_controles' => [
                            'current_page' => 1,
                            'last_page' => 1,
                            'total' => 1,
                        ],
                    ],
                ]);
            }
            if (str_contains($url, '/api/biens')) {
                return Http::response([
                    'data' => [[
                        'id' => 1,
                        'reference' => 'GPE-TEST-001',
                        'designation' => 'Ordinateur de test',
                    ]],
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 1,
                ]);
            }
            if (str_contains($url, '/api/services')) {
                return Http::response(['data' => [['id' => 2, 'nom' => 'Service de test']]]);
            }

            return Http::response([]);
        });

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Gestionnaire', 'role_slug' => 'gestionnaire-patrimoine'],
        ])->get('/inventaires/5?bien_q=GPE-TEST-001')
            ->assertOk()
            ->assertSee('Inventaire du service')
            ->assertSee('GPE-TEST-001')
            ->assertSee('Biens non contrôlés (1)')
            ->assertSee('GPE-TEST-002')
            ->assertSee('Bureau 12')
            ->assertSee('Rechercher')
            ->assertSee('Modifier');

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Gestionnaire', 'role_slug' => 'gestionnaire-patrimoine'],
        ])->patch('/inventaires/5/lignes/10', [
            'constat' => 'deteriore',
            'localisation_physique' => 'Bureau 14',
            'observation' => 'À réparer',
        ])->assertRedirect('/inventaires/5')
            ->assertSessionHas('success', 'Le constat physique a été mis à jour.');
        Http::assertSent(fn ($request) => $request->method() === 'PATCH'
            && $request->url() === 'http://mouvements-service:8000/api/inventaires/5/lignes/10'
            && $request['constat'] === 'deteriore');

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 7, 'name' => 'Auditeur', 'role_slug' => 'auditeur'],
        ])->get('/inventaires')
            ->assertOk()
            ->assertDontSee('Nouvelle campagne');
    }

    public function test_notifications_are_displayed_and_marked_read_through_movements_service(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/api/stats')) {
                return Http::response(['data' => ['transferts_en_attente' => 0]]);
            }
            if ($request->method() === 'PATCH' && str_contains($request->url(), '/api/notifications/11/lu')) {
                return Http::response(['data' => ['id' => 11, 'lue_le' => '2026-11-16T10:00:00Z']]);
            }
            if (str_contains($request->url(), '/api/notifications')) {
                return Http::response([
                    'data' => [[
                        'id' => 11,
                        'type' => 'transfert.approuvé',
                        'titre' => 'Mise à jour de votre transfert',
                        'message' => 'Le transfert #4 est maintenant « approuvé ».',
                        'lue_le' => null,
                        'created_at' => '2026-11-16T09:00:00Z',
                    ]],
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 1,
                ]);
            }

            return Http::response([]);
        });

        $session = [
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 42, 'name' => 'Gestionnaire', 'role_slug' => 'gestionnaire-patrimoine'],
        ];
        $this->withSession($session)->get('/notifications')
            ->assertOk()
            ->assertSee('Mise à jour de votre transfert')
            ->assertSee('Le transfert #4 est maintenant');
        $this->withSession($session)->patch('/notifications/11/lue')
            ->assertRedirect()
            ->assertSessionHas('success', 'La notification a été marquée comme lue.');
        Http::assertSent(fn ($request) => $request->method() === 'PATCH'
            && $request->url() === 'http://mouvements-service:8000/api/notifications/11/lu');
    }

    public function test_audit_page_reads_local_journals_and_restricts_admin_sources(): void
    {
        Http::fake([
            'http://mouvements-service:8000/api/stats' => Http::response(['data' => ['transferts_en_attente' => 0]]),
            'http://patrimoine-service:8000/api/audit*' => Http::response(['data' => [[
                'id' => 2,
                'utilisateur_id' => 42,
                'bien_id' => 10,
                'action' => 'acquisition.statut_modifie',
                'ancienne_valeur' => ['statut' => 'receptionnee'],
                'nouvelle_valeur' => ['statut' => 'validee'],
                'created_at' => '2026-11-16T10:00:00Z',
            ]], 'current_page' => 1, 'last_page' => 1]),
            'http://auth-service:8000/api/audit*' => Http::response(['data' => [[
                'id' => 1,
                'acteur_id' => 1,
                'sujet_id' => 42,
                'action' => 'utilisateur.cree',
                'ancienne_valeur' => null,
                'nouvelle_valeur' => ['name' => 'Agent'],
                'created_at' => '2026-11-16T09:00:00Z',
            ]], 'current_page' => 1, 'last_page' => 1]),
        ]);
        $session = [
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Auditeur', 'role_slug' => 'auditeur'],
        ];

        $this->withSession($session)->get('/historique?source=patrimoine')
            ->assertOk()
            ->assertSee('acquisition · statut_modifie')
            ->assertSee('Patrimoine');
        $this->withSession($session)->get('/historique?source=auth')
            ->assertOk()
            ->assertSee('utilisateur · cree')
            ->assertSee('Administration des comptes');
        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 2, 'name' => 'Agent', 'role_slug' => 'agent-gestionnaire'],
        ])->get('/historique?source=auth')->assertForbidden();
    }

    public function test_live_reports_can_be_downloaded_as_pdf_and_xlsx(): void
    {
        Http::fake([
            'http://patrimoine-service:8000/api/stats' => Http::response(['data' => [
                'biens' => 4,
                'valeur_totale' => 250000,
                'categories' => 2,
                'ministeres' => 1,
                'services' => 2,
                'acquisitions_en_cours' => 3,
                'sorties_en_cours' => 1,
                'biens_par_categorie' => [['nom' => 'Informatique', 'biens_count' => 4]],
                'biens_par_ministere' => [['nom' => 'Ministère test', 'biens_count' => 4]],
            ]]),
            'http://mouvements-service:8000/api/stats' => Http::response(['data' => [
                'transferts_en_attente' => 1,
                'maintenances_en_cours' => 2,
                'inventaires_en_cours' => 2,
                'cout_total_maintenances' => 35000,
            ]]),
        ]);
        $session = [
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Gestionnaire', 'role_slug' => 'gestionnaire-patrimoine'],
        ];

        $this->withSession($session)->get('/rapports')
            ->assertOk()
            ->assertSee('250 000 FCFA')
            ->assertSee('Informatique')
            ->assertSee('Acquisitions en cours')
            ->assertSee('Télécharger PDF')
            ->assertSee('Télécharger Excel');
        $this->withSession($session)->get('/rapports/export/pdf')
            ->assertOk()
            ->assertDownload('rapport-patrimoine.pdf');

        $excel = $this->withSession($session)->get('/rapports/export/excel');
        $excel->assertOk()->assertDownload('rapport-patrimoine.xlsx');
        $this->assertStringStartsWith('PK', $excel->streamedContent());
    }

    public function test_global_search_returns_real_records_and_hides_users_from_auditors(): void
    {
        $adminUserSearches = 0;
        Http::fake(function ($request) use (&$adminUserSearches) {
            $url = $request->url();

            if (str_contains($url, '/api/stats')) {
                return Http::response(['data' => ['transferts_en_attente' => 0]]);
            }
            if (str_contains($url, '/api/biens')) {
                return Http::response(['data' => [[
                    'id' => 1,
                    'reference' => 'GPE-SEARCH-001',
                    'designation' => 'Ordinateur recherché',
                ]]]);
            }
            if (str_contains($url, '/api/categories')) {
                return Http::response(['data' => [['id' => 2, 'nom' => 'Informatique']]]);
            }
            if (str_contains($url, '/api/ministeres')) {
                return Http::response(['data' => [['id' => 3, 'nom' => 'Ministère test']]]);
            }
            if (str_contains($url, '/api/services')) {
                return Http::response(['data' => [['id' => 4, 'nom' => 'Service test']]]);
            }
            if (str_contains($url, '/api/affectations')) {
                return Http::response(['data' => [['id' => 5, 'bien_id' => 1, 'statut' => 'active']]]);
            }
            if (str_contains($url, '/api/transferts')) {
                return Http::response(['data' => [['id' => 6, 'bien_id' => 1, 'statut' => 'en_attente']]]);
            }
            if (str_contains($url, '/api/maintenances')) {
                return Http::response(['data' => [['id' => 7, 'bien_id' => 1, 'statut' => 'en_cours']]]);
            }
            if (str_contains($url, '/api/documents')) {
                return Http::response(['data' => [[
                    'id' => 8,
                    'titre' => 'Facture ordinateur',
                    'type_document' => 'facture_acquisition',
                    'cible_type' => 'bien',
                    'cible_id' => 1,
                ]]]);
            }
            if (str_contains($url, '/api/historique')) {
                return Http::response(['data' => [[
                    'id' => 9,
                    'bien_id' => 1,
                    'action' => 'maintenance.creee',
                ]]]);
            }
            if (str_contains($url, '/api/admin/users')) {
                $adminUserSearches++;
                return Http::response(['data' => [[
                    'id' => 10,
                    'name' => 'Agent recherché',
                    'email' => 'agent@example.com',
                ]]]);
            }

            return Http::response(['data' => []]);
        });
        $session = [
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 1, 'name' => 'Administrateur', 'role_slug' => 'administrateur-general'],
        ];

        $this->withSession($session)->get('/recherche?q=ordinateur')
            ->assertOk()
            ->assertSee('GPE-SEARCH-001')
            ->assertSee('Informatique')
            ->assertSee('Facture ordinateur')
            ->assertSee('Agent recherché');
        $this->assertSame(1, $adminUserSearches);

        $this->withSession([
            'jwt_token' => 'token-de-test',
            'user' => ['id' => 2, 'name' => 'Auditeur', 'role_slug' => 'auditeur'],
        ])->get('/recherche?q=ordinateur')
            ->assertOk()
            ->assertDontSee('Agent recherché');
        $this->assertSame(1, $adminUserSearches);
    }
}
