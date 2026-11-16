# Microservices GPE — Avancement final

> Branche : `feature/microservices`
> Objectif : plateforme GPE microservices exploitable sur http://localhost:8080
> Dernière mise à jour : après validation des suites du 17 novembre 2026

## Mise à jour du chantier — 16 novembre 2026

### Reprise — accès aux justificatifs, validations et audit
- ✅ Les API de liste, métadonnées et téléchargement appliquent le périmètre du justificatif : créateur, agents du service et responsables du ministère concernés; administrateurs, gestionnaires et auditeurs ont l'accès de lecture défini par leur rôle. Les suppressions restent contrôlées séparément.
- ✅ Les tests couvrent les refus croisés entre utilisateurs/services et la non-divulgation des métadonnées et du fichier.
- ✅ La procédure de sauvegarde couvre les trois bases de production et le volume des justificatifs; elle inclut la vérification des archives et empreintes, ainsi qu'une restauration de test isolée sans écrasement de production.
- ✅ Le rapprochement d'inventaire liste les biens théoriques actifs non vérifiés, avec pagination et totaux pour absents, détériorés et écarts.
- ✅ Les notifications couvrent les décisions de transfert, les changements de maintenance, les acquisitions, les sorties et les transitions d'inventaire; l'échec d'une notification interservice est journalisé sans annuler l'opération métier validée.
- ✅ Les paramètres institutionnels sont persistés dans auth-service, consultables par les utilisateurs authentifiés et modifiables uniquement par l'administrateur général; la Web app les utilise.
- ✅ Les connexions réussies et échouées sont maintenant consignées dans l'audit Auth sans écrire de mot de passe ni de donnée d'identification dans les valeurs d'audit.
- ✅ PostgreSQL de validation répond sur le port configuré `5434` (et `5432` dans le conteneur). Les tests ont utilisé les bases isolées `gpe_validation_final_*`; aucune base applicative n'a été réinitialisée ni modifiée.
- ⚠️ Le script `scripts/test.ps1` n'a pas été exécuté : il crée des bases de test supplémentaires. Les suites ont été lancées directement sur les bases isolées déjà créées.
- ⚠️ Le backfill des périmètres documentaires n'a pas été exécuté sur les données existantes; les documents historiques nécessitent cette commande de mise à jour additive avant que les nouveaux droits par ministère/service s'appliquent à ces lignes.

### Modules ajoutés ou complétés
- ✅ **Administration des comptes** : API CRUD réservée à l’administrateur général, activation/désactivation, attribution de rôle/service, refus de l’auto-élévation et protection du dernier administrateur actif. Les comptes désactivés ne peuvent plus se connecter; la connexion est limitée à cinq tentatives par minute.
- ✅ **Acquisitions** : fournisseur, coût, date, mode et références des pièces; transitions brouillon → en attente → réceptionnée → validée/rejetée. La validation crée le bien dans la même transaction locale. Les documents peuvent référencer une acquisition sans FK inter-service.
- ✅ **Sorties et réformes** : demande, approbation/rejet puis exécution; l’exécution archive le bien comme sorti/inactif sans effacer son historique.
- ✅ **Inventaire physique** : campagnes avec périmètre de service, constats présent/absent/détérioré, localisation et observations. Transitions contrôlées, doublons refusés, périmètre de service vérifié via patrimoine-service, modifications historisées et rapport chiffré. L’interface Web permet de créer/rechercher une campagne, rechercher des biens par référence/désignation, enregistrer et corriger des constats, puis soumettre et valider.
- ✅ **Filtre d’identifiants de biens** dans patrimoine-service pour charger les références associées aux constats sans appel API par ligne.
- ✅ **Notifications personnelles** : notifications transactionnelles au demandeur lors d’une décision de transfert et au déclarant lorsqu’un autre utilisateur met à jour une maintenance. Consultation filtrée, pagination et lecture réservée au propriétaire; interface Web ajoutée.
- ✅ **Audit multi-service** : opérations des mouvements dans `historique_operations`, journal local du patrimoine (biens, références, acquisitions et sorties) et audit des comptes (création, modification, activation/désactivation). L’écran Historique permet de sélectionner le service source; Patrimoine et Comptes sont réservés aux administrateurs et auditeurs. Aucun mot de passe n’est écrit dans l’audit.
- ✅ **Rapports PDF et Excel** : page de synthèse basée sur les statistiques réelles des API, export PDF avec DOMPDF et classeur `.xlsx` avec Laravel Excel. L’image Web installe GD, nécessaire à PhpSpreadsheet.
- ✅ **Recherche globale** : biens, catégories, ministères, services, affectations, transferts, maintenances, justificatifs et opérations; recherche des utilisateurs visible uniquement pour l’administrateur général. Les filtres sont relayés aux API propriétaires.

