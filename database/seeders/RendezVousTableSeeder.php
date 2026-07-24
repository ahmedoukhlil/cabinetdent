<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Rendezvou;

class RendezVousTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Simuler un utilisateur connecté pour le seeder (cabinet ID = 1)
        $this->simulateAuthUser();
        
        $rendezVousData = [
            [
                'ActePrevu' => 'Consultation dentaire',
                'dtPrevuRDV' => Carbon::now()->addDays(1)->format('Y-m-d'),
                'HeureRdv' => Carbon::now()->addDays(1)->setHour(9)->setMinute(0),
                'fkidPatient' => 1,
                'rdvConfirmer' => 'Confirmé',
                'fkidMedecin' => 1,
                'fkidcabinet' => 1
            ],
            [
                'ActePrevu' => 'Détartrage',
                'dtPrevuRDV' => Carbon::now()->addDays(1)->format('Y-m-d'), // Même jour que le précédent
                'HeureRdv' => Carbon::now()->addDays(1)->setHour(10)->setMinute(30),
                'fkidPatient' => 2,
                'rdvConfirmer' => 'En Attente',
                'fkidMedecin' => 1, // Même médecin
                'fkidcabinet' => 1
            ],
            [
                'ActePrevu' => 'Soin carie',
                'dtPrevuRDV' => Carbon::now()->addDays(2)->format('Y-m-d'), // Jour différent
                'HeureRdv' => Carbon::now()->addDays(2)->setHour(14)->setMinute(0),
                'fkidPatient' => 3,
                'rdvConfirmer' => 'Confirmé',
                'fkidMedecin' => 1,
                'fkidcabinet' => 1
            ],
            [
                'ActePrevu' => 'Contrôle',
                'dtPrevuRDV' => Carbon::now()->addDays(2)->format('Y-m-d'), // Même jour que le précédent
                'HeureRdv' => Carbon::now()->addDays(2)->setHour(15)->setMinute(30),
                'fkidPatient' => 4,
                'rdvConfirmer' => 'En Attente',
                'fkidMedecin' => 2, // Médecin différent
                'fkidcabinet' => 1
            ],
            [
                'ActePrevu' => 'Extraction',
                'dtPrevuRDV' => Carbon::now()->addDays(3)->format('Y-m-d'), // Jour différent
                'HeureRdv' => Carbon::now()->addDays(3)->setHour(16)->setMinute(0),
                'fkidPatient' => 5,
                'rdvConfirmer' => 'En Attente',
                'fkidMedecin' => 1,
                'fkidcabinet' => 1
            ]
        ];

        foreach ($rendezVousData as $rdvData) {
            // Générer automatiquement l'OrdreRDV en utilisant la logique appropriée
            $ordreRDV = $this->generateOrderNumber($rdvData['dtPrevuRDV'], $rdvData['fkidMedecin'], $rdvData['fkidcabinet']);
            
            DB::table('rendezvous')->insert([
                'ActePrevu' => $rdvData['ActePrevu'],
                'DtAjRdv' => now(),
                'dtPrevuRDV' => $rdvData['dtPrevuRDV'],
                'user' => 'Admin',
                'HeureRdv' => $rdvData['HeureRdv'],
                'fkidPatient' => $rdvData['fkidPatient'],
                'rdvConfirmer' => $rdvData['rdvConfirmer'],
                'fkidMedecin' => $rdvData['fkidMedecin'],
                'OrdreRDV' => $ordreRDV,
                'HeureConfRDV' => $rdvData['rdvConfirmer'] === 'Confirmé' ? now() : null,
                'fkidcabinet' => $rdvData['fkidcabinet']
            ]);
        }
    }
    
    /**
     * Simule un utilisateur authentifié pour le seeder
     */
    private function simulateAuthUser()
    {
        // Créer un utilisateur factice pour Auth::user()
        $user = new \stdClass();
        $user->fkidcabinet = 1;
        
        // Mock Auth::user() pour le seeder
        Auth::shouldReceive('user')
            ->andReturn($user);
    }
    
    /**
     * Génère le numéro d'ordre pour une date et un médecin donnés
     * Version simplifiée pour le seeder
     */
    private function generateOrderNumber($date, $medecinId, $cabinetId)
    {
        $lastOrder = DB::table('rendezvous')
            ->whereDate('dtPrevuRDV', $date)
            ->where('fkidMedecin', $medecinId)
            ->where('fkidcabinet', $cabinetId)
            ->max('OrdreRDV');

        return ($lastOrder ?? 0) + 1;
    }
}
