<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MedecinsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();
        
        $medecins = [
            [
                'Nom' => 'Dr. Diop Amadou',
                'Contact' => '0600000001',
                'DtAjout' => $now,
                'fkidcabinet' => 1
            ],
            [
                'Nom' => 'Dr. Moctar Lemine',
                'Contact' => '0600000002',
                'DtAjout' => $now,
                'fkidcabinet' => 1
            ]
        ];

        foreach ($medecins as $medecin) {
            DB::table('medecins')->insert($medecin);
        }
    }
}
