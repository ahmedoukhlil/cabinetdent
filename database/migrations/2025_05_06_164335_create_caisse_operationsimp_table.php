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
        Schema::create('caisse_operationsimp', function (Blueprint $table) {
            $table->integer('cle', true);
            $table->dateTime('dateoper')->nullable()->index('Index_3');
            $table->double('MontantOperation')->nullable();
            $table->string('designation')->nullable();
            $table->string('Tiers')->nullable()->index('Index_2');
            $table->double('entreEspece')->default(0);
            $table->double('retraitEspece')->default(0);
            $table->double('pourPatFournisseur')->default(0);
            $table->double('pourCabinet')->default(0);
            $table->integer('fkiduser')->default(1)->index('Index_4');
            $table->double('exercice')->nullable()->index('Index_5');
            $table->string('TypeTiers', 200)->nullable()->index('Index_6');
            $table->double('fkidfacturebord')->default(0)->index('Index_7');
            $table->dateTime('DtCr')->nullable();
            $table->unsignedInteger('fkidCabinet')->default(1);
            $table->unsignedInteger('fkidtypePaie')->default(1)->index('Index_8');
            $table->string('TypePAie', 45)->default('CASH')->index('Index_9');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('caisse_operationsimp');
    }
};
