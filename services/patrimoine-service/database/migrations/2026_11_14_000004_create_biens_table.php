<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biens', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->string('designation', 150);
            $table->text('description')->nullable();
            $table->foreignId('categorie_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->string('numero_serie', 100)->nullable()->unique();
            $table->date('date_acquisition')->nullable();
            $table->decimal('valeur_acquisition', 15, 2)->nullable();
            $table->string('etat', 30)->default('bon_etat');
            $table->string('statut', 30)->default('disponible');
            $table->string('localisation')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['categorie_id', 'service_id', 'etat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biens');
    }
};
