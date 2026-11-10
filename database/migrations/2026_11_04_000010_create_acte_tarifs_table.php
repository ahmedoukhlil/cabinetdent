<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prix d'un acte propre à un assureur ; sans ligne ici, le prix de l'acte (sans assurance) s'applique.
        Schema::create('acte_tarifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('acte_id')->constrained('actes')->cascadeOnDelete();
            $table->foreignId('assureur_id')->constrained('assureurs')->cascadeOnDelete();
            $table->decimal('prix', 12, 2);
            $table->timestamps();

            $table->unique(['acte_id', 'assureur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acte_tarifs');
    }
};
