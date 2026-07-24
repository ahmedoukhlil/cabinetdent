<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Dossier médical permanent par patient
        Schema::create('dossier_medical', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fkidPatient')->index();
            $table->unsignedInteger('fkidCabinet')->default(1)->index();

            // Antécédents
            $table->text('antecedents_personnels')->nullable();
            $table->text('antecedents_familiaux')->nullable();
            $table->text('antecedents_chirurgicaux')->nullable();

            // Informations permanentes
            $table->string('groupe_sanguin', 10)->nullable();   // A+, B-, O+...
            $table->text('allergies')->nullable();
            $table->text('maladies_chroniques')->nullable();    // diabète, HTA...
            $table->text('traitements_permanents')->nullable(); // médicaments au long cours
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        // Une consultation médicale = une visite
        Schema::create('consultation_medicale', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fkidPatient')->index();
            $table->unsignedInteger('fkidMedecin')->nullable()->index();
            $table->unsignedInteger('fkidCabinet')->default(1)->index();
            $table->unsignedInteger('fkidFacture')->nullable(); // lien optionnel facture

            // Date de la consultation
            $table->dateTime('date_consultation');

            // Motif
            $table->text('motif')->nullable();

            // Constantes vitales
            $table->string('temperature', 10)->nullable();       // ex: 37.5
            $table->string('tension_arterielle', 20)->nullable(); // ex: 120/80
            $table->string('frequence_cardiaque', 10)->nullable(); // ex: 72
            $table->string('spo2', 10)->nullable();               // ex: 98%
            $table->string('poids', 10)->nullable();              // kg
            $table->string('taille', 10)->nullable();             // cm

            // Clinique
            $table->text('examen_clinique')->nullable();
            $table->text('diagnostic')->nullable();
            $table->text('conduite_a_tenir')->nullable();  // traitement prescrit, examens demandés

            // Médicaments prescrits (JSON : [{nom, posologie, duree}])
            $table->json('medicaments_prescrits')->nullable();

            // Examens demandés (JSON : [{type, libelle}])
            $table->json('examens_demandes')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('consultation_medicale');
        Schema::dropIfExists('dossier_medical');
    }
};
