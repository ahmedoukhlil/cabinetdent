<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cabinets', function (Blueprint $table) {
            $table->string('heure_ouverture', 5)->default('08:30')->after('duree_rdv_minutes');
            $table->string('heure_fermeture', 5)->default('18:00')->after('heure_ouverture');
            // Jours ISO (1 = lundi … 7 = dimanche).
            $table->json('jours_ouverture')->nullable()->after('heure_fermeture');
        });
    }

    public function down(): void
    {
        Schema::table('cabinets', function (Blueprint $table) {
            $table->dropColumn(['heure_ouverture', 'heure_fermeture', 'jours_ouverture']);
        });
    }
};
