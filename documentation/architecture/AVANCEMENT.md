# Microservices GPE — Avancement du jalon (a)

> Branche : `feature/microservices`
> Objectif : Docker + Nginx + auth-service + web (login fonctionnel sur http://localhost:8080)

## État actuel (commit 88bc4d6)

### Structure
|- `web/` : application Laravel monolithe déplacée (design togolais + dashboard commercial)
|- `services/auth-service/` : Laravel 12 API-only installé
|- `services/patrimoine-service/` : Laravel 12 API-only installé
|- `services/mouvements-service/` : Laravel 12 API-only installé

### Progression du jalon (a)

#### ✅ a1 — Infrastructure Docker et PostgreSQL
- ✅ `docker/postgres/init.sql` : initialisation PostgreSQL avec bases `gpe_auth`, `gpe_patrimoine`, `gpe_mouvements`, `gpe_web`
- ✅ `docker-compose.yml` à la racine : conteneurs `postgres`, les quatre applications et la passerelle Nginx
- ✅ `docker/gateway/nginx.conf` : routage HTTP vers les services et le web
- ✅ Dockerfiles des trois API en `php:8.2-cli`, écoute sur 8000 et migrations/seed au démarrage
- ✅ `.env.example` à la racine sans secret ; bases applicatives séparées

#### ✅ a2 — auth-service (commit f36f92e)
- ✅ Migrations : `roles` et `users` (sans FK inter-services)
- ✅ Modèles : `Role`, `User` (colonnes `role_id`, `service_id` conservées)
- ✅ Seeders : `RoleSeeder.php`, `UserSeeder.php` (admin: admin@gpe.local / GPE_Admin_2026!)
- ✅ `composer require firebase/php-jwt`
- ✅ Route `POST /api/login` : vérifie credentials, émet JWT HS256 (payload : `sub`, `role`, `service_id`, `iat`, `exp` = +2h)
- ✅ Route `GET /api/me` : renvoie l'utilisateur connecté (middleware JWT auth)
- ✅ Middleware `jwt.auth` : vérifie signature JWT avec `JWT_SECRET`
- ✅ Test PHPUnit : test login → JWT validé, test `/api/me` sans token → 401

#### ✅ a3 — web (commit 88bc4d6)
- ✅ Installer `firebase/php-jwt` dans web
- ✅ AuthController : appeler `auth-service` (`POST http://auth-service/api/login`) au lieu de `Auth::attempt`
- ✅ Stocker JWT en session PHP
- ✅ DashboardController : mock services pour stats (TODO: appels réels vers patrimoine-service et mouvements-service)
- ✅ Design et login inchangés (déjà adaptés)
- ✅ Middleware `jwt.auth` pour protéger les routes web
- ✅ Test PHPUnit : test login → session contient JWT, dashboard accessible

#### ⏳ a4 — Docker Compose et login
- ✅ Dockerfiles des trois API utilisent PHP CLI sur le port 8000 et migrent/seedent au démarrage
- ✅ `.dockerignore` des trois API excluent `vendor`, `node_modules`, `.env` et `storage/logs`
- ✅ Tests des trois services passés avec une clé de test éphémère (avertissements existants liés au `.env` absent)
- ⏳ Finaliser la passerelle, Compose, PostgreSQL, le Dockerfile web et l'idempotence des seeders
- ⏳ Vérifier le démarrage complet et le login sur http://localhost:8080

**Note**: Le build Docker prend beaucoup de temps à cause de l'import du contexte (vendor/ node_modules). Ajout de .dockerignore pour optimiser.

## Après le jalon (a)
- Jalon (b) : patrimoine-service + dashboard réel
- Jalon (c) : mouvements-service

## Contraintes
- Ne commite aucun `.env`
- Ne touche pas aux branches existantes
- À la fin : liste commits, résultats tests, commande de lancement, comptes de test
