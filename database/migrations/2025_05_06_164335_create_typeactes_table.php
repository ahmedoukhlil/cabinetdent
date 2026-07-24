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
        Schema::create('typeactes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('Type', 45)->default('');
            $table->string('CodeType', 45)->default('');
            $table->unsignedInteger('ISvisible')->default(1);
            $table->unsignedInteger('NumeroOrdre')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('typeactes');
    }
};
