<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affectations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bien_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('utilisateur_id')->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('motif')->nullable();
            $table->text('observation')->nullable();
            $table->string('statut', 30)->default('active');
            $table->timestamps();
            $table->index(['bien_id', 'statut']);
            $table->index(['service_id', 'statut']);
            $table->index('utilisateur_id');
        });

        DB::statement("CREATE UNIQUE INDEX affectations_bien_active_unique ON affectations (bien_id) WHERE statut = 'active'");
    }

    public function down(): void
    {
        Schema::dropIfExists('affectations');
    }
};
