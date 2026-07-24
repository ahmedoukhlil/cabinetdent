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
        Schema::table('consultation_medicale', function (Blueprint $table) {
            $table->string('anesthesie')->nullable()->after('tension_arterielle');
            $table->dropColumn(['temperature', 'frequence_cardiaque', 'spo2', 'gad', 'poids', 'taille']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_medicale', function (Blueprint $table) {
            $table->dropColumn('anesthesie');
            $table->string('temperature')->nullable();
            $table->string('frequence_cardiaque')->nullable();
            $table->string('spo2')->nullable();
            $table->string('gad')->nullable();
            $table->string('poids')->nullable();
            $table->string('taille')->nullable();
        });
    }
};
