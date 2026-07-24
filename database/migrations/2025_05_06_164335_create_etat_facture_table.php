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
        Schema::create('etat_facture', function (Blueprint $table) {
            $table->integer('idetatfacture', true);
            $table->double('idetatfact')->nullable()->index('Index_2');
            $table->string('libelle')->nullable();
            $table->string('niveau')->nullable();
            $table->double('iscaisse')->nullable();
            $table->double('isvisibledevis')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('etat_facture');
    }
};
