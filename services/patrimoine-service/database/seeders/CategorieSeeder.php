<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nom' => 'Terrains', 'type' => 'immobilier', 'description' => 'Parcelles et terrains appartenant à l’État.'],
            ['nom' => 'Bâtiments administratifs', 'type' => 'immobilier', 'description' => 'Bureaux et bâtiments utilisés par les administrations publiques.'],
            ['nom' => 'Établissements scolaires', 'type' => 'immobilier', 'description' => 'Écoles, collèges et lycées publics.'],
            ['nom' => 'Établissements sanitaires', 'type' => 'immobilier', 'description' => 'Hôpitaux, centres de santé et autres infrastructures sanitaires.'],
            ['nom' => 'Véhicules administratifs', 'type' => 'mobilier', 'description' => 'Voitures, motos et véhicules de service.'],
            ['nom' => 'Matériel informatique', 'type' => 'mobilier', 'description' => 'Ordinateurs, imprimantes, serveurs et périphériques.'],
            ['nom' => 'Mobilier de bureau', 'type' => 'mobilier', 'description' => 'Tables, chaises, armoires et autres meubles.'],
            ['nom' => 'Équipements techniques', 'type' => 'mobilier', 'description' => 'Équipements techniques et matériels spécialisés.'],
            ['nom' => 'Matériel médical', 'type' => 'mobilier', 'description' => 'Appareils et équipements utilisés dans les structures sanitaires.'],
            ['nom' => 'Matériel scolaire', 'type' => 'mobilier', 'description' => 'Matériels et équipements destinés aux établissements scolaires.'],
        ];

        foreach ($categories as $categorie) {
            Categorie::updateOrCreate(
                ['nom' => $categorie['nom']],
                [...$categorie, 'actif' => true],
            );
        }
    }
}
