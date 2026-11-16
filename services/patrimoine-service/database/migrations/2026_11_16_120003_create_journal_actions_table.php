<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_actions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('utilisateur_id')->nullable()->index();
            $table->unsignedBigInteger('bien_id')->nullable()->index();
            $table->string('ressource', 60);
            $table->unsignedBigInteger('ressource_id')->nullable();
            $table->string('action', 120)->index();
            $table->json('ancienne_valeur')->nullable();
            $table->json('nouvelle_valeur')->nullable();
            $table->string('adresse_ip', 45)->nullable();
            $table->timestamps();
            $table->index(['ressource', 'ressource_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_actions');
    }
};
