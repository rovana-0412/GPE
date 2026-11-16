<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaires', function (Blueprint $table): void {
            $table->id();
            $table->string('titre', 180);
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('statut', 30)->default('brouillon');
            $table->date('date_debut')->nullable();
            $table->date('date_cloture')->nullable();
            $table->unsignedBigInteger('createur_id')->nullable();
            $table->unsignedBigInteger('validateur_id')->nullable();
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'statut']);
            $table->index(['statut', 'date_debut']);
            $table->index('createur_id');
            $table->index('validateur_id');
        });

        Schema::create('inventaire_lignes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventaire_id')->constrained('inventaires')->cascadeOnDelete();
            $table->unsignedBigInteger('bien_id');
            $table->string('constat', 30);
            $table->string('localisation_physique', 255)->nullable();
            $table->text('observation')->nullable();
            $table->unsignedBigInteger('controleur_id')->nullable();
            $table->timestamp('controle_le')->nullable();
            $table->timestamps();

            $table->unique(['inventaire_id', 'bien_id']);
            $table->index(['bien_id', 'constat']);
            $table->index('controleur_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaire_lignes');
        Schema::dropIfExists('inventaires');
    }
};
