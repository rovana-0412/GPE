# Microservices GPE — Avancement du jalon (a)

> Branche : `feature/microservices`
> Objectif : Docker + Nginx + auth-service + web (login fonctionnel sur http://localhost:8080)
> Dernière validation : 14/11/2026

## État actuel

### Structure
|- `web/` : application Laravel monolithe déplacée (design togolais + dashboard commercial)
|- `services/auth-service/` : Laravel 12 API-only installé
|- `services/patrimoine-service/` : Laravel 12 API-only installé
|- `services/mouvements-service/` : Laravel 12 API-only installé

### Progression du jalon (a)

#### ✅ a1 — Infrastructure Docker et PostgreSQL
- ✅ `docker/postgres/init.sql` : initialisation des bases `gpe_auth`, `gpe_patrimoine`, `gpe_mouvements` et `gpe_test`
- ✅ `docker-compose.yml` à la racine : variables partagées, secrets requis, healthchecks et services sans `container_name`
- ✅ `docker/gateway/nginx.conf` : routage HTTP sur le port 8000, conservation de l'hôte et du port d'origine
- ✅ Dockerfiles des trois API en `php:8.2-cli`, écoute sur 8000 et migrations/seed au démarrage
- ✅ `.env.example` à la racine sans secret ; le web ne reçoit aucune variable `DB_*`

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
- ✅ `artisan serve --no-reload` permet d'utiliser `PHP_CLI_SERVER_WORKERS=4`
- ✅ `.dockerignore` des trois API excluent `vendor`, `node_modules`, `.env` et `storage/logs`
- ✅ Tests des trois services passés avec une clé de test éphémère (avertissements existants liés au `.env` absent)
- ✅ Compose, la passerelle, PostgreSQL et `.env.example` configurés pour le réseau interne sur 8000
- ✅ PostgreSQL publié sur 5433 par défaut, avec surcharge `POSTGRES_HOST_PORT` pour les environnements où le port est déjà occupé
- ✅ Web en build multi-étapes `node:22-alpine` / `php:8.2-cli`, sans migration
- ✅ Web sans variables `DB_*`; le flux login conserve le JWT en session et le dashboard utilise cette session
- ✅ Tests web : 14 assertions, aucun échec (avertissements existants lorsque le `.env` local est absent)
- ✅ `auth-service` lit le secret JWT depuis `config()` ; test login/JWT adapté à cette configuration
- ✅ Seeders des trois services idempotents, relancés deux fois après migration fraîche
- ✅ Syntaxe d'apostrophes corrigée dans `RoleSeeder`, requise au démarrage auth
- ✅ Stack construit et démarré ; PostgreSQL et les quatre applications sont healthy
- ✅ `POST /api/auth/login` via Nginx renvoie un JWT après redémarrage de la passerelle
- ⚠️ `GET /connexion` répond 200, mais ne renvoie aucun `Set-Cookie`; la soumission web échoue donc avec 419
- ⏳ Résoudre l'absence du cookie de session, puis valider le login complet sur http://localhost:8080

La validation Compose utilise le projet isolé `gpe-verify` et PostgreSQL hôte sur 5434, car le port 5433 est déjà occupé par une instance existante. Cette instance existante n'a pas été modifiée. Le redémarrage de Nginx après la recréation de ses services a été nécessaire pour rafraîchir ses adresses de backend.

Le diagnostic du 419 montre que `SESSION_DRIVER=file`, `StartSession` et le groupe middleware `web` sont actifs. Aucun cookie n'est pourtant émis, y compris par un serveur PHP CLI à un seul worker ; la cause reste à identifier.

## Après le jalon (a)
- Jalon (b) : patrimoine-service + dashboard réel
- Jalon (c) : mouvements-service

## Contraintes
- Ne commite aucun `.env`
- Ne touche pas aux branches existantes
- À la fin : liste commits, résultats tests, commande de lancement, comptes de test
