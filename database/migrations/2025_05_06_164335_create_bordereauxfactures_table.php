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
        Schema::create('bordereauxfactures', function (Blueprint $table) {
            $table->integer('IDBordFacture', true);
            $table->string('NumBord')->nullable();
            $table->integer('nordre')->nullable();
            $table->integer('anneeBord')->nullable();
            $table->string('PeriodeFacture')->nullable();
            $table->dateTime('DtCreation')->nullable();
            $table->dateTime('DtGeneration')->nullable();
            $table->double('MontantFacture')->nullable();
            $table->double('MontantPatient')->nullable();
            $table->double('MontantPEC')->nullable();
            $table->double('MontantPayeAssureur')->nullable();
            $table->string('user')->nullable();
            $table->unsignedInteger('fkidCabinet')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bordereauxfactures');
    }
};
