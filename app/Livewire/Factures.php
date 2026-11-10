<?php

namespace App\Livewire;

use App\Models\Acte;
use App\Models\Facture;
use App\Models\FactureLigne;
use App\Models\Paiement;
use App\Models\Patient;
use App\Services\FacturationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Facturation')]
class Factures extends Component
{
    use WithPagination;

    #[Url]
    public ?int $facture = null;

    #[Url]
    public string $filtre = 'impayees';

    #[Url(as: 'q')]
    public string $recherche = '';

    public bool $creationOuverte = false;

    public string $recherchePatient = '';

    public array $ligne = ['acte_id' => '', 'libelle' => '', 'quantite' => 1, 'prix_unitaire' => ''];

    public array $paiement = ['payeur' => 'patient', 'type' => 'encaissement', 'mode' => 'especes', 'montant' => '', 'reference' => ''];

    public string $motif = '';

    public function mount(FacturationService $service): void
    {
        $this->authorize('facture.view');

        if ($patient = Patient::find(request()->integer('patient'))) {
            $this->facture = $this->executer(fn () => $service->brouillonPour(auth()->user(), $patient)->id) ?? $this->facture;
            $this->filtre = 'brouillons';
        }

        $this->preparerPaiement(choisirPayeur: true);
    }

    public function updatedFiltre(): void
    {
        $this->resetPage();
    }

    public function updatedRecherche(): void
    {
        $this->resetPage();
    }

    public function ouvrir(int $id): void
    {
        $this->facture = $id;
        $this->creationOuverte = false;
        $this->resetValidation();
        $this->preparerPaiement(choisirPayeur: true);
    }

    public function nouvelleFacture(): void
    {
        $this->authorize('facture.edit');
        $this->creationOuverte = true;
        $this->recherchePatient = '';
        $this->resetValidation();
    }

    public function creerPourPatient(int $patientId, FacturationService $service): void
    {
        $patient = Patient::where('actif', true)->findOrFail($patientId);
        $facture = $this->executer(fn () => $service->brouillonPour(auth()->user(), $patient));

        if (!$facture) {
            return;
        }

        $this->facture = $facture->id;
        $this->filtre = 'brouillons';
        $this->creationOuverte = false;
        $this->recherchePatient = '';
        $this->dispatch('toast', message: "Devis ouvert pour {$patient->nom_complet}", type: 'success');
    }

    public function updatedLigneActeId($valeur): void
    {
        $acte = $valeur ? Acte::find($valeur) : null;
        $this->ligne['libelle'] = $acte?->libelle ?? '';
        $this->ligne['prix_unitaire'] = $acte ? (string) $acte->prix : '';
    }

    public function updatedPaiementPayeur(): void
    {
        $this->preparerPaiement();
    }

    public function updatedPaiementType(): void
    {
        $this->preparerPaiement();
    }

    /** Propose le montant restant dû (ou déjà réglé pour un remboursement). */
    private function preparerPaiement(bool $choisirPayeur = false): void
    {
        $f = $this->facture ? Facture::find($this->facture) : null;
        if (!$f || $f->statut !== 'validee') {
            return;
        }
        if ($f->part_pec <= 0) {
            $this->paiement['payeur'] = 'patient';
        } elseif ($choisirPayeur) {
            $this->paiement['type'] = 'encaissement';
            $this->paiement['payeur'] = $f->restePatient() <= 0 && $f->restePec() > 0 ? 'assureur' : 'patient';
        }
        $assureur = $this->paiement['payeur'] === 'assureur';
        $valeur = $this->paiement['type'] === 'remboursement'
            ? ($assureur ? $f->regle_pec : $f->regle_patient)
            : ($assureur ? $f->restePec() : $f->restePatient());
        $this->paiement['montant'] = $valeur > 0 ? (string) round($valeur) : '';
    }