### Dernière validation des suites
- Auth-service : **13 tests, 64 assertions — réussis**.
- Patrimoine-service : **12 tests, 95 assertions — réussis**.
- Mouvements-service : **25 tests, 194 assertions — réussis**.
- Web : **24 tests, 148 assertions — réussis**, avec GD chargé explicitement pour les exports.
- Exécution sur les bases PostgreSQL isolées `gpe_validation_final_auth`, `gpe_validation_final_patrimoine`, `gpe_validation_final_mouvements` et `gpe_validation_final_web`; `gpe_test` et les bases applicatives n’ont pas été réinitialisées. Aucun déploiement, redémarrage de conteneur, commit ou push.
- Le contrôle HTTP local de `/connexion` a répondu 200 avec les deux cookies de session attendus.
- La construction Docker n'a pas été retentée lors de cette reprise; le dernier essai connu était bloqué par le DNS vers Docker Hub. Aucun conteneur n'a été démarré ou redémarré.

Ces suites ont été exécutées sur les bases isolées de validation disponibles; seuls Auth a été relancé après l'ajout du test d'audit des connexions.

### Limites restant à traiter
- La séparation entre initiateur et validateur, ainsi que l'autorisation d'écriture fine par ministère/service, restent à compléter et tester sur tous les processus métier.
- Les tests exécutés sont des tests de service et d'interface avec fakes HTTP; le parcours E2E complet interservices et par rôle n'a pas été démontré.
- La normalisation 1FN/2FN/3FN de chaque base et la comparaison détaillée des diagrammes UML au code restent à documenter.
- La construction Docker et la vérification de bout en bout de Compose/Nginx restent à refaire lorsque Docker Hub sera accessible; les conteneurs n'ont pas été redémarrés.
- La recherche globale présente jusqu’à dix résultats par famille; chaque résultat renvoie vers l’écran métier correspondant.

## État actuel

### Structure
- `web/` : interface Laravel Blade sans base de données (identité institutionnelle togolaise + tableau de bord patrimonial)
- `services/auth-service/` : Laravel 12 API-only (authentification, JWT HS256, rôles)
- `services/patrimoine-service/` : Laravel 12 API-only (biens, catégories, ministères, services)
- `services/mouvements-service/` : Laravel 12 API-only (affectations, transferts, maintenances, historique, justificatifs)

### ✅ Phase 0 — Nettoyage et base saine
- ✅ Correction de la ligne vide avant `<?php` dans `web/routes/web.php`
- ✅ Suppression des migrations, modèles Eloquent, seeders et factory inutilisés de `web/`
- ✅ `web` ne reçoit aucune variable `DB_*`
- ✅ Suppression de `docker-compose.override.yml.bak` et des vues scaffold
- ✅ Les racines des trois API répondent en JSON

### ✅ Phase 1 — Infrastructure Docker et PostgreSQL
- ✅ `docker/postgres/init.sql` : initialisation des bases `gpe_auth`, `gpe_patrimoine`, `gpe_mouvements`, `gpe_test`
- ✅ `docker-compose.yml` : variables partagées, secrets requis, healthchecks
- ✅ `docker/gateway/nginx.conf` : routage HTTP via la passerelle `8080 → 80`
- ✅ Dockerfiles des trois API en `php:8.2-cli`, écoute sur 8000
- ✅ `.env.example` à la racine sans secret
- ✅ PostgreSQL publié sur 5433 par défaut

### ✅ Phase 2 — Auth-service
- ✅ Migrations : `roles` et `users` (sans FK inter-services)
- ✅ Modèles : `Role`, `User` (colonnes `role_id`, `service_id` conservées)
- ✅ Seeders : `RoleSeeder.php`, `UserSeeder.php` (admin: admin@gpe.local / GPE_Admin_2026!)
- ✅ `composer require firebase/php-jwt`
- ✅ Route `POST /api/login` : vérifie credentials, émet JWT HS256 (2h, payload avec slug de rôle)
- ✅ Route `GET /api/me` : renvoie l'utilisateur connecté
- ✅ Middleware `jwt.auth` : vérifie signature JWT
- ✅ Middleware `role` : vérifie les permissions par rôle
- ✅ Tests PHPUnit sur PostgreSQL `gpe_test`

