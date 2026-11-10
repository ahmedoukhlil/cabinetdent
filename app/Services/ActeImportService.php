<?php

namespace App\Services;

use App\Models\Acte;
use App\Models\ActeTarif;
use App\Models\Assureur;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Import groupé du catalogue des actes depuis Excel, avec un prix par assureur.
 *
 * Colonnes : Code | Libellé | Prix sans assurance | une colonne par assureur (nom exact).
 * Un acte est reconnu par son code, sinon par son libellé. Une case assureur vide
 * signifie « pas de tarif propre » : le prix sans assurance s'applique.
 * L'import est tout ou rien : à la moindre erreur, rien n'est enregistré.
 */
class ActeImportService
{
    public const MAX_LIGNES = 2000;

    private const COL_CODE = 'code';

    private const COL_LIBELLE = 'libelle';

    private const COL_PRIX = 'prix';

    private const COL_TYPE = 'type';

    /** Crée le modèle pré-rempli avec le catalogue actuel ; renvoie le chemin du fichier temporaire. */
    public function modele(): string
    {
        $assureurs = Assureur::where('actif', true)->orderBy('nom')->get();
        $actes = Acte::with('tarifs')->where('actif', true)->orderBy('libelle')->get();
        $chemin = tempnam(sys_get_temp_dir(), 'actes').'.xlsx';

        $writer = new Writer();
        $writer->openToFile($chemin);
        $gras = (new Style())->withFontBold(true);
        $writer->getCurrentSheet()->setName('Actes');
        $writer->addRow($this->ligneGrasse(['Code', 'Libellé', 'Type', 'Prix sans assurance', ...$assureurs->pluck('nom')->all()], $gras));

        if ($actes->isEmpty()) {
            $writer->addRow(Row::fromValues(['D01', 'Consultation', 'Consultation', 1000, ...$assureurs->map(fn () => '')->all()]));
        }
        foreach ($actes as $acte) {
            $writer->addRow(Row::fromValues([
                $acte->code ?? '',
                $acte->libelle,
                Acte::TYPES[$acte->type] ?? 'Soin',
                $acte->prix,
                ...$assureurs->map(fn ($a) => $acte->tarifs->firstWhere('assureur_id', $a->id)?->prix ?? '')->all(),
            ]));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Mode d\'emploi');
        foreach ([
            ['Mode d\'emploi'],
            ['1. Une ligne par acte. Le libellé et le prix sans assurance sont obligatoires.'],
            ['2. L\'acte est reconnu par son code (sinon par son libellé) : il est mis à jour, sinon créé.'],
            ['3. Type : « Soin » ou « Consultation » (facturée et encaissée dès l\'arrivée du patient). Vide : inchangé.'],
            ['4. Une colonne par assureur, avec son nom exact tel qu\'enregistré dans Sysdental.'],
            ['5. Case assureur vide : pas de tarif propre, le prix sans assurance s\'applique.'],
            ['6. Les prix sont en MRU. Les actes absents du fichier ne sont pas modifiés.'],
            ['7. À la moindre erreur, rien n\'est importé : corrigez les lignes signalées et recommencez.'],
        ] as $i => $ligne) {
            $writer->addRow($i === 0 ? $this->ligneGrasse($ligne, $gras) : Row::fromValues($ligne));
        }
        $writer->close();

        return $chemin;
    }

    private function ligneGrasse(array $valeurs, Style $style): Row
    {
        return Row::fromValuesWithStyles($valeurs, array_fill(0, count($valeurs), $style));
    }

