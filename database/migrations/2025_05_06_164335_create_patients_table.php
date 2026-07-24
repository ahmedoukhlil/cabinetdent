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
        Schema::create('patients', function (Blueprint $table) {
            $table->integer('ID', true);
            $table->string('Prenom')->nullable();
            $table->string('Nom')->nullable()->index('Index_10');
            $table->string('NNI')->nullable();
            $table->dateTime('DtNaissance')->nullable();
            $table->string('Genre')->nullable();
            $table->string('IdentifiantAssurance')->nullable();
            $table->integer('Assureur')->default(1)->index('Index_3');
            $table->string('Telephone1')->nullable()->index('Index_5');
            $table->string('Telephone2')->nullable()->index('Index_4');
            $table->string('MatriculeFonct')->nullable();
            $table->string('NomContact', 243)->nullable()->index('Index_2');
            $table->string('ClasserSous', 243)->nullable();
            $table->boolean('choix')->nullable();
            $table->string('user')->nullable();
            $table->double('IdentifiantPatient')->default(0)->index('Index_6');
            $table->dateTime('Dtajout')->nullable();
            $table->unsignedInteger('fkidUser')->nullable();
            $table->string('Adresse', 245)->nullable();
            $table->dateTime('DerniereDtOper')->nullable()->index('Index_9');
            $table->unsignedInteger('DernDossierFermer')->default(0);
            $table->dateTime('DtDernFermeture')->nullable();
            $table->unsignedInteger('fkidtypeTiers')->default(1)->index('Index_7');
            $table->dateTime('DtDernierRDV')->nullable()->index('Index_8');
            $table->unsignedInteger('fkidcabinet')->default(1)->index('Index_11');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients');
    }
};
