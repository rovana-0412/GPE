# Microservices GPE — Avancement du jalon (a)

> Branche : `feature/microservices`
> Objectif : Docker + Nginx + auth-service + web (login fonctionnel sur http://localhost:8080)

## État actuel (commit 36e27cc)

### Structure
- `web/` : application Laravel monolithe déplacée (design togolais + dashboard commercial)
- `services/auth-service/` : Laravel 12 API-only installé
- `services/patrimoine-service/` : Laravel 12 API-only installé
- `services/mouvements-service/` : Laravel 12 API-only installé

### Ce qui reste à faire (jalon a)

#### a1 — Infrastructure Docker et PostgreSQL
- `docker/postgres/init.sql` : initialisation PostgreSQL avec bases `gpe_auth`, `gpe_patrimoine`, `gpe_mouvements`, `gpe_web` (ou une seule base `gpe_db` avec schemas ?)
- `docker-compose.yml` à la racine : conteneurs `postgres`, `auth-service`, `patrimoine-service`, `mouvements-service`, `web` (PHP-FPM), `gateway` (Nginx)
- `docker/gateway/nginx.conf` : routing `/api/auth/*` → auth-service, `/api/patrimoine/*` → patrimoine-service, `/api/mouvements/*` → mouvements-service, `/` → web
- Dockerfile par service : image PHP-FPM artisanal, avec copie du code
- `.env.example` à la racine : `DB_PASSWORD`, `JWT_SECRET` (vides), `PATRIMOINE_URL`, `MOUVEMENTS_URL`, `AUTH_URL`
- Git : commiter a1, tests verts

#### a2 — auth-service
- Migrations : uniquement `roles` et `users` (sans FK inter-services)
- Modèles : `Role`, `User` (conserver colonnes `role_id`, `service_id` mais enlever `belongsTo`)
- Seeders : copier depuis web (`RoleSeeder.php`, et créer un admin de test)
- `composer require firebase/php-jwt`
- Route `POST /api/login` : vérifie credentials, émet JWT HS256 (payload : `sub`, `role`, `service_id`, `iat`, `exp` = +2h)
- Route `GET /api/me` : renvoie l'utilisateur connecté (middleware JWT auth)
- Middleware `jwt.auth` : vérifie signature JWT avec `JWT_SECRET`
- Test PHPUnit : test login → JWT validé, test `/api/me` sans token → 401
- Git : commiter a2, tests verts

#### a3 — web
- Installer `firebase/php-jwt` dans web
- AuthController : appeler `auth-service` (`POST http://auth-service/api/login`) au lieu de `Auth::attempt`
- Stocker JWT en session PHP
- DashboardController : appeler services pour stats (mock pour l'instant)
- Design et login inchangés (déjà adaptés)
- Git : commiter a3, tests verts

#### a4 — Docker compose up --build
- Vérifier que tous les conteneurs démarrent
- Vérifier login sur http://localhost:8080
- Git : commiter a4, tests verts

## Après le jalon (a)
- Jalon (b) : patrimoine-service + dashboard réel
- Jalon (c) : mouvements-service

## Contraintes
- Ne commite aucun `.env`
- Ne touche pas aux branches existantes
- À la fin : liste commits, résultats tests, commande de lancement, comptes de test
