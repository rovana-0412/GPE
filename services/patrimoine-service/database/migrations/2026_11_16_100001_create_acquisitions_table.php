<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acquisitions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->string('designation', 150);
            $table->text('description')->nullable();
            $table->foreignId('categorie_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->string('numero_serie', 100)->nullable();
            $table->string('localisation')->nullable();
            $table->string('fournisseur', 180);
            $table->decimal('cout', 15, 2);
            $table->date('date_acquisition');
            $table->string('mode_acquisition', 30);
            $table->string('reference_facture', 100)->nullable();
            $table->string('reference_bon_commande', 100)->nullable();
            $table->string('statut', 30)->default('brouillon');
            $table->foreignId('bien_id')->nullable()->constrained('biens')->restrictOnDelete();
            $table->unsignedBigInteger('demandeur_id')->nullable();
            $table->unsignedBigInteger('validateur_id')->nullable();
            $table->timestamp('receptionnee_le')->nullable();
            $table->timestamp('validee_le')->nullable();
            $table->text('motif_rejet')->nullable();
            $table->timestamps();

            $table->index(['statut', 'date_acquisition']);
            $table->index(['service_id', 'statut']);
            $table->index('demandeur_id');
            $table->index('validateur_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acquisitions');
    }
};
