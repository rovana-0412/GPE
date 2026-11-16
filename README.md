# GPE — Gestion du Patrimoine de l'État

Plateforme nationale togolaise de gestion du patrimoine public en architecture microservices.

## 📋 Table des matières

- [Présentation](#présentation)
- [Architecture](#architecture)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Lancement](#lancement)
- [Tests](#tests)
- [Comptes de démonstration](#comptes-de-démonstration)
- [Documentation](#documentation)

## Présentation

GPE est une plateforme permettant à l'État togolais de centraliser, suivre, gérer et contrôler l'ensemble de son patrimoine public, incluant :

- Terrains et réserves foncières
- Bâtiments administratifs
- Établissements scolaires
- Centres de santé et hôpitaux
- Véhicules administratifs
- Équipements informatiques
- Meubles et matériels de bureau
- Équipements techniques

**Technologies :**
- Laravel 12 / PHP 8.2
- PostgreSQL 16
- Architecture microservices REST
- Docker & Docker Compose
- Nginx (API Gateway)
- Blade (interface web)

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Nginx (Gateway)                        │
│                      Port 8080 → 80                           │
└────────────────────────┬────────────────────────────────────┘
                         │
            ┌────────────┼────────────┬──────────────┐
            │            │            │              │
    ┌───────▼──────┐ ┌───▼──────┐ ┌───▼──────┐ ┌───▼──────┐
    │  Web (Blade) │ │  Auth    │ │Patrimoine│ │Mouvements│
    │   :8000      │ │ :8000    │ │  :8000   │ │  :8000   │
    └──────────────┘ └──────────┘ └──────────┘ └──────────┘
                            │            │            │
                            └────────────┼────────────┘
                                         │
                                 ┌───────▼────────┐
                                 │  PostgreSQL    │
                                 │    :5432       │
                                 └────────────────┘
```

**Services :**
- **web** : Interface Laravel Blade, sans base de données
- **auth-service** : Authentification, gestion des utilisateurs et rôles
- **patrimoine-service** : Biens, catégories, ministères, services
- **mouvements-service** : Affectations, transferts, maintenances, historique et justificatifs (fichiers privés)

**Bases de données :**
- `gpe_auth` : Utilisateurs et rôles
- `gpe_patrimoine` : Patrimoine
- `gpe_mouvements` : Mouvements
- `gpe_test` : Tests d'intégration

Les métadonnées des justificatifs sont conservées dans `gpe_mouvements`. Les fichiers sont stockés hors du répertoire public dans le volume Docker `mouvements_documents`.

## Prérequis

- Docker Desktop
- Docker Compose
- Navigateur web moderne

## Installation

1. Cloner le dépôt :
```bash
git clone https://github.com/rovana-0412/GPE.git
cd GPE
```

2. Configurer les variables d'environnement :
```bash
cp .env.example .env
```

3. Renseigner les clés requises dans `.env` :
```
APP_KEY=<clé Laravel générée via php artisan key:generate>
JWT_SECRET=<secret JWT 32+ caractères>
DB_PASSWORD=<mot de passe PostgreSQL>
```

4. Générer la clé Laravel pour le service web :
```bash
cd web
php artisan key:generate
cd ..
```

## Lancement

```bash
docker compose up --build
```

L'application est accessible sur **http://localhost:8080**
PostgreSQL écoute sur le port hôte `5433` par défaut (port du conteneur : `5432`).

## Tests

Tests d'intégration sur PostgreSQL `gpe_test` (Docker Compose doit être démarré et `.env` renseigné) :

```bash
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\test.ps1
```

## Comptes de démonstration

- **Email** : `admin@gpe.local`
- **Mot de passe** : `GPE_Admin_2026!`
- **Rôle** : Administrateur général

## Documentation

- [Architecture technique](documentation/architecture/)
- [Guide utilisateur](documentation/guide-utilisateur/)
- [Diagrammes UML](documentation/uml/)
- [Procédure de déploiement](documentation/deploiement/)
