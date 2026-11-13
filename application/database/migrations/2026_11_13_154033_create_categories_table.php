<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table des catégories de biens.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->string('nom', 100)->unique();
            $table->string('type', 30);
            $table->text('description')->nullable();

            $table->boolean('actif')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Supprimer la table en cas de retour arrière.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};