### ✅ Phase 3 — Web
- ✅ `composer require firebase/php-jwt`
- ✅ AuthController : appeler `auth-service` (`POST http://auth-service/api/login`)
- ✅ Stocker JWT en session PHP
- ✅ DashboardController : appels réels vers services API
- ✅ Refonte institutionnelle de la connexion, du layout, de la navigation et du tableau de bord
- ✅ Refonte des vues métier (biens, catégories, ministères, services, affectations, transferts, maintenances et historique)
- ✅ Styles responsives pour les formulaires, tableaux, fiches et navigation mobile
- ✅ Contrôles d’écriture affichés selon le rôle de session; l’API conserve la responsabilité des permissions
- ✅ Middleware `jwt.auth` pour protéger les routes web
- ✅ Pages reliées : biens, catégories, ministères, services, affectations, transferts, maintenances, historique
- ✅ Tests PHPUnit sur PostgreSQL `gpe_test`

### ✅ Phase 4 — Patrimoine-service
- ✅ Modèles et migrations : ministeres, services, categories, biens (SoftDeletes)
- ✅ Clés étrangères limitées aux relations internes au service
- ✅ Seeders idempotents : 4 ministères, 9 services, 10 catégories, 6 biens
- ✅ API REST CRUD protégée par `jwt.auth` et `role`
- ✅ Validation FormRequest, pagination, filtres, statistiques
- ✅ Vérification JWT par configuration
- ✅ Interface web reliée avec JWT de session
- ✅ Gestion d'erreurs 401 et indisponibilité inter-services
- ✅ Tests PHPUnit sur PostgreSQL `gpe_test`

### ✅ Phase 5 — Mouvements-service
- ✅ Modèles et migrations : affectations, transferts, maintenances, historique_operations, documents_justificatifs
- ✅ API REST CRUD protégée par `jwt.auth` et `role`
- ✅ Workflow transferts : brouillon → en_attente → approuvé → effectué
- ✅ Vérification existence du bien via patrimoine-service
- ✅ Interface web reliée avec JWT de session
- ✅ Tests PHPUnit sur PostgreSQL `gpe_test`

### ✅ Module Documents justificatifs (17 novembre 2026)
- ✅ Téléversement, consultation, téléchargement en flux et suppression réelle de pièces justificatives
- ✅ Rattachement à un bien ou à une affectation, un transfert ou une maintenance; références distantes stockées sans clé étrangère inter-service
- ✅ Types, titre, référence, description et date validés côté Web et API
- ✅ MIME et extension contrôlés, limite serveur de 10 Mo, nom de stockage UUID et nom d’origine nettoyé
- ✅ Fichiers dans `storage/app/private`, volume persistant `mouvements_documents`, jamais dans `public`
- ✅ API protégée par JWT; création et suppression réservées aux rôles autorisés, vérification du rattachement à chaque téléchargement/suppression
- ✅ Événements `document.justificatif.ajoute` et `document.justificatif.supprime` inscrits dans `historique_operations`
- ✅ Section intégrée à la fiche d’un bien et liens depuis les listes d’affectations, transferts et maintenances
- ✅ Limites PHP/Nginx alignées sur la taille maximale; endpoint gateway `/api/documents`
- ✅ Tests couvrant stockage privé, association aux biens et maintenances, MIME, taille, accès par rôle, téléchargement, suppression et journalisation
- ✅ Migration `documents_justificatifs` appliquée à `gpe_mouvements` sans réinitialiser la base; volume privé Docker créé
- ✅ Résultats finaux : mouvements 14 tests / 95 assertions; web 16 / 73; total des quatre suites 44 / 226

### ✅ Corrections récentes (commits b85cfef et bd1a5b2)
- ✅ Tests sur PostgreSQL `gpe_test` (plus de SQLite)
- ✅ Timezone `Africa/Lome` dans tous les services
- ✅ Locale `fr` dans tous les services
- ✅ Suppression des GRANT codés en dur dans `init.sql`
- ✅ JWT avec slug de rôle cohérent (administrateur-general, gestionnaire-patrimoine, etc.)
- ✅ Middleware de rôle appliqué aux routes sensibles
- ✅ Routes séparées en lecture seule (tous les rôles) et écriture (gestionnaire/admin)

### ✅ Documentation et livrables
- ✅ README.md principal
- ✅ Documentation technique (ARCHITECTURE.md)
- ✅ Guide utilisateur (GUIDE.md)
- ✅ Procédure de déploiement (DEPLOIEMENT.md)
- ✅ Diagrammes PlantUML : architecture, cas d'utilisation, séquence transfert, activité bien, composants, déploiement

