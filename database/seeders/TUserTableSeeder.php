<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();
        
        $users = [
            [
                'login' => 'docteur1',
                'password' => Hash::make('docteur123'),
                'ismasquer' => 0,
                'NomComplet' => 'Dr. Diop Amadou',
                'IdClasseUser' => 3, // Doct Proprietaire
                'fonction' => 'Dentiste',
                'fkidmedecin' => 1,
                'DtCr' => $now,
                'fkidcabinet' => 1
            ],
            [
                'login' => 'secretaire',
                'password' => Hash::make('secretaire123'),
                'ismasquer' => 0,
                'NomComplet' => 'Secrétaire Cabinet Savwa',
                'IdClasseUser' => 1, // Secrétaire
                'fonction' => 'Secrétaire',
                'fkidmedecin' => 0,
                'DtCr' => $now,
                'fkidcabinet' => 1
            ],
            [
                'login' => 'docteur2',
                'password' => Hash::make('docteur123'),
                'ismasquer' => 0,
                'NomComplet' => 'Dr. Moctar Lemine',
                'IdClasseUser' => 2, // Docteur
                'fonction' => 'Dentiste',
                'fkidmedecin' => 2,
                'DtCr' => $now,
                'fkidcabinet' => 1
            ]
        ];

        foreach ($users as $user) {
            DB::table('t_user')->insert($user);
        }
    }
}
