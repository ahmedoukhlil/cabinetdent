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
        Schema::create('currentuser', function (Blueprint $table) {
            $table->integer('User_code')->default(0)->primary();
            $table->string('User_Nom_Connect', 10)->nullable();
            $table->string('User_Pwd', 10)->nullable();
            $table->string('User_fonction')->nullable();
            $table->string('User_NomPren', 50)->nullable();
            $table->double('visibleuser')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('currentuser');
    }
};
