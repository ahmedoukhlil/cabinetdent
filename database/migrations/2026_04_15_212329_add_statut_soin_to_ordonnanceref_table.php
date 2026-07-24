<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatutSoinToOrdonnancerefTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ordonnanceref', function (Blueprint $table) {
            // 'en_attente' = prescrit, pas encore traité | 'en_cours' = pris en charge | 'termine' = soins effectués
            $table->string('statutSoin', 20)->default('en_attente')->after('TypeOrdonnance');
        });
    }

    public function down()
    {
        Schema::table('ordonnanceref', function (Blueprint $table) {
            $table->dropColumn('statutSoin');
        });
    }
}
