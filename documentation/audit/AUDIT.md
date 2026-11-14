# Audit du projet GPE — Rapport initial

> Date : 14 novembre 2026 · Branche de référence : `design/heritage-modernite`
> Branche d'intégration : `integration/gpe-togo`

## 1. État du dépôt

- Dépôt Git : `GPE/` (remote GitHub `rovana-0412/GPE`)
- Application Laravel 12 / PHP 8.2 / PostgreSQL 16 / Blade / Vite dans `application/`
- Branche `design/heritage-modernite` : 7 commits (état initial + identité visuelle « Héritage & Modernité »)
- Base de démonstration : 6 biens, 10 catégories, 4 ministères, 9 services, 1 utilisateur admin, 5 rôles

## 2. Ce qui fonctionne

| Domaine | État |
|---|---|
| Authentification | Connexion / déconnexion fonctionnelles, routes protégées par le middleware `auth` |
| Tableau de bord | Identité « Héritage & Modernité » complète, données réelles PostgreSQL, responsive, états vides |
| Design system | `resources/css/gpe/` : tokens, base, layout, composants, dashboard ; polices Fraunces/Inter auto-hébergées |
| Composants Blade | `logo`, `icon` (12 icônes), `nav-item` (état « Bientôt »), `empty-state` |
| Schéma BDD | 12 tables cohérentes avec relations |
| Modèles | 10 modèles Eloquent avec relations correctes |
| Seeders | 5 rôles conformes à la mission, catégories immobilier/mobilier, ministères et services de démonstration |

## 3. Ce qui est incomplet

- Aucun CRUD métier : seuls `AuthController` et `DashboardController` existent.
- Page de connexion non redesignée (prévue dans la nouvelle identité visuelle togolaise).
- Aucune gestion de permissions : `User::role()` existe mais aucun Gate/Policy/middleware de rôle.
- `biens` : manque nature, source de financement, ministère gestionnaire, documents, photos, localisation structurée.
- Tables manquantes : notifications, documents justificatifs, localisations (régions/préfectures/communes).
- `historique_operations` : table prête (ancienne/nouvelle valeur JSON, IP) mais jamais alimentée.
- Statuts `biens.etat` / `biens.statut` : aucune logique métier ne les fait évoluer.
- Exports PDF/Excel : non implémentés.
- Tests : uniquement les tests d'exemple Laravel.

## 4. Anomalies et risques identifiés

| # | Anomalie / Risque | Traitement |
|---|---|---|
| 1 | `phpunit.xml` utilisait la base de démonstration `gpe_db` | Corrigé : base dédiée `gpe_test` |
| 2 | Fuseau `UTC`, locale `en`, `APP_NAME=Laravel` | Corrigé : `Africa/Lome`, `fr`, `GPE` |
| 3 | Conflit port 5432 : PostgreSQL natif Windows vs conteneur Docker | `docker-compose.override.yml` publie le conteneur sur 5433 ; `.env` sur `DB_PORT=5433` |
| 4 | `.env.example` obsolète (sqlite, Laravel) | Réécrit : pgsql, GPE, fr, sans secrets |
| 5 | `APP_DEBUG=true` | Acceptable en local ; à passer à `false` en démo/déploiement |
| 6 | `welcome.blade.php`, `app.css`/`app.js` (Tailwind) hérités et inutilisés | À nettoyer lors de la branche design |
| 7 | 9 services en base (8 annoncés précédemment) | Donnée de démo cohérente en interne |
| 8 | Dossiers `documentation/` vides | À remplir au fil des branches |

## 5. Corrections Phase 1 (réalisées)

- Branche `integration/gpe-togo` créée depuis `design/heritage-modernite`.
- `docker-compose.override.yml` recréé (mapping `5433:5432`) ; conteneur recréé, ports vérifiés, persistance confirmée après `restart`.
- Base `gpe_test` créée dans le conteneur ; `phpunit.xml` pointe dessus (identifiants hérités du `.env`, aucun secret versionné).
- `php artisan test` : 2 tests passent, `gpe_db` intacte (1 utilisateur, 6 biens).
- `config/app.php` : `timezone = env('APP_TIMEZONE', 'Africa/Lome')`, locales `fr`.
- `config/gpe.php` créé : devise `XOF` / `FCFA`, formats de nombres, mentions institutionnelles.
- `.env` : `APP_NAME=GPE`, `APP_LOCALE=fr`, `APP_FALLBACK_LOCALE=fr`, `APP_FAKER_LOCALE=fr_FR`, `APP_TIMEZONE=Africa/Lome`.
- `.env.example` réécrit sans secrets.
- `tests/Feature/ExampleTest.php` corrigé (l'accueil redirige désormais les invités).

## 6. Plan de branches

```
master (intouchée)
 └── integration/gpe-togo
      ├── feature/design-togo           Identité visuelle togolaise (vert/or/rouge)
      ├── feature/authentification      Rôles, permissions, mot de passe, redesign login
      ├── feature/dashboard             Statistiques étendues + filtres
      ├── feature/gestion-biens         CRUD biens, fiche, historique
      ├── feature/categories            CRUD catégories
      ├── feature/ministeres-services   CRUD ministères et services
      ├── feature/affectations          Affectations + règles métier
      ├── feature/transferts            Workflow de transfert
      ├── feature/maintenances          Maintenance + coûts FCFA
      ├── feature/utilisateurs-roles    Comptes et rôles
      ├── feature/historique            Journal de traçabilité
      ├── feature/rapports              Inventaires + exports
      ├── feature/notifications         Alertes
      ├── feature/tests-securite        Suite de tests + durcissement
      └── feature/documentation         README, guides, sauvegarde
```

Ordre : design → authentification → dashboard → biens/catégories → ministères/services →
affectations/transferts → maintenance → utilisateurs/historique → rapports/notifications →
tests → documentation. Chaque branche part de `integration/gpe-togo`, est testée, puis fusionnée.

## 7. Risques restants

- Exports PDF/Excel : nouvelles dépendances à valider (`barryvdh/laravel-dompdf`, `maatwebsite/excel`).
- Permissions fines par rôle : matrice proposée dans `PERMISSIONS.md`, implémentation via Gates/Policies à valider.
- Ajouts de colonnes sur `biens` : migrations additives uniquement.
- Règles métier transferts/affectations : statuts à cadrer avant implémentation.
