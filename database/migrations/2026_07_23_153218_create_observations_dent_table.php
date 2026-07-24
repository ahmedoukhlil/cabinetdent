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
        Schema::create('observations_dent', function (Blueprint $table) {
            $table->id();
            $table->integer('patient_id');
            $table->string('num_dent', 2);
            $table->text('texte');
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
        Schema::dropIfExists('observations_dent');
    }
};
