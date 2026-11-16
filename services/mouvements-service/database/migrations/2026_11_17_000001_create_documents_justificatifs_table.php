<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents_justificatifs', function (Blueprint $table): void {
            $table->id();
            $table->string('cible_type', 20);
            $table->unsignedBigInteger('cible_id');
            $table->string('type_document', 40);
            $table->string('titre', 180);
            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->date('date_document')->nullable();
            $table->string('nom_original', 255);
            $table->string('chemin', 255)->unique();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('taille_octets');
            $table->unsignedBigInteger('utilisateur_id');
            $table->timestamps();

            $table->index(['cible_type', 'cible_id']);
            $table->index(['type_document', 'date_document']);
            $table->index('utilisateur_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents_justificatifs');
    }
};
