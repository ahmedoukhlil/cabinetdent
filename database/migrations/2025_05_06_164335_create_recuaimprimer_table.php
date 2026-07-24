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
        Schema::create('recuaimprimer', function (Blueprint $table) {
            $table->increments('IdRecu');
            $table->dateTime('DtOperation')->nullable();
            $table->string('TypeOperation', 45)->nullable()->default('');
            $table->double('MontantOperation')->default(0);
            $table->string('Beneficiaire', 45)->nullable();
            $table->string('TypeBeneficiaire', 45)->nullable();
            $table->string('MontantEnLettre', 145)->nullable();
            $table->unsignedInteger('fkidUser')->default(0);
            $table->string('Motif', 145)->nullable();
            $table->string('Medecin', 45)->nullable();
            $table->string('Utilisateur', 45)->nullable();
            $table->unsignedInteger('fkidCabinet')->default(1);
            $table->unsignedInteger('fkidtypePaie')->default(1);
            $table->string('TypePAie', 45)->default('CASH');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recuaimprimer');
    }
};
