<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuantiteToOrdonnancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ordonnances', function (Blueprint $table) {
            $table->unsignedSmallInteger('Quantite')->default(1)->after('Utilisation');
        });
    }

    public function down()
    {
        Schema::table('ordonnances', function (Blueprint $table) {
            $table->dropColumn('Quantite');
        });
    }
}
