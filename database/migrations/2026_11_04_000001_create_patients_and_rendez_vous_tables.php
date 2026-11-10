<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('numero_fiche');
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->char('sexe', 1)->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('nni', 30)->nullable();
            $table->string('adresse')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['cabinet_id', 'numero_fiche']);
            $table->index(['cabinet_id', 'nom']);
            $table->index(['cabinet_id', 'telephone']);
        });

        Schema::create('rendez_vous', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dentiste_id')->constrained('users');
            $table->date('date');
            $table->time('heure');
            $table->unsignedSmallInteger('ordre');
            $table->string('acte_prevu')->nullable();
            $table->string('statut', 20)->default('planifie');
            $table->timestamp('arrive_at')->nullable();
            $table->timestamp('debut_at')->nullable();
            $table->timestamp('fin_at')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cabinet_id', 'date', 'dentiste_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendez_vous');
        Schema::dropIfExists('patients');
    }
};
