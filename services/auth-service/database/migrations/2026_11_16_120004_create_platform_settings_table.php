<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres_plateforme', function (Blueprint $table): void {
            $table->string('cle', 80)->primary();
            $table->text('valeur')->nullable();
            $table->unsignedBigInteger('modifie_par')->nullable()->index();
            $table->timestamps();
        });

        $now = now();
        DB::table('parametres_plateforme')->insertOrIgnore([
            ['cle' => 'nom_plateforme', 'valeur' => 'GPE', 'modifie_par' => null, 'created_at' => $now, 'updated_at' => $now],
            ['cle' => 'nom_institution', 'valeur' => 'République togolaise', 'modifie_par' => null, 'created_at' => $now, 'updated_at' => $now],
            ['cle' => 'contact_support', 'valeur' => null, 'modifie_par' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres_plateforme');
    }
};
