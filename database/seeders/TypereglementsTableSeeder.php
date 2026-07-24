<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypereglementsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $typesReglements = [
            ['idreglement' => 1, 'TypeReglement' => 'Consultation'],
            ['idreglement' => 2, 'TypeReglement' => 'Traitement'],
            ['idreglement' => 3, 'TypeReglement' => 'Fournitures'],
            ['idreglement' => 4, 'TypeReglement' => 'Équipement'],
            ['idreglement' => 5, 'TypeReglement' => 'Maintenance'],
            ['idreglement' => 6, 'TypeReglement' => 'Loyer'],
            ['idreglement' => 7, 'TypeReglement' => 'Électricité'],
            ['idreglement' => 8, 'TypeReglement' => 'Eau'],
            ['idreglement' => 9, 'TypeReglement' => 'Internet'],
            ['idreglement' => 10, 'TypeReglement' => 'Salaire'],
            ['idreglement' => 11, 'TypeReglement' => 'Autre'],
        ];

        foreach ($typesReglements as $type) {
            DB::table('typereglements')->updateOrInsert(
                ['idreglement' => $type['idreglement']],
                $type
            );
        }
    }
}
