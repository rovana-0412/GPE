<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('actif')->default(true);
            $table->index(['role_id', 'actif']);
            $table->index(['service_id', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role_id', 'actif']);
            $table->dropIndex(['service_id', 'actif']);
            $table->dropColumn('actif');
        });
    }
};
