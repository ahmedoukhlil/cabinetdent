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
        Schema::table('dossier_medical', function (Blueprint $table) {
            $table->text('alertes_dentaires')->nullable()->after('traitements_permanents');
            $table->text('alertes_dentaires_detail')->nullable()->after('alertes_dentaires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dossier_medical', function (Blueprint $table) {
            $table->dropColumn(['alertes_dentaires', 'alertes_dentaires_detail']);
        });
    }
};
