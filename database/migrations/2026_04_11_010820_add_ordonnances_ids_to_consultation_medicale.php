<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrdonnancesIdsToConsultationMedicale extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('consultation_medicale', function (Blueprint $table) {
            $table->json('ordonnances_ids')->nullable()->after('examens_demandes');
        });
    }

    public function down()
    {
        Schema::table('consultation_medicale', function (Blueprint $table) {
            $table->dropColumn('ordonnances_ids');
        });
    }
}
