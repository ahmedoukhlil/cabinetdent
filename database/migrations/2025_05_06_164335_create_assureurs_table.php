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
        Schema::create('assureurs', function (Blueprint $table) {
            $table->integer('IDAssureur', true);
            $table->string('LibAssurance')->nullable();
            $table->double('TauxdePEC')->nullable();
            $table->double('SeuilDevis')->default(0);
            $table->integer('ISTP')->default(0);
            $table->dateTime('DtConvention')->nullable();
            $table->string('ContactAssureur')->nullable();
            $table->string('AdresseMail')->nullable();
            $table->string('user')->nullable();
            $table->string('Adresse', 45)->nullable();
            $table->unsignedInteger('fkidtypeTiers')->default(4);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assureurs');
    }
};
