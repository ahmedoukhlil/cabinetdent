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
        Schema::create('t_depenses_recette', function (Blueprint $table) {
            $table->integer('iddepenserecette', true);
            $table->dateTime('dtdepense')->nullable()->index('Index_5');
            $table->dateTime('dtajoutdepense')->nullable();
            $table->double('idexercice')->nullable()->index('Index_2');
            $table->string('designation')->nullable();
            $table->string('modereglement')->nullable();
            $table->string('numtitreregl')->nullable();
            $table->string('typedep')->nullable()->index('Index_3');
            $table->double('xbeneficiaire')->nullable()->index('Index_4');
            $table->double('mntentree')->nullable();
            $table->double('mntsortie')->nullable();
            $table->string('nomuser')->nullable();
            $table->string('user')->nullable();
            $table->unsignedInteger('fkidCabinet')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_depenses_recette');
    }
};
