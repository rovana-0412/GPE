<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MinistereSeeder::class,
            ServiceSeeder::class,
            CategorieSeeder::class,
            BienSeeder::class,
        ]);
    }
}
