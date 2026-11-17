# Déploiement de démonstration GPE sur Render

> **Statut : PROJET — aucune opération externe effectuée.**
> Branche dédiée : `demo-render` (créée depuis `feature/microservices` @ `048f54c`).
> La branche `feature/microservices` et les données locales ne sont jamais modifiées.

## 1. Architecture déployée

Render ne supporte pas Docker Compose : chaque conteneur devient un **Web Service**
indépendant (plan gratuit, 512 Mo / 0,1 CPU, spin-down après 15 min).

| Service Render | Contenu | Image |
|---|---|---|
| `gpe-web` | Application Blade (frontend + exports PDF/Excel) | `web/Dockerfile` |
| `gpe-auth` | API auth, JWT, rôles, `/api/me` | `services/auth-service/Dockerfile` |
| `gpe-patrimoine` | API patrimoine | `services/patrimoine-service/Dockerfile` |
| `gpe-mouvements` | API mouvements + justificatifs | `services/mouvements-service/Dockerfile` |
| `gpe-postgres` | PostgreSQL 16 (plan gratuit) | Render managé |

**Passerelle Nginx : abandonnée.** Chaque API est exposée publiquement sous
`https://gpe-<nom>.onrender.com` et protégée par le middleware JWT existant
(vérification du compte actif F6 conservée). Les appels `web → APIs` passent par
les variables `AUTH_URL` / `PATRIMOINE_URL` / `MOUVEMENTS_URL` (HTTPS publiques).

**Justificatifs : Cloudflare R2** (S3-compatible, 10 Go gratuits, bucket privé).
Le disque `s3` de `filesystems.php` est utilisé via `FILESYSTEM_DISK=s3`.
Le FS Render étant éphémère, aucun fichier ne doit reposer sur le disque local.

## 2. Limites du plan gratuit (à connaître)

- **750 h/mois** partagées entre les 4 web services → spin-down après 15 min
  d'inactivité, cold start ~1 min par service (premier accès lent, possiblement
  plusieurs minutes en cascade web → auth → patrimoine → mouvements).
- **PostgreSQL gratuit : expire après ~30 jours**, 1 Go de stockage,
  100 connexions max, aucune sauvegarde incluse → exporter régulièrement
  (`pg_dump`) et recréer la base si suppression.
- **Carte bancaire : Render peut l'exiger pour vérification anti-fraude.**
  Si une carte est demandée à l'inscription ou à la validation → **ARRÊTER**,
  ne pas contourner, ne pas saisir de carte.
- Sans moyen de paiement : dépassement de bande passante → suspension (pas de
  facturation surprise, mais indisponibilité).

## 3. Prérequis externes (comptes à créer — AUTORISATION REQUISE)

1. Compte Render (gratuit) — vérifier qu'aucune carte n'est exigée.
2. Compte Cloudflare (gratuit) — pour le bucket R2.
   - R2 : créer un bucket **privé**, créer un **jeton API** limité au bucket
     (Object Read & Write). Noter : Account ID, Access Key ID, Secret, Endpoint
     `https://<account-id>.r2.cloudflarestorage.com`.
3. Le dépôt GitHub doit contenir la branche `demo-render` (push requis —
   autorisation séparée).

## 4. Fichiers de la branche `demo-render`

| Fichier | Rôle |
|---|---|
| `render.yaml` | Blueprint : 4 web services Docker + 1 PostgreSQL gratuit. Secrets en `sync: false`. |
| `services/mouvements-service/composer.json` / `.lock` | Ajout de `league/flysystem-aws-s3-v3` (driver S3 pour R2). Seule dépendance ajoutée. |
| `documentation/deploiement/RENDER.md` | Ce guide. |

Aucun autre fichier modifié. `docker-compose.yml`, Dockerfiles, `nginx.conf`,
`.env*` locaux : **inchangés**.

## 5. Procédure de déploiement (à exécuter après autorisation)

1. `git push origin demo-render` (autorisation requise).
2. Render Dashboard → **New → Blueprint** → sélectionner le dépôt, branche
   `demo-render`. Render lit `render.yaml`.
3. Renseigner les variables `sync: false` (voir §6).
4. Attendre le premier build de chaque service (Dockerfile inchangé :
   `composer install --no-dev` + assets Vite pour `web` + `artisan serve`).
