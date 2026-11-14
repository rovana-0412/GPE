Lis .github/copilot-instructions.md et documentation/architecture/AVANCEMENT.md. Enregistre CE message tel quel dans documentation/PLAN-COPILOT.md : tu le relis au début de chaque phase. Travaille phase par phase, dans l'ordre. À la fin de chaque phase : tests verts, un commit par partie (messages en français), mise à jour de AVANCEMENT.md, résumé de ce qui est vérifié et de ce qui ne l'est pas, puis ARRÊTE-TOI et attends que je réponde "continue".

CONTEXTE
Projet scolaire noté (gestion du patrimoine de l'État du Togo), à rendre le 15/11/2026. Brief : micro-services, Laravel, PostgreSQL et Docker. Évaluation orale : présentation, défense de l'architecture, fonctionnalités pertinentes, qualité du code. Livrables : plateforme hébergée gratuitement, dépôt GitHub avec README détaillé, plus conception UML, design, proposition financière, fiche technique, guide d'utilisation.
État : la stack Docker complète fonctionne, le login admin@gpe.local passe de bout en bout (web, passerelle Nginx, auth-service avec JWT).

RÈGLES TRANSVERSES (apprises pendant la mise au point, à respecter toujours)
1. Tout fichier PHP commence par <?php en tout premier caractère : aucune ligne vide avant, pas de BOM, pas de ?> final. Une ligne vide avant <?php dans web/routes/web.php avait supprimé tous les cookies de session (erreur 419). Après chaque lot de modifications, vérifie avec un script PowerShell que chaque fichier .php de web/ et services/ (hors vendor, storage, node_modules) commence par <?php. N'écris jamais de fichier avec Set-Content ou Out-File.
2. Après chaque changement touchant web ou la passerelle, vérifie avec curl.exe -s -D - -o NUL http://localhost:8080/connexion que les en-têtes Set-Cookie (XSRF-TOKEN et laravel_session) sont présents.
3. Après un rebuild d'un service, redémarre la passerelle : docker compose restart gateway (sinon 502).
4. Utilise le projet Compose par défaut (jamais l'option -p). Après toute vérification : docker compose down, pour ne laisser aucune stack parallèle ni port occupé.
5. Aucun secret commité. Le .env de la racine reste ignoré. .env.example ne contient aucune valeur secrète.
6. Les seeders sont idempotents (updateOrCreate). Aucun mot de passe de démo en dur dans le code sans qu'il soit documenté comme compte de démo dans le README.
7. Tests : base gpe_test, Http::fake() pour les appels inter-services. Rappel : le CSRF est désactivé pendant les tests, ajoute au moins un test de bout en bout avec curl contre http://localhost:8080.
8. Architecture inchangée : web sans base de données, auth-service (gpe_auth), patrimoine-service (gpe_patrimoine), mouvements-service (gpe_mouvements), pas de clé étrangère entre services, JWT HS256 de 2 h, pas de transaction distribuée. Un transfert qui passe à "effectué" appelle d'abord patrimoine-service pour mettre à jour le bien, puis enregistre le statut et l'historique ; en cas d'échec il reste "approuvé" et on renvoie une erreur.
9. Interface et messages en français, montants en FCFA (XOF), fuseau Africa/Lome. Les nouvelles pages n'utilisent que les composants Blade et les variables CSS existants (tokens.css), ce qui permettra la refonte graphique de la phase 6.

PHASE 0 : NETTOYAGE ET BASE SAINE
- Commite la correction de web/routes/web.php. Vérifie git status : aucun .env.
- Supprime docker-compose.override.yml.bak, les vues welcome.blade.php des 3 services, et dans web/ les migrations, modèles Eloquent, seeders et tests qui concernent la base de données (web n'a plus de base). web ne contient aucune variable DB_*.
- Ajoute la mention "UTF-8 sans BOM, <?php en premier caractère" au fichier .github/copilot-instructions.md.
- Vérifie que le compose par défaut démarre (docker compose up -d --build), que le login fonctionne par curl de bout en bout, puis docker compose down.

PHASE 1 : PATRIMOINE-SERVICE ET PAGES BIENS
- patrimoine-service : migrations et modèles de ministeres, services, categories, biens (SoftDeletes sur les biens), seeders avec les données de démo de l'audit (4 ministères, 9 services, 10 catégories, 6 biens). Endpoints REST protégés par le middleware jwt.auth : /api/biens, /api/categories, /api/ministeres, /api/services (CRUD, FormRequest, pagination, filtres catégorie, service, état, recherche texte) et /api/stats.
- web : le dashboard utilise /api/stats ; pages liste, fiche, création et modification des biens ; pages de gestion des catégories, ministères et services. Appels via Http:: avec le JWT de la session ; si un service répond 401, déconnecte l'utilisateur proprement ; si un service est injoignable, affiche une page d'erreur claire en français.
- Tests par endpoint principal et accès sans token (401).

PHASE 2 : MOUVEMENTS-SERVICE ET PAGES MOUVEMENTS
- mouvements-service : affectations (un bien n'a qu'une affectation active à la fois), transferts (brouillon, en_attente, approuvé, effectué, rejeté ; transitions invalides refusées ; règle 8 pour le passage à "effectué"), maintenances (statut, coût en FCFA), historique_operations en lecture seule, alimenté à chaque création, modification ou changement de statut (ancienne et nouvelle valeur en JSON, utilisateur, date). Avant d'enregistrer une opération, vérification de l'existence du bien via PATRIMOINE_URL. /api/stats.
- web : pages affectations, transferts (avec boutons d'action selon le statut), maintenances, historique ; badge du nombre de transferts en attente dans la barre latérale.
- Tests : transition invalide, bien inexistant, historique écrit, échec de patrimoine-service (le transfert reste "approuvé").

PHASE 3 : PERMISSIONS ET SÉCURITÉ
- Applique documentation/audit/PERMISSIONS.md : middleware role: dans chaque service, plus Policies. admin : tout ; gestionnaire : biens, affectations, maintenances, création de transferts ; responsable : approbation et rejet des transferts de son service, lecture ; agent : lecture des biens de son service, demandes de transfert ou de maintenance ; auditeur : lecture seule, historique inclus.
- Seeders : un compte de démo par rôle, mots de passe pris dans des variables d'environnement avec valeur de démo documentée dans le README.
- web : boutons et menus masqués selon le rôle du JWT ; page 403 en français. Utilisateurs : CRUD réservé à l'admin (auth-service).
- Sécurité : limitation du nombre de tentatives de login (throttle), validation stricte des entrées, pas de données sensibles dans les logs, APP_DEBUG=false.
- Tests : accès interdit par rôle sur au moins un endpoint par service, et un test de bout en bout par curl du parcours connexion, liste des biens, création d'un transfert.

PHASE 4 : DOCUMENTATION ET LIVRABLES (en français, ton professionnel)
- README.md à la racine, normalisé : présentation, contexte, fonctionnalités, architecture (schéma), technologies, prérequis, installation et lancement (docker compose up --build), variables d'environnement, comptes de démo, structure du dépôt, API par service, tests, conventions de commits et de branches, limites connues et évolutions, auteur.
- documentation/architecture/ : choix d'architecture et justification, cohérence éventuelle, artisan serve en démo contre PHP-FPM en production, redémarrage de la passerelle après un rebuild.
- documentation/analyse-besoin.md : acteurs, besoins fonctionnels et non fonctionnels, règles de gestion, user stories.
- documentation/uml/ : fichiers PlantUML (.puml) des diagrammes de cas d'utilisation, de classes (regroupées par service, d'après les migrations réelles), de séquence du transfert et de déploiement Docker.
- documentation/fiche-technique.md : stack, versions, architecture, API, sécurité, base de données, déploiement.
- documentation/guide-utilisation.md : pas à pas avec le rôle concerné pour chaque action ; prévois des emplacements de captures d'écran.
- documentation/proposition-financiere.md : hypothèses explicites (journées, taux journalier en FCFA, coûts d'hébergement, maintenance), détail des postes et total ; marque clairement les montants à valider par l'étudiant.
- documentation/design/ecrans.md : liste des écrans à reproduire dans Figma (connexion, tableau de bord, liste des biens, fiche bien, transfert) avec leurs composants et leur palette.

PHASE 5 : PRÉPARATION DU DÉPLOIEMENT GRATUIT
- Ne choisis pas l'hébergeur toi-même : liste dans documentation/DEPLOIEMENT.md les contraintes de l'application (6 conteneurs, PostgreSQL, variables d'environnement) et les options possibles, avec les points à vérifier. L'étudiant décidera.
- Prépare un fichier docker-compose.prod.yml (ou équivalent), un .env.production.example sans secrets, la liste des secrets à régénérer (APP_KEY, JWT_SECRET, DB_PASSWORD, comptes de démo), et un plan B documenté (captures d'écran ou vidéo de la démo locale).

PHASE 6 : REFONTE GRAPHIQUE (web/ uniquement : CSS et vues Blade)
- Branche feature/design-v2. Reprends la mise en page de la maquette jointe (dashboard avec sidebar verte, cartes colorées, graphique en barres, donut, liste d'opérations, transferts avec filtres, répartition par ministère) avec la palette togolaise existante (vert profond, or, rouge réservé aux alertes). Ne reprends ni le nom ni le logo de la maquette.
- Police : Plus Jakarta Sans variable, auto-hébergée via @fontsource-variable/plus-jakarta-sans importé dans le CSS (aucun CDN) ; supprime Fraunces et Inter. Titres en 700 ou 800, texte en 400 ou 500, chiffres des cartes en 800 avec tabular-nums.
- Tout passe par les variables de tokens.css. Responsive (sidebar coulissante sous 62 rem), contraste AA, page de connexion cohérente. npm run build doit réussir. Applique le style aux pages créées dans les phases 1 et 2.

Commence par la phase 0.
