<?php

namespace App\Http\Livewire;

use App\Traits\HasLazyLoadingPlaceholder;
use Livewire\Component;
use App\Models\Facture;
use App\Models\Patient;
use App\Models\CaisseOperation;
use App\Models\Medecin;
use App\Models\Medecin as MedecinModel;
use App\Models\RefTypePaiement;
use Illuminate\Support\Facades\DB;
use App\Models\Detailfacturepatient;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;
use App\Models\Facturepatient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\StockMedicament;
use App\Models\LotMedicament;
use App\Models\Assureur;
use App\Models\MouvementStock;

class ReglementFacture extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithPagination;

    public $selectedPatient = null;
    public $selectedPatientId = null; // cache de secours si selectedPatient est perdu
    protected $factures;
    public $factureSelectionnee;
    public $montantReglement;
    public $modePaiement;
    public $modesPaiement;
    public $dernierReglement = null;
    public $pourQui;
    public $showAddActeForm = false;
    public $selectedActeId = '';
    public $prixReference;
    public $prixFacture;
    public $quantite = 1;
    public $factureIdForActe = null;
    public $actes = [];
    public $searchActe = '';
    public $filteredActes = [];
    public $acteSelectionne = false;
    public $showReglementModal = false;
    protected $facturesEnAttente;
    protected $currentPage = 1;
    public $showMedecinModal = false;
    public $selectedMedecinId = null;
    public $medecins = [];
    public $searchMedecin = '';
    public $showDossierMedicalModal = false;
    public $factureIdForDossier = null;
    public $factureDossier = null;
    public $patientDossier = null;
    
    // Propriétés pour médicaments/analyses/radios
    public $showAddMedicamentForm = false;
    public $selectedMedicamentId = '';
    public $selectedMedicamentType = ''; // 'medicament', 'analyse', 'radio'
    public $prixReferenceMedicament;
    public $prixFactureMedicament;
    public $quantiteMedicament = 1;
    public $stockDisponibleMedicament = null; // Quantité disponible en stock

    protected $listeners = [
        'patientSelected' => 'handlePatientSelected',
        'acteSelected' => 'handleActeSelected',
        'medicamentSelected' => 'handleMedicamentSelected',
        'closeModal' => 'closeAddActeForm'
    ];

    public function getFacturesProperty()
    {
        if ($this->selectedPatient) {
            $patientId = is_array($this->selectedPatient) ? ($this->selectedPatient['ID'] ?? null) : ($this->selectedPatient->ID ?? null);
            if (!$patientId) {
                return null;
            }
            
            return Facture::where('IDPatient', $patientId)
                ->with([
                    'medecin' => function($query) {
                        $query->select('idMedecin', 'Nom');
                    }
                ])
                ->select([
                    'Idfacture', 'Nfacture', 'FkidMedecinInitiateur', 'DtFacture',
                    'TotFacture', 'ISTP', 'TXPEC', 'TotalPEC', 'ReglementPEC',
                    'TotalfactPatient', 'TotReglPatient', 'estfacturer'
                ])
                ->orderBy('DtFacture', 'desc')
                ->paginate(10, ['*'], 'page', $this->currentPage);
        }
        return null;
    }

    public function mount($selectedPatient = null)
    {
        $this->showMedecinModal = false;
        
        // Utiliser le cache pour les modes de paiement (rarement modifiés)
        $cacheKeyModesPaiement = 'modes_paiement_' . Auth::user()->fkidcabinet;
        $this->modesPaiement = Cache::remember($cacheKeyModesPaiement, 3600, function() {
            return RefTypePaiement::all();
        });
        
        // Utiliser le cache pour les actes (rarement modifiés)
        $cacheKeyActes = 'actes_list_' . Auth::user()->fkidcabinet;
        $this->actes = Cache::remember($cacheKeyActes, 3600, function() {
            return \App\Models\Acte::where('Masquer', 0)->get();
        });
        
        if ($selectedPatient) {
            if (is_object($selectedPatient)) {
                $selectedPatient = $selectedPatient->toArray();
            }
            $this->selectedPatient = $selectedPatient;
            $this->selectedPatientId = $selectedPatient['ID'] ?? $selectedPatient['id'] ?? null;
        }
        // Ne pas charger les factures en attente au mount (non utilisé dans le modal)
        // $this->loadFacturesEnAttente();
    }

    public function handlePatientSelected($patient)
    {
        $this->showMedecinModal = false;
        $this->selectedPatient = $patient;
        $this->selectedPatientId = is_array($patient) ? ($patient['ID'] ?? $patient['id'] ?? null) : ($patient->ID ?? null);
        $this->loadFactures();
    }

    public function handleActeSelected($id, $prixReference)
    {
        $this->selectedActeId = $id;
        $this->prixReference = $prixReference;
        $this->prixFacture = $prixReference;
    }

    public function loadFactures()
    {
        if ($this->selectedPatient) {
            $patientId = is_array($this->selectedPatient) ? ($this->selectedPatient['ID'] ?? null) : ($this->selectedPatient->ID ?? null);
            // Invalider le cache pour toutes les pages de ce patient
            if ($patientId) {
                // Invalider toutes les pages possibles (on peut optimiser en gardant trace des pages)
                for ($page = 1; $page <= 10; $page++) {
                    Cache::forget('factures_patient_' . $patientId . '_page_' . $page);
                }
            }
            $this->factures = $this->getFacturesProperty();
        } else {
            $this->factures = null;
        }
    }

    public function selectionnerFacture($factureId)
    {
        // S'assurer que les factures sont chargées
        if (!$this->factures && $this->selectedPatient) {
            $this->factures = $this->getFacturesProperty();
        }

        // Utiliser les données déjà chargées dans la pagination
        $facture = null;
        if ($this->factures) {
            $facture = $this->factures->firstWhere('Idfacture', $factureId);
            // Si la facture est trouvée mais n'a pas les détails, les charger
            if ($facture && !$facture->relationLoaded('details')) {
                $facture->load('details');
            }
        }
        
        if (!$facture) {
            // Fallback : charger la facture si elle n'est pas dans la pagination actuelle
            $facture = Facture::with(['medecin', 'details'])->find($factureId);
        } elseif ($facture && !$facture->relationLoaded('details')) {
            // Charger les détails si pas déjà chargés
            $facture->load('details');
        }
        
        if ($facture) {
            $this->factureSelectionnee = [
                'id' => $facture->Idfacture,
                'numero' => $facture->Nfacture,
                'medecin' => $facture->medecin ? ['Nom' => $facture->medecin->Nom] : ['Nom' => Auth::user()->NomComplet ?? Auth::user()->name],
                'montant_total' => $facture->TotFacture ?? 0,
                'montant_pec' => floatval($facture->TotalPEC ?? 0),
                'part_patient' => $facture->TotalfactPatient ?? 0,
                'montant_reglements_patient' => $facture->TotReglPatient ?? 0,
                'montant_reglements_pec' => $facture->ReglementPEC ?? 0,
                'reste_a_payer' => (($facture->ISTP > 0 ? ($facture->TotalfactPatient ?? 0) : ($facture->TotFacture ?? 0)) - ($facture->TotReglPatient ?? 0)),
                'reste_a_payer_pec' => $facture->ISTP > 0 ? (($facture->TotalPEC ?? 0) - ($facture->ReglementPEC ?? 0)) : 0,
                'TXPEC' => $facture->TXPEC ?? 0,
                'ISTP' => $facture->ISTP ?? 0,
                'est_reglee' => ((($facture->ISTP > 0 ? ($facture->TotalfactPatient ?? 0) : ($facture->TotFacture ?? 0)) - ($facture->TotReglPatient ?? 0)) <= 0) && ($facture->ISTP > 0 ? (($facture->TotalPEC ?? 0) - ($facture->ReglementPEC ?? 0)) <= 0 : true),
            ];
            // Initialiser le montant du paiement avec le reste à payer
            $this->montantReglement = max(0, $this->factureSelectionnee['reste_a_payer']);

            // Détection assuré ou non
            if ($facture->ISTP == 1) {
                $this->pourQui = 'patient'; // valeur par défaut
            } else {
                $this->pourQui = null;
            }
        }
    }

    public function enregistrerReglement()
    {
        $this->validate([
            'montantReglement' => 'required|numeric',
            'modePaiement' => 'required|exists:ref_type_paiement,idtypepaie',
        ]);
        // Si assuré, on doit avoir pourQui
        if ($this->pourQui === null && $this->factureSelectionnee && ($facture = Facture::find($this->factureSelectionnee['id'])) && $facture->ISTP == 1) {
            throw new \Exception('Veuillez préciser pour qui est le règlement (Patient ou PEC).');
        }
        try {
            DB::beginTransaction();

            $facture = Facture::find($this->factureSelectionnee['id']);
            if (!$facture) {
                throw new \Exception('Facture non trouvée');
            }

            $medecin = Medecin::find($facture->FkidMedecinInitiateur);
            if (!$medecin) {
                throw new \Exception('Médecin non trouvé');
            }

            $typePaiement = RefTypePaiement::find($this->modePaiement);
            if (!$typePaiement) {
                throw new \Exception('Mode de paiement non trouvé');
            }

            $isRemboursement = $this->montantReglement < 0;
            $isAcompte = $this->montantReglement > $this->factureSelectionnee['reste_a_payer'];
            $montantOperation = $this->montantReglement;
            $montantAbsolu = abs($montantOperation);

            $operation = CaisseOperation::create([
                'dateoper' => now(),
                'MontantOperation' => $montantOperation,
                'designation' => ($isRemboursement ? 'Remboursement' : ($isAcompte ? 'Acompte' : 'Règlement')) . ' facture N°' . $facture->Nfacture,
                'fkidTiers' => $this->selectedPatient['ID'],
                'entreEspece' => $isRemboursement ? 0 : $montantAbsolu,
                'retraitEspece' => $isRemboursement ? $montantAbsolu : 0,
                'pourPatFournisseur' => 0,
                'pourCabinet' => 1,
                'fkiduser' => auth()->id(),
                'exercice' => now()->year,
                'fkIdTypeTiers' => 1,
                'fkidfacturebord' => $facture->Idfacture,
                'DtCr' => now(),
                'fkidcabinet' => auth()->user()->fkidcabinet,
                'fkidtypePaie' => $this->modePaiement,
                'TypePAie' => $typePaiement->LibPaie,
                'fkidmedecin' => $facture->FkidMedecinInitiateur,
                'medecin' => $medecin->Nom
            ]);

            // Mise à jour de la facture selon le type de règlement
            if ($facture->ISTP == 1 && $this->pourQui === 'pec') {
                $facture->ReglementPEC = ($facture->ReglementPEC ?? 0) + $montantOperation;
            } else {
                $facture->TotReglPatient = ($facture->TotReglPatient ?? 0) + $montantOperation;
            }
            $facture->save();

            // NOTE: Le stock est maintenant déduit lors de l'ajout du médicament à la facture
            // Cette vérification reste comme sécurité au cas où le stock n'aurait pas été déduit
            // (pour les factures créées avant cette modification)
            $facture->refresh();
            if ($facture->estCompletementPayee()) {
                // Vérifier si le stock a déjà été déduit pour cette facture
                $detailsMedicaments = Detailfacturepatient::where('fkidfacture', $facture->Idfacture)
                    ->where('IsAct', 2)
                    ->whereNotNull('fkidmedicament')
                    ->get();
                
                $stockDejaDeduit = true;
                foreach ($detailsMedicaments as $detail) {
                    $mouvement = MouvementStock::where('fkidFacture', $facture->Idfacture)
                        ->where('fkidMedicament', $detail->fkidmedicament)
                        ->where('typeMouvement', 'SORTIE')
                        ->exists();
                    
                    if (!$mouvement) {
                        $stockDejaDeduit = false;
                        break;
                    }
                }
                
                // Si le stock n'a pas été déduit, le déduire maintenant (sécurité)
                if (!$stockDejaDeduit) {
                    \Log::info('Stock non déduit lors de la facturation, déduction lors du paiement (sécurité)', [
                        'facture_id' => $facture->Idfacture
                    ]);
                    $this->deduireStockFacture($facture, $operation);
                }
            }

            DB::commit();

            $this->dernierReglement = [
                'facture' => $facture,
                'patient' => $this->selectedPatient,
                'montant' => $montantOperation,
                'mode' => $typePaiement->LibPaie,
                'date' => now()->format('d/m/Y H:i'),
                'medecin' => $medecin->Nom,
                'operation' => $operation,
                'isRemboursement' => $isRemboursement,
                'isAcompte' => $isAcompte
            ];

            $this->reset(['montantReglement', 'modePaiement', 'factureSelectionnee', 'pourQui']);
            $this->loadFactures();
            session()->flash('message', ($isRemboursement ? 'Remboursement' : ($isAcompte ? 'Acompte' : 'Règlement')) . ' enregistré avec succès.');

            $receiptUrl = route('reglement-facture.receipt', $operation->getKey());
            $this->dispatch('open-receipt', url: $receiptUrl);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Une erreur est survenue lors de l\'enregistrement : ' . $e->getMessage());
        }
    }

    public function resetAddActeForm()
    {
        $this->selectedActeId = '';
        $this->prixReference = null;
        $this->prixFacture = null;
        $this->quantite = 1;
        $this->acteSelectionne = false;
    }

    public function resetAddMedicamentForm()
    {
        $this->selectedMedicamentId = '';
        $this->selectedMedicamentType = '';
        $this->prixReferenceMedicament = null;
        $this->prixFactureMedicament = null;
        $this->quantiteMedicament = 1;
        $this->stockDisponibleMedicament = null;
    }

    public function updatedSelectedMedicamentType($value)
    {
        // Réinitialiser la sélection du médicament quand on change de type
        $this->selectedMedicamentId = '';
        $this->prixReferenceMedicament = null;
        $this->prixFactureMedicament = null;
    }

    public function handleMedicamentSelected($id, $prixRef, $fkidtype)
    {
        $this->selectedMedicamentId = $id;
        $this->selectedMedicamentType = (string)$fkidtype;
        $this->prixReferenceMedicament = $prixRef;
        $this->prixFactureMedicament = $prixRef;
        
        // Récupérer la quantité disponible en stock (uniquement pour les médicaments, fkidtype = 1)
        if ($fkidtype == 1) {
            $cabinetId = Auth::user()->fkidcabinet ?? 1;
            $stock = StockMedicament::where('fkidMedicament', $id)
                ->where('fkidCabinet', $cabinetId)
                ->where('Masquer', 0)
                ->first();
            $this->stockDisponibleMedicament = $stock ? $stock->quantiteStock : 0;
        } else {
            $this->stockDisponibleMedicament = null; // Pas de stock pour analyses/radios
        }
    }

    public function updatedSelectedMedicamentId($value)
    {
        if (empty($value)) {
            $this->prixReferenceMedicament = null;
            $this->prixFactureMedicament = null;
            $this->stockDisponibleMedicament = null;
            return;
        }

        $medicamentId = (int) $value;
        $medicament = \App\Models\Medicament::find($medicamentId);
        
        if ($medicament) {
            $this->prixReferenceMedicament = $medicament->PrixRef ?? 0;
            $this->prixFactureMedicament = $medicament->PrixRef ?? 0;
            
            // Récupérer la quantité disponible en stock (uniquement pour les médicaments, fkidtype = 1)
            if ($medicament->fkidtype == 1) {
                $cabinetId = Auth::user()->fkidcabinet ?? 1;
                $stock = StockMedicament::where('fkidMedicament', $medicamentId)
                    ->where('fkidCabinet', $cabinetId)
                    ->where('Masquer', 0)
                    ->first();
                $this->stockDisponibleMedicament = $stock ? $stock->quantiteStock : 0;
            } else {
                $this->stockDisponibleMedicament = null; // Pas de stock pour analyses/radios
            }
        } else {
            $this->prixReferenceMedicament = null;
            $this->prixFactureMedicament = null;
            $this->stockDisponibleMedicament = null;
        }
    }

    public function selectMedicament($id, $type)
    {
        if (!$id) {
            $this->resetAddMedicamentForm();
            return;
        }

        $medicament = \App\Models\Medicament::find($id);
        if ($medicament && $medicament->fkidtype == $type) {
            $this->selectedMedicamentId = $medicament->IDMedic;
            $this->selectedMedicamentType = $type;
            $this->prixReferenceMedicament = $medicament->PrixRef ?? 0;
            $this->prixFactureMedicament = $medicament->PrixRef ?? 0;
            
            // Récupérer la quantité disponible en stock (uniquement pour les médicaments, fkidtype = 1)
            if ($medicament->fkidtype == 1) {
                $cabinetId = Auth::user()->fkidcabinet ?? 1;
                $stock = StockMedicament::where('fkidMedicament', $id)
                    ->where('fkidCabinet', $cabinetId)
                    ->where('Masquer', 0)
                    ->first();
                $this->stockDisponibleMedicament = $stock ? $stock->quantiteStock : 0;
            } else {
                $this->stockDisponibleMedicament = null; // Pas de stock pour analyses/radios
            }
        } else {
            $this->resetAddMedicamentForm();
        }
    }

    public function saveMedicamentToFacture()
    {
        $this->validate([
            'selectedMedicamentId' => 'required|exists:medicaments,IDMedic',
            'selectedMedicamentType' => 'required|in:1,2,3',
            'prixFactureMedicament' => 'required|numeric|min:0',
            'quantiteMedicament' => 'required|integer|min:1',
        ], [
            'selectedMedicamentType.required' => 'Veuillez sélectionner un type d\'opération',
            'selectedMedicamentId.required' => 'Veuillez sélectionner un item',
        ]);

        try {
            DB::beginTransaction();

            $medicament = \App\Models\Medicament::find($this->selectedMedicamentId);
            
            // Convertir selectedMedicamentType en integer pour la comparaison
            $typeInt = (int) $this->selectedMedicamentType;
            
            if (!$medicament || $medicament->fkidtype != $typeInt) {
                throw new \Exception('Médicament non trouvé ou type incorrect');
            }

            $isAct = $medicament->fkidtype + 1; // 2=Médicament, 3=Analyse, 4=Radio

            // Si c'est un médicament (fkidtype = 1, IsAct = 2), vérifier le stock
            $stock = null;
            if ($medicament->fkidtype == 1 && $isAct == 2) {
                $cabinetId = Auth::user()->fkidcabinet ?? 1;
                $stock = StockMedicament::where('fkidMedicament', $this->selectedMedicamentId)
                    ->where('fkidCabinet', $cabinetId)
                    ->first();

                if (!$stock) {
                    throw new \Exception('Le médicament "' . $medicament->LibelleMedic . '" n\'est pas en stock.');
                }

                // Vérifier le stock disponible (déduction immédiate lors de la facturation)
                if ($stock->quantiteStock < $this->quantiteMedicament) {
                    throw new \Exception('Stock insuffisant pour le médicament "' . $medicament->LibelleMedic . '". Stock disponible: ' . number_format($stock->quantiteStock, 0));
                }
            }

            $detail = \App\Models\Detailfacturepatient::create([
                'fkidfacture' => $this->factureIdForActe,
                'DtAjout' => now(),
                'Actes' => $medicament->LibelleMedic,
                'PrixRef' => $this->prixReferenceMedicament ?? 0,
                'PrixFacture' => $this->prixFactureMedicament,
                'Quantite' => $this->quantiteMedicament,
                'fkidmedicament' => $this->selectedMedicamentId,
                'IsAct' => $isAct,
                'fkidMedecin' => $this->factureSelectionnee->FkidMedecinInitiateur ?? 1,
                'fkidcabinet' => Auth::user()->fkidcabinet ?? 1,
                'ActesArab' => 'NR',
                'Dents' => 'Med',
            ]);

            $facture = \App\Models\Facture::find($this->factureIdForActe);
            $prixFactureItem = $this->prixFactureMedicament * $this->quantiteMedicament;
            $txpec = $facture->TXPEC ?? 0;
            $nouveauTotFacture = ($facture->TotFacture ?? 0) + $prixFactureItem;
            $montantPEC = $prixFactureItem * $txpec;
            $totalPEC = ($facture->TotalPEC ?? 0) + $montantPEC;
            $totalfactPatient = $nouveauTotFacture - $totalPEC;
            $facture->TotFacture = $nouveauTotFacture;
            $facture->TotalPEC = $totalPEC;
            $facture->TotalfactPatient = $totalfactPatient;
            $facture->save();

            // Si c'est un médicament, déduire le stock immédiatement selon la méthode FIFO
            if ($medicament->fkidtype == 1 && $isAct == 2 && $stock) {
                $this->deduireStockMedicament($stock, $this->quantiteMedicament, $this->factureIdForActe, $detail->idDetfacture, $medicament->LibelleMedic);
            }

            DB::commit();
            
            // Le formulaire reste ouvert pour permettre l'ajout d'autres items
            $this->showAddMedicamentForm = true;
            
            $typeLabel = match($medicament->fkidtype) {
                1 => 'Médicament',
                2 => 'Analyse',
                3 => 'Radio',
                default => 'Item'
            };
            session()->flash('message', $typeLabel . ' ajouté avec succès. Vous pouvez continuer à ajouter d\'autres items.');

            // Réinitialiser les champs pour permettre l'ajout d'un nouvel item
            $this->resetAddMedicamentForm();
            $this->loadFactures();
            if ($this->factureSelectionnee) {
                $this->selectionnerFacture($this->factureSelectionnee['id']);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Une erreur est survenue : ' . $e->getMessage());
        }
    }

    public function openAddMedicamentForm($factureId, $defaultType = null)
    {
        $this->factureIdForActe = $factureId;
        $this->resetAddMedicamentForm();
        if ($defaultType) {
            $this->selectedMedicamentType = (string) $defaultType;
        }
        $this->showAddMedicamentForm = true;
    }

    public function closeAddMedicamentForm()
    {
        $this->showAddMedicamentForm = false;
        $this->resetAddMedicamentForm();
    }

    public function updatedSelectedActeId($value)
    {
        if (empty($value)) {
            $this->prixReference = null;
            $this->prixFacture = null;
            return;
        }

        $acteId = (int) $value;
        $acte = \App\Models\Acte::find($acteId);

        if ($acte) {
            $this->prixReference = $acte->PrixRef;
            $this->prixFacture = $acte->PrixRef;
        } else {
            $this->prixReference = null;
            $this->prixFacture = null;
        }
    }

    public function updatedSearchActe($value)
    {
        if (!$this->acteSelectionne) {
            $this->filteredActes = \App\Models\Acte::where('Acte', 'like', '%' . $value . '%')->get();
        }
    }

    public function selectActe($id = null)
    {
        if (!$id) {
            $this->resetAddActeForm();
            return;
        }

        $acte = \App\Models\Acte::find($id);

        if ($acte) {
            $this->selectedActeId = $acte->ID;
            $this->prixReference = $acte->PrixRef;
            $this->prixFacture = $acte->PrixRef;
            $this->acteSelectionne = true;
        } else {
            $this->resetAddActeForm();
        }
    }

    public function saveActeToFacture()
    {
        $this->validate([
            'selectedActeId' => 'required|exists:actes,ID',
            'prixFacture' => 'required|numeric|min:0',
            'quantite' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            app(\App\Services\FacturationService::class)->ajouterActeAFacture(
                $this->factureIdForActe,
                $this->selectedActeId,
                $this->prixFacture,
                $this->quantite
            );

            DB::commit();
            $this->showAddActeForm = false;
            $this->loadFactures();
            if ($this->factureSelectionnee) {
                $this->selectionnerFacture($this->factureSelectionnee['id']);
            }
            session()->flash('message', 'Acte ajouté avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Une erreur est survenue lors de l\'ajout de l\'acte : ' . $e->getMessage());
        }
    }

    public function openAddActeForm($factureId)
    {
        $this->factureIdForActe = $factureId;
        $this->resetAddActeForm();
        $this->showAddActeForm = true;
    }

    public function closeAddActeForm()
    {
        $this->showAddActeForm = false;
        $this->resetAddActeForm();
    }

    public function closeReglementForm()
    {
        $this->factureSelectionnee = null;
        $this->montantReglement = null;
        $this->modePaiement = null;
        $this->pourQui = null;
        $this->showReglementModal = false;
    }

    public function setConsultationActe()
    {
        $acte = \App\Models\Acte::where('Acte', 'like', '%consultation%')
            ->orWhere('Acte', 'like', '%CONSULTATION%')
            ->first();

        if ($acte) {
            $this->selectedActeId = $acte->ID;
            $this->prixReference = $acte->PrixRef;
            $this->prixFacture = $acte->PrixRef;
        }
    }

    public function ouvrirReglementFacture($factureId)
    {
        $this->selectionnerFacture($factureId);
        $this->showReglementModal = true;
    }

    public function removeActe($detailId)
    {
        if (!\Auth::user()->hasPermission('facture.ligne.delete')) {
            $this->dispatch('toast', ['message' => 'Permission insuffisante pour supprimer un acte.', 'type' => 'error']);
            return;
        }

        try {
            DB::beginTransaction();

            // Récupérer le détail de l'acte
            $detail = Detailfacturepatient::find($detailId);
            if (!$detail) {
                throw new \Exception('Acte non trouvé');
            }

            // Récupérer la facture
            $facture = Facture::find($detail->fkidfacture);
            if (!$facture) {
                throw new \Exception('Facture non trouvée');
            }

            // Si c'est un médicament (IsAct = 2), restaurer le stock
            if ($detail->IsAct == 2 && $detail->fkidmedicament) {
                $medicamentId = $detail->fkidmedicament;
                $quantiteARestaurer = $detail->Quantite ?? 0;
                
                if ($quantiteARestaurer > 0) {
                    $cabinetId = Auth::user()->fkidcabinet ?? 1;
                    $stock = StockMedicament::where('fkidMedicament', $medicamentId)
                        ->where('fkidCabinet', $cabinetId)
                        ->where('Masquer', 0)
                        ->first();
                    
                    if ($stock) {
                        // Restaurer la quantité dans le stock
                        $stock->quantiteStock += $quantiteARestaurer;
                        $stock->save();
                        
                        // Essayer de restaurer dans les lots (si des mouvements de stock existent pour ce détail)
                        $mouvementsLot = MouvementStock::where('fkidDetailFacture', $detailId)
                            ->where('fkidMedicament', $medicamentId)
                            ->where('typeMouvement', 'SORTIE')
                            ->whereNotNull('fkidLot')
                            ->orderBy('dateMouvement', 'desc') // Restaurer dans l'ordre inverse
                            ->get();
                        
                        $quantiteRestanteARestaurer = $quantiteARestaurer;
                        foreach ($mouvementsLot as $mouvement) {
                            if ($quantiteRestanteARestaurer <= 0) {
                                break;
                            }
                            
                            $lot = LotMedicament::find($mouvement->fkidLot);
                            if ($lot) {
                                $quantiteDuMouvement = abs($mouvement->quantite);
                                $quantiteARestaurerDansLot = min($quantiteDuMouvement, $quantiteRestanteARestaurer);
                                $lot->quantiteRestante += $quantiteARestaurerDansLot;
                                $lot->save();
                                $quantiteRestanteARestaurer -= $quantiteARestaurerDansLot;
                            }
                        }
                        
                        \Log::info('Stock restauré lors de la suppression d\'un médicament de la facture', [
                            'detail_id' => $detailId,
                            'medicament_id' => $medicamentId,
                            'quantite_restauree' => $quantiteARestaurer,
                            'quantite_restauree_dans_lots' => $quantiteARestaurer - $quantiteRestanteARestaurer,
                            'stock_apres_restauration' => $stock->quantiteStock
                        ]);
                    }
                    
                    // Supprimer les mouvements de stock liés à ce détail
                    MouvementStock::where('fkidDetailFacture', $detailId)->delete();
                }
            }

            // Calculer le montant à soustraire
            $montantActe = $detail->PrixFacture * $detail->Quantite;
            $txpec = $facture->TXPEC ?? 0;
            $montantPEC = $montantActe * $txpec;

            // Mettre à jour les montants de la facture
            $facture->TotFacture = max(0, ($facture->TotFacture ?? 0) - $montantActe);
            $facture->TotalPEC = max(0, ($facture->TotalPEC ?? 0) - $montantPEC);
            $facture->TotalfactPatient = max(0, $facture->TotFacture - $facture->TotalPEC);
            $facture->save();

            // Supprimer le détail de l'acte
            $detail->delete();

            DB::commit();
            
            $typeItem = $detail->IsAct == 2 ? 'Médicament' : 'Acte';
            session()->flash('message', $typeItem . ' supprimé avec succès' . ($detail->IsAct == 2 ? '. Le stock a été restauré.' : '.'));
            $this->loadFactures();
            if ($this->factureSelectionnee) {
                $this->selectionnerFacture($this->factureSelectionnee['id']);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la suppression d\'un acte/médicament', [
                'detail_id' => $detailId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Une erreur est survenue lors de la suppression : ' . $e->getMessage());
        }
    }

    public function loadFacturesEnAttente()
    {
        $cacheKey = 'factures_en_attente_' . Auth::user()->fkidcabinet;
        $this->facturesEnAttente = Cache::remember($cacheKey, 300, function() {
            return Facture::with(['patient:id,ID,Nom,Prenom', 'medecin:idMedecin,Nom'])
                ->where('estfacturer', 0)
                ->where('fkidCabinet', Auth::user()->fkidcabinet)
                ->select(['Idfacture', 'Nfacture', 'IDPatient', 'FkidMedecinInitiateur', 'DtFacture'])
                ->orderBy('DtFacture', 'desc')
                ->limit(50) // Limiter le nombre de factures chargées
                ->get();
        });
    }

    public function openMedecinModal()
    {
        $user = auth()->user();
        $isMedecin = !empty($user->fkidmedecin);

        if ($isMedecin) {
            // Si l'utilisateur est un médecin, créer directement la facture
            $this->createFactureVide($user->fkidmedecin);
        } else {
            // Si l'utilisateur n'est pas un médecin, afficher le modal de sélection
            $this->medecins = \App\Models\Medecin::where('fkidcabinet', auth()->user()->fkidcabinet)
                ->orderBy('Nom')
                ->get();
            $this->showMedecinModal = true;
        }
    }

    public function selectMedecin($medecinId)
    {
        $this->selectedMedecinId = $medecinId;
        $this->createFactureVide($medecinId);
        $this->showMedecinModal = false;
    }

    public function updatedSearchMedecin()
    {
        $this->medecins = \App\Models\Medecin::where('fkidcabinet', auth()->user()->fkidcabinet)
            ->where('Nom', 'like', '%' . $this->searchMedecin . '%')
            ->orderBy('Nom')
            ->get();
    }

    public function openDossierMedicalModal($factureId)
    {
        $this->factureIdForDossier = $factureId;
        $this->factureDossier = Facture::with(['patient', 'medecin'])->find($factureId);
        if ($this->factureDossier && $this->factureDossier->patient) {
            $this->patientDossier = $this->factureDossier->patient;
        }
        $this->showDossierMedicalModal = true;
    }

    public function closeDossierMedicalModal()
    {
        $this->showDossierMedicalModal = false;
        $this->factureIdForDossier = null;
        $this->factureDossier = null;
        $this->patientDossier = null;
    }

    public function createFactureVide($medecinId = null)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();
            
            // Si aucun médecin n'est spécifié, on vérifie si l'utilisateur est un médecin
            if (!$medecinId) {
                if (empty($user->fkidmedecin)) {
                    throw new \Exception('Vous devez sélectionner un médecin pour créer la facture');
                }
                $medecinId = $user->fkidmedecin;
            }

            // Utiliser la méthode centralisée pour générer un numéro unique
            $factureData = Facture::generateUniqueFactureNumber($user->fkidcabinet);
            $nfacture = $factureData['Nfacture'];
            $nordre = $factureData['nordre'];
            $annee = $factureData['anneeFacture'];

            // Récupérer l'assureur du patient si disponible
            $fkidEtsAssurance = null;
            $istp = 0;
            $txpec = 0;
            if (isset($this->selectedPatient['ID'])) {
                $patient = Patient::find($this->selectedPatient['ID']);
                if ($patient && $patient->Assureur) {
                    $fkidEtsAssurance = $patient->Assureur;
                    $istp = 1;
                    $assureur = Assureur::find($patient->Assureur);
                    $txpec = $assureur ? floatval($assureur->TauxdePEC) / 100 : floatval($this->selectedPatient['TauxPEC'] ?? 0) / 100;
                } elseif (isset($this->selectedPatient['Assureur']) && intval($this->selectedPatient['Assureur']) > 0) {
                    $fkidEtsAssurance = $this->selectedPatient['Assureur'];
                    $istp = 1;
                    $txpec = floatval($this->selectedPatient['TauxPEC'] ?? 0);
                }
            }

            $facture = Facture::create([
                'Nfacture' => $nfacture,
                'anneeFacture' => $annee,
                'nordre' => $nordre,
                'DtFacture' => Carbon::now(),
                'IDPatient' => $this->selectedPatient['ID'],
                'ISTP' => $istp,
                'fkidEtsAssurance' => $fkidEtsAssurance,
                'TXPEC' => $txpec,
                'TotFacture' => 0,
                'TotalPEC' => 0,
                'TotalfactPatient' => 0,
                'FkidMedecinInitiateur' => $medecinId,
                'fkidCabinet' => $user->fkidcabinet,
                'user' => $user->NomComplet ?? $user->name,
                'TotReglPatient' => 0,
                'ReglementPEC' => 0,
                'PartLaboratoire' => 0,
                'MontantAffectation' => 0
            ]);

            DB::commit();
            $this->loadFactures();
            session()->flash('message', 'Facture créée avec succès');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Erreur lors de la création de la facture: ' . $e->getMessage());
        }
    }

    /**
     * Déduire le stock d'un médicament lors de l'ajout à une facture
     * Utilise la méthode FIFO (First In First Out) pour gérer les lots
     * 
     * @param StockMedicament $stock Le stock du médicament
     * @param float $quantite La quantité à déduire
     * @param int $factureId L'ID de la facture
     * @param int $detailId L'ID du détail de facture
     * @param string $libelleMedicament Le libellé du médicament
     * @return void
     */
    protected function deduireStockMedicament(StockMedicament $stock, $quantite, $factureId, $detailId, $libelleMedicament)
    {
        $userId = Auth::id();
        $medicamentId = $stock->fkidMedicament;
        $facture = \App\Models\Facture::find($factureId);

        // Déduire directement de quantiteStock
        $stock->quantiteStock = max(0, $stock->quantiteStock - $quantite);
        $stock->dateDerniereSortie = Carbon::now();
        $stock->save();

        // Enregistrer le mouvement de sortie
        MouvementStock::create([
            'fkidStock'          => $stock->idStock,
            'fkidMedicament'     => $medicamentId,
            'fkidLot'            => null,
            'typeMouvement'      => 'SORTIE',
            'quantite'           => $quantite,
            'prixUnitaire'       => $stock->prixAchat,
            'montantTotal'       => $stock->prixAchat * $quantite,
            'motif'              => 'Vente - Facture N°' . ($facture->Nfacture ?? $factureId),
            'fkidFacture'        => $factureId,
            'fkidDetailFacture'  => $detailId,
            'fkidPatient'        => $facture->IDPatient ?? null,
            'fkidUser'           => $userId,
            'dateMouvement'      => Carbon::now(),
            'reference'          => $facture->Nfacture ?? null,
            'notes'              => 'Déduction automatique lors de la facturation'
        ]);
    }

    /**
     * Déduire le stock des médicaments d'une facture complètement payée
     * Utilise la méthode FIFO (First In First Out) pour gérer les lots
     * NOTE: Cette méthode est maintenant utilisée comme sécurité si le stock n'a pas été déduit lors de la facturation
     * 
     * @param Facture $facture La facture complètement payée
     * @param CaisseOperation $operation L'opération de paiement
     * @return void
     */
    protected function deduireStockFacture(Facture $facture, CaisseOperation $operation)
    {
        $cabinetId = Auth::user()->fkidcabinet;
        $userId = Auth::id();
        
        // Récupérer tous les détails de facture qui sont des médicaments (IsAct = 2)
        $detailsMedicaments = Detailfacturepatient::where('fkidfacture', $facture->Idfacture)
            ->where('IsAct', 2) // Uniquement les médicaments
            ->whereNotNull('fkidmedicament')
            ->get();
        
        foreach ($detailsMedicaments as $detail) {
            $medicamentId = $detail->fkidmedicament;
            $quantiteADeduire = $detail->Quantite;
            
            // Récupérer le stock du médicament
            $stock = StockMedicament::where('fkidMedicament', $medicamentId)
                ->where('fkidCabinet', $cabinetId)
                ->first();
            
            if (!$stock) {
                \Log::warning('Stock non trouvé pour médicament', [
                    'medicament_id' => $medicamentId,
                    'facture_id' => $facture->Idfacture,
                    'cabinet_id' => $cabinetId
                ]);
                continue;
            }
            
            // Vérifier si le stock a déjà été déduit pour cette facture
            $dejaDeduit = MouvementStock::where('fkidFacture', $facture->Idfacture)
                ->where('fkidDetailFacture', $detail->idDetfacture)
                ->where('typeMouvement', 'SORTIE')
                ->exists();
            
            if ($dejaDeduit) {
                \Log::info('Stock déjà déduit pour ce détail de facture', [
                    'detail_id' => $detail->idDetfacture,
                    'facture_id' => $facture->Idfacture
                ]);
                continue;
            }
            
            // Déduire directement de quantiteStock
            $stock->quantiteStock = max(0, $stock->quantiteStock - $quantiteADeduire);
            $stock->dateDerniereSortie = Carbon::now();
            $stock->save();

            MouvementStock::create([
                'fkidStock'         => $stock->idStock,
                'fkidMedicament'    => $medicamentId,
                'fkidLot'           => null,
                'typeMouvement'     => 'SORTIE',
                'quantite'          => $quantiteADeduire,
                'prixUnitaire'      => $stock->prixAchat,
                'montantTotal'      => $stock->prixAchat * $quantiteADeduire,
                'motif'             => 'Vente - Facture N°' . $facture->Nfacture,
                'fkidFacture'       => $facture->Idfacture,
                'fkidDetailFacture' => $detail->idDetfacture,
                'fkidPatient'       => $facture->IDPatient,
                'fkidUser'          => $userId,
                'dateMouvement'     => Carbon::now(),
                'reference'         => $facture->Nfacture,
                'notes'             => 'Déduction automatique lors du paiement complet'
            ]);
        }
    }

    /**
     * Supprimer une facture complètement (médecin propriétaire uniquement)
     */
    public function supprimerFacture($factureId)
    {
        $user = Auth::user();
        if (!$user->hasPermission('facture.delete')) {
            session()->flash('error', 'Vous n\'avez pas la permission de supprimer une facture.');
            return;
        }
        
        try {
            DB::beginTransaction();
            
            $facture = Facture::find($factureId);
            if (!$facture) {
                session()->flash('error', 'Facture introuvable.');
                DB::rollBack();
                return;
            }
            
            // 1. Restaurer le stock à partir des mouvements de stock ET des détails de facture
            $cabinetId = Auth::user()->fkidcabinet ?? 1;
            
            // Récupérer tous les détails de facture qui sont des médicaments (IsAct = 2)
            $detailsMedicaments = Detailfacturepatient::where('fkidfacture', $factureId)
                ->where('IsAct', 2) // Médicaments
                ->whereNotNull('fkidmedicament')
                ->get();
            
            // Grouper par médicament pour restaurer les quantités
            $medicamentsARestaurer = [];
            foreach ($detailsMedicaments as $detail) {
                $medicamentId = $detail->fkidmedicament;
                $quantite = $detail->Quantite ?? 0;
                
                if ($medicamentId && $quantite > 0) {
                    if (!isset($medicamentsARestaurer[$medicamentId])) {
                        $medicamentsARestaurer[$medicamentId] = 0;
                    }
                    $medicamentsARestaurer[$medicamentId] += $quantite;
                }
            }
            
            // Restaurer le stock pour chaque médicament
            foreach ($medicamentsARestaurer as $medicamentId => $quantiteARestaurer) {
                $stock = StockMedicament::where('fkidMedicament', $medicamentId)
                    ->where('fkidCabinet', $cabinetId)
                    ->where('Masquer', 0)
                    ->first();
                
                if ($stock) {
                    // Restaurer la quantité dans le stock
                    $stock->quantiteStock += $quantiteARestaurer;
                    $stock->save();
                    
                    // Essayer de restaurer dans les lots (si des mouvements de stock existent)
                    $mouvementsLot = MouvementStock::where('fkidFacture', $factureId)
                        ->where('fkidMedicament', $medicamentId)
                        ->where('typeMouvement', 'SORTIE')
                        ->whereNotNull('fkidLot')
                        ->orderBy('dateMouvement', 'desc') // Restaurer dans l'ordre inverse (dernier d'abord)
                        ->get();
                    
                    $quantiteRestanteARestaurer = $quantiteARestaurer;
                    foreach ($mouvementsLot as $mouvement) {
                        if ($quantiteRestanteARestaurer <= 0) {
                            break;
                        }
                        
                        $lot = LotMedicament::find($mouvement->fkidLot);
                        if ($lot) {
                            $quantiteDuMouvement = abs($mouvement->quantite);
                            $quantiteARestaurerDansLot = min($quantiteDuMouvement, $quantiteRestanteARestaurer);
                            $lot->quantiteRestante += $quantiteARestaurerDansLot;
                            $lot->save();
                            $quantiteRestanteARestaurer -= $quantiteARestaurerDansLot;
                        }
                    }
                    
                    // Si toute la quantité n'a pas pu être restaurée dans les lots, elle est déjà dans le stock général
                    \Log::info('Stock restauré pour médicament', [
                        'medicament_id' => $medicamentId,
                        'quantite_restauree' => $quantiteARestaurer,
                        'quantite_restauree_dans_lots' => $quantiteARestaurer - $quantiteRestanteARestaurer,
                        'stock_apres_restauration' => $stock->quantiteStock
                    ]);
                }
            }
            
            // Supprimer tous les mouvements de stock liés
            MouvementStock::where('fkidFacture', $factureId)->delete();
            
            // 2. Supprimer les opérations de caisse liées
            $operationsCaisse = CaisseOperation::where('fkidfacturebord', $factureId)->get();
            $dateOperation = $operationsCaisse->first() ? $operationsCaisse->first()->dateoper : null;
            CaisseOperation::where('fkidfacturebord', $factureId)->delete();
            
            // 3. Supprimer les détails de facture
            Detailfacturepatient::where('fkidfacture', $factureId)->delete();
            
            // 4. Supprimer la facture elle-même
            $factureNumero = $facture->Nfacture;
            $facture->delete();
            
            DB::commit();
            
            // Invalider le cache des factures
            if ($this->selectedPatient) {
                $patientId = is_array($this->selectedPatient) ? ($this->selectedPatient['ID'] ?? null) : ($this->selectedPatient->ID ?? null);
                if ($patientId) {
                    // Invalider tous les caches de factures pour ce patient
                    for ($page = 1; $page <= 10; $page++) {
                        Cache::forget('factures_patient_' . $patientId . '_page_' . $page);
                    }
                    Cache::forget('factures_en_attente_patient_' . $patientId);
                }
            }
            
            // Invalider le cache des opérations de caisse pour mettre à jour la vue "caisse paie"
            $cabinetId = Auth::user()->fkidcabinet ?? 1;
            if ($dateOperation) {
                $dateOperationStr = Carbon::parse($dateOperation)->toDateString();
                // Invalider le cache pour toutes les combinaisons possibles (médecin, date)
                $medecins = Medecin::where('fkidCabinet', $cabinetId)->pluck('idMedecin');
                foreach ($medecins as $medecinId) {
                    Cache::forget('caisse_operations_' . $cabinetId . '_m' . $medecinId . '_d' . $dateOperationStr);
                    Cache::forget('caisse_operations_' . $cabinetId . '_m' . $medecinId . '_d' . $dateOperationStr . '_f' . $dateOperationStr);
                }
                // Invalider aussi pour le cabinet sans filtre médecin
                Cache::forget('caisse_operations_' . $cabinetId . '_d' . $dateOperationStr);
                Cache::forget('caisse_operations_' . $cabinetId . '_d' . $dateOperationStr . '_f' . $dateOperationStr);
            }
            // Invalider aussi le cache général (sans date spécifique)
            Cache::forget('caisse_operations_' . $cabinetId);
            
            // Émettre un événement Livewire pour rafraîchir le composant CaisseOperationsManager
            $this->dispatch('caisseOperationsUpdated');
            
            // Réinitialiser les factures pour recharger la liste
            $this->factures = null;
            $this->facturesEnAttente = null;
            $this->factureSelectionnee = null;
            
            // Forcer le rechargement des factures
            $this->factures = $this->getFacturesProperty();
            
            session()->flash('message', 'Facture N°' . $factureNumero . ' supprimée avec succès. Le stock a été restauré et les montants ont été retirés de la caisse.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la suppression de la facture', [
                'facture_id' => $factureId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Erreur lors de la suppression de la facture : ' . $e->getMessage());
        }
    }

    public function render()
    {
        $user = Auth::user();

        // Récupérer selectedPatient depuis la DB si perdu entre les requêtes Livewire
        if (!$this->selectedPatient && $this->selectedPatientId) {
            $patient = Patient::find($this->selectedPatientId);
            if ($patient) {
                $this->selectedPatient = $patient->toArray();
            }
        }

        // Toujours recharger les factures à chaque render (protected non sérialisé par Livewire)
        $factures = null;
        if ($this->selectedPatient) {
            $this->factures = $this->getFacturesProperty();
            $factures = $this->factures;
        }

        return view('livewire.reglement-facture', [
            'canDeleteFacture'        => $user->hasPermission('facture.delete'),
            'canEditFacture'          => $user->hasPermission('facture.edit'),
            'facturesEnAttente'       => $this->facturesEnAttente ?? collect(),
            'factures'                => $factures,
        ]);
    }
} 