    /**
     * @return array{crees: int, modifies: int, tarifs: int, avertissements: list<string>}
     */
    public function importer(User $auteur, string $chemin): array
    {
        if (!$auteur->can('tarifs.manage')) {
            throw new AuthorizationException();
        }

        [$entete, $lignes] = $this->lire($chemin);
        [$colonnes, $avertissements] = $this->colonnes($entete);

        $erreurs = [];
        $donnees = [];
        $vus = [];
        foreach ($lignes as $numero => $cellules) {
            $valeur = fn (string $cle) => trim((string) ($cellules[$colonnes[$cle] ?? -1] ?? ''));
            $code = Str::upper($valeur(self::COL_CODE));
            $libelle = $valeur(self::COL_LIBELLE);
            $prix = $this->montant($valeur(self::COL_PRIX));
            $typeBrut = Str::of($valeur(self::COL_TYPE))->ascii()->lower()->trim()->toString();
            $type = match (true) {
                $typeBrut === '' => null, // inchangé (soin pour un nouvel acte)
                str_starts_with($typeBrut, 'consult') => 'consultation',
                str_starts_with($typeBrut, 'soin') => 'soin',
                default => false,
            };

            if ($code === '' && $libelle === '' && $valeur(self::COL_PRIX) === '') {
                continue; // ligne vide
            }
            if ($libelle === '') {
                $erreurs[] = "Ligne {$numero} : libellé manquant.";
            } elseif (mb_strlen($libelle) > 150) {
                $erreurs[] = "Ligne {$numero} : libellé trop long (150 caractères max.).";
            }
            if ($type === false) {
                $erreurs[] = "Ligne {$numero} : type « {$valeur(self::COL_TYPE)} » inconnu (Soin ou Consultation).";
            }
            if (mb_strlen($code) > 20) {
                $erreurs[] = "Ligne {$numero} : code trop long (20 caractères max.).";
            }
            if ($prix === null) {
                $erreurs[] = "Ligne {$numero} : prix sans assurance manquant ou invalide.";
            }
            $cle = $code !== '' ? 'c:'.$code : 'l:'.mb_strtolower($libelle);
            if (isset($vus[$cle])) {
                $erreurs[] = "Ligne {$numero} : acte en double (déjà ligne {$vus[$cle]}).";
            }
            $vus[$cle] = $numero;

            $tarifs = [];
            foreach ($colonnes['assureurs'] as $assureurId => $index) {
                $brut = trim((string) ($cellules[$index] ?? ''));
                if ($brut === '') {
                    $tarifs[$assureurId] = null;
                } elseif (($montant = $this->montant($brut)) === null) {
                    $erreurs[] = "Ligne {$numero} : prix « {$brut} » invalide.";
                } else {
                    $tarifs[$assureurId] = $montant;
                }
            }
            $donnees[] = compact('code', 'libelle', 'type', 'prix', 'tarifs');
        }

        if (!$donnees && !$erreurs) {
            $erreurs[] = 'Aucun acte trouvé dans le fichier.';
        }
        if ($erreurs) {
            throw ValidationException::withMessages(['fichier' => array_slice($erreurs, 0, 20)]);
        }

        return DB::transaction(function () use ($donnees, $avertissements) {
            $resultat = ['crees' => 0, 'modifies' => 0, 'tarifs' => 0, 'avertissements' => $avertissements];
            $existants = Acte::with('tarifs')->get();

            foreach ($donnees as $d) {
                $acte = ($d['code'] !== '' ? $existants->first(fn ($a) => Str::upper((string) $a->code) === $d['code']) : null)
                    ?? $existants->first(fn ($a) => mb_strtolower($a->libelle) === mb_strtolower($d['libelle']));

                if ($acte) {
                    $acte->fill(['code' => $d['code'] ?: $acte->code, 'libelle' => $d['libelle'], 'type' => $d['type'] ?? $acte->type, 'prix' => $d['prix'], 'actif' => true]);
                    if ($acte->isDirty()) {
                        $acte->save();
                        $resultat['modifies']++;
                    }
                } else {
                    $acte = Acte::create(['code' => $d['code'] ?: null, 'libelle' => $d['libelle'], 'type' => $d['type'] ?? 'soin', 'prix' => $d['prix']]);
                    $existants->push($acte->setRelation('tarifs', collect()));
                    $resultat['crees']++;
                }

                foreach ($d['tarifs'] as $assureurId => $prix) {
                    if ($prix === null) {
                        ActeTarif::where('acte_id', $acte->id)->where('assureur_id', $assureurId)->delete();
                    } else {
                        ActeTarif::updateOrCreate(['acte_id' => $acte->id, 'assureur_id' => $assureurId], ['prix' => $prix]);
                        $resultat['tarifs']++;
                    }
                }
            }

            return $resultat;
        });
    }

