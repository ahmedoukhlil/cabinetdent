<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActesTypeCibleSeeder extends Seeder
{
    /**
     * Classifie les actes existants par type de cible (DENT, MULTI_DENTS,
     * HEMI_ARCADE, ARCADE, BOUCHE_ENTIERE, PATIENT), déduit du libellé de
     * chaque acte au moment de la revue manuelle avec le médecin.
     *
     * @return void
     */
    public function run()
    {
        $parType = [
            'PATIENT' => [
                'Consultation',
                'Consultation specialiste',
                'Nébulisation',
                'Surveillance',
                'CBCT Cône Beam',
                'Consultation d\'urgence',
                'Rdio dentaire PANORAMIQUE',
                'Rdio dentaire CEPHALOMETRIQUE',
                'COM',
            ],
            'HEMI_ARCADE' => [
                'Application préventive du fluor par hémi-arcade',
            ],
            'BOUCHE_ENTIERE' => [
                'Blanchiment dentaire totale',
                'Détartrage',
                'Détartrage et polissage',
                'Freinectomie labial – laser',
                'Orthodontie',
            ],
            'ARCADE' => [
                'Gouttière occlusale et bruxisme',
                'Prothèses mobile complet',
            ],
            'MULTI_DENTS' => [
                'Contention parodontale',
                'Détartrage, surfaçage radiculaire DSR',
                'Fixation du bridge par ciment',
                'Gingivectomie',
                'Prothèses mobile 10 dents',
                'Prothèses mobile 5 dents',
                'Prothèses mobile deux dents',
            ],
            // Tout le reste du catalogue est du type DENT par défaut
            // (colonne définie avec default('DENT') dans la migration).
        ];

        foreach ($parType as $type => $libelles) {
            DB::table('actes')
                ->whereIn('Acte', $libelles)
                ->update(['type_cible' => $type]);
        }
    }
}
