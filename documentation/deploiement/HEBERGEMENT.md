# Hébergement gratuit — étude et procédure de déploiement

> État : étude préparatoire — aucun compte créé, aucun déploiement effectué.
> Architecture cible : 6 conteneurs Docker Compose (web, 3 API, PostgreSQL, Nginx),
> un volume de données PostgreSQL, un volume privé de justificatifs.

## 1. Contraintes de la plateforme GPE

- Quatre applications Laravel 12 / PHP 8.2 + PostgreSQL 16 + Nginx.
- Communications internes HTTP entre conteneurs (réseau Docker privé).
- Persistance obligatoire : 3 bases (`gpe_auth`, `gpe_patrimoine`, `gpe_mouvements`)
  et le volume `mouvements_documents` (justificatifs).
- HTTPS + nom de domaine ou sous-domaine.
- Secrets par variables d'environnement (`APP_KEY`, `JWT_SECRET`, `DB_PASSWORD`, …).
- Services internes jamais exposés publiquement (seul le port 80/443 du gateway).

## 2. Comparatif des offres gratuites (novembre 2026)

| Solution | Docker Compose complet | PostgreSQL persistant | Volume fichiers | HTTPS/domaine | Sans carte | Limites notables |
|---|---|---|---|---|---|---|
| **Oracle Cloud Always Free** (VM ARM `VM.Standard.A1.Flex`) | ✅ VM complète | ✅ conteneur PG + volumes | ✅ volumes Docker sur disque 200 Go | ✅ via Nginx/Caddy + domaine gratuit (DuckDNS) ou domaine acheté | ⚠️ inscription généralement carte requise (validation, non débitée) | 2 OCPU / 12 Go RAM max, capacité ARM parfois indisponible (« out of host capacity »), données restent chez Oracle |
| **Render** | ⚠️ un service par déploiement, pas de compose multi-conteneurs | ⚠️ PG managé gratuit **expire après 30 jours** | ❌ disques persistants payants uniquement ; FS éphémère | ✅ TLS + domaine | ✅ | Services gratuits endormis après 15 min (démarrage ~1 min), 750 h/mois, redémarrages possibles, pas de volume pour justificatifs |
| **Koyeb** | ⚠️ services séparés, pas de compose | ⚠️ PG serverless gratuit : **5 h/mois actives, 1 Go max** | ⚠️ 2 Go SSD sur instance free | ✅ | ✅ | 1 instance free 512 Mo / 0,1 vCPU (insuffisant pour 4 apps + PG), workers non supportés en gratuit |
| **Fly.io** | ⚠️ `fly deploy` par app, possible mais pas compose | ⚠️ volumes 3 Go gratuits (legacy) | ✅ volumes | ✅ | ✅ | Plus de plan gratuit depuis oct. 2024 ; facturation à l'usage (~2 $/mois minimum par machine allumée), 6 machines pour la stack ≈ 13 $/mois |
| **Railway** | ⚠️ déploiement par service | ⚠️ PG managé | ✅ volumes | ✅ | ✅ | Plus d'offre gratuite pérenne (crédit de démarrage uniquement) |
| **Google Cloud e2-micro** | ✅ VM | ✅ | ✅ 30 Go boot | ✅ | ❌ carte requise | 1 Go RAM insuffisant pour la stack complète ; gratuite seulement dans certaines régions US |
| **AWS / Azure free tier** | ✅ VM | ✅ | ✅ | ✅ | ❌ carte requise | Instances gratuites limitées (12 mois AWS, B1s Azure) puis facturation |

### Verdict du comparatif

- **Aucune plateforme « serverless gratuite » (Render, Koyeb, Fly, Railway)
  n'héberge gratuitement l'ensemble de la stack** : elles ne supportent pas
  Docker Compose multi-conteneurs avec volumes persistants, et leurs bases
  gratuites expirent ou sont limitées à quelques heures.
- **La seule option réellement gratuite et compatible** est une **VM
  (Oracle Cloud Always Free)** ou une solution équivalente : elle exécute
  `docker compose` tel quel, conserve les volumes, et fournit 200 Go de
  stockage + 10 To de sortie mensuelle.

## 3. Solution recommandée : Oracle Cloud Always Free

### Architecture cible

```
Internet ──HTTPS──> VM Oracle (Ubuntu 22.04/24.04 ARM)
                      └── Docker Compose GPE (réseau interne gpe-net)
                            ├── gpe-gateway   :80/:443 (seul port exposé)
                            ├── gpe-web       :8000 (interne)
                            ├── gpe-auth      :8000 (interne)
                            ├── gpe-patrimoine:8000 (interne)
                            ├── gpe-mouvements:8000 (interne)
                            ├── gpe-postgres  :5432 (interne, jamais publié)
                            └── volumes : postgres_data, mouvements_documents
```

### Adaptations nécessaires

1. **HTTPS** : ajouter un conteneur reverse-proxy TLS (Caddy ou Nginx + Certbot)
   devant le gateway, ou terminer TLS dans le gateway avec des certificats
   Let's Encrypt. Le gateway actuel écoute en HTTP :80.
2. **Domaine** : sous-domaine gratuit via DuckDNS (`gpe-votrenom.duckdns.org`)
   ou domaine acheté (~10 €/an). Les certificats Let's Encrypt fonctionnent
   avec DuckDNS.
3. **Variables de production** dans `.env` : `APP_DEBUG=false`,
   `APP_ENV=production`, nouveaux `APP_KEY`/`JWT_SECRET`/`DB_PASSWORD`
   générés, `ADMIN_INITIAL_PASSWORD` pour le premier compte.
