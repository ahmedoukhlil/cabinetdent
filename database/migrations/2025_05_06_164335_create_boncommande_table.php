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
        Schema::create('boncommande', function (Blueprint $table) {
            $table->increments('idBC');
            $table->string('NumBC', 45)->default('')->index('Index_2');
            $table->dateTime('DtCreation')->nullable();
            $table->unsignedInteger('num')->default(0);
            $table->double('Annee')->default(0);
            $table->unsignedInteger('fkidfournisseur')->default(0)->index('Index_3');
            $table->string('fournisseur', 145)->nullable();
            $table->string('TypeDemande', 45)->default('Bon de commande')->index('Index_4');
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
        Schema::dropIfExists('boncommande');
    }
};
