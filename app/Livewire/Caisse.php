<?php

namespace App\Livewire;

use App\Models\Depense;
use App\Models\Paiement;
use App\Services\DepenseService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Caisse')]
class Caisse extends Component
{
    #[Url]
    public string $du = '';

    #[Url]
    public string $au = '';

    #[Url]
    public ?int $dentiste = null;

    public array $depense = [];

    public ?int $suppression = null;

    public string $motif = '';

    public function mount(): void
    {
        $this->authorize('caisse.view');
        $this->du = $this->date($this->du);
        $this->au = $this->date($this->au);
        $this->depenseVide();
    }

    private function depenseVide(): void
    {
        $this->depense = ['date_depense' => today()->format('Y-m-d'), 'categorie' => 'fournitures', 'libelle' => '', 'montant' => '', 'mode' => 'especes', 'reference' => '', 'dentiste_id' => ''];
    }

    public function enregistrerDepense(DepenseService $service): void
    {
        try {
            $d = $service->enregistrer(auth()->user(), [...$this->depense, 'dentiste_id' => $this->depense['dentiste_id'] ?: null]);
            $this->resetValidation();
            $this->depenseVide();
            $this->dispatch('toast', message: 'Dépense de '.number_format($d->montant, 0, ',', ' ').' MRU enregistrée', type: 'success');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $champ => $messages) {
                $this->addError('depense.'.$champ, $messages[0]);
            }
        } catch (AuthorizationException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function supprimerDepense(DepenseService $service): void
    {
        try {
            $service->supprimer(auth()->user(), Depense::findOrFail($this->suppression), $this->motif);
            $this->reset('suppression', 'motif');
            $this->dispatch('toast', message: 'Dépense supprimée', type: 'success');
        } catch (ValidationException $e) {
            $this->addError('motif', collect($e->errors())->flatten()->first());
        } catch (AuthorizationException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    private function date(?string $valeur): string
    {
        try {
            return Carbon::createFromFormat('Y-m-d', (string) $valeur)->format('Y-m-d');
        } catch (\Throwable) {
            return today()->format('Y-m-d');
        }
    }

    public function updated($champ): void
    {
        if (in_array($champ, ['du', 'au'], true)) {
            $this->{$champ} = $this->date($this->{$champ});
        }
        if ($this->au < $this->du) {
            $this->au = $this->du;
        }
    }

    public function render()
    {
        $operations = Paiement::with(['patient', 'facture', 'auteur'])
            ->whereBetween('created_at', [Carbon::parse($this->du)->startOfDay(), Carbon::parse($this->au)->endOfDay()])
            ->when($this->dentiste, fn ($q) => $q->where('dentiste_id', $this->dentiste))
            ->latest('id')
            ->get();

        $depenses = Depense::with(['dentiste', 'auteur'])
            ->whereDate('date_depense', '>=', $this->du)->whereDate('date_depense', '<=', $this->au)
            ->when($this->dentiste, fn ($q) => $q->where('dentiste_id', $this->dentiste))
            ->latest('date_depense')->latest('id')
            ->get();

        $encaissements = $operations->where('type', 'encaissement');
        $remboursements = $operations->where('type', 'remboursement');

        return view('livewire.caisse', [
            'operations' => $operations,
            'totaux' => [
                'encaisse' => $encaissements->sum('montant'),
                'rembourse' => $remboursements->sum('montant'),
                'net' => $operations->sum('montant_signe'),
                'depenses' => $depenses->sum('montant'),
                'solde' => $operations->sum('montant_signe') - $depenses->sum('montant'),
            ],
            'parMode' => $operations->groupBy('mode')->map(fn ($ops) => $ops->sum('montant_signe'))->sortDesc(),
            'depenses' => $depenses,
            'categories' => Depense::CATEGORIES,
            'modes' => Paiement::MODES,
            'dentistes' => User::dentistesDu(auth()->user())->get(),
        ]);
    }
}
