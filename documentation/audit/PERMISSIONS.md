# Matrice des permissions GPE — Proposition (avant implémentation)

> Statut : **proposition soumise à validation**. Implémentation prévue via les
> Gates et Policies natifs de Laravel dans `feature/authentification`.
> Contrôle systématique côté serveur (middleware + policies), jamais uniquement côté interface.

## Rôles

| Code | Rôle | Libellé |
|---|---|---|
| `admin` | Administrateur général | Administration complète de la plateforme |
| `gestionnaire` | Gestionnaire du patrimoine | Supervision nationale du patrimoine |
| `responsable` | Responsable ministériel | Périmètre de son ministère |
| `agent` | Agent gestionnaire | Saisie et suivi opérationnel |
| `auditeur` | Auditeur | Consultation et rapports, en lecture seule |

## Matrice proposée

Légende : ✅ autorisé · 🔶 limité au périmètre (ministère/service de l'utilisateur) · ❌ interdit

| Action | admin | gestionnaire | responsable | agent | auditeur |
|---|:---:|:---:|:---:|:---:|:---:|
| **Biens** | | | | | |
| Consulter tous les biens | ✅ | ✅ | 🔶 | 🔶 | ✅ |
| Créer / modifier un bien | ✅ | ✅ | 🔶 | 🔶 | ❌ |
| Archiver un bien | ✅ | ✅ | 🔶 | ❌ | ❌ |
| **Catégories** | | | | | |
| Consulter | ✅ | ✅ | ✅ | ✅ | ✅ |
| Créer / modifier / désactiver | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Ministères & services** | | | | | |
| Consulter | ✅ | ✅ | 🔶 | 🔶 | ✅ |
| Créer / modifier | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Affectations** | | | | | |
| Créer | ✅ | ✅ | 🔶 | 🔶 | ❌ |
| Valider | ✅ | ✅ | 🔶 (son ministère) | ❌ | ❌ |
| Annuler | ✅ | ✅ | 🔶 | ❌ | ❌ |
| **Transferts** | | | | | |
| Demander | ✅ | ✅ | 🔶 | 🔶 | ❌ |
| Approuver / refuser | ✅ | ✅ | 🔶 (ministère concerné) | ❌ | ❌ |
| Exécuter / annuler | ✅ | ✅ | ❌ | ❌ | ❌ |
| **Maintenances** | | | | | |
| Déclarer / suivre | ✅ | ✅ | 🔶 | 🔶 | ✅ (lecture) |
| Planifier / clôturer | ✅ | ✅ | 🔶 | ❌ | ❌ |
| **Utilisateurs & rôles** | | | | | |
| Consulter | ✅ | ✅ | ❌ | ❌ | ✅ |
| Créer / modifier / désactiver | ✅ | ❌ | ❌ | ❌ | ❌ |
| Réinitialiser un mot de passe | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Historique** | | | | | |
| Consulter | ✅ | ✅ | 🔶 | ❌ | ✅ |
| Modifier / supprimer | ❌ (personne) | ❌ | ❌ | ❌ | ❌ |
| **Rapports & exports** | | | | | |
| Consulter / exporter | ✅ | ✅ | 🔶 | ❌ | ✅ |
| **Notifications** | | | | | |
| Recevoir les siennes | ✅ | ✅ | ✅ | ✅ | ✅ |
| Consulter celles des autres | ✅ | ✅ | ❌ | ❌ | ❌ |

## Règles complémentaires proposées

1. **Périmètre `responsable` / `agent`** : limité aux biens des services rattachés à leur `service_id` / ministère.
2. **Historique en lecture seule** : aucune interface de modification ; l'écriture est réservée au code serveur.
3. **Auto-protection** : un utilisateur ne peut pas désactiver son propre compte ni modifier son propre rôle.
4. **Mot de passe** : modification libre pour soi-même ; réinitialisation par un tiers uniquement par `admin`.
5. **Auditeur** : strictement en lecture, y compris sur les exports.

## Implémentation prévue

- `app/Policies/` : `BienPolicy`, `CategoriePolicy`, `MinisterePolicy`, `ServicePolicy`,
  `AffectationPolicy`, `TransfertPolicy`, `MaintenancePolicy`, `UserPolicy`, `HistoriquePolicy`.
- `app/Http/Middleware/` : `EnsureRole` (middleware par rôle) pour les routes d'administration.
- Gates dans `AppServiceProvider` pour les actions transverses (`gerer-utilisateurs`, `voir-tout`, etc.).
- Vérification de propriété : `User::service_id` et chaîne `Service → Ministere`.
