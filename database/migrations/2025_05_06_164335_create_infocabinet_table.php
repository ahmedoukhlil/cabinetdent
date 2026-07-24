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
        Schema::create('infocabinet', function (Blueprint $table) {
            $table->unsignedInteger('idEntete')->default(0)->primary();
            $table->string('NomCabFr', 145)->nullable();
            $table->string('NomCabAr', 145)->nullable();
            $table->string('Specialite1Fr', 45)->nullable();
            $table->string('Specialite2fr', 45)->nullable();
            $table->string('Specialite3Fr', 45)->nullable();
            $table->string('Specialite1Ar', 45)->nullable();
            $table->string('Specialite2Ar', 45)->nullable();
            $table->string('Specialite3Ar', 45)->nullable();
            $table->string('AdresseL1AR', 145)->nullable();
            $table->string('AdresseL2AR', 145)->nullable();
            $table->string('AdresseFr1', 145)->nullable();
            $table->string('AdresseFr2', 145)->nullable();
            $table->string('ContactAR', 145)->nullable();
            $table->string('AdresseMail', 245)->nullable();
            $table->string('ContactFR', 45)->nullable();
            $table->string('TelephonePublic', 45)->nullable();
            $table->string('DRAr', 145)->nullable();
            $table->string('DrFr', 45)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('infocabinet');
    }
};
