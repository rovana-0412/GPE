
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table des maintenances.
     */
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();

            // Bien concerné
            $table->foreignId('bien_id')
                ->constrained('biens')
                ->restrictOnDelete();

            // Agent ayant déclaré la maintenance
            $table->foreignId('utilisateur_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Informations sur l'intervention
            $table->string('type', 50);
            $table->text('description');

            // Dates de l'intervention
            $table->date('date_debut');
            $table->date('date_fin')->nullable();

            // Coût et prestataire
            $table->decimal('cout', 15, 2)->nullable();
            $table->string('prestataire')->nullable();

            // Situation de la maintenance
            $table->string('statut', 30)->default('signalee');

            $table->text('observation')->nullable();

            $table->timestamps();

            // Index pour faciliter les recherches
            $table->index(['bien_id', 'statut']);
            $table->index('date_debut');
        });
    }

    /**
     * Supprimer la table en cas de retour arrière.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
