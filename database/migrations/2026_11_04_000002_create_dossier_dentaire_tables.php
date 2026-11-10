<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // État actuel de chaque dent (une ligne par dent et par patient).
        Schema::create('dents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->string('presence', 30)->default('presente');
            $table->string('restauration', 30)->default('aucune');
            $table->string('endodontie', 30)->default('non_traitee');
            $table->json('faces')->nullable();
            $table->json('constatations')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('modifie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['patient_id', 'numero']);
        });

        // Journal des modifications d'une dent, pour l'historique clinique.
        Schema::create('dent_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->json('avant')->nullable();
            $table->json('apres');
            $table->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['patient_id', 'numero']);
        });

        Schema::create('plans_traitement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('titre')->default('Plan de traitement');
            $table->string('statut', 20)->default('brouillon');
            $table->timestamp('accepte_at')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('plan_traitement_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_traitement_id')->constrained('plans_traitement')->cascadeOnDelete();
            $table->json('dents')->nullable();
            $table->string('libelle');
            $table->decimal('quantite', 8, 2)->default(1);
            $table->decimal('prix_unitaire', 12, 2)->default(0);
            $table->string('statut', 20)->default('prevu');
            $table->timestamp('realise_at')->nullable();
            $table->foreignId('realise_par')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_traitement_lignes');
        Schema::dropIfExists('plans_traitement');
        Schema::dropIfExists('dent_historiques');
        Schema::dropIfExists('dents');
    }
};
