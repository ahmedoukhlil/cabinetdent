<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('libelle');
            $table->decimal('prix', 12, 2)->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['cabinet_id', 'libelle']);
        });

        Schema::create('assureurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            // Taux de prise en charge entre 0 et 1 (0,8 = 80 %).
            $table->decimal('taux_pec', 5, 4)->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('assureur_id')->nullable()->after('adresse')->constrained()->nullOnDelete();
            $table->string('matricule_assure', 50)->nullable()->after('assureur_id');
        });

        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('dentiste_id')->nullable()->constrained('users')->nullOnDelete();
            // Numéro attribué à la validation uniquement, pour une numérotation sans trou.
            $table->string('numero', 20)->nullable();
            $table->unsignedSmallInteger('annee')->nullable();
            $table->unsignedInteger('sequence')->nullable();
            $table->string('statut', 20)->default('brouillon');
            $table->date('date_facture')->nullable();
            $table->foreignId('assureur_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('taux_pec', 5, 4)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('part_pec', 12, 2)->default(0);
            $table->decimal('part_patient', 12, 2)->default(0);
            $table->decimal('regle_patient', 12, 2)->default(0);
            $table->decimal('regle_pec', 12, 2)->default(0);
            $table->timestamp('validee_at')->nullable();
            $table->timestamp('annulee_at')->nullable();
            $table->string('motif_annulation')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cabinet_id', 'annee', 'sequence']);
            $table->index(['cabinet_id', 'statut']);
        });

        Schema::create('facture_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained()->cascadeOnDelete();
            $table->foreignId('acte_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_ligne_id')->nullable()->unique()->constrained('plan_traitement_lignes')->nullOnDelete();
            $table->string('libelle');
            $table->json('dents')->nullable();
            $table->decimal('quantite', 8, 2)->default(1);
            $table->decimal('prix_unitaire', 12, 2)->default(0);
            $table->decimal('montant', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facture_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('dentiste_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payeur', 20);
            $table->string('type', 20);
            $table->string('mode', 20);
            // Toujours positif ; le type indique encaissement ou remboursement.
            $table->decimal('montant', 12, 2);
            $table->string('reference', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['cabinet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
        Schema::dropIfExists('facture_lignes');
        Schema::dropIfExists('factures');
        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assureur_id');
            $table->dropColumn('matricule_assure');
        });
        Schema::dropIfExists('assureurs');
        Schema::dropIfExists('actes');
    }
};
