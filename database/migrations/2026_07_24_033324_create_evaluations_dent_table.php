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
        Schema::create('evaluations_dent', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id');
            $table->string('num_dent', 2);
            $table->string('etat_email')->nullable();
            $table->string('etat_dentine')->nullable();
            $table->string('etat_pulpe')->nullable();
            $table->string('etat_racine')->nullable();
            $table->string('etat_parodonte')->nullable();
            $table->integer('medecin_id')->nullable();
            $table->integer('cabinet_id')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('ID')->on('patients')->cascadeOnDelete();
            $table->foreign('medecin_id')->references('idMedecin')->on('medecins')->nullOnDelete();

            $table->index(['patient_id', 'num_dent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations_dent');
    }
};
