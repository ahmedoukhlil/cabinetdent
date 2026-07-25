<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // La colonne était en cp1256 (encodage legacy arabe Windows),
        // incompatible avec l'UTF-8 envoyé par PDO/Laravel — provoque une
        // erreur "Incorrect string value" à l'insertion de libellés arabes.
        // On repasse d'abord par binary pour éviter que MySQL ne tente de
        // réinterpréter/tronquer les octets existants lors du changement
        // direct de charset, puis on définit utf8mb4 sur les octets bruts.
        DB::statement("ALTER TABLE actes MODIFY ActeArab VARBINARY(245) NULL");
        DB::statement("UPDATE actes SET ActeArab = 'NR' WHERE ActeArab IS NULL");
        DB::statement("ALTER TABLE actes MODIFY ActeArab VARCHAR(245) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NR'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE actes MODIFY ActeArab VARCHAR(245) CHARACTER SET cp1256 NOT NULL DEFAULT 'NR'");
    }
};
