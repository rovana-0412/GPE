<?php

namespace Database\Seeders;

use App\Models\Ministere;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            'MEF' => [
                ['nom' => 'Direction du Budget', 'sigle' => 'DB'],
                ['nom' => 'Direction du Trésor', 'sigle' => 'DT'],
                ['nom' => 'Direction du Patrimoine', 'sigle' => 'DP'],
            ],
            'MTP' => [
                ['nom' => 'Direction des Routes', 'sigle' => 'DR'],
                ['nom' => 'Direction des Bâtiments publics', 'sigle' => 'DBP'],
            ],
            'MEN' => [
                ['nom' => "Direction de l'Administration", 'sigle' => 'DA'],
                ['nom' => 'Direction des Équipements scolaires', 'sigle' => 'DES'],
            ],
            'MSA' => [
                ['nom' => 'Direction des Hôpitaux', 'sigle' => 'DH'],
                ['nom' => 'Direction des Équipements médicaux', 'sigle' => 'DEM'],
            ],
        ];

        foreach ($services as $ministereSigle => $liste) {
            $ministere = Ministere::where('sigle', $ministereSigle)->firstOrFail();

            foreach ($liste as $service) {
                Service::updateOrCreate(
                    ['ministere_id' => $ministere->id, 'nom' => $service['nom']],
                    ['sigle' => $service['sigle'], 'actif' => true],
                );
            }
        }
    }
}
