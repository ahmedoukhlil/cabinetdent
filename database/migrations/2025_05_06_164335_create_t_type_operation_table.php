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
        Schema::create('t_type_operation', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('liboperation')->nullable();
            $table->double('isvisibleemployer')->nullable();
            $table->double('isvisibleclient')->nullable();
            $table->double('iscaisse')->nullable();
            $table->string('lib')->nullable();
            $table->string('cat_operation')->nullable();
            $table->double('isvisiblerech')->nullable();
            $table->string('lib2')->nullable();
            $table->double('categorie')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_type_operation');
    }
};
