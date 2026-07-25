<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActesTableSeeder extends Seeder
{
    /**
     * Catalogue de base des actes (dédupliqué depuis l'export cabinetahmedou :
     * le dump original répétait chaque acte une fois par assureur ;
     * on ne conserve ici que la variante de référence, fkidassureur = 1).
     *
     * @return void
     */
    public function run()
    {
        $actes = [
            ['Acte' => 'Consultation', 'PrixRef' => 1000, 'fkidTypeActe' => 1, 'nordre' => 1, 'ActeArab' => 'إستشارة (معاينة) '],
            ['Acte' => 'Apectomie', 'PrixRef' => 8000, 'fkidTypeActe' => 1, 'nordre' => 3, 'ActeArab' => 'قطع ذروة'],
            ['Acte' => 'Application préventive du fluor par hémi-arcade', 'PrixRef' => 4000, 'fkidTypeActe' => 1, 'nordre' => 4, 'ActeArab' => 'تطبيق فلور'],
            ['Acte' => 'Augmentation du plancher sinusien - Sinus lift', 'PrixRef' => 30000, 'fkidTypeActe' => 1, 'nordre' => 6, 'ActeArab' => 'رفع الجيب الفكي'],
            ['Acte' => 'Blanchiment dentaire totale', 'PrixRef' => 15000, 'fkidTypeActe' => 1, 'nordre' => 8, 'ActeArab' => 'تبييض الأسنان بالليزر'],
            ['Acte' => 'Couronne céramo-métalique', 'PrixRef' => 10000, 'fkidTypeActe' => 1, 'nordre' => 10, 'ActeArab' => 'تاج خزف على معدن'],
            ['Acte' => 'Contention parodontale', 'PrixRef' => 10000, 'fkidTypeActe' => 1, 'nordre' => 11, 'ActeArab' => 'جبيرة تثبيت'],
            ['Acte' => 'Couronne dentaire', 'PrixRef' => 4000, 'fkidTypeActe' => 1, 'nordre' => 12, 'ActeArab' => 'تاج مؤقت'],
            ['Acte' => 'Couronne en Zircone', 'PrixRef' => 14000, 'fkidTypeActe' => 1, 'nordre' => 14, 'ActeArab' => 'تاج زيركون'],
            ['Acte' => 'Détartrage', 'PrixRef' => 2000, 'fkidTypeActe' => 1, 'nordre' => 15, 'ActeArab' => 'تقليح الأسنان'],
            ['Acte' => 'Détartrage et polissage', 'PrixRef' => 3500, 'fkidTypeActe' => 1, 'nordre' => 16, 'ActeArab' => 'تقليح وصقل الأسنان'],
            ['Acte' => 'Détartrage, surfaçage radiculaire DSR', 'PrixRef' => 6000, 'fkidTypeActe' => 1, 'nordre' => 17, 'ActeArab' => 'NR'],
            ['Acte' => 'Exérèse d’un kyste apical', 'PrixRef' => 8000, 'fkidTypeActe' => 1, 'nordre' => 18, 'ActeArab' => 'قلع كيس جذري'],
            ['Acte' => 'Extraction chirurgicale d’un implant + Curettage', 'PrixRef' => 10000, 'fkidTypeActe' => 1, 'nordre' => 19, 'ActeArab' => 'قلع جراحي غرسة سنية مع تجريف'],
            ['Acte' => 'Extraction chirurgicale d’une canine incluse', 'PrixRef' => 10000, 'fkidTypeActe' => 1, 'nordre' => 20, 'ActeArab' => 'قلع جراحي ناب منطمر'],
            ['Acte' => 'Extraction chirurgicale d’une dent de sagesse enclavée', 'PrixRef' => 10000, 'fkidTypeActe' => 1, 'nordre' => 21, 'ActeArab' => 'قلع جراحي لرحى ثالثة مؤوفه'],
            ['Acte' => 'Extraction chirurgicale d’une dent de sagesse incluse', 'PrixRef' => 15000, 'fkidTypeActe' => 1, 'nordre' => 22, 'ActeArab' => 'قلع جراحي لرحى ثالثة منطمرة'],
            ['Acte' => 'Extraction d’une dent de lait', 'PrixRef' => 1000, 'fkidTypeActe' => 1, 'nordre' => 24, 'ActeArab' => 'قلع سن لبني'],
            ['Acte' => 'Extraction simple d’une racine', 'PrixRef' => 1000, 'fkidTypeActe' => 1, 'nordre' => 25, 'ActeArab' => 'قلع بقايا جذر بسيط'],
            ['Acte' => 'Extraction chirurgical d’une racine', 'PrixRef' => 2500, 'fkidTypeActe' => 1, 'nordre' => 26, 'ActeArab' => 'قلع جراحي بقايا جذر '],
            ['Acte' => 'Extraction simple d’une dent de sagesse', 'PrixRef' => 7000, 'fkidTypeActe' => 1, 'nordre' => 27, 'ActeArab' => 'قلع رحى ثالثة'],
            ['Acte' => 'Extraction simple d’une dent incisive ou canine', 'PrixRef' => 1500, 'fkidTypeActe' => 1, 'nordre' => 29, 'ActeArab' => 'قلع سن امامي (قواطع'],
            ['Acte' => 'Extraction simple d’une molaire', 'PrixRef' => 2500, 'fkidTypeActe' => 1, 'nordre' => 31, 'ActeArab' => 'قلع رحى'],
            ['Acte' => 'Extraction simple d’une prémolaire', 'PrixRef' => 2000, 'fkidTypeActe' => 1, 'nordre' => 32, 'ActeArab' => 'قلع ضاحك'],
            ['Acte' => 'Fixation du bridge par ciment', 'PrixRef' => 2000, 'fkidTypeActe' => 1, 'nordre' => 34, 'ActeArab' => 'الصاق جسر'],
            ['Acte' => 'Freinectomie labial – laser', 'PrixRef' => 15000, 'fkidTypeActe' => 1, 'nordre' => 35, 'ActeArab' => 'قطع لجام بالليزر'],
            ['Acte' => 'Gingivectomie', 'PrixRef' => 15000, 'fkidTypeActe' => 1, 'nordre' => 36, 'ActeArab' => 'قطع لثة بالليزر'],
            ['Acte' => 'Gouttière occlusale et bruxisme', 'PrixRef' => 15000, 'fkidTypeActe' => 1, 'nordre' => 37, 'ActeArab' => 'صفيحة'],
            ['Acte' => 'Intervention chirurgicale d\'une alvéolite', 'PrixRef' => 15000, 'fkidTypeActe' => 1, 'nordre' => 38, 'ActeArab' => 'NR'],
            ['Acte' => 'Membrane collagène CGR', 'PrixRef' => 15000, 'fkidTypeActe' => 1, 'nordre' => 39, 'ActeArab' => 'NR'],
            ['Acte' => 'Orthodontie', 'PrixRef' => 120000, 'fkidTypeActe' => 1, 'nordre' => 40, 'ActeArab' => 'تقويم الأسنان والفكين'],
            ['Acte' => 'Perforation du plancher pulpaire & Reprise du traitement endodontique - RX inclus', 'PrixRef' => 8000, 'fkidTypeActe' => 1, 'nordre' => 42, 'ActeArab' => 'معالجة لبية لانثقاب الحجرة اللبية'],
            ['Acte' => 'Pose Implant + Régénération osseuse + prothèse dentaire sur implant', 'PrixRef' => 55000, 'fkidTypeActe' => 1, 'nordre' => 43, 'ActeArab' => 'غرسة سنية مع طعم عظمي وتعويض فوق الغرس'],
            ['Acte' => 'Pose Implant simple + prothèse dentaire', 'PrixRef' => 45000, 'fkidTypeActe' => 1, 'nordre' => 45, 'ActeArab' => 'غرسة سنية مع التعويض'],
            ['Acte' => 'Prothèses mobile 10 dents', 'PrixRef' => 40000, 'fkidTypeActe' => 1, 'nordre' => 47, 'ActeArab' => 'جهاز متحرك 10 أسنان'],
            ['Acte' => 'Prothèses mobile 5 dents', 'PrixRef' => 30000, 'fkidTypeActe' => 1, 'nordre' => 48, 'ActeArab' => 'جهاز 5 متحرك أسنان'],
            ['Acte' => 'Prothèses mobile complet', 'PrixRef' => 50000, 'fkidTypeActe' => 1, 'nordre' => 49, 'ActeArab' => 'جهاز متحرك كامل'],
            ['Acte' => 'Prothèses mobile deux dents', 'PrixRef' => 8000, 'fkidTypeActe' => 1, 'nordre' => 50, 'ActeArab' => 'جهاز متحرك 2 أسنان'],
            ['Acte' => 'Prothèses mobile une dent', 'PrixRef' => 5000, 'fkidTypeActe' => 1, 'nordre' => 51, 'ActeArab' => 'جهاز متحرك سن واحد'],
            ['Acte' => 'Pulpectomie chez l’enfant RX inclus', 'PrixRef' => 5000, 'fkidTypeActe' => 1, 'nordre' => 52, 'ActeArab' => 'بتر لب سن لبني'],
            ['Acte' => 'Reconstruction osseuse verticale pré-implantaire', 'PrixRef' => 8000, 'fkidTypeActe' => 1, 'nordre' => 54, 'ActeArab' => 'NR'],
            ['Acte' => 'Restauration composite classe I', 'PrixRef' => 3000, 'fkidTypeActe' => 1, 'nordre' => 55, 'ActeArab' => 'حشوة تجميلية -صنف أول'],
            ['Acte' => 'Restauration composite classe II', 'PrixRef' => 4000, 'fkidTypeActe' => 1, 'nordre' => 56, 'ActeArab' => 'حشوة تجميلية -صنف ثاني'],
            ['Acte' => 'Restauration composite classe III', 'PrixRef' => 4000, 'fkidTypeActe' => 1, 'nordre' => 57, 'ActeArab' => 'حشوة تجميلية -صنف ثالث'],
            ['Acte' => 'Restauration composite classe IV', 'PrixRef' => 5000, 'fkidTypeActe' => 1, 'nordre' => 58, 'ActeArab' => 'حشوة تجميلية -صنف رابع'],
            ['Acte' => 'Restauration composite classe V', 'PrixRef' => 4000, 'fkidTypeActe' => 1, 'nordre' => 59, 'ActeArab' => 'حشوة تجميلية -صنف خامس'],
            ['Acte' => 'Restauration composite MOD', 'PrixRef' => 4000, 'fkidTypeActe' => 1, 'nordre' => 60, 'ActeArab' => 'حشوة سنية راتنجية -أنسي وحشي واطباقي'],
            ['Acte' => 'Retraitement endodontique mécanisé - RX inclus', 'PrixRef' => 8000, 'fkidTypeActe' => 1, 'nordre' => 62, 'ActeArab' => 'إعادة معالجة لبية – أشعة'],
            ['Acte' => 'Traitement endodontique mécanisé - RX inclus', 'PrixRef' => 5500, 'fkidTypeActe' => 1, 'nordre' => 64, 'ActeArab' => 'معالجة لبية روتينية مع الأشعة'],
            ['Acte' => 'Traitement endodontique mécanisé avec tenon en fibre - RX inclus ', 'PrixRef' => 7000, 'fkidTypeActe' => 1, 'nordre' => 66, 'ActeArab' => 'معالجة لبية مع وتد فايبر – أشعة'],
            ['Acte' => 'Traitement endodontique mécanisé de dent nécrosée - RX inclus', 'PrixRef' => 7500, 'fkidTypeActe' => 1, 'nordre' => 68, 'ActeArab' => 'معالجة لبية -سن متؤوفة – أشعة'],
            ['Acte' => 'Traitement dentaire sous Microscope', 'PrixRef' => 3000, 'fkidTypeActe' => 1, 'nordre' => 70, 'ActeArab' => 'NR'],
            ['Acte' => 'Séparation des racines et curettage inter-radiculaire', 'PrixRef' => 5000, 'fkidTypeActe' => 1, 'nordre' => 71, 'ActeArab' => 'فصل الجذور مع تجريف'],
            ['Acte' => 'soient gengival', 'PrixRef' => 2500, 'fkidTypeActe' => 4, 'nordre' => 0, 'ActeArab' => 'soient gengival'],
            ['Acte' => 'Extraction complique', 'PrixRef' => 10000, 'fkidTypeActe' => 5, 'nordre' => 0, 'ActeArab' => 'Extraction compliquee'],
            ['Acte' => 'CBCT Cône Beam', 'PrixRef' => 4000, 'fkidTypeActe' => 5, 'nordre' => 0, 'ActeArab' => 'CBCT Cône Beam'],
            ['Acte' => 'Traitement pulpaire', 'PrixRef' => 4000, 'fkidTypeActe' => 3, 'nordre' => 0, 'ActeArab' => 'Traitement pulpaire'],
            ['Acte' => 'Pulpotomie', 'PrixRef' => 5000, 'fkidTypeActe' => 3, 'nordre' => 0, 'ActeArab' => 'Pulpotomie'],
            ['Acte' => 'Pulpectomie dent temporaire', 'PrixRef' => 6000, 'fkidTypeActe' => 3, 'nordre' => 0, 'ActeArab' => 'Pulpectomie dent temporaire'],
            ['Acte' => 'Consultation d\'urgence', 'PrixRef' => 1500, 'fkidTypeActe' => 1, 'nordre' => 0, 'ActeArab' => 'معاينة طوارئ'],
            ['Acte' => 'Rdio dentaire PANORAMIQUE', 'PrixRef' => 600, 'fkidTypeActe' => 1, 'nordre' => 0, 'ActeArab' => 'أشعة الفم والأسنان بانوراما'],
            ['Acte' => 'Rdio dentaire CEPHALOMETRIQUE', 'PrixRef' => 600, 'fkidTypeActe' => 1, 'nordre' => 0, 'ActeArab' => 'أشعة الفم والأسنان سيفالوميتريك'],
            ['Acte' => 'COM', 'PrixRef' => 500, 'fkidTypeActe' => 4, 'nordre' => 0, 'ActeArab' => '.'],
        ];

        foreach ($actes as $acte) {
            DB::table('actes')->updateOrInsert(
                ['Acte' => $acte['Acte'], 'fkidassureur' => 1],
                [
                    'PrixRef' => $acte['PrixRef'],
                    'fkidTypeActe' => $acte['fkidTypeActe'],
                    'nordre' => $acte['nordre'],
                    'user' => '1',
                    'ActeArab' => $acte['ActeArab'],
                    'Masquer' => 0,
                ]
            );
        }
    }
}
