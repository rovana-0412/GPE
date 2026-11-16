<?php

namespace Tests\Feature;

use App\Models\HistoriqueOperation;
use App\Models\Maintenance;
use App\Models\DocumentJustificatif;
use App\Models\NotificationMetier;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MouvementsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'jwt.secret' => str_repeat('t', 32),
            'services.patrimoine.url' => 'http://patrimoine-service:8000',
        ]);
    }

    public function test_all_movement_routes_reject_requests_without_a_token(): void
    {
        foreach ([
            '/api/affectations',
            '/api/transferts',
            '/api/maintenances',
            '/api/inventaires',
            '/api/documents?cible_type=bien&cible_id=1',
            '/api/historique',
            '/api/notifications',
            '/api/stats',
        ] as $uri) {
            $this->getJson($uri)->assertUnauthorized();
        }

        $this->postJson('/api/transferts', [])->assertUnauthorized();
        $this->postJson('/api/notifications', [])->assertUnauthorized();
        $this->withToken('jeton-invalide')->getJson('/api/historique')->assertUnauthorized();
    }

    public function test_affectations_check_the_asset_and_allow_only_one_active_assignment(): void
    {
        $this->fakePatrimoine();
        $token = $this->token();

        $this->withToken($token)->postJson('/api/affectations', [
            'bien_id' => 1,
            'service_id' => 20,
            'date_debut' => '2026-01-15',
        ])->assertCreated();

        $this->withToken($token)->postJson('/api/affectations', [
            'bien_id' => 1,
            'service_id' => 20,
            'date_debut' => '2026-01-16',
        ])->assertConflict();

        $this->assertDatabaseCount('affectations', 1);
        $this->assertDatabaseCount('historique_operations', 1);
    }

    public function test_an_operation_is_not_saved_when_the_asset_does_not_exist(): void
    {
        Http::fake(fn (ClientRequest $request) => Http::response([], $request->method() === 'GET' ? 404 : 500));

        $this->withToken($this->token())->postJson('/api/maintenances', [
            'bien_id' => 404,
            'type' => 'preventive',
            'description' => 'Révision annuelle',
            'date_debut' => '2026-01-15',
            'cout' => 25000,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('bien_id');

        $this->assertDatabaseCount('maintenances', 0);
        $this->assertDatabaseCount('historique_operations', 0);
    }

    public function test_maintenance_creation_and_update_write_auditable_values(): void
    {
        $this->fakePatrimoine();
        $token = $this->token();

        $maintenanceId = $this->withToken($token)->postJson('/api/maintenances', [
            'bien_id' => 1,
            'type' => 'preventive',
            'description' => 'Révision annuelle',
            'date_debut' => '2026-01-15',
            'cout' => 25000,
        ])->assertCreated()
            ->json('data.id');

        $this->withToken($token)->patchJson('/api/maintenances/'.$maintenanceId, [
            'statut' => 'terminee',
        ])->assertOk()
            ->assertJsonPath('data.statut', 'terminee');

        $this->assertDatabaseCount('historique_operations', 2);
        $this->assertDatabaseHas('historique_operations', [
            'action' => 'maintenance.modifiee',
            'utilisateur_id' => 42,
            'bien_id' => 1,
        ]);
        $this->assertDatabaseCount('notifications_metier', 0);
        $this->assertInstanceOf(Maintenance::class, Maintenance::find($maintenanceId));
        $this->assertNotNull(HistoriqueOperation::firstOrFail()->nouvelle_valeur);
        $this->withToken($token)->getJson('/api/historique')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'maintenance.modifiee');
        $this->withToken($token)->getJson('/api/stats')
            ->assertOk()
            ->assertJsonPath('data.maintenances_en_cours', 0)
            ->assertJsonPath('data.inventaires_en_cours', 0);
    }

    public function test_documents_are_private_downloadable_and_target_scoped(): void
    {
        Storage::fake('local');
        $this->fakePatrimoine();
        $token = $this->token();
        $fakeUpload = UploadedFile::fake()->createWithContent('facture.pdf', "%PDF-1.4\njustificatif");
        $upload = new UploadedFile(
            $fakeUpload->getRealPath(),
            'C:\\private\\..\\facture.pdf',
            'application/pdf',
            null,
            true,
        );

        $documentId = $this->withToken($token)
            ->post('/api/documents', [
                'cible_type' => 'bien',
                'cible_id' => 1,
                'type_document' => 'facture_acquisition',
                'titre' => 'Facture ordinateur',
                'reference' => 'FAC-2026-001',
                'date_document' => '2026-01-15',
                'fichier' => $upload,
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonMissingPath('data.chemin')
            ->assertJsonPath('data.nom_original', 'facture.pdf')
            ->json('data.id');

        $document = DocumentJustificatif::findOrFail($documentId);
        Storage::disk('local')->assertExists($document->chemin);
        $this->assertDatabaseHas('historique_operations', [
            'action' => 'document.justificatif.ajoute',
            'utilisateur_id' => 42,
            'bien_id' => 1,
        ]);

        $this->withToken($token)
            ->getJson('/api/documents?cible_type=bien&cible_id=1')
            ->assertOk()
            ->assertJsonPath('data.0.titre', 'Facture ordinateur')
            ->assertJsonMissingPath('data.0.chemin');

        $this->withToken($token)
            ->get('/api/documents/transfert/1/'.$documentId.'/telecharger')
            ->assertNotFound();

        $this->withToken($token)
            ->deleteJson('/api/documents/transfert/1/'.$documentId)
            ->assertNotFound();
        Storage::disk('local')->assertExists($document->chemin);
        $this->assertDatabaseHas('documents_justificatifs', ['id' => $documentId]);

        $this->withToken($this->token('auditeur'))
            ->deleteJson('/api/documents/bien/1/'.$documentId)
            ->assertForbidden();
        Storage::disk('local')->assertExists($document->chemin);

        $this->withToken($this->token('auditeur'))->post('/api/documents', [
            'cible_type' => 'bien',
            'cible_id' => 1,
            'type_document' => 'autre',
            'titre' => 'Accès interdit',
            'fichier' => UploadedFile::fake()->createWithContent('autre.pdf', "%PDF-1.4\ncontenu"),
        ], ['Accept' => 'application/json'])->assertForbidden();

        $this->withToken($token)
            ->get('/api/documents/bien/1/'.$documentId.'/telecharger')
            ->assertOk()
            ->assertHeader('X-Document-Name', 'facture.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Type', 'application/pdf');

        $this->withToken($token)
            ->deleteJson('/api/documents/bien/1/'.$documentId)
            ->assertNoContent();
        Storage::disk('local')->assertMissing($document->chemin);
        $this->assertDatabaseMissing('documents_justificatifs', ['id' => $documentId]);
        $this->assertDatabaseHas('historique_operations', [
            'action' => 'document.justificatif.supprime',
            'utilisateur_id' => 42,
            'bien_id' => 1,
        ]);
    }

    public function test_document_upload_rejects_a_missing_asset(): void
    {
        Storage::fake('local');
        Http::fake(fn (ClientRequest $request) => Http::response([], 404));
        $token = $this->token();

        $this->withToken($token)->post('/api/documents', [
            'cible_type' => 'bien',
            'cible_id' => 404,
            'type_document' => 'autre',
            'titre' => 'Fichier invalide',
            'fichier' => UploadedFile::fake()->createWithContent('facture.pdf', "%PDF-1.4\njustificatif"),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cible_id');

        $this->assertDatabaseCount('documents_justificatifs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_document_upload_rejects_an_unapproved_mime_type(): void
    {
        Storage::fake('local');
        $this->fakePatrimoine();

        $this->withToken($this->token())->post('/api/documents', [
            'cible_type' => 'bien',
            'cible_id' => 1,
            'type_document' => 'autre',
            'titre' => 'Fichier invalide',
            'fichier' => UploadedFile::fake()->create('script.php', 1, 'application/x-php'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fichier');

        $this->assertDatabaseCount('documents_justificatifs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_document_upload_enforces_the_ten_megabyte_limit(): void
    {
        Storage::fake('local');
        $this->fakePatrimoine();

        $this->withToken($this->token())->post('/api/documents', [
            'cible_type' => 'bien',
            'cible_id' => 1,
            'type_document' => 'autre',
            'titre' => 'Fichier volumineux',
            'fichier' => UploadedFile::fake()->create('volume.pdf', 10241, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fichier');

        $this->assertDatabaseCount('documents_justificatifs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_documents_can_be_associated_with_existing_movement_operations(): void
    {
        Storage::fake('local');
        $this->fakePatrimoine();
        $maintenance = Maintenance::create([
            'bien_id' => 1,
            'utilisateur_id' => 42,
            'type' => 'preventive',
            'description' => 'Entretien annuel',
            'date_debut' => '2026-01-15',
            'statut' => 'planifiee',
        ]);
        $documentId = $this->withToken($this->token())
            ->post('/api/documents', [
                'cible_type' => 'maintenance',
                'cible_id' => $maintenance->id,
                'type_document' => 'document_maintenance',
                'titre' => 'Rapport d’intervention',
                'fichier' => UploadedFile::fake()->createWithContent('rapport.pdf', "%PDF-1.4\nrapport"),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('documents_justificatifs', [
            'id' => $documentId,
            'cible_type' => 'maintenance',
            'cible_id' => $maintenance->id,
            'utilisateur_id' => 42,
        ]);
        $this->assertDatabaseHas('historique_operations', [
            'action' => 'document.justificatif.ajoute',
            'bien_id' => 1,
            'nouvelle_valeur->cible_type' => 'maintenance',
        ]);
    }

    public function test_documents_can_be_associated_with_a_pending_acquisition_without_cross_service_foreign_keys(): void
    {
        Storage::fake('local');
        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/api/acquisitions/55')) {
                return Http::response([
                    'data' => ['id' => 55, 'bien_id' => null, 'statut' => 'brouillon'],
                ], 200);
            }

            return Http::response([], 404);
        });

        $documentId = $this->withToken($this->token())
            ->post('/api/documents', [
                'cible_type' => 'acquisition',
                'cible_id' => 55,
                'type_document' => 'facture_acquisition',
                'titre' => 'Facture de l’acquisition',
                'fichier' => UploadedFile::fake()->createWithContent('facture.pdf', "%PDF-1.4\nfacture"),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('documents_justificatifs', [
            'id' => $documentId,
            'cible_type' => 'acquisition',
            'cible_id' => 55,
        ]);
        $this->assertDatabaseHas('historique_operations', [
            'action' => 'document.justificatif.ajoute',
            'bien_id' => null,
            'nouvelle_valeur->cible_type' => 'acquisition',
        ]);
        Http::assertSent(fn (ClientRequest $request) => str_ends_with($request->url(), '/api/acquisitions/55'));
    }

    public function test_documents_can_be_searched_by_metadata_without_a_target_filter(): void
    {
        DocumentJustificatif::create([
            'cible_type' => 'acquisition',
            'cible_id' => 55,
            'type_document' => 'facture_acquisition',
            'titre' => 'Facture ordinateur portable',
            'reference' => 'FAC-2026-001',
            'description' => 'Acquisition de matériel informatique.',
            'date_document' => '2026-11-16',
            'nom_original' => 'facture.pdf',
            'chemin' => 'documents/document-test.pdf',
            'mime_type' => 'application/pdf',
            'taille_octets' => 100,
            'utilisateur_id' => 42,
        ]);

        $this->withToken($this->token())
            ->getJson('/api/documents?q=ordinateur')
            ->assertOk()
            ->assertJsonPath('data.0.titre', 'Facture ordinateur portable')
            ->assertJsonPath('data.0.cible_type', 'acquisition');
    }

    public function test_document_metadata_and_download_are_scoped_to_the_owner_for_service_roles(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/private.pdf', "%PDF-1.4\nprivate");
        $document = DocumentJustificatif::create([
            'cible_type' => 'bien',
            'cible_id' => 1,
            'type_document' => 'autre',
            'titre' => 'Document privé',
            'nom_original' => 'private.pdf',
            'chemin' => 'documents/private.pdf',
            'mime_type' => 'application/pdf',
            'taille_octets' => 20,
            'utilisateur_id' => 42,
        ]);
        $otherUser = $this->tokenForUser(77, 'agent-gestionnaire');
        $owner = $this->tokenForUser(42, 'agent-gestionnaire');

        $this->withToken($otherUser)->getJson('/api/documents?cible_type=bien&cible_id=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->withToken($otherUser)->get('/api/documents/bien/1/'.$document->id.'/telecharger')
            ->assertNotFound();
        $this->withToken($owner)->getJson('/api/documents?cible_type=bien&cible_id=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->withToken($owner)->get('/api/documents/bien/1/'.$document->id.'/telecharger')
            ->assertOk()
            ->assertStreamedContent("%PDF-1.4\nprivate");
    }

    public function test_document_access_respects_service_and_ministerial_perimeters(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/scoped.pdf', "%PDF-1.4\nscoped");
        $document = DocumentJustificatif::create([
            'cible_type' => 'bien',
            'cible_id' => 1,
            'type_document' => 'autre',
            'titre' => 'Justificatif du service 10',
            'nom_original' => 'scoped.pdf',
            'chemin' => 'documents/scoped.pdf',
            'mime_type' => 'application/pdf',
            'taille_octets' => 18,
            'utilisateur_id' => 42,
        ]);
        $document->perimetres()->create([
            'service_id' => 10,
            'ministere_id' => 100,
        ]);
        $sameService = $this->tokenForUser(77, 'agent-gestionnaire', 10);
        $otherService = $this->tokenForUser(88, 'agent-gestionnaire', 20);
        $sameMinistry = $this->tokenForUser(99, 'responsable-ministeriel', 30);
        $otherMinistry = $this->tokenForUser(100, 'responsable-ministeriel', 20);

        $this->withToken($sameService)->getJson('/api/documents?cible_type=bien&cible_id=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->withToken($sameService)->get('/api/documents/bien/1/'.$document->id.'/telecharger')
            ->assertOk();
        $this->withToken($otherService)->getJson('/api/documents?cible_type=bien&cible_id=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->withToken($otherService)->get('/api/documents/bien/1/'.$document->id.'/telecharger')
            ->assertNotFound();

        Http::fake(function (ClientRequest $request) {
            if (str_ends_with($request->url(), '/api/services/30')) {
                return Http::response(['data' => ['id' => 30, 'ministere_id' => 100]], 200);
            }

            if (str_ends_with($request->url(), '/api/services/20')) {
                return Http::response(['data' => ['id' => 20, 'ministere_id' => 200]], 200);
            }

            return Http::response([], 404);
        });

        $this->withToken($sameMinistry)->getJson('/api/documents?cible_type=bien&cible_id=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->withToken($sameMinistry)->get('/api/documents/bien/1/'.$document->id.'/telecharger')
            ->assertOk();
        $this->withToken($otherMinistry)->getJson('/api/documents?cible_type=bien&cible_id=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->withToken($otherMinistry)->get('/api/documents/bien/1/'.$document->id.'/telecharger')
            ->assertNotFound();
        $this->withToken($this->tokenForUser(42, 'agent-gestionnaire', 20))
            ->get('/api/documents/bien/1/'.$document->id.'/telecharger')
            ->assertOk();
    }

    public function test_legacy_document_perimeters_can_be_backfilled_idempotently(): void
    {
        $this->fakePatrimoine();
        $document = DocumentJustificatif::create([
            'cible_type' => 'bien',
            'cible_id' => 1,
            'type_document' => 'autre',
            'titre' => 'Justificatif antérieur',
            'nom_original' => 'legacy.pdf',
            'chemin' => 'documents/legacy.pdf',
            'mime_type' => 'application/pdf',
            'taille_octets' => 12,
            'utilisateur_id' => 42,
        ]);

        $this->artisan('documents:backfill-perimetres')->assertSuccessful();
        $this->assertDatabaseHas('document_justificatif_perimetres', [
            'document_id' => $document->id,
            'service_id' => 10,
            'ministere_id' => 100,
        ]);
        $this->artisan('documents:backfill-perimetres')->assertSuccessful();
        $this->assertDatabaseCount('document_justificatif_perimetres', 1);
    }

    public function test_transfer_transition_must_follow_the_allowed_sequence(): void
    {
        $this->fakePatrimoine();
        $token = $this->token();
        $transfertId = $this->createTransfer($token);

        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'approuvé',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('transferts', [
            'id' => $transfertId,
            'statut' => 'brouillon',
        ]);
        $this->assertDatabaseCount('historique_operations', 1);
    }

    public function test_transfer_decision_notifies_requester_and_notifications_are_private(): void
    {
        $this->fakePatrimoine();
        $requester = $this->token();
        $reviewer = $this->tokenForUser(77);
        $transferId = $this->createTransfer($requester);

        $this->withToken($reviewer)->patchJson('/api/transferts/'.$transferId.'/statut', [
            'statut' => 'en_attente',
        ])->assertOk();
        $this->withToken($reviewer)->patchJson('/api/transferts/'.$transferId.'/statut', [
            'statut' => 'approuvé',
        ])->assertOk();

        $notification = NotificationMetier::firstOrFail();
        $this->assertSame(42, $notification->utilisateur_id);
        $this->assertSame('transfert.approuvé', $notification->type);
        $this->assertSame($transferId, $notification->donnees['transfert_id']);

        $this->withToken($requester)->getJson('/api/notifications?non_lues=1')
            ->assertOk()
            ->assertJsonPath('data.0.titre', 'Mise à jour de votre transfert');
        $this->withToken($reviewer)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('total', 0);
        $this->withToken($reviewer)->patchJson('/api/notifications/'.$notification->id.'/lu')
            ->assertNotFound();
        $this->withToken($requester)->patchJson('/api/notifications/'.$notification->id.'/lu')
            ->assertOk()
            ->assertJsonPath('data.utilisateur_id', 42);
        $this->assertNotNull($notification->fresh()->lue_le);
    }

    public function test_cross_service_notifications_can_only_be_created_by_managers(): void
    {
        $payload = [
            'utilisateur_id' => 77,
            'type' => 'acquisition.validee',
            'titre' => 'Acquisition validée',
            'message' => 'Votre acquisition est validée.',
            'donnees' => ['acquisition_id' => 9],
        ];

        $this->withToken($this->token('agent-gestionnaire'))->postJson('/api/notifications', $payload)
            ->assertForbidden();
        $this->withToken($this->token())->postJson('/api/notifications', $payload)
            ->assertCreated()
            ->assertJsonPath('data.utilisateur_id', 77)
            ->assertJsonPath('data.donnees.acquisition_id', 9);
        $this->withToken($this->tokenForUser(77, 'agent-gestionnaire'))->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'acquisition.validee');
        $this->withToken($this->token())->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_inventory_campaign_records_physical_findings_and_locks_after_validation(): void
    {
        $this->fakePatrimoine();
        $token = $this->token();
        $inventoryId = $this->withToken($token)->postJson('/api/inventaires', [
            'titre' => 'Inventaire du service',
            'service_id' => 10,
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'brouillon')
            ->json('data.id');

        $this->withToken($token)->patchJson('/api/inventaires/'.$inventoryId.'/statut', [
            'statut' => 'a_valider',
        ])->assertUnprocessable();
        $this->withToken($token)->patchJson('/api/inventaires/'.$inventoryId.'/statut', [
            'statut' => 'en_cours',
        ])->assertOk();

        $lineId = $this->withToken($token)->postJson('/api/inventaires/'.$inventoryId.'/lignes', [
            'bien_id' => 1,
            'constat' => 'deteriore',
            'localisation_physique' => 'Bureau 12',
            'observation' => 'Écran fissuré.',
        ])->assertCreated()
            ->assertJsonPath('data.constat', 'deteriore')
            ->json('data.id');

        $this->withToken($token)->postJson('/api/inventaires/'.$inventoryId.'/lignes', [
            'bien_id' => 1,
            'constat' => 'present',
        ])->assertConflict();

        $this->withToken($token)->patchJson('/api/inventaires/'.$inventoryId.'/lignes/'.$lineId, [
            'constat' => 'present',
            'observation' => 'Vérifié et opérationnel.',
        ])->assertOk();
        $this->withToken($token)->patchJson('/api/inventaires/'.$inventoryId.'/statut', [
            'statut' => 'a_valider',
        ])->assertOk()
            ->assertJsonPath('rapport.biens_controles', 1)
            ->assertJsonPath('rapport.presents', 1);
        $this->withToken($token)->patchJson('/api/inventaires/'.$inventoryId.'/lignes/'.$lineId, [
            'constat' => 'absent',
        ])->assertConflict();
        $this->withToken($token)->patchJson('/api/inventaires/'.$inventoryId.'/statut', [
            'statut' => 'valide',
        ])->assertOk()
            ->assertJsonPath('data.statut', 'valide');

        $this->assertDatabaseHas('inventaire_lignes', [
            'id' => $lineId,
            'bien_id' => 1,
            'constat' => 'present',
        ]);
        $this->assertDatabaseHas('historique_operations', [
            'action' => 'inventaire.statut_modifie',
            'utilisateur_id' => 42,
        ]);
    }

    public function test_inventory_rejects_biens_outside_the_campaign_scope(): void
    {
        $this->fakePatrimoine();
        $token = $this->token();
        $inventoryId = $this->withToken($token)->postJson('/api/inventaires', [
            'titre' => 'Inventaire limité',
            'service_id' => 20,
        ])->assertCreated()->json('data.id');
        $this->withToken($token)->patchJson('/api/inventaires/'.$inventoryId.'/statut', [
            'statut' => 'en_cours',
        ])->assertOk();

        $this->withToken($token)->postJson('/api/inventaires/'.$inventoryId.'/lignes', [
            'bien_id' => 1,
            'constat' => 'present',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('bien_id');

        $this->assertDatabaseCount('inventaire_lignes', 0);
    }

    public function test_inventory_report_lists_theoretical_assets_not_checked_in_the_campaign(): void
    {
        $this->fakePatrimoine();
        $token = $this->token();
        $inventoryId = $this->withToken($token)->postJson('/api/inventaires', [
            'titre' => 'Inventaire annuel',
            'service_id' => 10,
        ])->assertCreated()->json('data.id');
        $this->withToken($token)->patchJson('/api/inventaires/'.$inventoryId.'/statut', [
            'statut' => 'en_cours',
        ])->assertOk();
        $this->withToken($token)->postJson('/api/inventaires/'.$inventoryId.'/lignes', [
            'bien_id' => 1,
            'constat' => 'present',
        ])->assertCreated();

        $this->withToken($token)->getJson('/api/inventaires/'.$inventoryId)
            ->assertOk()
            ->assertJsonPath('rapport.biens_theoriques', 2)
            ->assertJsonPath('rapport.biens_controles', 1)
            ->assertJsonPath('rapport.non_controles', 1)
            ->assertJsonPath('rapport.biens_non_controles.0.id', 2)
            ->assertJsonPath('rapport.ecarts', 1);
    }

    public function test_inventory_status_changes_notify_the_campaign_creator(): void
    {
        $this->fakePatrimoine();
        $creator = $this->tokenForUser(42);
        $reviewer = $this->tokenForUser(77);
        $inventoryId = $this->withToken($creator)->postJson('/api/inventaires', [
            'titre' => 'Campagne à valider',
            'service_id' => 10,
        ])->assertCreated()->json('data.id');
        $this->withToken($creator)->patchJson('/api/inventaires/'.$inventoryId.'/statut', [
            'statut' => 'en_cours',
        ])->assertOk();
        $this->withToken($creator)->postJson('/api/inventaires/'.$inventoryId.'/lignes', [
            'bien_id' => 1,
            'constat' => 'present',
        ])->assertCreated();

        $this->withToken($reviewer)->patchJson('/api/inventaires/'.$inventoryId.'/statut', [
            'statut' => 'a_valider',
        ])->assertOk();
        $this->withToken($reviewer)->patchJson('/api/inventaires/'.$inventoryId.'/statut', [
            'statut' => 'valide',
        ])->assertOk();

        $this->withToken($creator)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'inventaire.valide')
            ->assertJsonPath('data.1.type', 'inventaire.a_valider');
        $this->withToken($reviewer)->getJson('/api/notifications')->assertJsonPath('total', 0);
    }

    public function test_transfer_keeps_approved_status_when_patrimoine_update_fails(): void
    {
        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'PATCH') {
                return Http::response(['message' => 'Erreur distante'], 500);
            }

            if (str_ends_with($request->url(), '/api/services/20')) {
                return Http::response(['data' => ['id' => 20, 'ministere_id' => 200]], 200);
            }

            return Http::response(['data' => ['id' => 1, 'service_id' => 10]], 200);
        });

        $token = $this->token();
        $transfertId = $this->createTransfer($token);
        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'en_attente',
        ])->assertOk();
        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'approuvé',
        ])->assertOk();

        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'effectué',
        ])->assertServiceUnavailable()
            ->assertJsonPath('message', 'Le bien n’a pas pu être mis à jour; le transfert reste approuvé.');

        $this->assertDatabaseHas('transferts', [
            'id' => $transfertId,
            'statut' => 'approuvé',
        ]);
        $this->assertDatabaseCount('historique_operations', 3);
    }

    public function test_transfer_updates_patrimoine_before_recording_completion(): void
    {
        $this->fakePatrimoine();
        $token = $this->token();
        $transfertId = $this->createTransfer($token);
        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'en_attente',
        ])->assertOk();
        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'approuvé',
        ])->assertOk();

        $this->withToken($token)->patchJson('/api/transferts/'.$transfertId.'/statut', [
            'statut' => 'effectué',
        ])->assertOk()
            ->assertJsonPath('data.statut', 'effectué');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'PATCH'
            && str_ends_with($request->url(), '/api/biens/1')
            && $request['service_id'] === 20);
        $this->assertDatabaseHas('transferts', ['id' => $transfertId, 'statut' => 'effectué']);
        $this->assertDatabaseHas('historique_operations', [
            'action' => 'transfert.statut_modifie',
            'nouvelle_valeur->statut' => 'effectué',
        ]);
    }

    private function createTransfer(string $token): int
    {
        return $this->withToken($token)->postJson('/api/transferts', [
            'bien_id' => 1,
            'service_destination_id' => 20,
            'date_demande' => '2026-01-15',
            'motif' => 'Réorganisation des services',
        ])->assertCreated()->json('data.id');
    }

    private function fakePatrimoine(): void
    {
        Http::fake(function (ClientRequest $request) {
            if (preg_match('~/api/services/(\\d+)$~', $request->url(), $matches)) {
                $serviceId = (int) $matches[1];

                return Http::response([
                    'data' => [
                        'id' => $serviceId,
                        'ministere_id' => $serviceId === 20 ? 200 : 100,
                    ],
                ], 200);
            }

            if (str_ends_with($request->url(), '/api/biens/404')) {
                return Http::response([], 404);
            }

            if (parse_url($request->url(), PHP_URL_PATH) === '/api/biens') {
                return Http::response([
                    'data' => [
                        ['id' => 1, 'reference' => 'GPE-001', 'designation' => 'Ordinateur', 'service_id' => 10, 'actif' => true],
                        ['id' => 2, 'reference' => 'GPE-002', 'designation' => 'Imprimante', 'service_id' => 10, 'actif' => true],
                    ],
                    'current_page' => 1,
                    'last_page' => 1,
                ], 200);
            }

            return Http::response(['data' => ['id' => 1, 'service_id' => 10]], 200);
        });
    }

    private function token(string $role = 'administrateur-general'): string
    {
        return $this->tokenForUser(42, $role);
    }

    private function tokenForUser(
        int $userId,
        string $role = 'administrateur-general',
        ?int $serviceId = null,
    ): string
    {
        return JWT::encode([
            'sub' => (string) $userId,
            'role' => $role,
            'service_id' => $serviceId,
            'iat' => now()->timestamp,
            'exp' => now()->addHours(2)->timestamp,
        ], config('jwt.secret'), 'HS256');
    }
}
