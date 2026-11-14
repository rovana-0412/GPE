<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nom' => 'Administrateur général',
                'description' => 'Administre l'ensemble de la plateforme GPE et gère les utilisateurs, les rôles et les paramètres.',
            ],
            [
                'nom' => 'Gestionnaire du patrimoine',
                'description' => 'Supervise l'inventaire, les biens, les affectations, les transferts et les opérations de maintenance.',
            ],
            [
                'nom' => 'Responsable ministériel',
                'description' => 'Supervise la gestion du patrimoine au niveau de son ministère et valide les opérations autorisées.',
            ],
            [
                'nom' => 'Agent gestionnaire',
                'description' => 'Enregistre et met à jour les informations des biens et assure le suivi des opérations autorisées.',
            ],
            [
                'nom' => 'Auditeur',
                'description' => 'Consulte les informations, les rapports et l'historique des opérations à des fins de contrôle.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['nom' => $role['nom']],
                ['description' => $role['description']]
            );
        }
    }
}
