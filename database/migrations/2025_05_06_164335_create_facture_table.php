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
        Schema::create('facture', function (Blueprint $table) {
            $table->integer('Idfacture', true);
            $table->string('Nfacture')->nullable()->index('Index_2');
            $table->integer('anneeFacture')->nullable()->index('Index_3');
            $table->integer('nordre')->nullable()->index('Index_4');
            $table->dateTime('DtFacture')->nullable()->index('Index_5');
            $table->integer('IDPatient')->nullable()->index('Index_6');
            $table->integer('ISTP')->nullable()->index('Index_7');
            $table->integer('fkidEtsAssurance')->nullable()->index('Index_8');
            $table->double('TXPEC')->default(0);
            $table->double('TotFacture')->default(0);
            $table->double('TotalPEC')->default(0);
            $table->double('TotalfactPatient')->default(0);
            $table->double('TotReglPatient')->default(0);
            $table->double('ReglementPEC')->default(0);
            $table->string('ModeReglement')->nullable();
            $table->string('Areglepar')->nullable();
            $table->dateTime('DtReglement')->nullable();
            $table->double('fkidbordfacture')->default(0)->index('Index_9');
            $table->integer('ispayerAssureur')->default(0)->index('Index_10');
            $table->string('user')->nullable();
            $table->unsignedInteger('estfacturer')->default(0);
            $table->unsignedInteger('FkidMedecinInitiateur')->default(1)->index('Index_11');
            $table->double('PartLaboratoire')->default(0);
            $table->double('MontantAffectation')->default(0);
            $table->string('Type', 45)->default('Devis');
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
        Schema::dropIfExists('facture');
    }
};
