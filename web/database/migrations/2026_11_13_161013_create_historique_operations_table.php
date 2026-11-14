
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table d'historique des opérations.
     */
    public function up(): void
    {
        Schema::create('historique_operations', function (Blueprint $table) {
            $table->id();

            // Auteur de l'opération
            $table->foreignId('utilisateur_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Bien concerné, si applicable
            $table->foreignId('bien_id')
                ->nullable()
                ->constrained('biens')
                ->nullOnDelete();

            // Nature de l'opération
            $table->string('action', 100);
            $table->text('description')->nullable();

            // Données avant et après l'opération
            $table->json('ancienne_valeur')->nullable();
            $table->json('nouvelle_valeur')->nullable();

            // Informations techniques
            $table->string('adresse_ip', 45)->nullable();

            // Date de l'opération
            $table->timestamps();

            // Index pour les recherches
            $table->index('action');
            $table->index('created_at');
            $table->index(['utilisateur_id', 'created_at']);
            $table->index(['bien_id', 'created_at']);
        });
    }

    /**
     * Supprimer la table en cas de retour arrière.
     */
    public function down(): void
    {
        Schema::dropIfExists('historique_operations');
    }
};
