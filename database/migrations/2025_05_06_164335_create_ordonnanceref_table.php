<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ordonnanceref', function (Blueprint $table) {
            $table->increments('id');
            $table->string('refOrd', 45)->default('NR')->index('Index_2');
            $table->unsignedInteger('Annee')->default(0)->index('Index_3');
            $table->unsignedInteger('numordre')->default(0);
            $table->unsignedInteger('fkidpatient')->default(0)->index('Index_4');
            $table->unsignedInteger('fkidprescripteur')->default(1)->index('Index_5');
            $table->dateTime('dtPrescript')->nullable()->index('Index_6');
            $table->unsignedInteger('fkidCabinet')->default(1);
            $table->string('TypeOrdonnance', 145)->default('Ordonnances')->index('Index_7');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ordonnanceref');
    }
};