    /** @return array{0: list<string>, 1: array<int, list<mixed>>} en-tête et lignes indexées par numéro Excel */
    private function lire(string $chemin): array
    {
        $reader = new Reader();
        try {
            $reader->open($chemin);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['fichier' => 'Fichier illisible : utilisez le modèle Excel (.xlsx).']);
        }

        $entete = null;
        $lignes = [];
        try {
            foreach ($reader->getSheetIterator() as $feuille) {
                foreach ($feuille->getRowIterator() as $numero => $row) {
                    $valeurs = array_map(fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v, $row->toArray());
                    if ($entete === null) {
                        $entete = array_map(fn ($v) => trim((string) $v), $valeurs);
                        continue;
                    }
                    if (count($lignes) >= self::MAX_LIGNES) {
                        throw ValidationException::withMessages(['fichier' => 'Fichier trop long : '.self::MAX_LIGNES.' actes au maximum par import.']);
                    }
                    $lignes[$numero] = $valeurs;
                }
                break; // première feuille seulement
            }
        } finally {
            $reader->close();
        }

        if (!$entete) {
            throw ValidationException::withMessages(['fichier' => 'Le fichier est vide.']);
        }

        return [$entete, $lignes];
    }

    /** Associe les colonnes de l'en-tête ; les colonnes d'assureurs inconnus sont ignorées avec un avertissement. */
    private function colonnes(array $entete): array
    {
        $normaliser = fn (string $v) => Str::of($v)->ascii()->lower()->squish()->toString();
        $assureurs = Assureur::all()->keyBy(fn ($a) => $normaliser($a->nom));
        $colonnes = ['assureurs' => []];
        $avertissements = [];

        foreach ($entete as $index => $titre) {
            $cle = $normaliser($titre);
            if ($cle === '') {
                continue;
            }
            match (true) {
                $cle === 'code' => $colonnes[self::COL_CODE] = $index,
                $cle === 'type' => $colonnes[self::COL_TYPE] = $index,
                in_array($cle, ['libelle', 'acte', 'designation'], true) => $colonnes[self::COL_LIBELLE] = $index,
                str_starts_with($cle, 'prix sans assurance') || in_array($cle, ['prix', 'tarif', 'prix de base'], true) => $colonnes[self::COL_PRIX] = $index,
                $assureurs->has($cle) => $colonnes['assureurs'][$assureurs[$cle]->id] = $index,
                default => $avertissements[] = "Colonne « {$titre} » ignorée : aucun assureur de ce nom.",
            };
        }

        if (!isset($colonnes[self::COL_LIBELLE], $colonnes[self::COL_PRIX])) {
            throw ValidationException::withMessages(['fichier' => 'Colonnes « Libellé » et « Prix sans assurance » introuvables : utilisez le modèle.']);
        }

        return [$colonnes, $avertissements];
    }

    /** Accepte 1500, « 1 500 », « 1500,50 » ; null si invalide ou négatif. */
    private function montant(string $brut): ?float
    {
        $nettoye = str_replace([' ', "\u{00A0}", "\u{202F}", 'MRU', 'mru'], '', $brut);
        if (str_contains($nettoye, ',') && !str_contains($nettoye, '.')) {
            $nettoye = str_replace(',', '.', $nettoye);
        }
        if ($nettoye === '' || !is_numeric($nettoye) || (float) $nettoye < 0 || (float) $nettoye > 99999999) {
            return null;
        }

        return round((float) $nettoye, 2);
    }
}
