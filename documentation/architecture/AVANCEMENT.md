# Microservices GPE — Avancement final

> Branche : `feature/microservices`
> Objectif : plateforme GPE microservices exploitable sur http://localhost:8080
> Dernière mise à jour : 16 novembre 2026

## État actuel

### Structure
- `web/` : interface Laravel Blade sans base de données (design togolais + tableau de bord commercial)
- `services/auth-service/` : Laravel 12 API-only (authentification, JWT HS256, rôles)
- `services/patrimoine-service/` : Laravel 12 API-only (biens, catégories, ministères, services)
- `services/mouvements-service/` : Laravel 12 API-only (affectations, transferts, maintenances, historique)

### ✅ Phase 0 — Nettoyage et base saine
- ✅ Correction de la ligne vide avant `<?php` dans `web/routes/web.php`
- ✅ Suppression des migrations, modèles Eloquent, seeders et factory inutilisés de `web/`
- ✅ `web` ne reçoit aucune variable `DB_*`
- ✅ Suppression de `docker-compose.override.yml.bak` et des vues scaffold
- ✅ Les racines des trois API répondent en JSON

### ✅ Phase 1 — Infrastructure Docker et PostgreSQL
- ✅ `docker/postgres/init.sql` : initialisation des bases `gpe_auth`, `gpe_patrimoine`, `gpe_mouvements`, `gpe_test`
- ✅ `docker-compose.yml` : variables partagées, secrets requis, healthchecks
- ✅ `docker/gateway/nginx.conf` : routage HTTP sur le port 8000
- ✅ Dockerfiles des trois API en `php:8.2-cli`, écoute sur 8000
- ✅ `.env.example` à la racine sans secret
- ✅ PostgreSQL publié sur 5433 par défaut

### ✅ Phase 2 — Auth-service
- ✅ Migrations : `roles` et `users` (sans FK inter-services)
- ✅ Modèles : `Role`, `User` (colonnes `role_id`, `service_id` conservées)
- ✅ Seeders : `RoleSeeder.php`, `UserSeeder.php` (admin: admin@gpe.local / GPE_Admin_2026!)
- ✅ `composer require firebase/php-jwt`
- ✅ Route `POST /api/login` : vérifie credentials, émet JWT HS256 (2h, payload avec slug de rôle)
- ✅ Route `GET /api/me` : renvoie l'utilisateur connecté
- ✅ Middleware `jwt.auth` : vérifie signature JWT
- ✅ Middleware `role` : vérifie les permissions par rôle
- ✅ Tests PHPUnit sur PostgreSQL `gpe_test`

### ✅ Phase 3 — Web
- ✅ `composer require firebase/php-jwt`
- ✅ AuthController : appeler `auth-service` (`POST http://auth-service/api/login`)
- ✅ Stocker JWT en session PHP
- ✅ DashboardController : appels réels vers services API
- ✅ Design et login inchangés (design togolais + dashboard commercial)
- ✅ Middleware `jwt.auth` pour protéger les routes web
- ✅ Pages reliées : biens, catégories, ministères, services, affectations, transferts, maintenances, historique
- ✅ Tests PHPUnit sur PostgreSQL `gpe_test`

### ✅ Phase 4 — Patrimoine-service
- ✅ Modèles et migrations : ministeres, services, categories, biens (SoftDeletes)
- ✅ Clés étrangères limitées aux relations internes au service
- ✅ Seeders idempotents : 4 ministères, 9 services, 10 catégories, 6 biens
- ✅ API REST CRUD protégée par `jwt.auth` et `role`
- ✅ Validation FormRequest, pagination, filtres, statistiques
- ✅ Vérification JWT par configuration
- ✅ Interface web reliée avec JWT de session
- ✅ Gestion d'erreurs 401 et indisponibilité inter-services
- ✅ Tests PHPUnit sur PostgreSQL `gpe_test`

