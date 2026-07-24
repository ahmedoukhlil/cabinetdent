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
        Schema::create('fichetraitement', function (Blueprint $table) {
            $table->increments('idFicheTraitement');
            $table->unsignedInteger('fkidfacture')->default(0)->index('Index_2');
            $table->unsignedInteger('fkidacte')->default(0)->index('Index_3');
            $table->unsignedInteger('fkidmedecin')->default(0)->index('Index_4');
            $table->string('Acte', 145)->default('ok');
            $table->string('Traitement', 245)->default('ok');
            $table->double('Prix')->default(0);
            $table->dateTime('dateTraite')->nullable();
            $table->string('NomMedecin', 45)->default('Medecin');
            $table->unsignedInteger('Ordre')->default(0);
            $table->unsignedInteger('IsImprimer')->default(0);
            $table->unsignedInteger('IsSupprimer')->default(0);
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
        Schema::dropIfExists('fichetraitement');
    }
};
