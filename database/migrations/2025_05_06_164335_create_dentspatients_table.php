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
        Schema::create('dentspatients', function (Blueprint $table) {
            $table->increments('idDentsPatients');
            $table->unsignedInteger('fkidfacture')->default(0)->index('Index_2');
            $table->string('NumDent', 45)->default('')->index('Index_3');
            $table->timestamp('DtAjout')->nullable();
            $table->unsignedInteger('fkidpatient')->default(0)->index('Index_4');
            $table->unsignedInteger('FkidActe')->default(0)->index('Index_5');
            $table->string('acte', 245)->default('');
            $table->unsignedInteger('fkidmedecin')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dentspatients');
    }
};
