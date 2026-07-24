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
        Schema::create('typetiers', function (Blueprint $table) {
            $table->increments('IdTypeTiers');
            $table->string('LibelleTypeTiers', 45)->default('');
            $table->unsignedInteger('Estvisible')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('typetiers');
    }
};
