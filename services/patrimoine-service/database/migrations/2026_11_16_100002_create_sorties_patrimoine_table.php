<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sorties_patrimoine', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bien_id')->constrained('biens')->restrictOnDelete();
            $table->string('type_sortie', 30);
            $table->text('motif');
            $table->string('statut', 30)->default('demandee');
            $table->unsignedBigInteger('demandeur_id')->nullable();
            $table->unsignedBigInteger('validateur_id')->nullable();
            $table->date('date_demande');
            $table->date('date_execution')->nullable();
            $table->text('motif_rejet')->nullable();
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->index(['bien_id', 'statut']);
            $table->index(['statut', 'date_demande']);
            $table->index('demandeur_id');
            $table->index('validateur_id');
        });

        DB::statement(
            "CREATE UNIQUE INDEX sorties_bien_en_cours_unique ON sorties_patrimoine (bien_id) WHERE statut IN ('demandee', 'approuvee')"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('sorties_patrimoine');
    }
};
