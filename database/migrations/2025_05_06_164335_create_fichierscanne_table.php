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
        Schema::create('fichierscanne', function (Blueprint $table) {
            $table->integer('idfichier', true);
            $table->string('NomFichier')->nullable()->index('Index_4');
            $table->mediumText('Chemindaccess')->nullable();
            $table->string('Numfacture')->nullable();
            $table->double('Fkidfacture')->nullable()->index('Index_2');
            $table->integer('fkidpatient')->nullable()->index('Index_3');
            $table->dateTime('dtajoutPJ')->nullable();
            $table->string('user')->nullable();
            $table->string('CategoriePJ', 45)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fichierscanne');
    }
};
