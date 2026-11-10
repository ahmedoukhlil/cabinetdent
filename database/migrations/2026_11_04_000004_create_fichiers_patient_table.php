<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fichiers_patient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->string('categorie', 20);
            $table->string('titre', 150);
            $table->date('date_examen')->nullable();
            $table->json('dents')->nullable();
            $table->string('chemin');
            $table->string('nom_origine');
            $table->string('mime', 100);
            $table->unsignedBigInteger('taille');
            $table->foreignId('ajoute_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cabinet_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fichiers_patient');
    }
};
