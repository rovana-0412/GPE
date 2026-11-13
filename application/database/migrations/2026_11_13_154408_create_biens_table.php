
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table des biens du patrimoine.
     */
    public function up(): void
    {
        Schema::create('biens', function (Blueprint $table) {
            $table->id();

            // Identification du bien
            $table->string('reference', 50)->unique();
            $table->string('designation', 150);
            $table->text('description')->nullable();

            // Classification
            $table->foreignId('categorie_id')
                ->constrained('categories')
                ->restrictOnDelete();

            // Informations complémentaires
            $table->string('numero_serie', 100)->nullable()->unique();
            $table->date('date_acquisition')->nullable();
            $table->decimal('valeur_acquisition', 15, 2)->nullable();

            // État physique et situation administrative
            $table->string('etat', 30)->default('bon_etat');
            $table->string('statut', 30)->default('disponible');

            // Localisation actuelle
            $table->string('localisation')->nullable();

            // Conservation de la fiche
            $table->boolean('actif')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Supprimer la table en cas de retour arrière.
     */
    public function down(): void
    {
        Schema::dropIfExists('biens');
    }
};
