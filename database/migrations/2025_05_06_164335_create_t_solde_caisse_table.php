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
        Schema::create('t_solde_caisse', function (Blueprint $table) {
            $table->integer('idoper', true);
            $table->string('typeoperation')->nullable();
            $table->double('idtypeoperation')->nullable();
            $table->double('xclie')->nullable();
            $table->double('especesortie')->nullable();
            $table->double('especeentre')->nullable();
            $table->dateTime('dateoper')->nullable();
            $table->dateTime('dtgeneral')->nullable();
            $table->double('iduser')->nullable();
            $table->double('exercice')->nullable();
            $table->double('xboutique')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_solde_caisse');
    }
};
