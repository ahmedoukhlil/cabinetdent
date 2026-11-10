<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consommables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->string('nom', 150);
            $table->string('categorie', 30);
            $table->string('unite', 30)->default('unité');
            $table->decimal('quantite', 12, 2)->default(0);
            $table->decimal('quantite_min', 12, 2)->default(0);
            // Prix d'achat moyen pondéré, recalculé à chaque entrée.
            $table->decimal('prix_achat', 12, 2)->default(0);
            // Date de péremption du lot le plus proche (saisie à l'entrée).
            $table->date('peremption')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['cabinet_id', 'nom']);
        });

        Schema::create('mouvements_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consommable_id')->constrained('consommables')->cascadeOnDelete();
            // entree : achat ; sortie : utilisation ; ajustement : inventaire (quantité signée).
            $table->string('type', 20);
            $table->decimal('quantite', 12, 2);
            $table->decimal('stock_apres', 12, 2);
            $table->decimal('prix_unitaire', 12, 2)->nullable();
            $table->date('peremption')->nullable();
            $table->string('motif', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cabinet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_stock');
        Schema::dropIfExists('consommables');
    }
};
