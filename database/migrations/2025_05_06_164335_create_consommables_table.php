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
        Schema::create('consommables', function (Blueprint $table) {
            $table->increments('idConsommables');
            $table->string('Libelle', 45)->default('');
            $table->double('Quantite')->default(1);
            $table->double('PrixUnit')->default(0);
            $table->double('SousTotal')->default(0);
            $table->dateTime('DtAchat')->nullable()->index('Index_6');
            $table->double('fkidFournisseur')->default(1)->index('Index_4');
            $table->unsignedInteger('fkidUser')->default(1);
            $table->string('Designation', 145)->nullable();
            $table->double('fkidFacturePat')->default(0)->index('Index_3');
            $table->unsignedInteger('IscommandePat')->default(0)->index('Index_2');
            $table->unsignedInteger('fkidProduit')->default(0)->index('Index_5');
            $table->string('NumfactFourniss', 45)->default('Non Renseigne');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consommables');
    }
};
