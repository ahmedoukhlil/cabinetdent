<?php

namespace App\Http\Livewire;

use App\Traits\HasLazyLoadingPlaceholder;
use Livewire\Component;
use App\Models\Ordonnanceref;
use App\Models\Ordonnance;
use App\Models\Medicament;
use App\Models\Patient;
use App\Models\Facture;
use App\Models\Detailfacturepatient;
use App\Models\StockMedicament;
use App\Models\MouvementStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrdonnanceManager extends Component
{
    use HasLazyLoadingPlaceholder;

    // Propriétés du patient
    public $patient;
    public $patientId;

    // Type d'ordonnance à créer (1=Médicaments, 2=Analyses, 3=Radios)
    public $typeOrdonnance = 1; // Par défaut : Médicaments

    // Mode : 'urgence' (interne, déduit stock) ou 'sortie' (externe, imprimée)
    public $modeOrdonnance = 'urgence';

    // Si défini, le mode est forcé et le choix est masqué
    public $modeForce = null;

    // Lignes de l'ordonnance (peut contenir plusieurs lignes du même type)
    public $lignesOrdonnance = [];

    // Recherche pour chaque ligne (index => search term)
    public $searchTerms = [];

    // Résultats de recherche pour chaque ligne (index => [results])
    public $searchResults = [];

    // Affichage des résultats pour chaque ligne
    public $showSearchResults = [];

    // Liste des ordonnances du patient
    public $ordonnancesPatient = [];

    // Accordéons pour l'affichage
    public $accordeonOuvert = null; // 'medicaments', 'analyses', 'radios'

    // Référentiel des médicaments/analyses/radios (tous chargés)
    public $medicaments = [];
    public $analyses = [];
    public $radios = [];

    protected $listeners = [
        'refreshOrdonnances' => 'loadOrdonnancesPatient',
        'patientSelected' => 'updatePatient'
    ];

    protected $rules = [
        'typeOrdonnance' => 'required|in:1,2,3',
        'lignesOrdonnance.*.medicament_id' => 'required',
        'lignesOrdonnance.*.posologie' => 'nullable|string',
    ];

    protected $messages = [
        'typeOrdonnance.required' => 'Veuillez sélectionner un type d\'ordonnance',
        'lignesOrdonnance.*.medicament_id.required' => 'Veuillez sélectionner un élément',
        'lignesOrdonnance.*.medicament_id.exists' => 'L\'élément sélectionné n\'existe pas',
    ];

    public function mount($patient = null)
    {
        try {
            Log::info('OrdonnanceManager: mount appelé', ['patient' => $patient ? 'existe' : 'null']);
            
            if ($patient) {
                $this->patient = $patient;
                if (is_array($patient)) {
                    $this->patientId = $patient['ID'] ?? $patient['id'] ?? null;
                } elseif (is_object($patient)) {
                    $this->patientId = $patient->ID ?? $patient->id ?? null;
                }

                if ($this->patientId) {
                    $this->loadOrdonnancesPatient();
                }
            }

            // Appliquer le mode forcé si fourni
            if ($this->modeForce) {
                $this->modeOrdonnance = $this->modeForce;
            }

            // Initialiser une ligne vide
            $this->ajouterLigneVide();

            // Charger le référentiel avec cache
            $this->loadReferentielMedicaments();
            
            Log::info('OrdonnanceManager: mount terminé avec succès');
        } catch (\Exception $e) {
            Log::error('OrdonnanceManager: Erreur dans mount', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Erreur lors du chargement du formulaire d\'ordonnance.');
        }
    }

    public function loadReferentielMedicaments()
    {
        // v2 dans la clé pour invalider l'ancien cache sans estInterne
        $cacheKey = 'referentiel_medicaments_v2_' . Auth::user()->fkidcabinet;

        $referentiel = Cache::remember($cacheKey, 3600, function () {
            $cols = ['IDMedic', 'LibelleMedic', 'fkidtype', 'PrixRef', 'estInterne'];
            return [
                'medicaments' => Medicament::select($cols)->where('fkidtype', 1)
                    ->orderBy('LibelleMedic')->get()->toArray(),
                'analyses'    => Medicament::select($cols)->where('fkidtype', 2)
                    ->orderBy('LibelleMedic')->get()->toArray(),
                'radios'      => Medicament::select($cols)->where('fkidtype', 3)
                    ->orderBy('LibelleMedic')->get()->toArray(),
            ];
        });

        $this->medicaments = $referentiel['medicaments'];
        $this->analyses = $referentiel['analyses'];
        $this->radios = $referentiel['radios'];
    }

    public function loadOrdonnancesPatient()
    {
        if (!$this->patientId) return;

        $this->ordonnancesPatient = Ordonnanceref::where('fkidpatient', $this->patientId)
            ->with(['ordonnances', 'prescripteur'])
            ->orderBy('dtPrescript', 'desc')
            ->get()
            ->toArray();
    }

    public function updatePatient($patient)
    {
        $this->patient = $patient;
        if (is_array($patient)) {
            $this->patientId = $patient['ID'] ?? $patient['id'] ?? null;
        } elseif (is_object($patient)) {
            $this->patientId = $patient->ID ?? $patient->id ?? null;
        }
        $this->loadOrdonnancesPatient();
    }

    public function changerTypeOrdonnance($type)
    {
        $this->typeOrdonnance = $type;
        $this->lignesOrdonnance = [];
        $this->searchTerms = [];
        $this->searchResults = [];
        $this->showSearchResults = [];
        $this->ajouterLigneVide();
    }

    public function changerModeOrdonnance($mode)
    {
        $this->modeOrdonnance = $mode;
        $this->lignesOrdonnance = [];
        $this->searchTerms = [];
        $this->searchResults = [];
        $this->showSearchResults = [];
        $this->ajouterLigneVide();
    }

    public function ajouterLigneVide()
    {
        $index = count($this->lignesOrdonnance);
        $this->lignesOrdonnance[] = [
            'medicament_id'      => '',
            'posologie'          => '',
            'medicament_libelle' => '',
            'libre'              => false,
            'estInterne'         => $this->modeOrdonnance === 'urgence',
            'quantite'           => 1,
            'stock_quantite'     => null,
            'stock_prix'         => null,
        ];
        $this->searchTerms[$index] = '';
        $this->searchResults[$index] = [];
        $this->showSearchResults[$index] = false;
    }

    public function supprimerLigne($index)
    {
        if (count($this->lignesOrdonnance) > 1) {
            unset($this->lignesOrdonnance[$index]);
            unset($this->searchTerms[$index]);
            unset($this->searchResults[$index]);
            unset($this->showSearchResults[$index]);
            
            // Réindexer les tableaux
            $this->lignesOrdonnance = array_values($this->lignesOrdonnance);
            $newSearchTerms = [];
            $newSearchResults = [];
            $newShowSearchResults = [];
            foreach ($this->lignesOrdonnance as $newIndex => $ligne) {
                $oldIndex = $newIndex < $index ? $newIndex : $newIndex + 1;
                $newSearchTerms[$newIndex] = $this->searchTerms[$oldIndex] ?? '';
                $newSearchResults[$newIndex] = $this->searchResults[$oldIndex] ?? [];
                $newShowSearchResults[$newIndex] = $this->showSearchResults[$oldIndex] ?? false;
            }
            $this->searchTerms = $newSearchTerms;
            $this->searchResults = $newSearchResults;
            $this->showSearchResults = $newShowSearchResults;
        }
    }

    public function updatedSearchTerms($value, $key)
    {
        // $key est au format "searchTerms.0", "searchTerms.1", etc.
        // Extraire l'index
        $index = (int) str_replace('searchTerms.', '', $key);
        
        if (strlen(trim($value)) >= 1) {
            $this->searchMedicament($index, $value);
        } else {
            $this->searchResults[$index] = [];
            $this->showSearchResults[$index] = false;
        }
    }

    public function searchMedicament($index, $searchTerm = null)
    {
        $search = trim($searchTerm ?? $this->searchTerms[$index] ?? '');
        
        if (empty($search)) {
            $this->searchResults[$index] = [];
            $this->showSearchResults[$index] = false;
            return;
        }

        // Récupérer la liste selon le type d'ordonnance
        $liste = match($this->typeOrdonnance) {
            1 => $this->medicaments,
            2 => $this->analyses,
            3 => $this->radios,
            default => []
        };

        // Filtrer les résultats
        $this->searchResults[$index] = array_filter($liste, function($item) use ($search) {
            return stripos($item['LibelleMedic'], $search) !== false;
        });

        // Limiter à 10 résultats
        $this->searchResults[$index] = array_slice($this->searchResults[$index], 0, 10);
        $this->showSearchResults[$index] = true;
    }

    public function selectMedicament($index, $medicamentId)
    {
        $liste = match($this->typeOrdonnance) {
            1 => $this->medicaments,
            2 => $this->analyses,
            3 => $this->radios,
            default => []
        };

        $medicament = collect($liste)->firstWhere('IDMedic', $medicamentId);

        if ($medicament) {
            $this->lignesOrdonnance[$index]['medicament_id']      = $medicament['IDMedic'];
            $this->lignesOrdonnance[$index]['medicament_libelle'] = $medicament['LibelleMedic'];
            $this->lignesOrdonnance[$index]['libre']              = false;
            $this->lignesOrdonnance[$index]['estInterne']         = $this->modeOrdonnance === 'urgence';
            $this->searchTerms[$index]                            = $medicament['LibelleMedic'];
            $this->searchResults[$index]                          = [];
            $this->showSearchResults[$index]                      = false;

            // Charger l'état du stock uniquement pour les médicaments en mode urgence
            if ($this->modeOrdonnance === 'urgence' && $this->typeOrdonnance == 1) {
                $stock = StockMedicament::where('fkidMedicament', $medicament['IDMedic'])
                    ->where('fkidCabinet', Auth::user()->fkidcabinet)
                    ->where('Masquer', 0)
                    ->first();
                $this->lignesOrdonnance[$index]['stock_quantite'] = $stock ? (int)$stock->quantiteStock : 0;
                $this->lignesOrdonnance[$index]['stock_prix']     = $medicament['PrixRef'] ?? null;
            } else {
                $this->lignesOrdonnance[$index]['stock_quantite'] = null;
                $this->lignesOrdonnance[$index]['stock_prix']     = null;
            }
        }
    }

    // Sélection libre : utiliser le terme saisi tel quel
    public function selectLibre($index)
    {
        $libelle = trim($this->searchTerms[$index] ?? '');
        if (empty($libelle)) return;

        $this->lignesOrdonnance[$index]['medicament_id']      = 'libre_' . $index; // marqueur non-BDD
        $this->lignesOrdonnance[$index]['medicament_libelle'] = $libelle;
        $this->lignesOrdonnance[$index]['libre']              = true;
        $this->searchResults[$index]                          = [];
        $this->showSearchResults[$index]                      = false;
    }

    public function clearMedicamentSearch($index)
    {
        $this->lignesOrdonnance[$index]['medicament_id'] = '';
        $this->lignesOrdonnance[$index]['medicament_libelle'] = '';
        $this->searchTerms[$index] = '';
        $this->searchResults[$index] = [];
        $this->showSearchResults[$index] = false;
    }

    public function getListeMedicamentsProperty()
    {
        return match($this->typeOrdonnance) {
            1 => $this->medicaments,  // Médicaments
            2 => $this->analyses,      // Analyses
            3 => $this->radios,        // Radios
            default => []
        };
    }

    public function getTypeOrdonnanceLibelleProperty()
    {
        return match($this->typeOrdonnance) {
            1 => 'Ordonnance Médicale',
            2 => 'Ordonnance d\'Analyses',
            3 => 'Ordonnance de Radiologie',
            default => 'Ordonnance'
        };
    }

    public function sauvegarderOrdonnance()
    {
        // Filtrer les lignes vides
        $lignesValides = array_filter($this->lignesOrdonnance, function($ligne) {
            return !empty($ligne['medicament_id']);
        });

        if (empty($lignesValides)) {
            session()->flash('error', 'Veuillez ajouter au moins une ligne à l\'ordonnance.');
            return;
        }

        // Réindexer le tableau
        $lignesValides = array_values($lignesValides);

        $this->validate();

        try {
            DB::beginTransaction();

            // Générer la référence d'ordonnance
            $annee = now()->year;
            $lastNumOrdre = Ordonnanceref::where('Annee', $annee)
                ->where('fkidCabinet', Auth::user()->fkidcabinet)
                ->max('numordre') ?? 0;

            $numOrdre = $lastNumOrdre + 1;
            $refOrd = 'ORD-' . $annee . '-' . str_pad($numOrdre, 4, '0', STR_PAD_LEFT);

            // Créer l'ordonnance référence
            $ordonnanceRef = Ordonnanceref::create([
                'refOrd' => $refOrd,
                'Annee' => $annee,
                'numordre' => $numOrdre,
                'fkidpatient' => $this->patientId,
                'fkidprescripteur' => Auth::id(),
                'dtPrescript' => now(),
                'fkidCabinet' => Auth::user()->fkidcabinet,
                'TypeOrdonnance' => $this->modeOrdonnance === 'urgence'
                    ? match($this->typeOrdonnance) {
                        2 => 'Ordonnance d\'Analyses',
                        3 => 'Ordonnance de Radiologie',
                        default => 'Traitement d\'urgence'
                      }
                    : $this->typeOrdonnanceLibelle
            ]);

            // Créer les lignes d'ordonnance
            $numOrdreLigne = 1;
            foreach ($lignesValides as $ligne) {
                // Libellé libre ou depuis la BDD
                if (!empty($ligne['libre'])) {
                    $libelle = $ligne['medicament_libelle'];
                } else {
                    $medicament = Medicament::find($ligne['medicament_id']);
                    $libelle = $medicament ? $medicament->LibelleMedic : ($ligne['medicament_libelle'] ?? null);
                }

                if ($libelle) {
                    Ordonnance::create([
                        'Libelle'        => $libelle,
                        'DtPrescription' => now(),
                        'fkidrefOrd'     => $ordonnanceRef->id,
                        'NumordreOrd'    => $numOrdreLigne++,
                        'Utilisation'    => $ligne['posologie'] ?? null,
                        'Quantite'       => max(1, (int)($ligne['quantite'] ?? 1)),
                        'fkiduser'       => Auth::id(),
                        'estInterne'     => !empty($ligne['estInterne']),
                    ]);
                }
            }

            // ── Facturation automatique des éléments internes ──
            $itemsFactures = 0;

            // Chercher la dernière facture ouverte du patient (tous types)
            $derniereFacture = Facture::where('IDPatient', $this->patientId)
                ->where('fkidCabinet', Auth::user()->fkidcabinet)
                ->where('estfacturer', 0)
                ->orderBy('DtFacture', 'desc')
                ->first();

            $horsStock = []; // médicaments sans stock disponible

            if ($derniereFacture && $this->modeOrdonnance === 'urgence') {
                foreach ($lignesValides as $ligne) {
                    if (!empty($ligne['libre']) || empty($ligne['medicament_id'])) continue;

                    $medicament = Medicament::find($ligne['medicament_id']);
                    if (!$medicament) continue;

                    $prix     = 0;
                    $quantite = max(1, (int)($ligne['quantite'] ?? 1));
                    $stock    = null;

                    if ($this->typeOrdonnance == 1) {
                        // Médicament : vérifier l'existence en stock avant de facturer
                        $stock = StockMedicament::where('fkidMedicament', $ligne['medicament_id'])
                            ->where('fkidCabinet', Auth::user()->fkidcabinet)
                            ->where('Masquer', 0)
                            ->where('quantiteStock', '>', 0)
                            ->first();

                        if (!$stock) {
                            // Médicament interne mais absent du stock : noter et ignorer
                            $horsStock[] = $medicament->LibelleMedic;
                            continue;
                        }

                        $prix = $medicament->PrixRef ?? 0;

                        // Décrémenter le stock
                        $stock->quantiteStock     -= $quantite;
                        $stock->dateDerniereSortie = now();
                        $stock->save();
                    } else {
                        // Analyse (type 2) ou Radio (type 3) : pas de stock, prix depuis PrixRef
                        $prix = $medicament->PrixRef ?? 0;
                    }

                    // Ajouter ligne sur la facture
                    $detail = Detailfacturepatient::create([
                        'fkidfacture'    => $derniereFacture->Idfacture,
                        'DtAjout'        => now(),
                        'Actes'          => $medicament->LibelleMedic,
                        'PrixRef'        => $prix,
                        'PrixFacture'    => $prix,
                        'Quantite'       => $quantite,
                        'fkidMedecin'    => Auth::user()->fkidmedecin,
                        'user'           => Auth::id(),
                        'fkidmedicament' => $ligne['medicament_id'],
                        'IsAct'          => ($medicament->fkidtype ?? 1) + 1, // 2=Médicament, 3=Analyse, 4=Radio
                        'fkidcabinet'    => Auth::user()->fkidcabinet,
                    ]);

                    // Recalculer le total de la facture
                    $montantLigne = $prix * $quantite;
                    $txpec = $derniereFacture->TXPEC ?? 0;
                    $derniereFacture->TotFacture        = ($derniereFacture->TotFacture ?? 0) + $montantLigne;
                    $derniereFacture->TotalPEC          = ($derniereFacture->TotalPEC ?? 0) + ($montantLigne * $txpec);
                    $derniereFacture->TotalfactPatient  = $derniereFacture->TotFacture - $derniereFacture->TotalPEC;
                    $derniereFacture->save();

                    // Mouvement de stock uniquement pour les médicaments
                    if ($this->typeOrdonnance == 1 && $stock) {
                        MouvementStock::create([
                            'fkidStock'         => $stock->idStock,
                            'fkidMedicament'    => $ligne['medicament_id'],
                            'typeMouvement'     => 'sortie',
                            'quantite'          => $quantite,
                            'prixUnitaire'      => $prix,
                            'montantTotal'      => $prix * $quantite,
                            'motif'             => 'Ordonnance ' . $refOrd,
                            'fkidFacture'       => $derniereFacture->Idfacture,
                            'fkidDetailFacture' => $detail->idDetfacture,
                            'fkidPatient'       => $this->patientId,
                            'fkidUser'          => Auth::id(),
                            'dateMouvement'     => now(),
                            'reference'         => $refOrd,
                        ]);
                    }

                    $itemsFactures++;
                }
            }

            DB::commit();

            $typeLabel = match($this->typeOrdonnance) {
                1 => 'médicament(s)',
                2 => 'analyse(s)',
                3 => 'examen(s) radio',
                default => 'élément(s)',
            };
            $msg = 'Ordonnance créée avec succès.';
            if ($itemsFactures > 0) {
                $msg .= " {$itemsFactures} {$typeLabel} interne(s) ajouté(s) à la facture.";
            }
            if (!empty($horsStock)) {
                $msg .= ' Non facturé (stock épuisé) : ' . implode(', ', $horsStock) . '.';
            }
            session()->flash('message', $msg);
            $this->loadOrdonnancesPatient();
            $this->resetForm();

            // Émettre un événement pour rafraîchir la liste
            $this->dispatch('ordonnanceCreated', $ordonnanceRef->id);

            // Notification salle de soins si au moins une ligne interne
            if ($itemsFactures > 0 || collect($lignesValides)->contains(fn($l) => !empty($l['estInterne']))) {
                $nomPatient = '';
                if ($this->patient) {
                    $nomPatient = is_array($this->patient)
                        ? (($this->patient['Prenom'] ?? '') . ' ' . ($this->patient['NomPatient'] ?? ''))
                        : (($this->patient->Prenom ?? '') . ' ' . ($this->patient->Nom ?? ''));
                }
                $this->dispatch('nouvelle-ordonnance-interne-notif', nom: trim($nomPatient) ?: 'Patient');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Erreur lors de la création de l\'ordonnance : ' . $e->getMessage());
            Log::error('Erreur création ordonnance', [
                'error' => $e->getMessage(),
                'patient_id' => $this->patientId,
                'type' => $this->typeOrdonnance
            ]);
        }
    }

    public function resetForm()
    {
        $this->lignesOrdonnance = [];
        $this->searchTerms = [];
        $this->searchResults = [];
        $this->showSearchResults = [];
        $this->typeOrdonnance = 1;
        $this->modeOrdonnance = 'urgence';
        $this->ajouterLigneVide();
    }

    public function toggleAccordeon($type)
    {
        if ($this->accordeonOuvert === $type) {
            $this->accordeonOuvert = null;
        } else {
            $this->accordeonOuvert = $type;
        }
    }

    public function getOrdonnancesByType($type)
    {
        if (empty($this->ordonnancesPatient)) return [];

        $result = [];
        foreach ($this->ordonnancesPatient as $ordonnanceRef) {
            // Vérifier le type d'ordonnance selon le libellé
            $t = $ordonnanceRef['TypeOrdonnance'] ?? '';
            if (str_contains($t, 'Analyse') || str_contains($t, 'analyse')) {
                $typeOrdonnance = 2;
            } elseif (str_contains($t, 'Radio') || str_contains($t, 'radio')) {
                $typeOrdonnance = 3;
            } else {
                $typeOrdonnance = 1; // Médicale, Traitement d'urgence, Ordonnance de sortie, etc.
            }

            if ($typeOrdonnance === $type && isset($ordonnanceRef['ordonnances'])) {
                $result[] = [
                    'ref' => $ordonnanceRef,
                    'ordonnances' => $ordonnanceRef['ordonnances']
                ];
            }
        }

        return $result;
    }

    /**
     * Retourne uniquement les ordonnances internes (estInterne=true) d'un type donné.
     * Utilisé par les rôles sans permission de créer (ex: infirmier).
     */
    public function getOrdonnancesInternesByType($type)
    {
        $all = $this->getOrdonnancesByType($type);
        $result = [];
        foreach ($all as $item) {
            $lignesInternes = array_filter($item['ordonnances'], fn($o) => !empty($o['estInterne']));
            if (count($lignesInternes) > 0) {
                $result[] = [
                    'ref'        => $item['ref'],
                    'ordonnances'=> array_values($lignesInternes),
                ];
            }
        }
        return $result;
    }

    public function imprimerOrdonnance($ordonnanceId)
    {
        // Ouvrir l'URL d'impression dans un nouvel onglet (identique à la logique du reçu de consultation)
        $url = route('ordonnance.print', ['id' => $ordonnanceId]);
        \Log::info('Émission de l\'événement open-receipt pour ordonnance', ['url' => $url, 'ordonnance_id' => $ordonnanceId]);
        $this->dispatch('open-receipt', url: $url);
    }

    public function supprimerOrdonnance($ordonnanceId)
    {
        try {
            $ordonnanceRef = Ordonnanceref::find($ordonnanceId);

            if (!$ordonnanceRef) {
                session()->flash('error', 'Ordonnance introuvable.');
                return;
            }

            // Vérifier les permissions
            if ($ordonnanceRef->fkidprescripteur != Auth::id() && !Auth::user()->isDocteurProprietaire()) {
                session()->flash('error', 'Vous n\'avez pas la permission de supprimer cette ordonnance.');
                return;
            }

            DB::beginTransaction();

            // Supprimer les lignes
            Ordonnance::where('fkidrefOrd', $ordonnanceId)->delete();

            // Supprimer la référence
            $ordonnanceRef->delete();

            DB::commit();

            session()->flash('message', 'Ordonnance supprimée avec succès.');
            $this->loadOrdonnancesPatient();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.ordonnance-manager');
    }
}
