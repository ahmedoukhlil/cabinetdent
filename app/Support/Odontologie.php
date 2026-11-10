<?php

namespace App\Support;

/**
 * Référentiels fermés du dossier dentaire (notation FDI, dentition permanente).
 * Toute valeur enregistrée est validée contre ces listes.
 */
final class Odontologie
{
    /** Ordre d'affichage : arcade supérieure (droite → gauche du patient), puis inférieure. */
    public const ARCADE_SUPERIEURE = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28];

    public const ARCADE_INFERIEURE = [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38];

    public const PRESENCE = [
        'presente' => 'Présente',
        'absente' => 'Absente',
        'non_eruptee' => 'Non éruptée',
        'incluse' => 'Incluse',
        'racine_residuelle' => 'Racine résiduelle',
        'surnumeraire' => 'Surnuméraire',
    ];

    public const RESTAURATION = [
        'aucune' => 'Aucune',
        'couronne' => 'Couronne',
        'facette' => 'Facette',
        'pilier_bridge' => 'Pilier de bridge',
        'pontique' => 'Élément intermédiaire de bridge',
        'implant' => 'Implant sans prothèse',
        'couronne_implant' => 'Couronne sur implant',
    ];

    public const ENDODONTIE = [
        'non_traitee' => 'Non traitée',
        'traitee' => 'Traitement antérieur',
        'en_cours' => 'Traitement en cours',
        'retraitement' => 'Retraitement indiqué',
    ];

    public const FACES = ['O', 'M', 'D', 'V', 'L'];

    public const ETATS_FACE = [
        'sain' => 'Saine',
        'carie' => 'Carie',
        'obturation' => 'Obturation',
    ];

    public const CONSTATATIONS = [
        'Clinique' => [
            'carie_secondaire' => 'Carie secondaire',
            'fissure' => 'Fissure',
            'fracture' => 'Fracture',
            'usure' => 'Usure (attrition, abrasion, érosion)',
            'hypersensibilite' => 'Hypersensibilité',
            'dyschromie' => 'Dyschromie',
            'pulpite' => 'Pulpite',
            'necrose' => 'Nécrose pulpaire',
            'lesion_periapicale' => 'Lésion périapicale',
            'abces' => 'Abcès',
            'fistule' => 'Fistule',
            'mobilite' => 'Mobilité',
            'resorption' => 'Résorption',
        ],
        'Parodontal' => [
            'gingivite' => 'Gingivite',
            'parodontite' => 'Parodontite',
            'recession' => 'Récession gingivale',
            'poche' => 'Poche parodontale',
            'tartre' => 'Tartre',
        ],
        'Orthodontique' => [
            'rotation' => 'Rotation',
            'version' => 'Version / inclinaison',
            'ectopie' => 'Ectopie',
            'encombrement' => 'Encombrement',
            'diasteme' => 'Diastème',
            'articule_croise' => 'Articulé croisé',
        ],
    ];

    public static function toutesLesDents(): array
    {
        return array_merge(self::ARCADE_SUPERIEURE, self::ARCADE_INFERIEURE);
    }

    public static function estDentValide(int $numero): bool
    {
        return in_array($numero, self::toutesLesDents(), true);
    }

    public static function codesConstatations(): array
    {
        return array_keys(array_merge(...array_values(self::CONSTATATIONS)));
    }

    public static function libelleConstatation(string $code): string
    {
        foreach (self::CONSTATATIONS as $groupe) {
            if (isset($groupe[$code])) {
                return $groupe[$code];
            }
        }

        return $code;
    }

    /** Libellé des faces adapté à l'arcade : linguale en bas, palatine en haut ; occlusale/incisive. */
    public static function libelleFace(string $face, int $numero): string
    {
        $anterieure = in_array($numero % 10, [1, 2, 3], true);

        return match ($face) {
            'O' => $anterieure ? 'Incisive' : 'Occlusale',
            'M' => 'Mésiale',
            'D' => 'Distale',
            'V' => 'Vestibulaire',
            'L' => in_array(intdiv($numero, 10), [1, 2], true) ? 'Palatine' : 'Linguale',
        };
    }

    public static function facesSaines(): array
    {
        return array_fill_keys(self::FACES, 'sain');
    }

    /** Couleurs de l'odontogramme anatomique, par statut de synthèse (même légende que la vue par faces). */
    public const CONDITIONS_GRAPHE = [
        'a_traiter' => ['label' => 'À traiter (carie…)', 'fillColor' => '#fecaca', 'outlineColor' => '#dc2626'],
        'obturee' => ['label' => 'Obturation', 'fillColor' => '#bfdbfe', 'outlineColor' => '#2563eb'],
        'prothese' => ['label' => 'Prothèse', 'fillColor' => '#fde68a', 'outlineColor' => '#d97706'],
        'endo' => ['label' => 'Traitement endodontique', 'fillColor' => '#ddd6fe', 'outlineColor' => '#7c3aed'],
        'implant' => ['label' => 'Implant', 'fillColor' => '#99f6e4', 'outlineColor' => '#0d9488'],
        'absente' => ['label' => 'Absente', 'fillColor' => '#f1f5f9', 'outlineColor' => '#94a3b8'],
        'prevu' => ['label' => 'Soin prévu au plan', 'fillColor' => '#ffedd5', 'outlineColor' => '#ea580c'],
    ];

    /**
     * Groupes de couleurs pour react-odontogram : l'état clinique de chaque dent prime ;
     * une dent saine qui a un soin prévu (non réalisé) au plan apparaît en « soin prévu ».
     *
     * @param  \Illuminate\Support\Collection  $dents  modèles Dent indexés par numéro
     * @param  int[]  $dentsPrevues
     */
    public static function conditionsGraphe($dents, array $dentsPrevues = []): array
    {
        $groupes = [];
        foreach ($dents as $numero => $dent) {
            $statut = $dent->synthese();
            if (isset(self::CONDITIONS_GRAPHE[$statut])) {
                $groupes[$statut][] = (int) $numero;
            }
        }
        $dejaColorees = array_merge([], ...array_values($groupes));
        foreach (array_unique($dentsPrevues) as $numero) {
            if (self::estDentValide((int) $numero) && !in_array((int) $numero, $dejaColorees, true)) {
                $groupes['prevu'][] = (int) $numero;
            }
        }

        $sortie = [];
        foreach (self::CONDITIONS_GRAPHE as $cle => $style) {
            if (!empty($groupes[$cle])) {
                $sortie[] = $style + ['teeth' => $groupes[$cle]];
            }
        }

        return $sortie;
    }
}
