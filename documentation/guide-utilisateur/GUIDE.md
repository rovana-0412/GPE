# Guide utilisateur — GPE

## Connexion

1. Accéder à l'application : http://localhost:8080
2. Saisir l'email : `admin@gpe.local`
3. Saisir le mot de passe : `GPE_Admin_2026!`
4. Cliquer sur "Se connecter"

## Tableau de bord

Le tableau de bord présente une synthèse du patrimoine :

- **Carte Valeur du patrimoine** : valeur cumulée des biens actifs en FCFA
- **Carte Biens actifs** : nombre total de biens en service
- **Donut Répartition** : proportion immobilier/mobilier
- **Liste Dernières opérations** : historique récent des actions
- **Bar chart Ministères** : répartition des biens par ministère
- **Bar chart Catégories** : répartition des biens par catégorie
- **Repères** : nombre de catégories, ministères, services
- **À traiter** : transferts en attente, maintenances signalées

## Gestion des biens

### Consulter la liste des biens

1. Cliquer sur "Tous les biens" dans le menu "Patrimoine"
2. La liste affiche : référence, désignation, catégorie, ministère, service, état, valeur d'acquisition
3. Filtres disponibles : par catégorie, par ministère, par service, par état

### Consulter un bien

1. Cliquer sur un bien dans la liste
2. La fiche affiche toutes les informations : référence, désignation, description, catégorie, type, nature, date d'acquisition, valeur, état, localisation, ministère, service

### Créer un bien

1. Cliquer sur "Ajouter un bien"
2. Remplir le formulaire :
   - Référence (générée automatiquement ou saisie)
   - Désignation
   - Description
   - Catégorie
   - Type (immobilier/mobilier)
   - Nature
   - Date d'acquisition
   - Valeur d'acquisition (en FCFA)
   - État
   - Localisation
   - Ministère
   - Service
3. Cliquer sur "Enregistrer"

### Modifier un bien

1. Cliquer sur "Modifier" sur la fiche du bien
2. Mettre à jour les champs nécessaires
3. Cliquer sur "Enregistrer"

### Archiver un bien

1. Cliquer sur "Archiver" sur la fiche du bien
2. Confirmer l'action
3. Le bien est marqué comme supprimé (SoftDelete) et n'apparaît plus dans la liste

## Gestion des catégories

### Consulter les catégories

1. Cliquer sur "Catégories" dans le menu "Patrimoine"
2. La liste affiche : nom, type (immobilier/mobilier), nombre de biens

### Créer une catégorie

1. Cliquer sur "Ajouter une catégorie"
2. Saisir le nom
3. Choisir le type (immobilier/mobilier)
4. Cliquer sur "Enregistrer"

## Gestion des ministères

### Consulter les ministères

1. Cliquer sur "Ministères" dans le menu "Administration"
2. La liste affiche : nom, nombre de services, nombre de biens

### Créer un ministère

1. Cliquer sur "Ajouter un ministère"
2. Saisir le nom
3. Cliquer sur "Enregistrer"

## Gestion des services

### Consulter les services

1. Cliquer sur "Services" dans le menu "Administration"
2. La liste affiche : nom, ministère rattaché, nombre de biens

### Créer un service

1. Cliquer sur "Ajouter un service"
2. Saisir le nom
3. Choisir le ministère
4. Cliquer sur "Enregistrer"

## Affectations

### Consulter les affectations

1. Cliquer sur "Affectations" dans le menu "Mouvements"
2. La liste affiche : bien, service, date de début, statut

### Affecter un bien

1. Cliquer sur "Nouvelle affectation"
2. Choisir le bien
3. Choisir le service
4. Saisir la date de début
5. Cliquer sur "Enregistrer"

## Transferts

### Consulter les transferts

1. Cliquer sur "Transferts" dans le menu "Mouvements"
2. La liste affiche : bien, service source, service destination, statut

### Demander un transfert

1. Cliquer sur "Nouveau transfert"
2. Choisir le bien
3. Choisir le service source
4. Choisir le service destination
5. Saisir la justification
6. Cliquer sur "Soumettre" (statut passe à "en_attente")

### Valider un transfert

1. Cliquer sur "Valider" sur un transfert en attente
2. Le statut passe à "approuvé"
3. Le bien est mis à jour dans le service destination
4. Le statut passe à "effectué"

## Maintenances

### Consulter les maintenances

1. Cliquer sur "Maintenances" dans le menu "Mouvements"
2. La liste affiche : bien, type, date, coût, statut

### Signaler une maintenance

1. Cliquer sur "Nouvelle maintenance"
2. Choisir le bien
3. Choisir le type (préventive/curative)
4. Saisir la description
5. Saisir la date prévue
6. Saisir le coût estimé (en FCFA)
7. Cliquer sur "Enregistrer"

## Historique

### Consulter l'historique

1. Cliquer sur "Historique" dans le menu "Traçabilité"
2. La liste affiche : utilisateur, action, élément concerné, date/heure

## Déconnexion

1. Cliquer sur "Se déconnecter" en bas de la sidebar
2. La session est fermée et vous êtes redirigé vers la page de connexion
