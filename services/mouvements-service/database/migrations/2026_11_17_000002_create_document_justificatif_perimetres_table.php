<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_justificatif_perimetres', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('documents_justificatifs')->cascadeOnDelete();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('ministere_id');
            $table->timestamps();

            $table->unique(['document_id', 'service_id']);
            $table->index(['service_id', 'document_id']);
            $table->index(['ministere_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_justificatif_perimetres');
    }
};
