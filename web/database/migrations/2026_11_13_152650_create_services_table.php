<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table des services.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ministere_id')
                ->constrained('ministeres')
                ->restrictOnDelete();

            $table->string('nom', 150);
            $table->string('sigle', 30)->nullable();
            $table->text('description')->nullable();

            $table->string('adresse')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('email')->nullable();

            $table->boolean('actif')->default(true);

            $table->timestamps();

            $table->unique(['ministere_id', 'nom']);
        });
    }

    /**
     * Supprimer la table en cas de retour arrière.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};