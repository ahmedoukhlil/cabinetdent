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
        if (!Schema::hasTable('actes')) {
            Schema::create('actes', function (Blueprint $table) {
            $table->integer('ID', true);
            $table->string('Acte')->nullable();
            $table->double('PrixRef')->default(0);
            $table->unsignedInteger('fkidTypeActe')->default(0);
            $table->integer('nordre')->default(1);
            $table->string('user')->default('1');
            $table->unsignedInteger('fkidassureur')->default(1)->index('Index_2');
            $table->string('ActeArab', 245)->nullable();
            $table->unsignedInteger('Masquer')->default(0)->index('Index_3');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('actes');
    }
};
