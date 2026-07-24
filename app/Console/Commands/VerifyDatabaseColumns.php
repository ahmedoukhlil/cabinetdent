<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyDatabaseColumns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:verify-columns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier que toutes les colonnes utilisées dans les optimisations existent dans les tables';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Vérification des colonnes de base de données...');

        $tables = [
            'assureurs' => ['IDAssureur', 'LibAssurance', 'TauxdePEC', 'SeuilDevis'],
            'ref_type_paiement' => ['idtypepaie', 'LibPaie'],
            'actes' => ['ID', 'Acte', 'PrixRef', 'nordre', 'Masquer'],
            'medecins' => ['idMedecin', 'Nom', 'Contact', 'fkidcabinet'],
            't_user' => ['Iduser', 'NomComplet', 'fkidmedecin', 'fkidcabinet', 'ismasquer'],
            'rendezvous' => ['IDRdv', 'dtPrevuRDV', 'HeureRdv', 'OrdreRDV', 'ActePrevu', 'rdvConfirmer', 'fkidPatient', 'fkidMedecin', 'fkidcabinet'],
            'patients' => ['ID', 'Nom', 'Prenom', 'Telephone1', 'Telephone2']
        ];

        $errors = [];
        $success = [];

        foreach ($tables as $table => $columns) {
            $this->info("📋 Vérification de la table: {$table}");
            
            if (!Schema::hasTable($table)) {
                $errors[] = "❌ Table '{$table}' n'existe pas";
                continue;
            }

            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $success[] = "✅ {$table}.{$column}";
                } else {
                    $errors[] = "❌ Colonne '{$column}' n'existe pas dans la table '{$table}'";
                }
            }
        }

        // Afficher les résultats
        if (!empty($success)) {
            $this->info('✅ Colonnes existantes:');
            foreach ($success as $item) {
                $this->line("  {$item}");
            }
        }

        if (!empty($errors)) {
            $this->error('❌ Erreurs détectées:');
            foreach ($errors as $error) {
                $this->error("  {$error}");
            }
            return 1;
        }

        $this->info('🎉 Toutes les colonnes sont correctes !');
        return 0;
    }
}
