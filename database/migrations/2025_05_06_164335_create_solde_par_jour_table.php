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
        Schema::create('solde_par_jour', function (Blueprint $table) {
            $table->integer('idoperation')->nullable();
            $table->double('solde_debit')->nullable();
            $table->double('Solde_Credit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('solde_par_jour');
    }
};
