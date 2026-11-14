# Projet GPE : gestion du patrimoine de l'État du Togo

Projet scolaire noté, individuel, à rendre le 15/11/2026. Évaluation orale : présentation de la solution et défense de l'architecture, fonctionnalités pertinentes, qualité du code.

## Exigences du brief
- Laravel, PostgreSQL conteneurisé dans le docker-compose, Docker
- **Architecture micro-service obligatoire**
- Conception UML, design Figma, proposition financière, fiche technique, guide d'utilisation
- Livrables : plateforme hébergée gratuitement, dépôt GitHub avec README détaillé et normalisé

## Architecture
Navigateur → passerelle Nginx (:8080) → `web` (Blade, design vert/or/rouge, FCFA) ou services API.
- `services/auth-service` (base gpe_auth) : roles, users, login, JWT
- `services/patrimoine-service` (gpe_patrimoine) : ministeres, services, categories, biens
- `services/mouvements-service` (gpe_mouvements) : affectations, transferts, maintenances, historique_operations
- `web` : aucune base de données, appelle les services avec `Http::` (JWT en session)
- Un seul conteneur PostgreSQL 16 (port hôte 5433), bases gpe_auth, gpe_patrimoine, gpe_mouvements, gpe_test

## Règles de conception
- Aucune clé étrangère entre services : `service_id`, `bien_id`, `user_id` sont des entiers indexés
- JWT HS256, durée 2 h, sans refresh token, claims `sub`, `role`, `service_id`. Émis par auth-service, vérifié par chaque service avec `JWT_SECRET` (32 caractères minimum). Secret lu via `config()`, jamais `env()` dans le code
- Pas de transaction distribuée. Un transfert qui passe à `effectué` appelle d'abord patrimoine-service pour mettre à jour le bien, puis enregistre le statut et l'historique ; en cas d'échec il reste `approuvé` et on renvoie une erreur
- Transferts : brouillon → en_attente → approuvé → effectué, plus rejeté. Transitions invalides refusées
- Chaque création, modification ou changement de statut écrit une ligne dans `historique_operations`
- Rôles : admin, gestionnaire, responsable, agent, auditeur (voir documentation/audit/PERMISSIONS.md)
- Tout le texte de l'interface est en français, montants en FCFA (XOF), fuseau Africa/Lome

## Docker
- Services Laravel : image php:8.2-cli, `artisan serve` sur le port 8000 (PHP_CLI_SERVER_WORKERS=4). La production utiliserait PHP-FPM
- Nginx fait un proxy HTTP : `/api/auth/` → auth-service:8000/api/, idem pour patrimoine et mouvements, `/` → web:8000
- Secrets uniquement dans `.env` (jamais commité) ; `.env.example` sans secret : DB_USERNAME, DB_PASSWORD, JWT_SECRET, APP_KEY
- Chaque service reçoit APP_KEY, JWT_SECRET, SESSION_DRIVER=file, CACHE_STORE=file, QUEUE_CONNECTION=sync, LOG_CHANNEL=stderr
- Seeders idempotents (`updateOrCreate`), car ils tournent à chaque démarrage

## Méthode
- Une étape à la fois, un commit par étape (message en français, préfixe : Infra, Auth, Patrimoine, Mouvements, Web, Docs), tests verts avant chaque commit
- Tests inter-services avec `Http::fake()`, base gpe_test
- Ne jamais commiter de `.env` ni de secret. Ne pas toucher aux autres branches
- Après chaque étape, mettre à jour `documentation/architecture/AVANCEMENT.md` puis s'arrêter et résumer

- Fichiers PHP : `<?php` doit être le tout premier caractère (aucune ligne vide avant). Après tout lot de modifications, vérifier avec `curl.exe -s -D - -o NUL http://localhost:8080/connexion` que les en-têtes `Set-Cookie` sont bien présents.