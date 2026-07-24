<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration de documentation : tables existantes en base sans migration correspondante.
 * Ces tables ont été créées manuellement ou par un autre outil.
 * Cette migration est idempotente (createIfNotExists) — elle ne recrée pas ce qui existe.
 */
class CreateMissingTables extends Migration
{
    public function up()
    {
        // ── consultation_medicale ────────────────────────────────────────────
        if (!Schema::hasTable('consultation_medicale')) {
            Schema::create('consultation_medicale', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('fkidPatient');
                $table->unsignedInteger('fkidMedecin')->nullable();
                $table->unsignedInteger('fkidCabinet')->default(1);
                $table->unsignedInteger('fkidFacture')->nullable();
                $table->dateTime('date_consultation');
                $table->text('motif')->nullable();
                $table->string('temperature', 10)->nullable();
                $table->string('tension_arterielle', 20)->nullable();
                $table->string('frequence_cardiaque', 10)->nullable();
                $table->string('spo2', 10)->nullable();
                $table->string('poids', 10)->nullable();
                $table->string('taille', 10)->nullable();
                $table->text('examen_clinique')->nullable();
                $table->text('diagnostic')->nullable();
                $table->text('conduite_a_tenir')->nullable();
                $table->longText('medicaments_prescrits')->nullable();
                $table->longText('examens_demandes')->nullable();
                $table->longText('ordonnances_ids')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // ── donneesmedicales ─────────────────────────────────────────────────
        if (!Schema::hasTable('donneesmedicales')) {
            Schema::create('donneesmedicales', function (Blueprint $table) {
                $table->increments('idDonneesMedicales');
                $table->string('DonneesMedicale', 245);
                $table->unsignedInteger('fkidpatient')->default(1);
                $table->string('ObservationMedicale', 245)->nullable();
                $table->dateTime('DtAjout')->nullable();
                $table->dateTime('DtPatho')->nullable();
            });
        }

        // ── pathologies ──────────────────────────────────────────────────────
        if (!Schema::hasTable('pathologies')) {
            Schema::create('pathologies', function (Blueprint $table) {
                $table->increments('idPathologies');
                $table->string('Pathologie', 245);
            });
        }

        // ── stock_medicaments ────────────────────────────────────────────────
        if (!Schema::hasTable('stock_medicaments')) {
            Schema::create('stock_medicaments', function (Blueprint $table) {
                $table->increments('idStock');
                $table->unsignedInteger('fkidMedicament');
                $table->unsignedInteger('fkidCabinet');
                $table->double('quantiteStock')->default(0);
                $table->double('quantiteMin')->default(0);
                $table->double('prixAchat')->default(0);
                $table->double('prixVente')->default(0);
                $table->dateTime('dateDerniereEntree')->nullable();
                $table->dateTime('dateDerniereSortie')->nullable();
                $table->unsignedInteger('Masquer')->default(0);
            });
        }

        // ── lots_medicaments ─────────────────────────────────────────────────
        if (!Schema::hasTable('lots_medicaments')) {
            Schema::create('lots_medicaments', function (Blueprint $table) {
                $table->increments('idLot');
                $table->unsignedInteger('fkidStock');
                $table->unsignedInteger('fkidMedicament');
                $table->string('numeroLot', 100)->nullable();
                $table->double('quantiteInitiale')->default(0);
                $table->double('quantiteRestante')->default(0);
                $table->date('dateExpiration')->nullable();
                $table->dateTime('dateEntree')->nullable();
                $table->double('prixAchatUnitaire')->default(0);
                $table->string('fournisseur', 255)->nullable();
                $table->string('referenceFacture', 100)->nullable();
                $table->unsignedInteger('fkidUser')->default(1);
                $table->unsignedInteger('Masquer')->default(0);
            });
        }

        // ── mouvements_stock ─────────────────────────────────────────────────
        if (!Schema::hasTable('mouvements_stock')) {
            Schema::create('mouvements_stock', function (Blueprint $table) {
                $table->increments('idMouvement');
                $table->unsignedInteger('fkidStock');
                $table->unsignedInteger('fkidMedicament');
                $table->unsignedInteger('fkidLot')->nullable();
                $table->enum('typeMouvement', ['ENTREE', 'SORTIE', 'AJUSTEMENT']);
                $table->double('quantite');
                $table->double('prixUnitaire')->default(0);
                $table->double('montantTotal')->default(0);
                $table->string('motif', 255)->nullable();
                $table->unsignedInteger('fkidFacture')->nullable();
                $table->unsignedInteger('fkidDetailFacture')->nullable();
                $table->unsignedInteger('fkidPatient')->nullable();
                $table->unsignedInteger('fkidUser');
                $table->dateTime('dateMouvement')->nullable();
                $table->string('reference', 100)->nullable();
                $table->text('notes')->nullable();
            });
        }

        // ── detailpaiefacture ────────────────────────────────────────────────
        if (!Schema::hasTable('detailpaiefacture')) {
            Schema::create('detailpaiefacture', function (Blueprint $table) {
                $table->increments('idDetailPaieFacture');
                $table->string('Nfacture', 45)->nullable();
                $table->string('NomContact', 145)->nullable();
                $table->string('Telephone1', 145)->nullable();
                $table->double('totalReglment')->default(0);
                $table->double('entre')->default(0);
                $table->double('sortie')->default(0);
                $table->double('TotalfactPatient')->default(0);
                $table->string('Designation', 245)->nullable();
                $table->dateTime('dtoperat')->nullable();
            });
        }

        // ── reservationrdv ───────────────────────────────────────────────────
        if (!Schema::hasTable('reservationrdv')) {
            Schema::create('reservationrdv', function (Blueprint $table) {
                $table->increments('idReservation');
                $table->unsignedInteger('fkidmedecin')->default(0);
                $table->dateTime('dtdebut');
                $table->dateTime('dtfin');
                $table->unsignedInteger('annee')->default(2023);
                $table->dateTime('DtCreation');
                $table->unsignedBigInteger('num1')->default(0);
                $table->unsignedBigInteger('num2')->default(0);
            });
        }
    }

    public function down()
    {
        // Ne pas supprimer — ces tables contiennent des données de production
        // Schema::dropIfExists('consultation_medicale');
        // Schema::dropIfExists('donneesmedicales');
        // etc.
    }
}
