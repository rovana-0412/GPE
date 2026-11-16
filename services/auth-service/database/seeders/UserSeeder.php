<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer l'utilisateur admin UNIQUEMENT s'il n'existe pas.
        // Ne jamais réécrire un mot de passe existant : la valeur initiale
        // provient de la variable d'environnement ADMIN_INITIAL_PASSWORD
        // (obligatoire pour un premier déploiement, jamais committée).

        $adminRole = Role::where('nom', 'Administrateur général')->first();
        if (!$adminRole) {
            Log::warning('UserSeeder: rôle Administrateur général introuvable, seed ignoré.');
            return;
        }

        if (User::where('email', 'admin@gpe.local')->exists()) {
            return; // Compte existant : on ne touche à rien.
        }

        $password = env('ADMIN_INITIAL_PASSWORD');
        if (empty($password) || strlen($password) < 12) {
            Log::warning('UserSeeder: ADMIN_INITIAL_PASSWORD absent ou trop court, compte admin non créé. Définissez la variable puis relancez le seeder.');
            return;
        }

        User::create([
            'name' => 'Administrateur GPE',
            'email' => 'admin@gpe.local',
            'password' => Hash::make($password),
            'role_id' => $adminRole->id,
            'service_id' => null,
        ]);
    }
}
