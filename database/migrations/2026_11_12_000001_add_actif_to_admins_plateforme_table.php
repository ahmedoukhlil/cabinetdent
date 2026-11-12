<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins_plateforme', function (Blueprint $table) {
            $table->boolean('actif')->default(true)->after('password');
            $table->timestamp('derniere_connexion_at')->nullable()->after('actif');
        });
    }

    public function down(): void
    {
        Schema::table('admins_plateforme', function (Blueprint $table) {
            $table->dropColumn(['actif', 'derniere_connexion_at']);
        });
    }
};
