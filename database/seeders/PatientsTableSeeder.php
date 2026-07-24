<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $patients = [
            [
                'Nom' => 'Diop',
                'Prenom' => 'Fatou',
                'NNI' => '1234567890123',
                'DtNaissance' => '1990-01-15',
                'Genre' => 'F',
                'Telephone1' => '0600000001',
                'Telephone2' => '0700000001',
                'Adresse' => 'Nouakchott, Tevragh Zeina',
                'choix' => 0,
                'Dtajout' => now(),
                'fkidcabinet' => 1
            ],
            [
                'Nom' => 'Moctar',
                'Prenom' => 'Lemine',
                'NNI' => '1234567890124',
                'DtNaissance' => '1985-05-20',
                'Genre' => 'M',
                'Telephone1' => '0600000002',
                'Telephone2' => '0700000002',
                'Adresse' => 'Nouakchott, Dar Naïm',
                'choix' => 0,
                'Dtajout' => now(),
                'fkidcabinet' => 1
            ],
            [
                'Nom' => 'Abdellahi',
                'Prenom' => 'Moulaye',
                'NNI' => '1234567890125',
                'DtNaissance' => '1995-08-10',
                'Genre' => 'M',
                'Telephone1' => '0600000003',
                'Telephone2' => '0700000003',
                'Adresse' => 'Nouakchott, Ksar',
                'choix' => 0,
                'Dtajout' => now(),
                'fkidcabinet' => 1
            ],
            [
                'Nom' => 'Zeinebou',
                'Prenom' => 'Mariem',
                'NNI' => '1234567890126',
                'DtNaissance' => '1988-12-25',
                'Genre' => 'F',
                'Telephone1' => '0600000004',
                'Telephone2' => '0700000004',
                'Adresse' => 'Nouakchott, El Mina',
                'choix' => 0,
                'Dtajout' => now(),
                'fkidcabinet' => 1
            ],
            [
                'Nom' => 'Amadou',
                'Prenom' => 'Sidi',
                'NNI' => '1234567890127',
                'DtNaissance' => '1992-03-30',
                'Genre' => 'M',
                'Telephone1' => '0600000005',
                'Telephone2' => '0700000005',
                'Adresse' => 'Nouakchott, Arafat',
                'choix' => 0,
                'Dtajout' => now(),
                'fkidcabinet' => 1
            ]
        ];

        foreach ($patients as $patient) {
            DB::table('patients')->insert($patient);
        }
    }
}
