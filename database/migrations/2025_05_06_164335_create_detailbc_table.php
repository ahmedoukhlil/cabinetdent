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
        Schema::create('detailbc', function (Blueprint $table) {
            $table->increments('idDetailBC');
            $table->unsignedInteger('FkidBC')->default(0)->index('Index_2');
            $table->string('Designation', 345)->nullable();
            $table->double('Qte')->default(0);
            $table->dateTime('DtCr')->nullable();
            $table->double('MontantTotal')->default(0);
            $table->double('PU')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detailbc');
    }
};
