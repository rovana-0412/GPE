<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Créer la table des ministères.
     */
    public function up(): void
    {
        Schema::create('ministeres', function (Blueprint $table) {
            $table->id();

            $table->string('nom', 150)->unique();
            $table->string('sigle', 30)->nullable()->unique();
            $table->text('description')->nullable();

            $table->string('adresse')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('email')->nullable();

            $table->boolean('actif')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Supprimer la table en cas de retour arrière.
     */
    public function down(): void
    {
        Schema::dropIfExists('ministeres');
    }
};