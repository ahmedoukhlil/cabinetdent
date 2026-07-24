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
        Schema::create('ordonnances', function (Blueprint $table) {
            $table->integer('IDOrdonnances', true);
            $table->mediumText('Libelle')->nullable();
            $table->dateTime('DtPrescription')->nullable();
            $table->integer('fkidrefOrd')->nullable()->index('Index_2');
            $table->integer('NumordreOrd')->nullable();
            $table->string('Utilisation', 45)->nullable();
            $table->unsignedInteger('fkiduser')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ordonnances');
    }
};
