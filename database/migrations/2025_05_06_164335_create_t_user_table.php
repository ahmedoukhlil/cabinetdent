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
        Schema::create('t_user', function (Blueprint $table) {
            $table->increments('Iduser');
            $table->string('login', 45)->default('');
            $table->string('password', 150);
            $table->unsignedInteger('ismasquer')->default(0);
            $table->string('NomComplet', 45)->default('');
            $table->unsignedInteger('IdClasseUser')->default(1)->comment('1 Secretaire, 2 Doct,3 Propreitaire,4 Doct Proprietaire');
            $table->string('fonction', 45)->nullable();
            $table->unsignedInteger('fkidmedecin')->default(0);
            $table->dateTime('DtCr')->nullable();
            $table->unsignedInteger('fkidcabinet')->default(1)->index('Index_2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_user');
    }
};
