<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // « consultation » : acte facturé dès l'arrivée du patient (facture + encaissement).
        Schema::table('actes', function (Blueprint $table) {
            $table->string('type', 20)->default('soin')->after('libelle');
        });
        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->foreignId('facture_id')->nullable()->after('acte_prevu')->constrained('factures')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facture_id');
        });
        Schema::table('actes', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
