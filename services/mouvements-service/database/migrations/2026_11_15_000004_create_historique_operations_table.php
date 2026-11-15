<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historique_operations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('utilisateur_id')->nullable();
            $table->unsignedBigInteger('bien_id')->nullable();
            $table->string('action', 100);
            $table->text('description')->nullable();
            $table->json('ancienne_valeur')->nullable();
            $table->json('nouvelle_valeur')->nullable();
            $table->string('adresse_ip', 45)->nullable();
            $table->timestamps();
            $table->index('action');
            $table->index(['utilisateur_id', 'created_at']);
            $table->index(['bien_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_operations');
    }
};
