<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bien_id');
            $table->unsignedBigInteger('service_origine_id');
            $table->unsignedBigInteger('service_destination_id');
            $table->unsignedBigInteger('demandeur_id');
            $table->unsignedBigInteger('validateur_id')->nullable();
            $table->date('date_demande');
            $table->date('date_validation')->nullable();
            $table->date('date_effective')->nullable();
            $table->text('motif');
            $table->text('observation')->nullable();
            $table->text('motif_refus')->nullable();
            $table->string('statut', 30)->default('brouillon');
            $table->timestamps();
            $table->index(['bien_id', 'statut']);
            $table->index(['service_origine_id', 'statut']);
            $table->index(['service_destination_id', 'statut']);
            $table->index(['demandeur_id', 'created_at']);
            $table->index('date_demande');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferts');
    }
};
