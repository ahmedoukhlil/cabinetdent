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
        Schema::create('factureaimprimer', function (Blueprint $table) {
            $table->unsignedInteger('IDDetail')->default(0)->primary();
            $table->string('Numfacture', 45)->nullable();
            $table->dateTime('DtFacture')->nullable();
            $table->string('NomPatient', 145)->nullable();
            $table->string('Assureur', 145)->nullable();
            $table->string('Actes', 145)->nullable();
            $table->string('Dents', 145)->nullable();
            $table->string('TelephonePatient', 145)->nullable();
            $table->double('Qte')->nullable();
            $table->double('PU')->nullable();
            $table->double('Soustotal')->nullable();
            $table->double('TotFacture')->nullable();
            $table->double('TotalPEC')->nullable();
            $table->double('TotalFactPatient')->nullable();
            $table->string('IntituleTotal', 145)->nullable();
            $table->string('EnLettre', 245)->nullable();
            $table->dateTime('DtNaissance')->nullable();
            $table->string('Genre', 45)->nullable();
            $table->double('NumDossierPat')->default(0);
            $table->string('Type', 45)->nullable();
            $table->string('Medecin', 145)->nullable();
            $table->string('TelCabinet', 145)->nullable();
            $table->double('TotReglPat')->nullable();
            $table->string('ActesAr', 145)->default('NR');
            $table->string('TypeAr', 145)->default('NR');
            $table->string('NomAr', 145)->default('NR');
            $table->double('remise')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('factureaimprimer');
    }
};
