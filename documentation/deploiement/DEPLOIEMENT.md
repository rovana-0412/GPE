# Procédure de déploiement — GPE Microservices

## Prérequis de production

- Serveur Linux (Ubuntu 22.04 LTS recommandé)
- Docker 24+ et Docker Compose 2+
- PostgreSQL 16 (ou Docker)
- Nginx (ou Docker)
- PHP 8.2-FPM (ou Docker)
- SSL/TLS (Let's Encrypt recommandé)

## Configuration

### 1. Variables d'environnement

Créer un fichier `.env` à la racine avec les valeurs de production :

```bash
APP_NAME=GPE
APP_ENV=production
APP_DEBUG=false
APP_KEY=<clé Laravel générée>
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR
APP_TIMEZONE=Africa/Lome

JWT_SECRET=<secret JWT 32+ caractères, généré avec openssl rand -base64 32>

DB_USERNAME=gpe_user
DB_PASSWORD=<mot de passe PostgreSQL fort>

POSTGRES_HOST_PORT=5433
```

### 2. Configuration Nginx

En production, remplacer la configuration Docker par une configuration Nginx directe :

```nginx
server {
    listen 80;
    server_name gpe.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name gpe.example.com;

    ssl_certificate /etc/letsencrypt/live/gpe.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/gpe.example.com/privkey.pem;

    # Auth-service
    location /api/auth/ {
        proxy_pass http://localhost:9000/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    # Patrimoine-service
    location /api/patrimoine/ {
        proxy_pass http://localhost:9001/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    # Mouvements-service
    location /api/mouvements/ {
        proxy_pass http://localhost:9002/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    # Web
    location / {
        proxy_pass http://localhost:9003/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

## Déploiement

### Option 1 : Docker Compose (recommandé)

1. Cloner le dépôt :
```bash
git clone https://github.com/rovana-0412/GPE.git
cd GPE
```

2. Configurer `.env` (voir ci-dessus)

3. Générer les clés :
```bash
cd web
php artisan key:generate
cd ../services/auth-service
php artisan key:generate
cd ..
```

4. Lancer :
```bash
docker compose up -d --build
```

5. Vérifier :
```bash
docker compose ps
curl http://localhost:8080/api/up
```

### Option 2 : Déploiement manuel

#### PostgreSQL

```bash
# Créer les bases
sudo -u postgres psql
CREATE DATABASE gpe_auth;
CREATE DATABASE gpe_patrimoine;
CREATE DATABASE gpe_mouvements;
CREATE DATABASE gpe_test;
CREATE USER gpe_user WITH PASSWORD 'mot_de_passe_fort';
GRANT ALL PRIVILEGES ON DATABASE gpe_auth TO gpe_user;
GRANT ALL PRIVILEGES ON DATABASE gpe_patrimoine TO gpe_user;
GRANT ALL PRIVILEGES ON DATABASE gpe_mouvements TO gpe_user;
GRANT ALL PRIVILEGES ON DATABASE gpe_test TO gpe_user;
\q
```

#### Services Laravel

Pour chaque service (web, auth-service, patrimoine-service, mouvements-service) :

```bash
cd <service>
composer install --no-dev
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
```

#### Démarrage des services

```bash
# Auth-service
cd services/auth-service
php-fpm -D
php artisan serve --host=0.0.0.0 --port=9000 --no-reload &

# Patrimoine-service
cd ../patrimoine-service
php-fpm -D
php artisan serve --host=0.0.0.0 --port=9001 --no-reload &

# Mouvements-service
cd ../mouvements-service
php-fpm -D
php artisan serve --host=0.0.0.0 --port=9002 --no-reload &

# Web
cd ../../web
npm run build
php-fpm -D
php artisan serve --host=0.0.0.0 --port=9003 --no-reload &
```

## Sauvegarde et restauration

Les bases et les justificatifs privés forment un ensemble cohérent à sauvegarder. Ne
pas restaurer directement sur une base utilisée par l'application : une restauration
se valide d'abord dans un environnement isolé. Conserver les sauvegardes chiffrées
hors du serveur et limiter leur accès aux personnes autorisées.

### Sauvegarde et vérification

Depuis la racine du dépôt, avec Docker Compose et le fichier `.env` configurés :

```bash
set -eu
backup_dir="backups/$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$backup_dir"

for database in gpe_auth gpe_patrimoine gpe_mouvements; do
  docker compose exec -T postgres sh -lc \
    "pg_dump -U \"\$POSTGRES_USER\" -Fc '$database'" \
    > "$backup_dir/$database.dump"
done

docker compose exec -T mouvements-service \
  tar -czf - -C /var/www/html/storage/app/private . \
  > "$backup_dir/mouvements_documents.tar.gz"

for database in gpe_auth gpe_patrimoine gpe_mouvements; do
  docker compose exec -T postgres pg_restore --list \
    < "$backup_dir/$database.dump" > /dev/null
done
tar -tzf "$backup_dir/mouvements_documents.tar.gz" > /dev/null
sha256sum "$backup_dir"/* > "$backup_dir/SHA256SUMS"
```

La sauvegarde doit inclure les trois bases de production et le contenu du stockage
privé `mouvements_documents`. Vérifier les sommes de contrôle après copie vers le
lieu de conservation :

```bash
cd backups/<horodatage>
sha256sum -c SHA256SUMS
```

### Restauration contrôlée

1. Obtenir l'autorisation du responsable de la plateforme et conserver une copie
   intacte des sauvegardes.
2. Créer un environnement PostgreSQL et un volume de documents isolés de la
   production. Ne pas réutiliser les bases actives.
3. Créer des bases de récupération vides, puis restaurer sans option `--clean` :

```bash
createdb -h <hote-recovery> -U <utilisateur> gpe_restore_auth
createdb -h <hote-recovery> -U <utilisateur> gpe_restore_patrimoine
createdb -h <hote-recovery> -U <utilisateur> gpe_restore_mouvements

pg_restore --exit-on-error --no-owner --no-privileges \
  -h <hote-recovery> -U <utilisateur> -d gpe_restore_auth gpe_auth.dump
pg_restore --exit-on-error --no-owner --no-privileges \
  -h <hote-recovery> -U <utilisateur> -d gpe_restore_patrimoine gpe_patrimoine.dump
pg_restore --exit-on-error --no-owner --no-privileges \
  -h <hote-recovery> -U <utilisateur> -d gpe_restore_mouvements gpe_mouvements.dump
```

4. Extraire les justificatifs dans un répertoire isolé, vérifier le nombre et
   l'intégrité des fichiers, puis tester l'accès aux pièces depuis l'application
   de récupération.
5. Exécuter les contrôles d'intégrité et les parcours métier avant toute décision
   de bascule. Une remise en production nécessite une procédure séparée,
   approuvée et planifiée; aucune commande de cette procédure ne supprime ou
   n'écrase les données de production.

Enregistrer la date, les versions, les sommes de contrôle, le résultat de la
restauration de test et la personne responsable. Définir et tester périodiquement
les objectifs de reprise (RPO/RTO); ils restent à valider avec l'exploitant.
```

## Monitoring

### Logs

```bash
# Logs Docker
docker compose logs -f

# Logs Laravel
cd web
php artisan log:tail
```

### Health checks

```bash
# Services
curl http://localhost:9000/up
curl http://localhost:9001/up
curl http://localhost:9002/up
curl http://localhost:9003/up

# API Gateway
curl http://localhost:8080/api/up
```

## Sécurité

- Changer les mots de passe par défaut
- Utiliser des secrets forts pour `JWT_SECRET` et `APP_KEY`
- Activer HTTPS avec Let's Encrypt
- Configurer un firewall (UFW)
- Limiter l'accès PostgreSQL au localhost
- Mettre à jour régulièrement les dépendances
- Configurer des backups automatiques
