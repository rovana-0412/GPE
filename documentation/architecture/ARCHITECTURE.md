# Architecture technique — GPE Microservices

## Vue d'ensemble

L'application GPE utilise une architecture microservices avec une passerelle API Nginx, quatre services Laravel 12 et une base PostgreSQL 16 avec séparation des données par service.

## Services

### 1. Web (Interface)

- **Technologie** : Laravel 12 Blade
- **Port interne** : 8000
- **Base de données** : Aucune (communique avec les services via API)
- **Responsabilités** :
  - Affichage des pages (login, dashboard, CRUD)
  - Stockage du JWT en session
  - Appels HTTP vers les services API
  - Formatage des montants en FCFA

### 2. Auth-service

- **Technologie** : Laravel 12 API-only
- **Port interne** : 8000
- **Base de données** : `gpe_auth`
- **Tables** : `roles`, `users`
- **Responsabilités** :
  - Authentification (`POST /api/login`)
  - Émission de JWT HS256 (durée 2h)
  - Vérification du token (`GET /api/me`)
  - Gestion des rôles (5 rôles prédéfinis)

**Payload JWT :**
```json
{
  "sub": 1,
  "role": "administrateur-general",
  "role_id": 1,
  "service_id": null,
  "iat": 1699999999,
  "exp": 1700007199
}
```

### 3. Patrimoine-service

- **Technologie** : Laravel 12 API-only
- **Port interne** : 8000
- **Base de données** : `gpe_patrimoine`
- **Tables** : `ministeres`, `services`, `categories`, `biens`
- **Responsabilités** :
  - CRUD biens (avec SoftDeletes)
  - CRUD catégories
  - CRUD ministères
  - CRUD services
  - Statistiques du patrimoine
  - Validation JWT et rôles

**Contrôle d'accès :**
- Lecture : tous les rôles authentifiés
- Écriture : gestionnaire du patrimoine ou administrateur général

**API justificatifs :**
- `GET /api/documents?cible_type={type}&cible_id={id}` : consulter les métadonnées
- `POST /api/documents` : téléverser une pièce en multipart
- `GET /api/documents/{cibleType}/{cibleId}/{document}/telecharger` : télécharger après vérification du rattachement
- `DELETE /api/documents/{cibleType}/{cibleId}/{document}` : supprimer après vérification du rattachement et du rôle

### 4. Mouvements-service

- **Technologie** : Laravel 12 API-only
- **Port interne** : 8000
- **Base de données** : `gpe_mouvements`
- **Tables** : `affectations`, `transferts`, `maintenances`, `historique_operations`, `documents_justificatifs`
- **Responsabilités** :
  - CRUD affectations
  - Workflow transferts (brouillon → en_attente → approuvé → effectué)
  - CRUD maintenances
  - Historique des opérations
  - Métadonnées et API des documents justificatifs, avec fichiers dans un stockage privé persistant
  - Vérification existence des biens via patrimoine-service

**Workflow transfert :**
1. Demande de transfert (statut `brouillon`)
2. Soumission (`en_attente`)
3. Validation par responsable (`approuvé`)
4. Mise à jour du bien via patrimoine-service
5. Passage à `effectué` ou retour à `approuvé` en cas d'échec

## Communications inter-services

### Authentification

```
Web → POST /api/login → auth-service
Web ← JWT token ← auth-service
Web stocke JWT en session
```

### Appels métier

```
Web → GET /api/patrimoine/biens (header: Authorization: Bearer JWT) → patrimoine-service
Web ← JSON biens ← patrimoine-service
```

### Validation de bien

```
mouvements-service → GET /api/patrimoine/biens/{id} → patrimoine-service
mouvements-service ← JSON bien ← patrimoine-service
```

## Configuration Nginx

Le fichier `docker/gateway/nginx.conf` route les requêtes :

- `/api/auth/*` → `auth-service:8000`
- `/api/patrimoine/*` → `patrimoine-service:8000`
- `/api/mouvements/*` → `mouvements-service:8000`
- `/api/documents*` → API documents du `mouvements-service`
- `/` → `web:8000`

Les services Laravel écoutent tous sur le port interne `8000`. PostgreSQL écoute sur `5432` dans le réseau Docker et sur `5433` côté hôte par défaut.

## Base de données

### Séparation des données

Chaque service a sa propre base de données PostgreSQL :
- `gpe_auth` : utilisateurs et rôles
- `gpe_patrimoine` : patrimoine
- `gpe_mouvements` : mouvements
- `gpe_test` : tests d'intégration

Les documents référencent un bien ou une opération par `cible_type` et `cible_id` sans clé étrangère inter-service. Les fichiers sont conservés dans le volume Docker privé `mouvements_documents`, monté sur `storage/app/private`.

### Absence de clés étrangères inter-services

Les relations entre services sont gérées par le code PHP (colonnes `role_id`, `service_id`, `bien_id` stockées comme entiers indexés, sans contrainte FK).

## Sécurité

### JWT

- Algorithme : HS256
- Secret : `JWT_SECRET` (variable d'environnement)
- Durée : 2 heures
- Middleware : `jwt.auth` (vérifie signature et payload)

### Rôles

- Administrateur général : tous les droits
- Gestionnaire du patrimoine : lecture + écriture
- Responsable ministériel : lecture limité à son ministère
- Agent gestionnaire : lecture + écriture limité
- Auditeur : lecture seule

## Contraintes et limites

1. **Pas de transaction distribuée** : chaque opération reste dans un seul service
2. **Éventuelle incohérence** : si un service échoue après qu'un autre a réussi, un état incohérent peut exister temporairement
3. **Pas de refresh token** : les utilisateurs doivent se reconnecter après 2h
4. **Démo vs production** : en démo, `artisan serve` peut être utilisé ; en production, PHP-FPM est recommandé

## Tests

Les tests d'intégration utilisent PostgreSQL `gpe_test` (pas SQLite) :
- `phpunit.xml` configuré pour PostgreSQL
- Base `gpe_test` créée dans `init.sql`
- Tests mockent les appels inter-services avec `Http::fake()`
