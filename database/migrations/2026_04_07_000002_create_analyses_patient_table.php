<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('analyses_patient', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fkidPatient')->index();
            $table->unsignedInteger('fkidCabinet')->default(1)->index();
            $table->unsignedInteger('fkidConsultation')->nullable()->index(); // lien optionnel
            $table->string('libelle');           // "NFS", "Glycémie", "Radio thorax"...
            $table->string('type')->default('biologie'); // biologie | imagerie | autre
            $table->string('fichier_path');      // storage path
            $table->string('fichier_nom');       // nom original
            $table->string('fichier_mime')->nullable();
            $table->unsignedBigInteger('fichier_taille')->nullable(); // bytes
            $table->date('date_analyse')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('analyses_patient');
    }
};
