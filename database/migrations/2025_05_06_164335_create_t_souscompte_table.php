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
        Schema::create('t_souscompte', function (Blueprint $table) {
            $table->integer('id', true);
            $table->double('Ncompte')->nullable()->index('Index_2');
            $table->double('Numordresouscompte')->nullable();
            $table->string('souscompte')->nullable();
            $table->dateTime('dtcreation')->nullable();
            $table->double('iduser_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_souscompte');
    }
};
