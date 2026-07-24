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
        Schema::create('t_arret_situation_compte', function (Blueprint $table) {
            $table->integer('idarret')->nullable();
            $table->dateTime('dateoper')->nullable();
            $table->double('Ncompte')->nullable();
            $table->double('MontantOperation')->nullable();
            $table->double('typeoperation')->nullable();
            $table->string('designation')->nullable();
            $table->string('Numfacture')->nullable();
            $table->double('fkidboutique')->nullable();
            $table->double('entreEspece')->nullable();
            $table->double('retraitEspece')->nullable();
            $table->double('pourlui')->nullable();
            $table->double('pourbtique')->nullable();
            $table->string('nombene', 250)->nullable();
            $table->string('telebene', 250)->nullable();
            $table->double('compmouv')->nullable();
            $table->double('remi')->nullable();
            $table->string('numecheqbanq')->nullable();
            $table->integer('User_code')->nullable();
            $table->integer('typedepense')->nullable();
            $table->double('exercice')->nullable();
            $table->string('archive')->nullable();
            $table->string('soldetouche')->nullable();
            $table->dateTime('dtgeneraloper')->nullable();
            $table->string('operationsurfacture')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_arret_situation_compte');
    }
};
