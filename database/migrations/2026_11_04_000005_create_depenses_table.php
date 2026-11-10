<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->date('date_depense');
            $table->string('categorie', 30);
            $table->string('libelle', 150);
            $table->decimal('montant', 12, 2);
            $table->string('mode', 20);
            $table->string('reference', 100)->nullable();
            // Praticien concerné (prothésiste, fournitures personnelles…) ; null = frais du cabinet.
            $table->foreignId('dentiste_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motif_suppression')->nullable();
            $table->foreignId('supprime_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cabinet_id', 'date_depense']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