### ✅ Phase 5 — Mouvements-service
- ✅ Modèles et migrations : affectations, transferts, maintenances, historique_operations
- ✅ API REST CRUD protégée par `jwt.auth` et `role`
- ✅ Workflow transferts : brouillon → en_attente → approuvé → effectué
- ✅ Vérification existence du bien via patrimoine-service
- ✅ Interface web reliée avec JWT de session
- ✅ Tests PHPUnit sur PostgreSQL `gpe_test`

### ✅ Corrections récentes (commits b85cfef et bd1a5b2)
- ✅ Tests sur PostgreSQL `gpe_test` (plus de SQLite)
- ✅ Timezone `Africa/Lome` dans tous les services
- ✅ Locale `fr` dans tous les services
- ✅ Suppression des GRANT codés en dur dans `init.sql`
- ✅ JWT avec slug de rôle cohérent (administrateur-general, gestionnaire-patrimoine, etc.)
- ✅ Middleware de rôle appliqué aux routes sensibles
- ✅ Routes séparées en lecture seule (tous les rôles) et écriture (gestionnaire/admin)

### ✅ Documentation et livrables
- ✅ README.md principal
- ✅ Documentation technique (ARCHITECTURE.md)
- ✅ Guide utilisateur (GUIDE.md)
- ✅ Procédure de déploiement (DEPLOIEMENT.md)
- ✅ Diagrammes PlantUML : architecture, cas d'utilisation, séquence transfert, activité bien, composants, déploiement

### ⚠️ Limites connues
- **Exports PDF/Excel** : non implémentés (paquets incompatibles PHP 8.2 vs 8.3)
- **Pas de transaction distribuée** : chaque opération reste dans un seul service
- **Éventuelle incohérence** : si un service échoue après qu'un autre a réussi
- **Pas de refresh token** : JWT durée fixe 2h

### ✅ Correctifs de configuration des tests (16 novembre 2026)
- ✅ Les tests hôtes utilisent maintenant le port PostgreSQL configurable de `.env` au lieu d'imposer 5433
- ✅ Suppression des identifiants PostgreSQL codés en dur dans les configurations PHPUnit
- ✅ Configuration d'une APP_KEY dédiée aux tests patrimoine, mouvements et web
- ✅ Correction des jetons de test utilisant le rôle `admin` non reconnu par le middleware
- ✅ Ajout de `PATCH /api/maintenances/{id}` pour aligner l'API sur l'appel partiel du web, en conservant `PUT`
- ✅ Ajout de `scripts/test.ps1` pour exécuter séquentiellement les quatre suites PHPUnit sur `gpe_test`
- ✅ Résultats vérifiés : auth 6 tests / 22 assertions, patrimoine 7 / 34, mouvements 9 / 49, web 11 / 43 (toutes réussies)

## Commande de lancement

```bash
docker compose up --build
```

Accès : http://localhost:8080

## Comptes de test

- **Email** : `admin@gpe.local`
- **Mot de passe** : `GPE_Admin_2026!`
- **Rôle** : Administrateur général

## État des tests

- **Auth-service** : PHPUnit sur PostgreSQL `gpe_test`
- **Patrimoine-service** : PHPUnit sur PostgreSQL `gpe_test`
- **Mouvements-service** : PHPUnit sur PostgreSQL `gpe_test`
- **Web** : PHPUnit sur PostgreSQL `gpe_test`

## Commits récents

```
bd1a5b2 Permissions: middleware de rôle, routes protégées par rôle, JWT avec slug de rôle cohérent
b85cfef Tests et config: tests sur PostgreSQL gpe_test, timezone Africa/Lome, locale fr, suppression GRANT en dur
eb1903a Mouvements: API (affectations, transferts, maintenances, historique), tests et écrans web
d96a7e4 Docs: valider la phase patrimoine
16c7e3e Web: relier les écrans au patrimoine
405c13f Patrimoine: créer l’API du patrimoine
[... commits infrastructure et auth ...]
```

## Prochaines étapes (si nécessaires)

- Implémenter les exports PDF/Excel avec des paquets compatibles PHP 8.2
- Ajouter des tests E2E avec Laravel Dusk
- Configurer le monitoring (Prometheus/Grafana)
- Mettre en place une CI/CD (GitHub Actions)
