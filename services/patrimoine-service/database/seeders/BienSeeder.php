<?php

namespace Database\Seeders;

use App\Models\Bien;
use App\Models\Categorie;
use App\Models\Service;
use Illuminate\Database\Seeder;

class BienSeeder extends Seeder
{
    public function run(): void
    {
        $biens = [
            ['reference' => 'GPE-IMM-001', 'designation' => 'Terrain administratif de Lomé', 'categorie' => 'Terrains', 'service' => 'DP', 'date_acquisition' => '2020-03-15', 'valeur_acquisition' => 25000000, 'etat' => 'bon_etat', 'statut' => 'disponible', 'localisation' => 'Lomé'],
            ['reference' => 'GPE-IMM-002', 'designation' => 'Bâtiment administratif central', 'categorie' => 'Bâtiments administratifs', 'service' => 'DBP', 'date_acquisition' => '2018-06-10', 'valeur_acquisition' => 85000000, 'etat' => 'bon_etat', 'statut' => 'disponible', 'localisation' => 'Lomé'],
            ['reference' => 'GPE-VEH-001', 'designation' => 'Toyota Hilux de service', 'categorie' => 'Véhicules administratifs', 'service' => 'DR', 'date_acquisition' => '2022-01-20', 'valeur_acquisition' => 22000000, 'etat' => 'bon_etat', 'statut' => 'disponible', 'localisation' => 'Lomé'],
            ['reference' => 'GPE-INF-001', 'designation' => 'Ordinateur HP ProDesk', 'categorie' => 'Matériel informatique', 'service' => 'DB', 'date_acquisition' => '2023-05-12', 'valeur_acquisition' => 450000, 'etat' => 'bon_etat', 'statut' => 'disponible', 'localisation' => 'Lomé'],
            ['reference' => 'GPE-MOB-001', 'designation' => 'Lot de mobilier de bureau', 'categorie' => 'Mobilier de bureau', 'service' => 'DA', 'date_acquisition' => '2021-09-05', 'valeur_acquisition' => 1200000, 'etat' => 'bon_etat', 'statut' => 'disponible', 'localisation' => 'Kara'],
            ['reference' => 'GPE-MED-001', 'designation' => 'Équipement de diagnostic médical', 'categorie' => 'Matériel médical', 'service' => 'DEM', 'date_acquisition' => '2024-02-18', 'valeur_acquisition' => 3500000, 'etat' => 'bon_etat', 'statut' => 'disponible', 'localisation' => 'Tsévié'],
        ];

        foreach ($biens as $bien) {
            $categorie = Categorie::where('nom', $bien['categorie'])->firstOrFail();
            $service = Service::where('sigle', $bien['service'])->firstOrFail();
            $attributes = [
                'designation' => $bien['designation'],
                'categorie_id' => $categorie->id,
                'service_id' => $service->id,
                'date_acquisition' => $bien['date_acquisition'],
                'valeur_acquisition' => $bien['valeur_acquisition'],
                'etat' => $bien['etat'],
                'statut' => $bien['statut'],
                'localisation' => $bien['localisation'],
                'actif' => true,
            ];
            $model = Bien::withTrashed()->updateOrCreate(
                ['reference' => $bien['reference']],
                $attributes,
            );

            if ($model->trashed()) {
                $model->restore();
            }
        }
    }
}
