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
        Schema::create('medecins', function (Blueprint $table) {
            $table->integer('idMedecin', true);
            $table->string('Nom')->nullable();
            $table->string('Contact', 45)->default('Aucun');
            $table->dateTime('DtAjout')->nullable();
            $table->unsignedInteger('fkidcabinet')->default(1)->index('Index_2');
            $table->longText('cachetMedecin')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('medecins');
    }
};
