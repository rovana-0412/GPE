<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ministere;

class MinistereSeeder extends Seeder
{
    public function run(): void
    {
        $ministeres = [
            [
                'nom' => "Ministère de l'Économie et des Finances",
                'sigle' => 'MEF',
                'description' => 'Chargé de la politique économique, budgétaire et financière de l’État.',
                'actif' => true,
            ],
            [
                'nom' => 'Ministère des Travaux publics',
                'sigle' => 'MTP',
                'description' => 'Chargé des infrastructures publiques et des travaux de construction.',
                'actif' => true,
            ],
            [
                'nom' => "Ministère de l'Éducation nationale",
                'sigle' => 'MEN',
                'description' => 'Chargé de la politique éducative et de la gestion des établissements publics.',
                'actif' => true,
            ],
            [
                'nom' => 'Ministère de la Santé',
                'sigle' => 'MSA',
                'description' => 'Chargé de la politique sanitaire et des infrastructures de santé.',
                'actif' => true,
            ],
        ];

        foreach ($ministeres as $ministere) {
            Ministere::updateOrCreate(
                ['sigle' => $ministere['sigle']],
                $ministere
            );
        }
    }
}