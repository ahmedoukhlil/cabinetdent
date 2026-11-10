<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_traitement_lignes', function (Blueprint $table) {
            // Acte du catalogue choisi à la saisie (null = soin libre).
            $table->foreignId('acte_id')->nullable()->after('plan_traitement_id')->constrained('actes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('plan_traitement_lignes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acte_id');
        });
    }
};
