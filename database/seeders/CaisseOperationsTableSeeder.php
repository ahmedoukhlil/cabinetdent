<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CaisseOperationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $operations = [
            [
                'cle' => 1,
                'dateoper' => Carbon::now()->subDays(5),
                'MontantOperation' => 50000,
                'designation' => 'Consultation',
                'fkidTiers' => 1,
                'entreEspece' => 1,
                'retraitEspece' => 0,
                'pourPatFournisseur' => 0,
                'pourCabinet' => 0,
                'fkiduser' => 1,
                'exercice' => 2025,
                'fkIdTypeTiers' => 1,
                'fkidfacturebord' => 0,
                'DtCr' => Carbon::now(),
                'fkidcabinet' => 1,
                'fkidtypePaie' => 1,
                'TypePAie' => 'CASH',
                'fkidmedecin' => 1,
                'medecin' => 'Dr. Diop Amadou'
            ],
            [
                'cle' => 2,
                'dateoper' => Carbon::now()->subDays(4),
                'MontantOperation' => 75000,
                'designation' => 'Détartrage',
                'fkidTiers' => 2,
                'entreEspece' => 1,
                'retraitEspece' => 0,
                'pourPatFournisseur' => 0,
                'pourCabinet' => 0,
                'fkiduser' => 1,
                'exercice' => 2025,
                'fkIdTypeTiers' => 1,
                'fkidfacturebord' => 0,
                'DtCr' => Carbon::now(),
                'fkidcabinet' => 1,
                'fkidtypePaie' => 1,
                'TypePAie' => 'CASH',
                'fkidmedecin' => 2,
                'medecin' => 'Dr. Moctar Lemine'
            ],
            [
                'cle' => 3,
                'dateoper' => Carbon::now()->subDays(3),
                'MontantOperation' => 100000,
                'designation' => 'Soin carie',
                'fkidTiers' => 3,
                'entreEspece' => 1,
                'retraitEspece' => 0,
                'pourPatFournisseur' => 0,
                'pourCabinet' => 0,
                'fkiduser' => 1,
                'exercice' => 2025,
                'fkIdTypeTiers' => 1,
                'fkidfacturebord' => 0,
                'DtCr' => Carbon::now(),
                'fkidcabinet' => 1,
                'fkidtypePaie' => 1,
                'TypePAie' => 'CASH',
                'fkidmedecin' => 1,
                'medecin' => 'Dr. Diop Amadou'
            ],
            [
                'cle' => 4,
                'dateoper' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'MontantOperation' => 25000,
                'designation' => 'Contrôle',
                'entreEspece' => 1,
                'fkidTiers' => 4,
                'fkidmedecin' => 2,
                'medecin' => 'Dr. Moctar Lemine',
                'DtCr' => now(),
                'fkidcabinet' => 1,
                'TypePAie' => 'CASH',
                'fkidtypePaie' => 1
            ],
            [
                'cle' => 5,
                'dateoper' => Carbon::now()->subDays(1)->format('Y-m-d'),
                'MontantOperation' => 150000,
                'designation' => 'Extraction',
                'entreEspece' => 1,
                'fkidTiers' => 5,
                'fkidmedecin' => 1,
                'medecin' => 'Dr. Diop Amadou',
                'DtCr' => now(),
                'fkidcabinet' => 1,
                'TypePAie' => 'CASH',
                'fkidtypePaie' => 1
            ]
        ];

        foreach ($operations as $operation) {
            DB::table('caisse_operations')->insert($operation);
        }
    }
}