5. Créer les 3 bases sur l'instance PostgreSQL Render :
   ```
   psql "<connection-string-externe>" -c "CREATE DATABASE gpe_auth;"
   psql "<connection-string-externe>" -c "CREATE DATABASE gpe_patrimoine;"
   psql "<connection-string-externe>" -c "CREATE DATABASE gpe_mouvements;"
   ```
   ⚠️ Non vérifié : le compte PostgreSQL Render autorise-t-il `CREATE DATABASE` ?
   Si non → plan B : 3 schémas ou 3 instances (coût) — à trancher avant.
6. Exécuter les migrations **une seule fois par service** (depuis le Shell
   Render ou une tâche manuelle — ne PAS les mettre dans le start command) :
   ```
   php artisan migrate --force
   ```
   dans chaque service : `gpe-auth`, `gpe-patrimoine`, `gpe-mouvements`.
7. Seeder les données **fictives** (seeders existants, `ADMIN_INITIAL_PASSWORD`
   avec un mot de passe de démonstration — jamais le mot de passe réel).
8. Vérifier `https://gpe-web.onrender.com` : login, API, upload/download
   justificatif vers R2.

## 6. Variables d'environnement

| Variable | Services | Valeur |
|---|---|---|
| `APP_ENV` / `APP_DEBUG` | tous | `production` / `false` (**obligatoire**) |
| `APP_KEY` | tous | `php artisan key:generate` local → reporter (distinct par app ou commun) |
| `JWT_SECRET` | tous | **Identique sur les 4 services** (signature JWT partagée) |
| `APP_URL` | tous | URL publique `https://<service>.onrender.com` |
| `AUTH_URL` / `PATRIMOINE_URL` / `MOUVEMENTS_URL` | web, patrimoine, mouvements | URLs publiques HTTPS des APIs |
| `DB_*` | 3 APIs | injectées par `fromDatabase` (hormis `DB_DATABASE` littéral) |
| `FILESYSTEM_DISK` | mouvements | `s3` |
| `AWS_*` | mouvements | credentials R2 + `AWS_ENDPOINT` + `AWS_USE_PATH_STYLE_ENDPOINT=true` |
| `SESSION_DRIVER`/`CACHE_STORE`/`QUEUE_CONNECTION` | tous | `file`/`file`/`sync` (pas de Redis/DB requis) |

## 7. Sécurité — points de vigilance démo

- APIs publiques : JWT + vérification compte actif (F6) + rôles/périmètres
  (F1–F5) restent actifs — **ne rien désactiver**.
- `APP_DEBUG=false` partout ; `LOG_CHANNEL=stderr` (logs Render).
- CORS : `CORS_ALLOWED_ORIGINS` non défini → same-origin uniquement ; les
  appels inter-services sont **server-to-server** (pas de CORS concerné).
- Rate limiting : `throttle:login` déjà présent sur `/login` ; pas de
  rate limiting global sur les APIs → documenter comme risque résiduel.
- HTTPS : géré par Render (TLS automatique sur `*.onrender.com`).
- Justificatifs : bucket R2 **privé**, accès uniquement via
  `DocumentController@download` (JWT + périmètre). Jamais d'URL publique.

## 8. Sauvegarde et retour arrière

- **Code** : `git checkout feature/microservices` — la branche n'est jamais
  fusionnée. Supprimer `demo-render` ne touche rien à l'original.
- **Base de démo** : `pg_dump` via la connexion externe Render avant toute
  expiration à 30 jours ; restauration = recréer l'instance + `psql < dump`.
- **Justificatifs démo** : objets R2 (téléchargement groupé possible).
- **Suppression Render** : supprimer le workspace Render + bucket R2 —
  aucune incidence sur le dépôt ni les données locales.

## 9. Plan de tests (données fictives uniquement)

Login/logout, JWT expiré/invalide, rôles (admin/gestionnaire/lecteur),
périmètres de lecture/écriture, CRUD bien, affectation, transfert
(anti auto-validation F4), maintenance, inventaire, notifications,
journal d'audit, exports PDF/Excel, upload/download justificatif R2
(+ test d'accès anonyme → 401/403), cold start (mesurer le délai réel),
comportement après spin-down.

## 10. Problèmes techniques non résolus

1. **Carte Render** : exigence possible non vérifiable sans créer un compte.
2. **`CREATE DATABASE`** sur PostgreSQL Render gratuit : permission non confirmée.
3. **Cold starts en cascade** : 4 services indépendants → latence imprévisible.
4. **Expiration PG 30 jours** : démo non durable sans export/renouvellement.
5. **Rate limiting global** absent sur les APIs publiques.
6. **750 h/mois** : risque de suspension si la démo est sollicitée en continu.
