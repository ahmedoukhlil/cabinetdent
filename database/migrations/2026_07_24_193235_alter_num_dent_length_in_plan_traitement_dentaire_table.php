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
        Schema::table('plan_traitement_dentaire', function (Blueprint $table) {
            // '11'..'48' pour une dent seule, ou liste "11,12,13,..." pour un
            // acte groupé appliqué à plusieurs dents (ex: détartrage) — plus
            // besoin de dropper l'index existant, on élargit juste la colonne.
            $table->string('num_dent', 191)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_traitement_dentaire', function (Blueprint $table) {
            $table->string('num_dent', 2)->change();
        });
    }
};
