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
        Schema::create('t_banque', function (Blueprint $table) {
            $table->integer('Id_BNQ', true);
            $table->string('LibBanque')->nullable();
            $table->string('Ncompte_Bnq')->nullable();
            $table->string('CompteDebit')->nullable();
            $table->double('Isvisible')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_banque');
    }
};
