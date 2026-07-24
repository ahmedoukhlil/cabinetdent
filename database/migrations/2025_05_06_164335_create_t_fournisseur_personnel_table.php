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
        Schema::create('t_fournisseur_personnel', function (Blueprint $table) {
            $table->integer('IDFournisseur', true);
            $table->string('NomTiers')->nullable();
            $table->string('TelephoneAutre')->nullable();
            $table->double('fkidtypeTiers')->nullable();
            $table->double('userCr')->nullable();
            $table->unsignedInteger('fkidcaibnet')->default(1)->index('Index_2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('t_fournisseur_personnel');
    }
};
