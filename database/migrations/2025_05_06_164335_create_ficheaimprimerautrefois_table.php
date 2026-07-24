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
        Schema::create('ficheaimprimerautrefois', function (Blueprint $table) {
            $table->increments('idFicheAImprimer');
            $table->string('Traitement', 45)->nullable();
            $table->dateTime('DateTraitement')->nullable();
            $table->double('Prix')->nullable();
            $table->unsignedInteger('NumLigneAImprimer')->default(0);
            $table->unsignedInteger('fkidfacture')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ficheaimprimerautrefois');
    }
};
