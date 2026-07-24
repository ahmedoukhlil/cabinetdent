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
        Schema::create('affectationactes', function (Blueprint $table) {
            $table->increments('idAffectationActes');
            $table->unsignedInteger('fkidfacture')->default(0)->index('Index_3');
            $table->unsignedInteger('fkidpatient')->default(0)->index('Index_2');
            $table->dateTime('DtActe')->nullable();
            $table->double('MontantActe')->default(0);
            $table->unsignedInteger('fkidMedecin')->default(0);
            $table->string('DescriptionActe', 145)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('affectationactes');
    }
};
