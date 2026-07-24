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
        Schema::create('problèmes', function (Blueprint $table) {
            $table->integer('Champ1')->nullable();
            $table->integer('ID')->nullable();
            $table->string('Résumé')->nullable();
            $table->string('État')->nullable();
            $table->string('Priorité')->nullable();
            $table->string('Catégorie')->nullable();
            $table->string('Projet')->nullable();
            $table->dateTime('DateOuverture')->nullable();
            $table->dateTime('Échéance')->nullable();
            $table->string('MotsClés')->nullable();
            $table->string('Résolution')->nullable();
            $table->string('VersionRésolue')->nullable();
            $table->mediumText('PiècesJointes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('problèmes');
    }
};
