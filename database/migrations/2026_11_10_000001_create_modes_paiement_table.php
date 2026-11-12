<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modes de paiement propres à chaque cabinet. Paiements et dépenses gardent le code (colonne « mode »).
        Schema::create('modes_paiement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('libelle', 50);
            $table->boolean('actif')->default(true);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->unique(['cabinet_id', 'code']);
        });

        // Les cabinets existants reçoivent les modes jusqu'ici fixés dans le code.
        $defauts = ['especes' => 'Espèces', 'mobile' => 'Paiement mobile', 'carte' => 'Carte bancaire', 'virement' => 'Virement', 'cheque' => 'Chèque'];
        $maintenant = now();
        foreach (DB::table('cabinets')->pluck('id') as $cabinetId) {
            $ordre = 0;
            foreach ($defauts as $code => $libelle) {
                DB::table('modes_paiement')->insert([
                    'cabinet_id' => $cabinetId, 'code' => $code, 'libelle' => $libelle, 'actif' => true,
                    'ordre' => ++$ordre, 'created_at' => $maintenant, 'updated_at' => $maintenant,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('modes_paiement');
    }
};
