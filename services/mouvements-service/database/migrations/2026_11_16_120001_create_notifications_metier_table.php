<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_metier', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('utilisateur_id');
            $table->string('type', 80);
            $table->string('titre', 180);
            $table->text('message');
            $table->json('donnees')->nullable();
            $table->timestamp('lue_le')->nullable();
            $table->timestamps();
            $table->index(['utilisateur_id', 'lue_le', 'created_at'], 'notifications_utilisateur_lecture_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_metier');
    }
};
