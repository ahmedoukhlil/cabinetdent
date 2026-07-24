<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('t_user')->updateOrInsert(
            ['login' => 'admin'],
            [
                'password' => Hash::make('12345678'),
                'ismasquer' => 0,
                'NomComplet' => 'Administrateur',
                'IdClasseUser' => 5, // Administrateur
                'fonction' => 'Administrateur',
                'fkidmedecin' => 0,
                'DtCr' => Carbon::now(),
                'fkidcabinet' => 1,
            ]
        );
    }
}