    public function ajouterLigne(FacturationService $service): void
    {
        $this->executer(function () use ($service) {
            $service->ajouterLigne(auth()->user(), $this->factureOuverte(), [
                'acte_id' => $this->ligne['acte_id'] ?: null,
                'libelle' => $this->ligne['libelle'],
                'quantite' => $this->ligne['quantite'],
                'prix_unitaire' => $this->ligne['prix_unitaire'] === '' ? null : $this->ligne['prix_unitaire'],
            ]);
            $this->ligne = ['acte_id' => '', 'libelle' => '', 'quantite' => 1, 'prix_unitaire' => ''];
        });
    }

    public function supprimerLigne(int $id, FacturationService $service): void
    {
        $this->executer(fn () => $service->supprimerLigne(auth()->user(), FactureLigne::where('facture_id', $this->factureOuverte()->id)->findOrFail($id)));
    }

    public function valider(FacturationService $service): void
    {
        $this->executer(function () use ($service) {
            $facture = $service->valider(auth()->user(), $this->factureOuverte());
            $this->filtre = 'impayees';
            $this->preparerPaiement(choisirPayeur: true);
            $this->dispatch('toast', message: "Facture {$facture->numero} validée", type: 'success');
        });
    }

    public function annuler(FacturationService $service): void
    {
        $this->executer(function () use ($service) {
            $facture = $this->factureOuverte();
            $brouillon = $facture->statut === 'brouillon';
            $service->annuler(auth()->user(), $facture, $this->motif);
            $this->motif = '';
            if ($brouillon) {
                $this->facture = null;
            }
            $this->dispatch('toast', message: $brouillon ? 'Devis supprimé' : 'Facture annulée', type: 'success');
        });
    }

    public function enregistrerPaiement(FacturationService $service): void
    {
        $this->executer(function () use ($service) {
            $p = $service->enregistrerPaiement(auth()->user(), $this->factureOuverte(), $this->paiement);
            $this->paiement['reference'] = '';
            $this->preparerPaiement(choisirPayeur: true);
            $this->dispatch('toast', message: ($p->type === 'remboursement' ? 'Remboursement de ' : 'Encaissement de ').number_format($p->montant, 0, ',', ' ').' MRU enregistré', type: 'success');
        });
    }

    private function factureOuverte(): Facture
    {
        return Facture::findOrFail($this->facture);
    }

    private function executer(callable $action): mixed
    {
        try {
            return $action();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $champ => $messages) {
                $this->addError($champ, $messages[0]);
            }
            $this->dispatch('toast', message: collect($e->errors())->flatten()->first(), type: 'error');
        } catch (AuthorizationException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }

        return null;
    }

    public function render()
    {
        $terme = trim($this->recherche);
        $factures = Facture::with('patient')
            ->when($this->filtre === 'impayees', fn ($q) => $q->impayees())
            ->when($this->filtre === 'brouillons', fn ($q) => $q->where('statut', 'brouillon'))
            ->when($this->filtre === 'validees', fn ($q) => $q->where('statut', 'validee'))
            ->when($this->filtre === 'annulees', fn ($q) => $q->where('statut', 'annulee'))
            ->when($terme !== '', fn ($q) => $q->where(fn ($q) => $q->where('numero', 'like', "%{$terme}%")
                ->orWhereHas('patient', fn ($p) => $p->recherche($terme))))
            ->latest('id')
            ->paginate(15);

        $ouverte = $this->facture
            ? Facture::with(['patient', 'assureur', 'dentiste', 'lignes', 'paiements.auteur'])->find($this->facture)
            : null;

        $termePatient = trim($this->recherchePatient);
        $patientsProposes = $this->creationOuverte && $termePatient !== ''
            ? Patient::where('actif', true)->recherche($termePatient)->orderBy('nom')->limit(8)->get()
            : collect();

        return view('livewire.factures', [
            'factures' => $factures,
            'ouverte' => $ouverte,
            'actes' => $ouverte?->statut === 'brouillon' ? Acte::with('tarifs')->where('actif', true)->orderBy('libelle')->get() : collect(),
            'modes' => Paiement::MODES,
            'user' => auth()->user(),
            'patientsProposes' => $patientsProposes,
        ]);
    }
}
