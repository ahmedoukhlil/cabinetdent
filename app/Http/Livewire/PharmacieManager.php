<?php

namespace App\Http\Livewire;
use App\Traits\HasLazyLoadingPlaceholder;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\StockMedicament;
use App\Models\Medicament;
use App\Models\LotMedicament;
use App\Models\MouvementStock;
use App\Models\Patient;
use App\Models\Facture;
use App\Models\Detailfacturepatient;
use App\Models\Medecin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PharmacieManager extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithPagination;

    // Onglet actif
    public $activeTab = 'dashboard';

    // Propriétés pour le stock
    public $searchStock = '';
    public $filterStock = 'tous'; // tous, faible, expires, expire_bientot
    public $selectedStock = null;

    // Propriétés pour l'entrée de stock
    public $showEntreeModal = false;
    public $entreeMedicamentId = null;
    public $entreeLibelleMedic = '';
    public $entreeQuantite = 1;
    public $entreePrixAchat = 0;
    public $entreeQuantiteMin = 0; // Seuil minimum
    public $entreeNumeroLot = '';
    public $entreeDateExpiration = null;
    public $entreeFournisseur = '';
    public $entreeReferenceFacture = '';
    public $entreeNotes = '';
    
    // Propriétés pour l'autocomplete de médicament
    public $entreeSearchMedicament = '';
    public $entreeMedicamentsResults = [];
    public $entreeShowMedicamentResults = false;
    public $entreeIsSearchingMedicament = false;

    // Patient sélectionné depuis le composant parent
    public $patientId = null;

    // Propriétés pour la vente
    public $panierVente = []; // Tableau des médicaments sélectionnés pour la vente
    public $factureVenteId = null; // ID de la facture créée pour la vente
    public $showFactureModal = false; // Modal pour voir/créer la facture
    public $quantiteVente = []; // Quantités par médicament pour l'input

    // Propriétés pour l'historique
    public $searchHistorique = '';
    public $filterTypeMouvement = '';
    public $filterDateDebut = '';
    public $filterDateFin = '';

    // Alertes
    public $alertesStockFaible = 0;
    public $alertesExpires = 0;
    public $alertesExpireBientot = 0;
    
    // Modals pour les détails
    public $showDetailModal = false;
    public $detailType = ''; // 'total', 'valeur', 'quantite', 'rupture', 'faible', 'expires', 'expire_bientot', 'entrees', 'sorties'
    public $detailData = [];
    public $detailPage = 1;
    public $detailPerPage = 20;
    public $detailSearch = '';

    // Modal ajustement de stock (inventaire)
    public $showAjustementModal = false;
    public $ajustementMedicamentId = null;
    public $ajustementLibelle = '';
    public $ajustementQuantiteActuelle = 0;
    public $ajustementNouvelleQuantite = 0;
    public $ajustementValeurActuelle = 0; // Valeur réelle basée sur les lots
    public $ajustementPrixNouveauStock = 0; // Prix d'achat pour quantité ajoutée (ajustement positif)
    public $ajustementMotif = '';
    public $ajustementLots = []; // Détail des lots pour affichage

    protected $paginationTheme = 'bootstrap';
    
    protected $listeners = [
        'patientUpdated' => 'updatePatient'
    ];

    public function mount($patientId = null)
    {
        $this->patientId = $patientId;
        $this->calculerAlertes();
    }
    
    public function updatePatient($patientId)
    {
        $this->patientId = $patientId;
        
        // Si le modal de vente est ouvert, mettre à jour le patient
        if ($this->showVenteModal && $patientId) {
            $patient = Patient::find($patientId);
            if ($patient) {
                $this->ventePatientId = $patient->ID;
                $this->ventePatientNom = trim($patient->Nom . ' ' . $patient->Prenom);
            }
        }
    }

    public function updatingSearchStock()
    {
        $this->resetPage();
    }

    public function updatingFilterStock()
    {
        $this->resetPage();
    }

    // ========== ONGLET STOCK ACTUEL ==========

    public function getStocksProperty()
    {
        $query = StockMedicament::with(['medicament'])
            ->where('fkidCabinet', Auth::user()->fkidcabinet)
            ->where('Masquer', 0)
            // Filtrer uniquement les médicaments (fkidtype = 1)
            ->whereHas('medicament', function($q) {
                $q->where('fkidtype', 1);
            });

        // Filtre par recherche
        if ($this->searchStock) {
            $query->whereHas('medicament', function($q) {
                $q->where('LibelleMedic', 'like', '%' . $this->searchStock . '%')
                  ->where('fkidtype', 1); // S'assurer que ce sont des médicaments
            });
        }

        // Filtres de statut
        switch ($this->filterStock) {
            case 'faible':
                $query->whereColumn('quantiteStock', '<=', 'quantiteMin');
                break;
            case 'expires':
                $query->whereHas('lots', function($q) {
                    $q->whereNotNull('dateExpiration')
                      ->where('dateExpiration', '<', Carbon::now())
                      ->where('quantiteRestante', '>', 0);
                });
                break;
            case 'expire_bientot':
                $dateLimite = Carbon::now()->addDays(30);
                $query->whereHas('lots', function($q) use ($dateLimite) {
                    $q->whereNotNull('dateExpiration')
                      ->where('dateExpiration', '>=', Carbon::now())
                      ->where('dateExpiration', '<=', $dateLimite)
                      ->where('quantiteRestante', '>', 0);
                });
                break;
        }

        return $query->orderBy('quantiteStock', 'asc')->paginate(15);
    }

    // ========== ONGLET ENTRÉES DE STOCK ==========

    public function openEntreeModal()
    {
        $this->resetEntreeForm();
        $this->showEntreeModal = true;
    }

    public function openEntreeModalForMedicament($medicamentId)
    {
        $this->resetEntreeForm();
        $medicament = Medicament::find($medicamentId);
        if ($medicament) {
            $this->entreeMedicamentId = $medicament->IDMedic;
            $this->entreeLibelleMedic = $medicament->LibelleMedic;
            $this->entreeSearchMedicament = $medicament->LibelleMedic;
            $cabinetId = Auth::user()->fkidcabinet;
            $stock = StockMedicament::where('fkidMedicament', $medicamentId)
                ->where('fkidCabinet', $cabinetId)->first();
            if ($stock) {
                $this->entreeQuantiteMin = $stock->quantiteMin;
                $this->entreePrixAchat = $stock->prixAchat;
            }
        }
        $this->showEntreeModal = true;
    }

    public function closeEntreeModal()
    {
        $this->showEntreeModal = false;
        $this->resetEntreeForm();
    }

    public function openAjustementModal($medicamentId)
    {
        $medicament = Medicament::find($medicamentId);
        if (!$medicament) {
            session()->flash('error', 'Médicament introuvable.');
            return;
        }
        $cabinetId = Auth::user()->fkidcabinet;
        $stock = StockMedicament::where('fkidMedicament', $medicamentId)
            ->where('fkidCabinet', $cabinetId)->first();

        // Charger les lots actifs (FIFO : ordonnés par date d'entrée)
        $lots = [];
        $valeurActuelle = 0;
        if ($stock) {
            $lotsQuery = LotMedicament::where('fkidStock', $stock->idStock)
                ->where('quantiteRestante', '>', 0)
                ->where('Masquer', 0)
                ->orderBy('dateEntree', 'asc')
                ->get();
            foreach ($lotsQuery as $lot) {
                $lots[] = [
                    'id' => $lot->idLot,
                    'numeroLot' => $lot->numeroLot,
                    'quantite' => (float)$lot->quantiteRestante,
                    'prixAchat' => (float)$lot->prixAchatUnitaire,
                    'valeur' => (float)$lot->quantiteRestante * (float)$lot->prixAchatUnitaire,
                    'dateEntree' => $lot->dateEntree,
                ];
                $valeurActuelle += (float)$lot->quantiteRestante * (float)$lot->prixAchatUnitaire;
            }
        }

        $this->ajustementMedicamentId = $medicamentId;
        $this->ajustementLibelle = $medicament->LibelleMedic;
        $this->ajustementQuantiteActuelle = $stock ? (float)$stock->quantiteStock : 0;
        $this->ajustementNouvelleQuantite = $this->ajustementQuantiteActuelle;
        $this->ajustementValeurActuelle = $valeurActuelle;
        $this->ajustementPrixNouveauStock = $stock ? (float)$stock->prixAchat : 0;
        $this->ajustementLots = $lots;
        $this->ajustementMotif = '';
        $this->showAjustementModal = true;
    }

    public function closeAjustementModal()
    {
        $this->showAjustementModal = false;
        $this->ajustementMedicamentId = null;
        $this->ajustementLibelle = '';
        $this->ajustementQuantiteActuelle = 0;
        $this->ajustementNouvelleQuantite = 0;
        $this->ajustementValeurActuelle = 0;
        $this->ajustementPrixNouveauStock = 0;
        $this->ajustementLots = [];
        $this->ajustementMotif = '';
    }

    public function enregistrerAjustement()
    {
        $rules = [
            'ajustementNouvelleQuantite' => 'required|numeric|min:0',
            'ajustementMotif' => 'required|string|min:3',
        ];
        $messages = [
            'ajustementNouvelleQuantite.required' => 'La nouvelle quantité est requise.',
            'ajustementNouvelleQuantite.min' => 'La quantité ne peut pas être négative.',
            'ajustementMotif.required' => 'Le motif est obligatoire.',
            'ajustementMotif.min' => 'Le motif doit contenir au moins 3 caractères.',
        ];

        $ecart = (float)$this->ajustementNouvelleQuantite - (float)$this->ajustementQuantiteActuelle;
        if ($ecart > 0) {
            $rules['ajustementPrixNouveauStock'] = 'required|numeric|min:0';
            $messages['ajustementPrixNouveauStock.required'] = 'Le prix d\'achat du nouveau stock est requis.';
        }

        $this->validate($rules, $messages);

        $cabinetId = Auth::user()->fkidcabinet;
        $userId = Auth::id();

        $stock = StockMedicament::where('fkidMedicament', $this->ajustementMedicamentId)
            ->where('fkidCabinet', $cabinetId)->first();

        if (!$stock) {
            session()->flash('error', 'Stock introuvable pour ce médicament.');
            return;
        }

        $ancienneQuantite = (float)$stock->quantiteStock;
        $nouvelleQuantite = (float)$this->ajustementNouvelleQuantite;
        $ecart = $nouvelleQuantite - $ancienneQuantite;

        if ($ecart == 0) {
            session()->flash('error', 'Aucun changement de quantité.');
            return;
        }

        DB::transaction(function () use ($stock, $nouvelleQuantite, $ecart, $userId) {
            MouvementStock::create([
                'fkidStock'     => $stock->idStock,
                'fkidMedicament'=> $this->ajustementMedicamentId,
                'fkidLot'       => null,
                'typeMouvement' => 'AJUSTEMENT',
                'quantite'      => $ecart,
                'prixUnitaire'  => $stock->prixAchat,
                'montantTotal'  => $stock->prixAchat * abs($ecart),
                'motif'         => $this->ajustementMotif,
                'fkidUser'      => $userId,
                'dateMouvement' => Carbon::now(),
                'reference'     => null,
                'notes'         => 'Ajustement d\'inventaire',
            ]);

            $stock->update([
                'quantiteStock' => $nouvelleQuantite,
            ]);
        });

        session()->flash('message', 'Stock ajusté avec succès (' . ($ecart > 0 ? '+' : '') . number_format($ecart, 0) . ').');
        $this->closeAjustementModal();
        $this->calculerAlertes();

        // Rafraîchir le modal de détails si ouvert
        if ($this->showDetailModal) {
            $this->ouvrirDetailModal($this->detailType);
        }
    }

    public function resetEntreeForm()
    {
        $this->entreeMedicamentId = null;
        $this->entreeLibelleMedic = '';
        $this->entreeQuantite = 1;
        $this->entreePrixAchat = 0;
        $this->entreeQuantiteMin = 0;
        $this->entreeNumeroLot = '';
        $this->entreeDateExpiration = null;
        $this->entreeFournisseur = '';
        $this->entreeReferenceFacture = '';
        $this->entreeNotes = '';
        $this->entreeSearchMedicament = '';
        $this->entreeMedicamentsResults = [];
        $this->entreeShowMedicamentResults = false;
        $this->entreeIsSearchingMedicament = false;
    }

    public function updatedEntreeSearchMedicament()
    {
        $search = trim($this->entreeSearchMedicament);
        
        if (strlen($search) >= 1) {
            $this->searchEntreeMedicaments();
        } else {
            $this->entreeMedicamentsResults = [];
            $this->entreeShowMedicamentResults = false;
            $this->entreeMedicamentId = null;
            $this->entreeLibelleMedic = '';
        }
    }

    public function searchEntreeMedicaments()
    {
        $this->entreeIsSearchingMedicament = true;
        
        try {
            $search = trim($this->entreeSearchMedicament);
            
            if (empty($search)) {
                $this->entreeMedicamentsResults = [];
                $this->entreeShowMedicamentResults = false;
                $this->entreeIsSearchingMedicament = false;
                return;
            }
            
            $query = Medicament::where('fkidtype', 1); // Seulement les médicaments
            
            // Si la recherche est numérique, chercher aussi par ID
            if (is_numeric($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('LibelleMedic', 'like', '%' . $search . '%')
                      ->orWhere('IDMedic', '=', (int)$search);
                });
            } else {
                $query->where('LibelleMedic', 'like', '%' . $search . '%');
            }

            $this->entreeMedicamentsResults = $query
                ->select('IDMedic', 'LibelleMedic', 'PrixRef', 'fkidtype')
                ->orderBy('LibelleMedic')
                ->limit(20)
                ->get();

            $this->entreeShowMedicamentResults = true;
            
        } catch (\Exception $e) {
            \Log::error('Erreur recherche médicament', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Erreur lors de la recherche : ' . $e->getMessage());
            $this->entreeMedicamentsResults = [];
            $this->entreeShowMedicamentResults = false;
        } finally {
            $this->entreeIsSearchingMedicament = false;
        }
    }

    public function selectEntreeMedicament($medicamentId)
    {
        try {
            $medicament = Medicament::find($medicamentId);

            if ($medicament && $medicament->fkidtype == 1) {
                $this->entreeMedicamentId = $medicament->IDMedic;
                $this->entreeLibelleMedic = $medicament->LibelleMedic;
                $this->entreeSearchMedicament = $medicament->LibelleMedic;
                $this->entreeMedicamentsResults = [];
                $this->entreeShowMedicamentResults = false;
            } else {
                session()->flash('error', 'Médicament non trouvé ou invalide.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur est survenue lors de la sélection du médicament.');
        }
    }

    public function closeEntreeMedicamentResults()
    {
        $this->entreeShowMedicamentResults = false;
    }

    public function enregistrerEntree()
    {
        $this->validate([
            'entreeMedicamentId' => 'required|integer|exists:medicaments,IDMedic',
            'entreeQuantite' => 'required|integer|min:1',
            'entreePrixAchat' => 'required|numeric|min:0',
            'entreeQuantiteMin' => 'required|integer|min:0',
        ], [
            'entreeMedicamentId.required' => 'Veuillez sélectionner un médicament',
            'entreeQuantite.required' => 'La quantité est requise',
            'entreePrixAchat.required' => 'Le prix d\'achat est requis',
            'entreeQuantiteMin.required' => 'Le seuil minimum est requis',
        ]);

        DB::transaction(function () {
            $cabinetId = Auth::user()->fkidcabinet;
            $userId = Auth::id();

            // Récupérer ou créer le stock
            $stock = StockMedicament::firstOrCreate(
                [
                    'fkidMedicament' => $this->entreeMedicamentId,
                    'fkidCabinet' => $cabinetId
                ],
                [
                    'quantiteStock' => 0,
                    'quantiteMin' => $this->entreeQuantiteMin,
                    'prixAchat' => $this->entreePrixAchat,
                    'Masquer' => 0
                ]
            );

            // Créer le lot si date d'expiration renseignée
            $lotId = null;
            if ($this->entreeDateExpiration) {
                $lot = LotMedicament::create([
                    'fkidStock' => $stock->idStock,
                    'fkidMedicament' => $this->entreeMedicamentId,
                    'numeroLot' => $this->entreeNumeroLot ?: null,
                    'quantiteInitiale' => $this->entreeQuantite,
                    'quantiteRestante' => $this->entreeQuantite,
                    'dateExpiration' => $this->entreeDateExpiration,
                    'dateEntree' => Carbon::now(),
                    'prixAchatUnitaire' => $this->entreePrixAchat,
                    'fournisseur' => $this->entreeFournisseur ?: null,
                    'referenceFacture' => $this->entreeReferenceFacture ?: null,
                    'fkidUser' => $userId,
                    'Masquer' => 0
                ]);
                $lotId = $lot->idLot;
            }

            // Mettre à jour le stock
            $stock->update([
                'quantiteStock' => $stock->quantiteStock + $this->entreeQuantite,
                'prixAchat' => $this->entreePrixAchat,
                'quantiteMin' => $this->entreeQuantiteMin,
                'dateDerniereEntree' => Carbon::now()
            ]);

            // Créer le mouvement
            MouvementStock::create([
                'fkidStock' => $stock->idStock,
                'fkidMedicament' => $this->entreeMedicamentId,
                'fkidLot' => $lotId,
                'typeMouvement' => 'ENTREE',
                'quantite' => $this->entreeQuantite,
                'prixUnitaire' => $this->entreePrixAchat,
                'montantTotal' => $this->entreePrixAchat * $this->entreeQuantite,
                'motif' => 'Entrée de stock',
                'fkidUser' => $userId,
                'dateMouvement' => Carbon::now(),
                'reference' => $this->entreeReferenceFacture ?: null,
                'notes' => $this->entreeNotes ?: null
            ]);
        });

        session()->flash('message', 'Entrée de stock enregistrée avec succès.');
        $this->closeEntreeModal();
        $this->calculerAlertes();
    }

    // ========== ONGLET VENTE ==========

    public function ajouterAuPanierVente($medicamentId, $quantite = null)
    {
        if (!$this->patientId) {
            session()->flash('error', 'Veuillez sélectionner un patient pour effectuer une vente.');
            return;
        }

        // Utiliser la quantité depuis quantiteVente si disponible, sinon utiliser le paramètre ou 1
        $quantiteFinale = $quantite ?? ($this->quantiteVente[$medicamentId] ?? 1);
        
        if ($quantiteFinale <= 0) {
            session()->flash('error', 'La quantité doit être supérieure à 0.');
            return;
        }

        $stock = StockMedicament::where('fkidMedicament', $medicamentId)
            ->where('fkidCabinet', Auth::user()->fkidcabinet)
            ->first();

        if (!$stock) {
            session()->flash('error', 'Médicament non trouvé en stock.');
            return;
        }

        if ($stock->quantiteStock < $quantiteFinale) {
            session()->flash('error', 'Stock insuffisant. Stock disponible: ' . number_format($stock->quantiteStock, 0));
            return;
        }

        $medicament = Medicament::find($medicamentId);
        if (!$medicament) {
            session()->flash('error', 'Médicament non trouvé.');
            return;
        }

        // Vérifier que c'est bien un médicament (fkidtype = 1)
        if ($medicament->fkidtype != 1) {
            session()->flash('error', 'Seuls les médicaments peuvent être vendus depuis l\'onglet Pharmacie.');
            return;
        }

        // Prix de référence (toujours depuis la table medicaments)
        $prixRef = $medicament->PrixRef ?? 0;
        // Prix facturé (par défaut égal au prix de référence, mais peut être modifié)
        $prixFacture = $prixRef;

        // Vérifier si le médicament est déjà dans le panier
        $index = array_search($medicamentId, array_column($this->panierVente, 'medicamentId'));
        
        if ($index !== false) {
            $nouvelleQuantite = $this->panierVente[$index]['quantite'] + $quantiteFinale;
            if ($nouvelleQuantite > $stock->quantiteStock) {
                session()->flash('error', 'Quantité totale dépasse le stock disponible.');
                return;
            }
            $this->panierVente[$index]['quantite'] = $nouvelleQuantite;
            $this->panierVente[$index]['montant'] = $this->panierVente[$index]['quantite'] * $this->panierVente[$index]['prixFacture'];
        } else {
            $this->panierVente[] = [
                'medicamentId' => $medicamentId,
                'libelle' => $medicament->LibelleMedic,
                'quantite' => $quantiteFinale,
                'prixRef' => $prixRef,
                'prixFacture' => $prixFacture,
                'montant' => $prixFacture * $quantiteFinale,
                'stockDisponible' => $stock->quantiteStock
            ];
        }

        // Réinitialiser la quantité dans l'input
        $this->quantiteVente[$medicamentId] = 1;
        session()->flash('message', 'Médicament ajouté au panier.');
    }

    public function retirerDuPanierVente($index)
    {
        unset($this->panierVente[$index]);
        $this->panierVente = array_values($this->panierVente);
        session()->flash('message', 'Médicament retiré du panier.');
    }

    public function modifierQuantitePanier($index, $quantite)
    {
        if ($quantite <= 0) {
            $this->retirerDuPanierVente($index);
            return;
        }

        if (isset($this->panierVente[$index])) {
            if ($quantite > $this->panierVente[$index]['stockDisponible']) {
                session()->flash('error', 'La quantité ne peut pas dépasser le stock disponible.');
                return;
            }
            $this->panierVente[$index]['quantite'] = $quantite;
            $this->panierVente[$index]['montant'] = $this->panierVente[$index]['quantite'] * $this->panierVente[$index]['prixFacture'];
        }
    }

    public function modifierPrixFacturePanier($index, $prixFacture)
    {
        if (isset($this->panierVente[$index])) {
            if ($prixFacture < 0) {
                session()->flash('error', 'Le prix ne peut pas être négatif.');
                return;
            }
            $this->panierVente[$index]['prixFacture'] = $prixFacture;
            $this->panierVente[$index]['montant'] = $this->panierVente[$index]['quantite'] * $prixFacture;
        }
    }

    public function getTotalPanierProperty()
    {
        return array_sum(array_column($this->panierVente, 'montant'));
    }

    public function creerFacture()
    {
        if (empty($this->panierVente)) {
            session()->flash('error', 'Le panier est vide.');
            return;
        }

        if (!$this->patientId) {
            session()->flash('error', 'Veuillez sélectionner un patient.');
            return;
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $patient = Patient::find($this->patientId);
            
            if (!$patient) {
                throw new \Exception('Patient non trouvé.');
            }

            // Créer la facture vide - Utiliser la méthode centralisée pour générer un numéro unique
            $factureData = Facture::generateUniqueFactureNumber($user->fkidcabinet);
            $nfacture = $factureData['Nfacture'];
            $nordre = $factureData['nordre'];
            $annee = $factureData['anneeFacture'];

            $medecinId = $user->fkidmedecin ?? 1;
            $medecin = Medecin::find($medecinId);
            if (!$medecin) {
                $medecinId = 1; // Médecin par défaut
            }

            $facture = Facture::create([
                'Nfacture' => $nfacture,
                'anneeFacture' => $annee,
                'nordre' => $nordre,
                'DtFacture' => Carbon::now(),
                'IDPatient' => $this->patientId,
                'ISTP' => 0,
                'fkidEtsAssurance' => null,
                'TXPEC' => 0,
                'TotFacture' => 0,
                'TotalPEC' => 0,
                'TotalfactPatient' => 0,
                'TotReglPatient' => 0,
                'ReglementPEC' => 0,
                'ModeReglement' => null,
                'Areglepar' => null,
                'DtReglement' => null,
                'fkidbordfacture' => 0,
                'ispayerAssureur' => 0,
                'user' => $user->NomComplet ?? 'System',
                'estfacturer' => 1,
                'FkidMedecinInitiateur' => $medecinId,
                'PartLaboratoire' => 0,
                'MontantAffectation' => 0,
                'Type' => 'Facture',
                'fkidCabinet' => $user->fkidcabinet
            ]);

            $totalFacture = 0;

            // Vérifier le stock pour tous les médicaments avant de créer la facture
            // Utiliser la même logique que ReglementFacture pour la cohérence
            foreach ($this->panierVente as $item) {
                $medicament = Medicament::find($item['medicamentId']);
                if (!$medicament) {
                    throw new \Exception('Médicament non trouvé: ' . $item['libelle']);
                }

                // Vérifier que c'est bien un médicament (fkidtype = 1)
                if ($medicament->fkidtype != 1) {
                    throw new \Exception('Seuls les médicaments peuvent être facturés depuis l\'onglet Pharmacie.');
                }

                // Vérifier le stock disponible en utilisant la même logique centralisée
                $stock = StockMedicament::where('fkidMedicament', $item['medicamentId'])
                    ->where('fkidCabinet', $user->fkidcabinet)
                    ->first();

                if (!$stock) {
                    throw new \Exception('Le médicament "' . $item['libelle'] . '" n\'est pas en stock.');
                }

                // Calculer la quantité déjà facturée mais non payée pour ce médicament
                // Prendre en compte TOUTES les factures non payées (pas seulement celles du patient)
                // pour une meilleure gestion du stock global
                $quantiteDejaFacturee = Detailfacturepatient::join('facture', 'detailfacturepatient.fkidfacture', '=', 'facture.Idfacture')
                    ->where('detailfacturepatient.fkidmedicament', $item['medicamentId'])
                    ->where('detailfacturepatient.IsAct', 2)
                    ->whereRaw('(CASE WHEN facture.ISTP > 0 THEN facture.TotalfactPatient ELSE facture.TotFacture END) > (facture.TotReglPatient + COALESCE(facture.ReglementPEC, 0))')
                    ->sum('detailfacturepatient.Quantite');

                $stockDisponible = $stock->quantiteStock - $quantiteDejaFacturee;

                if ($stockDisponible < $item['quantite']) {
                    throw new \Exception('Stock insuffisant pour le médicament "' . $item['libelle'] . '". Stock disponible: ' . number_format($stockDisponible, 0) . ' (Stock total: ' . number_format($stock->quantiteStock, 0) . ', Déjà facturé non payé: ' . number_format($quantiteDejaFacturee, 0) . ')');
                }
            }

            // Si toutes les vérifications passent, créer la facture et ajouter les détails
            foreach ($this->panierVente as $item) {
                $medicament = Medicament::find($item['medicamentId']);
                if (!$medicament || $medicament->fkidtype != 1) {
                    continue; // Déjà vérifié plus haut
                }

                // IsAct = 2 pour les médicaments uniquement (fkidtype = 1, donc IsAct = 1 + 1 = 2)
                $isAct = 2; // Médicament uniquement
                $prixItem = ($item['prixFacture'] ?? $item['prixRef']) * $item['quantite'];
                $totalFacture += $prixItem;

                $detail = Detailfacturepatient::create([
                    'fkidfacture' => $facture->Idfacture,
                    'DtAjout' => Carbon::now(),
                    'Actes' => $item['libelle'],
                    'PrixRef' => $item['prixRef'] ?? $item['prixUnitaire'] ?? 0,
                    'PrixFacture' => $item['prixFacture'] ?? $item['prixRef'] ?? $item['prixUnitaire'] ?? 0,
                    'Quantite' => $item['quantite'],
                    'fkidmedicament' => $item['medicamentId'],
                    'IsAct' => $isAct, // Toujours 2 pour les médicaments
                    'fkidMedecin' => $medecinId,
                    'fkidcabinet' => $user->fkidcabinet,
                    'ActesArab' => 'NR',
                    'Dents' => 'Med',
                    'DTajout2' => Carbon::now(),
                    'user' => $user->NomComplet ?? 'System',
                    'DtActe' => Carbon::now()
                ]);
            }

            // Mettre à jour le total de la facture
            $facture->TotFacture = $totalFacture;
            $facture->TotalfactPatient = $totalFacture;
            $facture->save();

            $this->factureVenteId = $facture->Idfacture;
            $this->panierVente = []; // Vider le panier
            $this->showFactureModal = true;

            DB::commit();
            session()->flash('message', 'Facture créée avec succès. Le stock sera déduit lors du paiement complet de la facture.');
            $this->calculerAlertes();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Erreur lors de la création de la facture : ' . $e->getMessage());
        }
    }

    public function fermerFactureModal()
    {
        $this->showFactureModal = false;
        $this->factureVenteId = null;
        $this->panierVente = [];
    }

    // ========== ONGLET HISTORIQUE ==========

    public function getMouvementsProperty()
    {
        $query = MouvementStock::with(['medicament', 'user', 'patient', 'lot'])
            ->whereHas('stock', function($q) {
                $q->where('fkidCabinet', Auth::user()->fkidcabinet);
            });

        if ($this->searchHistorique) {
            $query->whereHas('medicament', function($q) {
                $q->where('LibelleMedic', 'like', '%' . $this->searchHistorique . '%');
            });
        }

        if ($this->filterTypeMouvement) {
            $query->where('typeMouvement', $this->filterTypeMouvement);
        }

        if ($this->filterDateDebut) {
            $query->where('dateMouvement', '>=', $this->filterDateDebut);
        }

        if ($this->filterDateFin) {
            $query->where('dateMouvement', '<=', $this->filterDateFin . ' 23:59:59');
        }

        return $query->orderBy('dateMouvement', 'desc')->paginate(20);
    }

    // ========== ALERTES ==========

    public function calculerAlertes()
    {
        $cabinetId = Auth::user()->fkidcabinet;

        $this->alertesStockFaible = StockMedicament::where('fkidCabinet', $cabinetId)
            ->whereColumn('quantiteStock', '<=', 'quantiteMin')
            ->where('Masquer', 0)
            ->whereHas('medicament', function($q) {
                $q->where('fkidtype', 1);
            })
            ->count();

        $this->alertesExpires = LotMedicament::whereHas('stock', function($q) use ($cabinetId) {
                $q->where('fkidCabinet', $cabinetId);
            })
            ->whereNotNull('dateExpiration')
            ->where('dateExpiration', '<', Carbon::now())
            ->where('quantiteRestante', '>', 0)
            ->where('Masquer', 0)
            ->count();

        $dateLimite = Carbon::now()->addDays(30);
        $this->alertesExpireBientot = LotMedicament::whereHas('stock', function($q) use ($cabinetId) {
                $q->where('fkidCabinet', $cabinetId);
            })
            ->whereNotNull('dateExpiration')
            ->where('dateExpiration', '>=', Carbon::now())
            ->where('dateExpiration', '<=', $dateLimite)
            ->where('quantiteRestante', '>', 0)
            ->where('Masquer', 0)
            ->count();
    }

    // ========== TABLEAU DE BORD ==========

    public function getStatistiquesDashboardProperty()
    {
        $cabinetId = Auth::user()->fkidcabinet;
        
        // Total des médicaments en stock
        $totalMedicaments = StockMedicament::where('fkidCabinet', $cabinetId)
            ->where('Masquer', 0)
            ->whereHas('medicament', function($q) {
                $q->where('fkidtype', 1);
            })
            ->count();

        // Médicaments en rupture de stock
        $medicamentsRupture = StockMedicament::where('fkidCabinet', $cabinetId)
            ->where('quantiteStock', '<=', 0)
            ->where('Masquer', 0)
            ->whereHas('medicament', function($q) {
                $q->where('fkidtype', 1);
            })
            ->count();

        // Médicaments avec stock faible
        $medicamentsStockFaible = StockMedicament::where('fkidCabinet', $cabinetId)
            ->whereColumn('quantiteStock', '<=', 'quantiteMin')
            ->where('quantiteStock', '>', 0)
            ->where('Masquer', 0)
            ->whereHas('medicament', function($q) {
                $q->where('fkidtype', 1);
            })
            ->count();

        // Valeur totale du stock (quantité * prix d'achat moyen)
        $valeurStock = StockMedicament::where('fkidCabinet', $cabinetId)
            ->where('Masquer', 0)
            ->whereHas('medicament', function($q) {
                $q->where('fkidtype', 1);
            })
            ->selectRaw('SUM(quantiteStock * prixAchat) as total')
            ->value('total') ?? 0;

        // Total des quantités en stock
        $totalQuantiteStock = StockMedicament::where('fkidCabinet', $cabinetId)
            ->where('Masquer', 0)
            ->whereHas('medicament', function($q) {
                $q->where('fkidtype', 1);
            })
            ->sum('quantiteStock');

        // Entrées ce mois
        $entreesCeMois = MouvementStock::whereHas('stock', function($q) use ($cabinetId) {
                $q->where('fkidCabinet', $cabinetId);
            })
            ->where('typeMouvement', 'ENTREE')
            ->whereMonth('dateMouvement', Carbon::now()->month)
            ->whereYear('dateMouvement', Carbon::now()->year)
            ->count();

        // Sorties ce mois
        $sortiesCeMois = MouvementStock::whereHas('stock', function($q) use ($cabinetId) {
                $q->where('fkidCabinet', $cabinetId);
            })
            ->where('typeMouvement', 'SORTIE')
            ->whereMonth('dateMouvement', Carbon::now()->month)
            ->whereYear('dateMouvement', Carbon::now()->year)
            ->count();

        // Lots expirés
        $lotsExpires = LotMedicament::whereHas('stock', function($q) use ($cabinetId) {
                $q->where('fkidCabinet', $cabinetId);
            })
            ->whereNotNull('dateExpiration')
            ->where('dateExpiration', '<', Carbon::now())
            ->where('quantiteRestante', '>', 0)
            ->where('Masquer', 0)
            ->count();

        // Lots expirant dans 30 jours
        $dateLimite = Carbon::now()->addDays(30);
        $lotsExpireBientot = LotMedicament::whereHas('stock', function($q) use ($cabinetId) {
                $q->where('fkidCabinet', $cabinetId);
            })
            ->whereNotNull('dateExpiration')
            ->where('dateExpiration', '>=', Carbon::now())
            ->where('dateExpiration', '<=', $dateLimite)
            ->where('quantiteRestante', '>', 0)
            ->where('Masquer', 0)
            ->count();

        // Bénéfice des ventes facturées et payées
        // Sorties liées à une facture encaissée (estfacturer=1 ou TotReglPatient > 0)
        $beneficeVentes = \DB::table('mouvements_stock as ms')
            ->join('lots_medicaments as lm', 'ms.fkidLot', '=', 'lm.idLot')
            ->join('stock_medicaments as sm', 'ms.fkidStock', '=', 'sm.idStock')
            ->join('facture as f', 'ms.fkidFacture', '=', 'f.Idfacture')
            ->where('sm.fkidCabinet', $cabinetId)
            ->where('ms.typeMouvement', 'SORTIE')
            ->whereNotNull('ms.fkidFacture')
            ->where(function($q) {
                $q->where('f.estfacturer', 1)
                  ->orWhere('f.TotReglPatient', '>', 0);
            })
            ->selectRaw('SUM((ms.prixUnitaire - lm.prixAchatUnitaire) * ABS(ms.quantite)) as benefice')
            ->value('benefice') ?? 0;

        return [
            'totalMedicaments' => $totalMedicaments,
            'medicamentsRupture' => $medicamentsRupture,
            'medicamentsStockFaible' => $medicamentsStockFaible,
            'valeurStock' => $valeurStock,
            'totalQuantiteStock' => $totalQuantiteStock,
            'entreesCeMois' => $entreesCeMois,
            'sortiesCeMois' => $sortiesCeMois,
            'lotsExpires' => $lotsExpires,
            'lotsExpireBientot' => $lotsExpireBientot,
            'beneficeVentes' => $beneficeVentes,
        ];
    }

    public function getMedicamentsProperty()
    {
        return Medicament::where('fkidtype', 1) // Uniquement les médicaments
            ->orderBy('LibelleMedic', 'asc')
            ->get();
    }

    // ========== MÉTHODES POUR LES DÉTAILS ==========
    
    public function ouvrirDetailModal($type)
    {
        $this->detailType = $type;
        $this->detailPage = 1;
        $cabinetId = Auth::user()->fkidcabinet;
        
        switch ($type) {
            case 'total':
                $this->detailData = StockMedicament::where('fkidCabinet', $cabinetId)
                    ->where('Masquer', 0)
                    ->whereHas('medicament', function($q) {
                        $q->where('fkidtype', 1);
                    })
                    ->with(['medicament', 'lots' => function($q) {
                        $q->where('quantiteRestante', '>', 0)->where('Masquer', 0);
                    }])
                    ->orderBy('quantiteStock', 'desc')
                    ->get()
                    ->map(function($stock) {
                        $valeur = $stock->lots->sum(function($l) {
                            return (float)$l->quantiteRestante * (float)$l->prixAchatUnitaire;
                        });
                        // Fallback si aucun lot (stock ancien sans lot) : utiliser prix moyen
                        if ($valeur == 0 && $stock->quantiteStock > 0) {
                            $valeur = $stock->quantiteStock * $stock->prixAchat;
                        }
                        return [
                            'medicament_id' => $stock->medicament->IDMedic ?? null,
                            'medicament' => $stock->medicament->LibelleMedic ?? 'N/A',
                            'quantite' => $stock->quantiteStock,
                            'seuil_min' => $stock->quantiteMin,
                            'prix_achat' => $stock->prixAchat,
                            'prix_vente' => $stock->prixVente,
                            'valeur' => $valeur,
                            'statut' => $stock->isStockFaible() ? 'faible' : ($stock->quantiteStock == 0 ? 'rupture' : 'ok')
                        ];
                    })
                    ->toArray();
                break;

            case 'valeur':
                $this->detailData = StockMedicament::where('fkidCabinet', $cabinetId)
                    ->where('Masquer', 0)
                    ->whereHas('medicament', function($q) {
                        $q->where('fkidtype', 1);
                    })
                    ->with('medicament')
                    ->get()
                    ->map(function($stock) {
                        return [
                            'medicament' => $stock->medicament->LibelleMedic ?? 'N/A',
                            'quantite' => $stock->quantiteStock,
                            'prix_achat' => $stock->prixAchat,
                            'valeur' => $stock->quantiteStock * $stock->prixAchat,
                        ];
                    })
                    ->sortByDesc('valeur')
                    ->values()
                    ->toArray();
                break;
                
            case 'quantite':
                $this->detailData = StockMedicament::where('fkidCabinet', $cabinetId)
                    ->where('Masquer', 0)
                    ->whereHas('medicament', function($q) {
                        $q->where('fkidtype', 1);
                    })
                    ->with('medicament')
                    ->orderBy('quantiteStock', 'desc')
                    ->get()
                    ->map(function($stock) {
                        return [
                            'medicament' => $stock->medicament->LibelleMedic ?? 'N/A',
                            'quantite' => $stock->quantiteStock,
                            'seuil_min' => $stock->quantiteMin,
                        ];
                    })
                    ->toArray();
                break;
                
            case 'rupture':
                $this->detailData = StockMedicament::where('fkidCabinet', $cabinetId)
                    ->where('quantiteStock', '<=', 0)
                    ->where('Masquer', 0)
                    ->whereHas('medicament', function($q) {
                        $q->where('fkidtype', 1);
                    })
                    ->with('medicament')
                    ->get()
                    ->map(function($stock) {
                        return [
                            'medicament_id' => $stock->medicament->IDMedic ?? null,
                            'medicament' => $stock->medicament->LibelleMedic ?? 'N/A',
                            'quantite' => $stock->quantiteStock,
                            'seuil_min' => $stock->quantiteMin,
                            'prix_achat' => $stock->prixAchat,
                        ];
                    })
                    ->toArray();
                break;

            case 'faible':
                $this->detailData = StockMedicament::where('fkidCabinet', $cabinetId)
                    ->whereColumn('quantiteStock', '<=', 'quantiteMin')
                    ->where('quantiteStock', '>', 0)
                    ->where('Masquer', 0)
                    ->whereHas('medicament', function($q) {
                        $q->where('fkidtype', 1);
                    })
                    ->with('medicament')
                    ->orderBy('quantiteStock', 'asc')
                    ->get()
                    ->map(function($stock) {
                        return [
                            'medicament_id' => $stock->medicament->IDMedic ?? null,
                            'medicament' => $stock->medicament->LibelleMedic ?? 'N/A',
                            'quantite' => $stock->quantiteStock,
                            'seuil_min' => $stock->quantiteMin,
                            'difference' => $stock->quantiteMin - $stock->quantiteStock,
                            'prix_achat' => $stock->prixAchat,
                        ];
                    })
                    ->toArray();
                break;
                
            case 'expires':
                $this->detailData = LotMedicament::whereHas('stock', function($q) use ($cabinetId) {
                        $q->where('fkidCabinet', $cabinetId);
                    })
                    ->whereNotNull('dateExpiration')
                    ->where('dateExpiration', '<', Carbon::now())
                    ->where('quantiteRestante', '>', 0)
                    ->where('Masquer', 0)
                    ->with(['medicament', 'stock'])
                    ->orderBy('dateExpiration', 'asc')
                    ->get()
                    ->map(function($lot) {
                        return [
                            'medicament' => $lot->medicament->LibelleMedic ?? 'N/A',
                            'numero_lot' => $lot->numeroLot,
                            'quantite' => $lot->quantiteRestante,
                            'date_expiration' => $lot->dateExpiration ? Carbon::parse($lot->dateExpiration)->format('d/m/Y') : 'N/A',
                            'jours_expires' => $lot->dateExpiration ? Carbon::parse($lot->dateExpiration)->diffInDays(Carbon::now(), false) : null,
                        ];
                    })
                    ->toArray();
                break;
                
            case 'expire_bientot':
                $dateLimite = Carbon::now()->addDays(30);
                $this->detailData = LotMedicament::whereHas('stock', function($q) use ($cabinetId) {
                        $q->where('fkidCabinet', $cabinetId);
                    })
                    ->whereNotNull('dateExpiration')
                    ->where('dateExpiration', '>=', Carbon::now())
                    ->where('dateExpiration', '<=', $dateLimite)
                    ->where('quantiteRestante', '>', 0)
                    ->where('Masquer', 0)
                    ->with(['medicament', 'stock'])
                    ->orderBy('dateExpiration', 'asc')
                    ->get()
                    ->map(function($lot) {
                        return [
                            'medicament' => $lot->medicament->LibelleMedic ?? 'N/A',
                            'numero_lot' => $lot->numeroLot,
                            'quantite' => $lot->quantiteRestante,
                            'date_expiration' => $lot->dateExpiration ? Carbon::parse($lot->dateExpiration)->format('d/m/Y') : 'N/A',
                            'jours_restants' => $lot->dateExpiration ? Carbon::now()->diffInDays(Carbon::parse($lot->dateExpiration), false) : null,
                        ];
                    })
                    ->toArray();
                break;
                
            case 'entrees':
                $this->detailData = MouvementStock::whereHas('stock', function($q) use ($cabinetId) {
                        $q->where('fkidCabinet', $cabinetId);
                    })
                    ->where('typeMouvement', 'ENTREE')
                    ->whereMonth('dateMouvement', Carbon::now()->month)
                    ->whereYear('dateMouvement', Carbon::now()->year)
                    ->with(['medicament', 'user', 'lot'])
                    ->orderBy('dateMouvement', 'desc')
                    ->get()
                    ->map(function($mouvement) {
                        // Utiliser le prix d'achat du lot si disponible, sinon le prixUnitaire du mouvement
                        $prixAchat = $mouvement->lot ? ($mouvement->lot->prixAchatUnitaire ?? $mouvement->prixUnitaire) : $mouvement->prixUnitaire;
                        
                        return [
                            'medicament' => $mouvement->medicament->LibelleMedic ?? 'N/A',
                            'quantite' => $mouvement->quantite,
                            'prix_unitaire' => $prixAchat, // Prix d'achat
                            'montant' => $prixAchat * $mouvement->quantite, // Recalculer avec le prix d'achat
                            'date' => $mouvement->dateMouvement ? Carbon::parse($mouvement->dateMouvement)->format('d/m/Y H:i') : 'N/A',
                            'utilisateur' => $mouvement->user->NomComplet ?? 'N/A',
                            'reference' => $mouvement->reference,
                            'motif' => $mouvement->motif,
                        ];
                    })
                    ->toArray();
                break;
                
            case 'sorties':
                $this->detailData = MouvementStock::whereHas('stock', function($q) use ($cabinetId) {
                        $q->where('fkidCabinet', $cabinetId);
                    })
                    ->where('typeMouvement', 'SORTIE')
                    ->whereMonth('dateMouvement', Carbon::now()->month)
                    ->whereYear('dateMouvement', Carbon::now()->year)
                    ->where('quantite', '!=', 0) // Exclure les quantités nulles
                    ->with(['medicament', 'user', 'patient', 'facture', 'detailFacture'])
                    ->orderBy('dateMouvement', 'desc')
                    ->get()
                    ->map(function($mouvement) {
                        // Utiliser uniquement le PrixFacture du détail de facture
                        $prixFacture = $mouvement->detailFacture ? ($mouvement->detailFacture->PrixFacture ?? 0) : 0;
                        // S'assurer que la quantité est toujours positive pour les sorties
                        $quantite = abs($mouvement->quantite);
                        $montantVente = $prixFacture * $quantite;
                        
                        return [
                            'medicament' => $mouvement->medicament->LibelleMedic ?? 'N/A',
                            'quantite' => $quantite, // Quantité toujours positive
                            'prix_unitaire' => $prixFacture, // PrixFacture du détail de facture
                            'montant' => $montantVente, // Montant de vente
                            'date' => $mouvement->dateMouvement ? Carbon::parse($mouvement->dateMouvement)->format('d/m/Y H:i') : 'N/A',
                            'utilisateur' => $mouvement->user->NomComplet ?? 'N/A',
                            'patient' => $mouvement->patient ? trim($mouvement->patient->Nom . ' ' . $mouvement->patient->Prenom) : null,
                            'facture' => $mouvement->facture ? $mouvement->facture->Nfacture : null,
                            'motif' => $mouvement->motif,
                        ];
                    })
                    ->filter(function($item) {
                        // Filtrer les entrées avec quantité nulle (au cas où)
                        return $item['quantite'] > 0;
                    })
                    ->values()
                    ->toArray();
                break;
        }
        
        $this->showDetailModal = true;
    }
    
    public function fermerDetailModal()
    {
        $this->showDetailModal = false;
        $this->detailType = '';
        $this->detailData = [];
        $this->detailPage = 1;
        $this->detailSearch = '';
    }
    
    public function getDetailDataFilteredProperty()
    {
        if (empty($this->detailData)) {
            return [];
        }
        $search = trim($this->detailSearch);
        if ($search === '') {
            return $this->detailData;
        }
        $needle = mb_strtolower($search);
        return array_values(array_filter($this->detailData, function($item) use ($needle) {
            foreach (['medicament', 'patient', 'utilisateur', 'facture', 'numero_lot', 'reference'] as $key) {
                if (!empty($item[$key]) && mb_stripos((string)$item[$key], $needle) !== false) {
                    return true;
                }
            }
            return false;
        }));
    }

    public function getDetailDataPaginatedProperty()
    {
        $data = $this->detailDataFiltered;
        if (empty($data)) {
            return [];
        }
        $offset = ($this->detailPage - 1) * $this->detailPerPage;
        return array_slice($data, $offset, $this->detailPerPage);
    }

    public function getDetailTotalPagesProperty()
    {
        $data = $this->detailDataFiltered;
        if (empty($data)) {
            return 1;
        }
        return ceil(count($data) / $this->detailPerPage);
    }

    public function updatedDetailSearch()
    {
        $this->detailPage = 1;
    }
    
    public function previousDetailPage()
    {
        if ($this->detailPage > 1) {
            $this->detailPage--;
        }
    }
    
    public function nextDetailPage()
    {
        if ($this->detailPage < $this->detailTotalPages) {
            $this->detailPage++;
        }
    }
    
    public function goToDetailPage($page)
    {
        if ($page >= 1 && $page <= $this->detailTotalPages) {
            $this->detailPage = $page;
        }
    }

    public function render()
    {
        return view('livewire.pharmacie-manager', [
            'stocks' => $this->stocks,
            'mouvements' => $this->mouvements,
            'medicaments' => $this->medicaments
        ]);
    }
}
