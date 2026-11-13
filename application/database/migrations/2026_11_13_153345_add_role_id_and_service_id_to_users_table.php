<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajouter les relations entre utilisateurs, rôles et services.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->foreignId('role_id')
                ->nullable()
                ->after('id')
                ->constrained('roles')
                ->restrictOnDelete();

            $table->foreignId('service_id')
                ->nullable()
                ->after('role_id')
                ->constrained('services')
                ->restrictOnDelete();

        });
    }

    /**
     * Supprimer les relations en cas de retour arrière.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
            $table->dropConstrainedForeignId('role_id');
        });
    }
};