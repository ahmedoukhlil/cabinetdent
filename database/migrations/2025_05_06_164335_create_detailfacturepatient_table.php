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
        Schema::create('detailfacturepatient', function (Blueprint $table) {
            $table->integer('idDetfacture', true);
            $table->integer('fkidfacture')->default(0)->index('Index_2');
            $table->dateTime('DtAjout')->nullable();
            $table->string('Actes')->nullable();
            $table->double('PrixRef')->nullable();
            $table->double('PrixFacture')->nullable();
            $table->double('Quantite')->nullable();
            $table->integer('fkidMedecin')->default(1)->index('Index_3');
            $table->dateTime('DTajout2')->nullable();
            $table->string('user')->nullable();
            $table->string('Dents', 145)->nullable();
            $table->dateTime('DtActe')->nullable();
            $table->double('fkidacte')->nullable()->index('Index_4');
            $table->unsignedInteger('IsAct')->default(1);
            $table->string('ActesArab', 245)->default('NR');
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
        Schema::dropIfExists('detailfacturepatient');
    }
};
