<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Mot de passe de l'espace patient — null tant que le patient n'a
            // jamais défini de mot de passe (autorise la connexion par
            // téléphone seul lors de la toute première connexion).
            $table->string('password')->nullable()->after('Telephone2');
            $table->timestamp('mdp_defini_le')->nullable()->after('password');
            $table->rememberToken()->after('mdp_defini_le');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['password', 'mdp_defini_le', 'remember_token']);
        });
    }
};
