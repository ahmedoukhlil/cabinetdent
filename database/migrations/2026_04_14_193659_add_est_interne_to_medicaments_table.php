<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEstInterneToMedicamentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medicaments', function (Blueprint $table) {
            // true = réalisé en interne (facturé), false = externe (ordonnance seulement)
            $table->boolean('estInterne')->default(false)->after('PrixRef');
        });
    }

    public function down()
    {
        Schema::table('medicaments', function (Blueprint $table) {
            $table->dropColumn('estInterne');
        });
    }
}