4. **Port PostgreSQL** : ne pas publier `5432` sur l'hôte (actuellement
   `${POSTGRES_HOST_PORT:-5433}`) — accès uniquement via le réseau Docker.
5. **Sauvegardes** : `pg_dump` planifié (cron) vers Object Storage Oracle
   (20 Go gratuits) ou stockage local chiffré.
6. **Architecture ARM** : les images `php:8.2-cli`, `postgres:16`,
   `nginx` sont multi-arch — le build fonctionne sur Ampere A1.

### Limites à assumer

- « Out of host capacity » fréquent sur les shapes ARM gratuites :
  réessayer dans un autre domaine de disponibilité ou surveiller.
- 2 OCPU / 12 Go RAM suffisent pour la démo (stack ~2-3 Go utilisés).
- L'inscription Oracle demande en général une carte bancaire de
  validation (aucun débit sur les ressources Always Free) — **exception
  à la contrainte « sans carte »** ; sans carte, aucune solution ne couvre
  l'intégralité de la stack gratuitement.
- Wake/sleep : les VMs Oracle restent allumées ; prévoir un arrêt
  planifié si besoin (les données persistent sur les volumes).

## 4. Procédure de déploiement (étape par étape)

Prérequis : compte Oracle Cloud, domaine ou DuckDNS, Docker installé sur la VM.

1. **Provisionner** : instance `VM.Standard.A1.Flex` (2 OCPU / 12 Go),
   Ubuntu 24.04 ARM, volume de boot 50 Go (dans le quota 200 Go).
2. **Ouvrir les ports** : dans le Security List/NSG Oracle, n'autoriser
   que 22 (SSH), 80 et 443 vers la VM.
3. **Installer** : `git`, `docker.io`, `docker compose` plugin.
4. **Récupérer le code** : `git clone` de la branche déployée
   (push GitHub requis au préalable — non autorisé à ce jour).
5. **Configurer `.env`** de production (valeurs générées, jamais commitées) :
   `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`, `JWT_SECRET`,
   `DB_PASSWORD`, `DB_USERNAME=gpe_user`, `ADMIN_INITIAL_PASSWORD`,
   `POSTGRES_HOST_PORT` non publié (commenter le mapping de port).
6. **Construire** : `docker compose build` sur la VM (ou `docker compose up -d --build`).
7. **Migrer explicitement** (jamais automatique) :
   `docker exec gpe-auth-service-1 php artisan migrate --force` puis
   `gpe-patrimoine-service-1`, `gpe-mouvements-service-1`.
8. **Seeder uniquement les références** si bases vides :
   `RoleSeeder`, `MinistereSeeder`, `CategorieSeeder`, `ServiceSeeder`,
   `BienSeeder` — jamais `UserSeeder` sans `ADMIN_INITIAL_PASSWORD`.
9. **HTTPS** : déployer Caddy/Nginx-TLS ou activer les certificats ;
   pointer le domaine vers l'IP publique de la VM.
10. **Vérifier** : healthchecks `docker compose ps`, login admin,
    routes `/api/*`, exports, upload justificatif.

## 5. Sauvegarde et restauration

### Sauvegarde planifiée (cron quotidien sur la VM)

```bash
#!/usr/bin/env bash
# scripts/backup-prod.sh — à adapter les chemins
set -euo pipefail
STAMP=$(date +%F)
DIR=/opt/gpe/backups/$STAMP
mkdir -p "$DIR"
for db in gpe_auth gpe_patrimoine gpe_mouvements; do
  docker exec gpe-postgres-1 pg_dump -U gpe_user -Fc "$db" > "$DIR/$db.dump"
done
docker run --rm -v gpe_mouvements_documents:/data:ro -v "$DIR":/backup alpine \
  tar czf "$DIR/mouvements_documents.tar.gz" -C /data .
# envoi vers Oracle Object Storage (oci os object put) ou rclone
echo "Sauvegarde $STAMP terminée"
```

### Restauration (sur une VM de test ou après incident)

```bash
# 1. Restaurer les bases dans des bases temporaires, vérifier, puis basculer
createdb restore_check && pg_restore -d restore_check gpe_auth.dump
# 2. Volume justificatifs : docker run --rm -v gpe_mouvements_documents:/data \
#      -v $PWD:/backup alpine tar xzf /backup/mouvements_documents.tar.gz -C /data
# 3. Rollback applicatif : git checkout <tag précédent> && docker compose up -d --build
```

### Retour arrière

- **Code** : `git revert`/`checkout` du commit précédent + `docker compose up -d --build`.
- **Base** : restaurer le dump `pg_dump -Fc` du jour J-1 ; les migrations
  additives rendent un retour code/données cohérent si le dump précède la
  migration.
- **JAMAIS** de `migrate:fresh`, `db:wipe` ou suppression de volume en
  production.

## 6. Vérifications encore nécessaires avant déploiement

- [ ] Compte Oracle Cloud créé (validation carte) ou alternative acceptée.
- [ ] Domaine/DuckDNS configuré + certificats TLS fonctionnels.
- [ ] Push de la branche sur GitHub (non autorisé à ce jour).
- [ ] `.env` de production généré sur la VM (jamais commité).
- [ ] Premier administrateur créé via `ADMIN_INITIAL_PASSWORD`.
- [ ] Test de restauration complet depuis un dump de production.
- [ ] Cron de sauvegarde + test de restauration planifié.
- [ ] `APP_DEBUG=false` vérifié (page d'erreur sans stack trace).
- [ ] Monitoring minimal : healthchecks + alerte budget Oracle.
- [ ] Validation des rôles `responsable-ministeriel` / `agent-gestionnaire`
      et de l'inventaire complet avant la démonstration publique.
