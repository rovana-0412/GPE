# Microservices GPE — Avancement des phases

> Branche : `feature/microservices`
> Objectif : plateforme GPE microservices exploitable sur http://localhost:8080
> Dernière validation : 15/11/2026

## État actuel

### Structure
|- `web/` : interface Laravel Blade sans base de données (design togolais + tableau de bord)
|- `services/auth-service/` : Laravel 12 API-only installé
|- `services/patrimoine-service/` : Laravel 12 API-only installé
|- `services/mouvements-service/` : Laravel 12 API-only installé

### ✅ Phase 0 — Nettoyage et base saine
- ✅ Correction de la ligne vide avant `<?php` dans `web/routes/web.php`, déjà enregistrée dans `70640f5`
- ✅ Suppression des migrations, modèles Eloquent, seeders et factory inutilisés de `web/`; ses tests conservés n'accèdent pas à une base
- ✅ `web` ne reçoit aucune variable `DB_*`; mappings d'autoload des factories et seeders retirés
- ✅ Suppression de `docker-compose.override.yml.bak` et des vues scaffold `welcome.blade.php` des trois API
- ✅ Les racines des trois API répondent désormais en JSON, avec tests dédiés
- ✅ Plan complet enregistré dans `documentation/PLAN-COPILOT.md`; consigne UTF-8 sans BOM ajoutée à `.github/copilot-instructions.md`
- ✅ `docker compose up -d --build` sur le projet par défaut; services healthy, puis redémarrage de la passerelle après rebuild
- ✅ Test curl de bout en bout : cookies `XSRF-TOKEN` et `laravel_session`, login admin en 302 vers le tableau de bord, tableau de bord en 200
- ✅ `docker compose down` exécuté après vérification; aucun conteneur de la stack ne reste actif
- ✅ `.env` racine ignoré et non suivi; aucun secret de test ajouté au dépôt

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

#### ✅ a4 — Docker Compose et login
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
- ✅ Stack construit et démarré avec le projet Compose par défaut ; PostgreSQL et les quatre applications sont healthy
- ✅ Passerelle redémarrée après rebuild des services
- ✅ Login web par curl confirmé de bout en bout sur `http://localhost:8080`
- ✅ Après vérification, `docker compose down` a arrêté les six conteneurs sans supprimer le volume PostgreSQL
- ✅ Tests après nettoyage : web 14 assertions, auth-service 22, patrimoine-service 3, mouvements-service 3 ; aucun échec (avertissements dus aux `.env` locaux absents)

### ✅ Phase 1 — Patrimoine-service et pages biens
- ✅ Modèles et migrations PostgreSQL de ministeres, services, categories et biens; les biens utilisent `SoftDeletes`
- ✅ Clés étrangères limitées aux relations internes au service patrimoine
- ✅ Seeders idempotents : 4 ministères, 9 services, 10 catégories et 6 biens
- ✅ API REST CRUD protégée par `jwt.auth`, validation FormRequest, pagination, filtres et statistiques
- ✅ Vérification JWT HS256 par configuration, sans requête vers la base des utilisateurs de auth-service
- ✅ Interface web reliée avec le JWT de session : dashboard réel, liste/fiche/création/modification des biens et gestion des référentiels
- ✅ Une réponse 401 d’un service invalide la session; une indisponibilité affiche une erreur claire en français
- ✅ Tests patrimoine : 34 assertions sans échec, couvrant accès sans token, filtres, pagination, CRUD, suppression logique, contraintes et idempotence
- ✅ Tests web : 32 assertions sans échec, incluant pages, dashboard, déconnexion sur 401 et indisponibilité inter-services
- ✅ `npm.cmd run build` réussi; contrôle PHP : 150 sources sans BOM, avec `<?php` initial et sans balise finale
- ✅ `docker compose up -d --build` : six conteneurs healthy; rebuild suivi de `docker compose restart gateway`
- ✅ Curl de bout en bout : cookies `XSRF-TOKEN` et `laravel_session`, login web 302, dashboard alimenté par l’API
- ✅ Curl JWT : 6 biens et valeur d’acquisition totale de 137 150 000 FCFA via `/api/patrimoine`
- ✅ `docker compose down` exécuté après validation; volume PostgreSQL conservé
- ✅ Commits : `405c13f` (Patrimoine) et `16c7e3e` (Web)
- ℹ️ Les tests locaux sortent avec code 0 mais signalent un avertissement par test car les fichiers `.env` applicatifs locaux sont absents; les clés de test ont été fournies uniquement au processus.

## Prochaine phase
- Phase 2 : mouvements-service et pages affectations, transferts, maintenances et historique

## Contraintes
- Ne commite aucun `.env`
- Ne touche pas aux branches existantes
- À la fin : liste commits, résultats tests, commande de lancement, comptes de test
