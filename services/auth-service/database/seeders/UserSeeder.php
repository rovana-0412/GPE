<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer l'utilisateur admin par défaut
        $adminRole = Role::where('nom', 'Administrateur général')->first();

        User::updateOrCreate(
            ['email' => 'admin@gpe.local'],
            [
                'name' => 'Administrateur GPE',
                'password' => Hash::make('GPE_Admin_2026!'),
                'role_id' => $adminRole ? $adminRole->id : null,
                'service_id' => null,
            ]
        );
    }
}
