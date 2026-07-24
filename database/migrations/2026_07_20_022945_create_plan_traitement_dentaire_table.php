<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plan_traitement_dentaire', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id');
            $table->string('num_dent', 2); // notation FDI '11'..'48'
            $table->integer('acte_id');
            $table->string('acte_libelle', 245);
            $table->integer('medecin_id')->nullable();
            $table->enum('statut', ['planifie', 'en_cours', 'termine'])->default('planifie');
            $table->decimal('prix_ref', 10, 2)->nullable();
            $table->integer('cabinet_id')->nullable();
            $table->integer('detail_facture_id')->nullable();
            $table->integer('facture_id')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('ID')->on('patients')->cascadeOnDelete();
            $table->foreign('acte_id')->references('ID')->on('actes');
            $table->foreign('medecin_id')->references('idMedecin')->on('medecins');
            $table->foreign('detail_facture_id')->references('idDetfacture')->on('detailfacturepatient')->nullOnDelete();
            $table->foreign('facture_id')->references('Idfacture')->on('facture')->nullOnDelete();

            $table->index(['patient_id', 'num_dent']);
            $table->index(['patient_id', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_traitement_dentaire');
    }
};
