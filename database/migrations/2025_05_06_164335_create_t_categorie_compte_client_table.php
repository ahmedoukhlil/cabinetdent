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
        Schema::create('t_categorie_compte_client', function (Blueprint $table) {
            $table->integer('Codecategorie', true);
            $table->string('libecate')->nullable();
            $table->integer('cle')->nullable();
            $table->double('clas')->nullable();
            $table->double('numecompcate')->nullable();
            $table->string('libecatearab', 250)->nullable();
            $table->double('derniernum')->nullable();
            $table->double('isvisible')->nullable();
            $table->double('isvisiblefact')->nullable();
            $table->double('isvisibleoper')->nullable();
            $table->double('numTris')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_categorie_compte_client');
    }
};
