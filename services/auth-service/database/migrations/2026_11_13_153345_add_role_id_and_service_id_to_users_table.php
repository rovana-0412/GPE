<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajouter les relations entre utilisateurs, rôles et services.
     * Note: Les FK sont supprimées car les relations restent côté code PHP.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('id');
            $table->unsignedBigInteger('service_id')->nullable()->after('role_id');
        });
    }

    /**
     * Supprimer les relations en cas de retour arrière.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['service_id', 'role_id']);
        });
    }
};
