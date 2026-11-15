<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bien_id');
            $table->unsignedBigInteger('utilisateur_id')->nullable();
            $table->string('type', 50);
            $table->text('description');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->decimal('cout', 15, 2)->nullable();
            $table->string('prestataire')->nullable();
            $table->string('statut', 30)->default('signalee');
            $table->text('observation')->nullable();
            $table->timestamps();
            $table->index(['bien_id', 'statut']);
            $table->index(['utilisateur_id', 'created_at']);
            $table->index('date_debut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