### ⚠️ Limites connues
- **Pas de transaction distribuée** : chaque opération reste dans un seul service
- **Éventuelle incohérence** : si un service échoue après qu'un autre a réussi
- **Pas de refresh token** : JWT durée fixe 2h

### ✅ Correctifs de configuration des tests (16 novembre 2026)
- ✅ Les tests hôtes utilisent maintenant le port PostgreSQL configurable de `.env` au lieu d'imposer 5433
- ✅ Suppression des identifiants PostgreSQL codés en dur dans les configurations PHPUnit
- ✅ Configuration d'une APP_KEY dédiée aux tests patrimoine, mouvements et web
- ✅ Correction des jetons de test utilisant le rôle `admin` non reconnu par le middleware
- ✅ Ajout de `PATCH /api/maintenances/{id}` pour aligner l'API sur l'appel partiel du web, en conservant `PUT`
- ✅ Ajout de `scripts/test.ps1` pour exécuter séquentiellement les quatre suites PHPUnit sur `gpe_test`
- ✅ Résultats vérifiés : auth 6 tests / 22 assertions, patrimoine 7 / 34, mouvements 9 / 49, web 11 / 43 (toutes réussies)

## Commande de lancement

```bash
docker compose up --build
```

Accès : http://localhost:8080

## Comptes de test

- **Email** : `admin@gpe.local`
- **Mot de passe** : `GPE_Admin_2026!`
- **Rôle** : Administrateur général

## État des tests

- **Auth-service** : PHPUnit sur PostgreSQL `gpe_test`
- **Patrimoine-service** : PHPUnit sur PostgreSQL `gpe_test`
- **Mouvements-service** : PHPUnit sur PostgreSQL `gpe_test` (incluant les tests documents)
- **Web** : PHPUnit sur PostgreSQL `gpe_test`

## Refonte de l’interface

- Connexion validée dans le navigateur avec le compte administrateur local.
- Écran de connexion compact : à 1366×768 et 768×768, la page tient sans défilement; sur mobile 390×844, champs et bouton restent visibles au premier écran.
- Emblème réduit et hiérarchie GPE/République renforcée; ajout de l’affichage du mot de passe, du chargement de connexion et du message de succès après authentification.
- Le tableau de bord et les huit pages métier répondent en HTTP 200 après authentification.
- Affichage bureau et mobile vérifié; les tableaux restent défilables horizontalement dans leur conteneur sans débordement horizontal de la page.
- Normalisation des rôles accentués en slugs compatibles avec les permissions API; `role_id` et `service_id` sont maintenant persistés lors de l’ensemencement des utilisateurs.
- Connexion, visibilité du mot de passe, accès administrateur au formulaire de création et déconnexion vérifiés dans le navigateur.
- Validation PHPUnit : auth 7 tests / 24 assertions, patrimoine 7 / 34, mouvements 9 / 49, web 13 / 56.
- Compilations Vite et Docker réussies. Les tableaux restent défilables dans leur conteneur sans débordement de page.
- L’interface Web d’administration des utilisateurs est disponible; l’attribution de permissions reste limitée aux rôles existants.
- Les images Docker ont été construites; la migration et le volume privé sont en place. Les conteneurs applicatifs en cours n’ont pas été redémarrés, donc la nouvelle API et l’interface ne sont pas encore servies par la stack courante; aucun déploiement/redémarrage n’a été effectué.

## Commits récents

```
bd1a5b2 Permissions: middleware de rôle, routes protégées par rôle, JWT avec slug de rôle cohérent
b85cfef Tests et config: tests sur PostgreSQL gpe_test, timezone Africa/Lome, locale fr, suppression GRANT en dur
eb1903a Mouvements: API (affectations, transferts, maintenances, historique), tests et écrans web
d96a7e4 Docs: valider la phase patrimoine
16c7e3e Web: relier les écrans au patrimoine
405c13f Patrimoine: créer l’API du patrimoine
[... commits infrastructure et auth ...]
```

## Prochaines étapes

- Compléter les autorisations d'écriture par ministère/service et séparer initiateur et validateur sur les processus métier.
- Construire et exécuter des scénarios E2E réels par rôle sur une stack correspondant au code courant; les suites PHPUnit existantes n'en tiennent pas lieu.
- Examiner séparément le schéma de chacune des bases pour documenter 1FN/2FN/3FN et les anomalies effectivement observées.
- Comparer les diagrammes PlantUML (cas d'utilisation, classes, séquences, composants et déploiement) au code actuel.
- Revalider `docker compose build`, la configuration Compose et Nginx lorsque Docker Hub sera accessible. Ne pas redémarrer les conteneurs sans autorisation.
