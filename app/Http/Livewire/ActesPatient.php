<?php

namespace App\Http\Livewire;
use App\Traits\HasLazyLoadingPlaceholder;

use Livewire\Component;
use App\Models\Acte;
use App\Models\Facture;
use App\Models\DetailFacturePatient;
use App\Models\Patient;
use App\Models\Medecin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ActesPatient extends Component
{
    use HasLazyLoadingPlaceholder;

    public $patient = null;
    public $search_acte = '';
    public $actes = [];
    public $lignes = []; // [['acte_id'=>, 'acte_nom'=>, 'prix'=>, 'quantite'=>1]]
    public $total = 0;

    public function mount($patient = null)
    {
        $this->patient = $patient;
        $this->loadActes();
    }

    public function loadActes()
    {
        $query = Acte::where('Masquer', 0)
            ->where('Acte', 'not like', '%Consultation%')
            ->orderBy('nordre');
        if (trim($this->search_acte) !== '') {
            $query->where('Acte', 'like', '%' . $this->search_acte . '%');
        }
        $this->actes = $query->get()->toArray();
    }

    public function updatedSearchActe()
    {
        $this->loadActes();
    }

    public function ajouterLigne($acteId)
    {
        $acte = Acte::find($acteId);
        if (!$acte) return;

        foreach ($this->lignes as $i => $ligne) {
            if ($ligne['acte_id'] == $acteId) {
                $this->lignes[$i]['quantite']++;
                $this->calculerTotal();
                return;
            }
        }

        $this->lignes[] = [
            'acte_id'  => $acte->ID,
            'acte_nom' => $acte->Acte,
            'prix'     => $acte->PrixRef,
            'quantite' => 1,
        ];

        $this->calculerTotal();
    }

    public function supprimerLigne($index)
    {
        array_splice($this->lignes, $index, 1);
        $this->calculerTotal();
    }

    public function updatedLignes()
    {
        $this->calculerTotal();
    }

    public function calculerTotal()
    {
        $this->total = collect($this->lignes)->sum(fn($l) => floatval($l['prix']) * intval($l['quantite']));
    }

    public function save()
    {
        $this->validate([
            'lignes' => 'required|array|min:1',
        ], [
            'lignes.min' => 'Veuillez ajouter au moins un acte.',
        ]);

        $user      = Auth::user();
        $patientId = is_array($this->patient) ? $this->patient['ID'] : $this->patient->ID;
        $patient   = Patient::find($patientId);

        // Médecin connecté
        $medecinId = $user->fkidmedecin;
        $medecin   = Medecin::find($medecinId);

        try {
            DB::transaction(function () use ($patient, $patientId, $medecinId, $medecin, $user) {
                $this->calculerTotal();

                // Taux PEC depuis le patient
                $txpec = 0;
                $fkidEtsAssurance = null;
                if ($patient && $patient->Assureur) {
                    $fkidEtsAssurance = $patient->Assureur;
                    $txpec = floatval($patient->assureur->TauxdePEC ?? 0) / 100;
                }

                // Chercher la facture non encore totalement encaissée la plus récente du patient
                $facture = Facture::where('IDPatient', $patientId)
                    ->where('estfacturer', 0)
                    ->where('fkidCabinet', $user->fkidcabinet)
                    ->orderBy('DtFacture', 'desc')
                    ->first();

                if ($facture) {
                    // Les détails seront insérés ci-dessous, puis on recalcule depuis la DB

                    $messageSucces = 'Actes ajoutés à la facture existante avec succès.';
                } else {
                    // Créer une nouvelle facture (non encaissée)
                    $totalPEC     = $this->total * $txpec;
                    $totalPatient = $this->total * (1 - $txpec);

                    $factureData = Facture::generateUniqueFactureNumber($user->fkidcabinet);
                    $facture = Facture::create([
                        'Nfacture'              => $factureData['Nfacture'],
                        'anneeFacture'          => $factureData['anneeFacture'],
                        'nordre'                => $factureData['nordre'],
                        'DtFacture'             => Carbon::now(),
                        'IDPatient'             => $patientId,
                        'ISTP'                  => $txpec > 0 ? 1 : 0,
                        'fkidEtsAssurance'      => $fkidEtsAssurance,
                        'TXPEC'                 => $txpec,
                        'TotFacture'            => $this->total,
                        'TotalPEC'              => $totalPEC,
                        'TotalfactPatient'      => $totalPatient,
                        'ModeReglement'         => null,
                        'DtReglement'           => null,
                        'FkidMedecinInitiateur' => $medecinId,
                        'fkidCabinet'           => $user->fkidcabinet,
                        'ispayerAssureur'       => 0,
                        'user'                  => $user->NomComplet ?? $user->name,
                        'TotReglPatient'        => 0,
                        'ReglementPEC'          => 0,
                        'PartLaboratoire'       => 0,
                        'MontantAffectation'    => 0,
                        'Type'                  => 'Facture',
                        'estfacturer'           => 0,
                    ]);

                    $messageSucces = 'Actes prescrits et nouvelle facture créée avec succès.';
                }

                // Créer les détails de facture
                foreach ($this->lignes as $ligne) {
                    $prixLigne = floatval($ligne['prix']) * intval($ligne['quantite']);
                    DetailFacturePatient::create([
                        'fkidfacture'    => $facture->Idfacture,
                        'DtAjout'        => Carbon::now(),
                        'Actes'          => $ligne['acte_nom'],
                        'PrixFacture'    => floatval($ligne['prix']),
                        'PrixRef'        => floatval($ligne['prix']),
                        'Quantite'       => intval($ligne['quantite']),
                        'fkidMedecin'    => $medecinId,
                        'fkidacte'       => $ligne['acte_id'],
                        'IsAct'          => 1,
                        'fkidcabinet'    => $user->fkidcabinet,
                        'ActesArab'      => null,
                        'user'           => $user->NomComplet ?? $user->name,
                        'TauxPEC'        => $txpec,
                        'MontantPEC'     => $prixLigne * $txpec,
                        'MontantPatient' => $prixLigne * (1 - $txpec),
                        'Dents'          => 'Acte',
                    ]);
                }

                // Recalculer TotFacture depuis la somme réelle des détails
                $nouveauTotal = DB::table('detailfacturepatient')
                    ->where('fkidfacture', $facture->Idfacture)
                    ->selectRaw('SUM(PrixFacture * Quantite) as total')
                    ->value('total') ?? 0;

                $facture->update([
                    'TotFacture'       => $nouveauTotal,
                    'TotalPEC'         => $nouveauTotal * $txpec,
                    'TotalfactPatient' => $nouveauTotal * (1 - $txpec),
                ]);

                // Vider le cache factures pour forcer le rechargement dans ReglementFacture
                for ($page = 1; $page <= 5; $page++) {
                    Cache::forget('factures_patient_' . $patientId . '_page_' . $page);
                }
                $this->factures = null;

                $this->lignes      = [];
                $this->total       = 0;
                $this->search_acte = '';
                $this->loadActes();

                session()->flash('success', $messageSucces);
            });
        } catch (\Exception $e) {
            \Log::error('Erreur ActesPatient::save()', ['error' => $e->getMessage()]);
            $this->addError('general', 'Erreur : ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.actes-patient');
    }
}
