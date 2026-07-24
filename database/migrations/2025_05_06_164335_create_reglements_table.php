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
        Schema::create('reglements', function (Blueprint $table) {
            $table->integer('idreglement', true);
            $table->integer('fkidFactBord')->nullable()->index('Index_2');
            $table->integer('fkidTiers')->nullable()->index('Index_3');
            $table->dateTime('dtreglement')->nullable();
            $table->string('typeReglement')->nullable();
            $table->double('MontantDep')->default(0);
            $table->unsignedInteger('fkiduser')->default(1);
            $table->string('Motif', 145)->nullable();
            $table->unsignedInteger('fkidtypeDepRectte')->default(0)->index('Index_4')->comment('1 dep et 2 recette');
            $table->unsignedInteger('FkidtypeTiers')->default(0);
            $table->double('MontantRec')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reglements');
    }
};
