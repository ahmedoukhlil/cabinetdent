<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypetiersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $typesTiers = [
            ['IdTypeTiers' => 1, 'LibelleTypeTiers' => 'Patient', 'Estvisible' => 1],
            ['IdTypeTiers' => 2, 'LibelleTypeTiers' => 'Fournisseur', 'Estvisible' => 1],
            ['IdTypeTiers' => 3, 'LibelleTypeTiers' => 'Médecin', 'Estvisible' => 1],
            ['IdTypeTiers' => 4, 'LibelleTypeTiers' => 'Assureur', 'Estvisible' => 1],
            ['IdTypeTiers' => 5, 'LibelleTypeTiers' => 'Cabinet', 'Estvisible' => 1],
            ['IdTypeTiers' => 6, 'LibelleTypeTiers' => 'Autre', 'Estvisible' => 1],
        ];

        foreach ($typesTiers as $type) {
            DB::table('typetiers')->updateOrInsert(
                ['IdTypeTiers' => $type['IdTypeTiers']],
                $type
            );
        }
    }
}
