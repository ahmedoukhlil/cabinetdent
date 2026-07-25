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
        Schema::table('actes', function (Blueprint $table) {
            // Détermine sur quoi l'acte s'applique et donc quelle interaction
            // du schéma dentaire le déclenche (dent unique, sélection
            // multiple, hémi-arcade, arcade, toute la bouche, ou aucune
            // zone — actes administratifs/généraux).
            $table->enum('type_cible', [
                'DENT',
                'MULTI_DENTS',
                'HEMI_ARCADE',
                'ARCADE',
                'BOUCHE_ENTIERE',
                'PATIENT',
            ])->default('DENT')->after('ActeArab');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actes', function (Blueprint $table) {
            $table->dropColumn('type_cible');
        });
    }
};
