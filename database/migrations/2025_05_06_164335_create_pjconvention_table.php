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
        Schema::create('pjconvention', function (Blueprint $table) {
            $table->integer('IdPJ', true);
            $table->integer('fkidassureur')->nullable()->index('Index_2');
            $table->string('NomFichier')->nullable()->index('Index_3');
            $table->mediumText('Chemindaccess')->nullable();
            $table->dateTime('dtajoutPJ')->nullable();
            $table->string('user')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pjconvention');
    }
};
