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
        Schema::create('t_arret_compte', function (Blueprint $table) {
            $table->integer('iderret')->nullable();
            $table->double('ncompte')->nullable();
            $table->double('soldedebit')->nullable();
            $table->double('soldecredit')->nullable();
            $table->double('idboutique')->nullable();
            $table->double('exercice')->nullable();
            $table->double('fkidcleoperation')->nullable();
            $table->double('User_code')->nullable();
            $table->dateTime('dtgeneral')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_arret_compte');
    }
};
