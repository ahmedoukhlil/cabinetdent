<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Comptes de l'administrateur plateforme : séparés des comptes de cabinet (garde « plateforme »).
        Schema::create('admins_plateforme', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('plans_abonnement', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100)->unique();
            $table->decimal('prix_mensuel', 12, 2);
            // null = illimité.
            $table->unsignedInteger('max_utilisateurs')->nullable();
            $table->unsignedInteger('max_stockage_mo')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans_abonnement');
            // essai, actif, impaye, suspendu, resilie.
            $table->string('statut', 20);
            $table->date('essai_fin')->nullable();
            $table->date('periode_fin')->nullable();
            $table->date('impaye_depuis')->nullable();
            $table->timestamp('suspendu_at')->nullable();
            $table->timestamp('resilie_at')->nullable();
            $table->string('motif_resiliation')->nullable();
            $table->timestamps();

            $table->index(['cabinet_id', 'statut']);
        });

        Schema::create('paiements_abonnement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonnement_id')->constrained('abonnements')->cascadeOnDelete();
            $table->decimal('montant', 12, 2);
            $table->unsignedSmallInteger('mois');
            $table->date('periode_debut');
            $table->date('periode_fin');
            $table->string('mode', 20);
            $table->string('reference', 100)->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins_plateforme')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_abonnement');
        Schema::dropIfExists('abonnements');
        Schema::dropIfExists('plans_abonnement');
        Schema::dropIfExists('admins_plateforme');
    }
};
