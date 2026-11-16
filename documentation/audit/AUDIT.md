# Audit et validation du projet GPE

> Branche de référence : `feature/microservices`
> Dernière validation complète : 16 novembre 2026
> Architecture : microservices Laravel 12 / PHP 8.2 / PostgreSQL 16 / Docker Compose / Nginx

Ce document remplace le rapport d'audit initial (monolithe `application/`, 14 novembre 2026).
L'application a depuis été restructurée en quatre applications conteneurisées.

## 1. Architecture livrée

| Composant | Rôle | Base de données |
|---|---|---|
| `web/` | Interface Blade, session JWT, aucun accès direct aux bases | aucune |
| `services/auth-service/` | Comptes, rôles, JWT HS256 (2 h), audit, paramètres | `gpe_auth` |
| `services/patrimoine-service/` | Biens, catégories, ministères, services, acquisitions, sorties | `gpe_patrimoine` |
| `services/mouvements-service/` | Affectations, transferts, maintenances, inventaires, notifications, justificatifs | `gpe_mouvements` |
| `gpe-postgres-1` | PostgreSQL 16, une base par service + `gpe_test` | — |
| `gpe-gateway-1` | Nginx : `/api/auth/*`, `/api/patrimoine/*`, `/api/mouvements/*`, `/api/documents`, reste → `web` | — |

Communication inter-services strictement par API HTTP avec le JWT de
l'utilisateur ; aucun service ne lit les tables d'un autre.

## 2. Tests automatisés — résultats exacts

Exécutés le 16 novembre 2026 sur la base PostgreSQL dédiée `gpe_test`
(`RefreshDatabase`, migrations rejouées par suite, aucun accès aux bases
de production) :

| Suite | Tests réussis | Échoués | Ignorés | Assertions |
|---|---|---|---|---|
| auth-service | 13 | 0 | 0 | 64 |
| patrimoine-service | 12 | 0 | 0 | 95 |
| mouvements-service | 25 | 0 | 0 | 194 |
| web | 24 | 0 | 0 | 148 |
| **Total** | **74** | **0** | **0** | **501** |

Correction de tests (aucun test supprimé) : deux tests de workflow
(acquisition, sortie) utilisaient le même jeton pour créer et valider.
La règle métier « le demandeur ne peut pas valider sa propre demande »
renvoyait correctement 403 ; les tests utilisent désormais un second
jeton validateur, comme le test de notifications existant.

## 3. Scénarios exécutés en conditions réelles (stack Docker)

| Scénario | Résultat |
|---|---|
| Authentification admin + JWT (`role_slug` correct) | OK |
| Création d'un bien (`GPE-DEMO-001`, 8 500 000 FCFA) | OK |
| Affectation à un service | OK |
| Transfert brouillon → en attente → approuvé → effectué | OK, service du bien mis à jour (6 → 5) |
| Maintenance (révision, 150 000 FCFA) | OK |
| Campagne d'inventaire | OK (création ; constats non exercés en live) |
| Sortie `mise_au_rebut` → exécutée | OK, bien archivé `actif=false, statut=sorti` sans suppression |
| Permissions : auditeur en écriture → 403 ; gestionnaire sur `/admin/users` → 403 | OK |
| Exports PDF (3 pages, ~880 Ko) et XLSX valides | OK, fichiers réels générés |
| Justificatif PDF : upload 201, stockage privé, téléchargement identique | OK |
| Notifications `sortie.approuvee` / `sortie.executee` | OK |
| Audits : auth (connexions, comptes) et journal patrimoine | OK |

## 4. Correctifs appliqués durant la validation

| Correctif | Commit |
|---|---|
| Dockerfiles : suppression de `migrate --force --seed` au démarrage ; seeders non destructifs ; mot de passe admin via `ADMIN_INITIAL_PASSWORD` | `59440db` |
| `PatrimoineClient::changerService` : `PATCH` → `PUT /api/biens/{id}` (la route PATCH n'existe pas ; tout transfert `effectué` échouait) | `55db1f8` (code) + `51f7b9c` |
| `web/Dockerfile` : réinstallation de `libzip5` après purge (requis par `ext-zip` pour les exports) | `51f7b9c` |
| `phpunit.xml` : port PostgreSQL de test 5433 → 5434 | `51f7b9c` |
| Tests de workflow : jeton validateur distinct (règle initiateur/validateur) | `51f7b9c` |
| Sauvegardes exclues du suivi Git (`.gitignore`) | `4076ef6` |

## 5. Données de démonstration présentes en production

- Bien `GPE-DEMO-001` (sorti du patrimoine, archivé).
- Utilisateurs de test : `gestionnaire@gpe.local`, `auditeur@gpe.local`
  (rôles Gestionnaire du patrimoine et Auditeur).
- Affectation, transfert effectué, maintenance, campagne d'inventaire,
  sortie et un justificatif PDF rattachés à ce bien.
- Ces données sont fictives et cohérentes ; elles peuvent être conservées
  pour la démonstration ou purgées sur demande explicite.

## 6. Couverture de tests — distinctions

**Couvert par les tests automatisés (74) :** authentification JWT,
rate limiting, désactivation de compte, auto-élévation refusée,
paramètres plateforme, CRUD biens/catégories/références, soft delete,
acquisitions et sorties avec notification mockée, affectations,
transferts (séquences, échec patrimoine), maintenances, documents
(mime, taille, périmètres, téléchargement, backfill), inventaires
(campagnes, constats, rapport, notifications), écrans web, exports
PDF/XLSX, recherche globale, gestion des erreurs service.

**Exécuté en conditions réelles :** voir section 3.

**Non couvert / à compléter :**

- Périmètres d'écriture par ministère/service des rôles
  `responsable-ministeriel` et `agent-gestionnaire` (non exercés en live).
- Inventaire complet : constats présent/absent/détérioré, réconciliation
  et verrouillage après validation (couvert par tests, non rejoué en live).
- Parcours E2E navigateur complet pour chaque rôle.
- `mouvements-service/DatabaseSeeder` reste volontairement minimal.

## 7. Sauvegardes

- Dumps SQL du 16 novembre 2026 dans `backups/2026-11-16/` (non versionnés) :
  `gpe_auth`, `gpe_patrimoine`, `gpe_mouvements`, `gpe_test` + archive du
  volume `mouvements_documents` (vide à cette date).
- Restauration vérifiée dans des bases temporaires isolées (puis supprimées).
- Les cinq bases `gpe_validation_*` sont conservées intactes.

## 8. Risques et points de vigilance

- `APP_DEBUG` doit être `false` en exposition publique.
- Secrets (`APP_KEY`, `JWT_SECRET`, `DB_PASSWORD`) fournis par variables
  d'environnement ; jamais versionnés.
- Pas de transaction distribuée entre services (limitation acceptée).
- JWT sans refresh token (durée fixe 2 h).
- Healthchecks Docker cohérents ; les conteneurs ne migrent ni ne seedent
  plus automatiquement.
