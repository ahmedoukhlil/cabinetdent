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
        Schema::create('rendezvous', function (Blueprint $table) {
            $table->integer('IDRdv', true);
            $table->mediumText('ActePrevu')->nullable();
            $table->dateTime('DtAjRdv')->nullable();
            $table->dateTime('dtPrevuRDV')->nullable()->index('Index_3');
            $table->string('user')->nullable();
            $table->dateTime('HeureRdv')->nullable();
            $table->unsignedInteger('fkidPatient')->nullable()->index('Index_4');
            $table->string('rdvConfirmer', 20)->default('En Attente')->index('Index_5');
            $table->unsignedInteger('fkidMedecin')->nullable()->index('Index_6');
            $table->unsignedInteger('OrdreRDV')->nullable();
            $table->dateTime('HeureConfRDV')->nullable();
            $table->unsignedInteger('fkidcabinet')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rendezvous');
    }
};
