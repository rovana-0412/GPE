
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table des affectations.
     */
    public function up(): void
    {
        Schema::create('affectations', function (Blueprint $table) {
            $table->id();

            // Bien concerné
            $table->foreignId('bien_id')
                ->constrained('biens')
                ->restrictOnDelete();

            // Service bénéficiaire
            $table->foreignId('service_id')
                ->constrained('services')
                ->restrictOnDelete();

            // Agent ayant enregistré l'affectation
            $table->foreignId('utilisateur_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Période de l'affectation
            $table->date('date_debut');
            $table->date('date_fin')->nullable();

            // Informations complémentaires
            $table->string('motif')->nullable();
            $table->text('observation')->nullable();

            // Situation de l'affectation
            $table->string('statut', 30)->default('active');

            $table->timestamps();

            // Faciliter les recherches par bien et par service
            $table->index(['bien_id', 'statut']);
            $table->index(['service_id', 'statut']);
        });
    }

    /**
     * Supprimer la table en cas de retour arrière.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectations');
    }
};
