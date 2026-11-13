
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table des transferts.
     */
    public function up(): void
    {
        Schema::create('transferts', function (Blueprint $table) {
            $table->id();

            // Bien concerné
            $table->foreignId('bien_id')
                ->constrained('biens')
                ->restrictOnDelete();

            // Services concernés
            $table->foreignId('service_origine_id')
                ->constrained('services')
                ->restrictOnDelete();

            $table->foreignId('service_destination_id')
                ->constrained('services')
                ->restrictOnDelete();

            // Utilisateurs responsables
            $table->foreignId('demandeur_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('validateur_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Dates du transfert
            $table->date('date_demande');
            $table->date('date_validation')->nullable();
            $table->date('date_effective')->nullable();

            // Justification et décision
            $table->text('motif');
            $table->text('observation')->nullable();
            $table->text('motif_refus')->nullable();

            // En attente, accepté ou refusé
            $table->string('statut', 30)->default('en_attente');

            $table->timestamps();

            // Index pour faciliter les recherches
            $table->index(['bien_id', 'statut']);
            $table->index('date_demande');
        });
    }

    /**
     * Supprimer la table en cas de retour arrière.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferts');
    }
};
