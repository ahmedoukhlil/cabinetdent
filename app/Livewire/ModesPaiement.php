<?php

namespace App\Livewire;

use App\Models\ModePaiement;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Modes de paiement')]
class ModesPaiement extends Component
{
    public array $form = ['id' => null, 'libelle' => '', 'actif' => true];

    public function mount(): void
    {
        $this->authorize('tarifs.manage');
    }

    public function nouveau(): void
    {
        $this->resetValidation();
        $this->reset('form');
    }

    public function modifier(int $id): void
    {
        $mode = ModePaiement::findOrFail($id);
        $this->resetValidation();
        $this->form = ['id' => $mode->id, 'libelle' => $mode->libelle, 'actif' => $mode->actif];
    }

    public function enregistrer(): void
    {
        $this->authorize('tarifs.manage');
        $cabinetId = auth()->user()->cabinet_id;
        $this->form['libelle'] = trim($this->form['libelle']);

        $this->validate([
            'form.libelle' => ['required', 'string', 'max:50', function ($attribut, $valeur, $echec) {
                // Comparaison sans tenir compte de la casse, quelle que soit la base de données.
                $doublon = ModePaiement::whereRaw('LOWER(libelle) = ?', [mb_strtolower($valeur)])
                    ->when($this->form['id'], fn ($q) => $q->whereKeyNot($this->form['id']))->exists();
                if ($doublon) {
                    $echec('Ce mode de paiement existe déjà.');
                }
            }],
            'form.actif' => ['boolean'],
        ], [], ['form.libelle' => 'libellé']);

        if ($this->form['id']) {
            $mode = ModePaiement::findOrFail($this->form['id']);
            if ($mode->actif && !$this->form['actif'] && $this->dernierActif($mode)) {
                $this->addError('form.actif', 'Gardez au moins un mode de paiement actif.');

                return;
            }
            // Le code ne change pas : les paiements déjà enregistrés gardent leur mode.
            $mode->update(['libelle' => $this->form['libelle'], 'actif' => (bool) $this->form['actif']]);
            $message = 'Mode de paiement mis à jour';
        } else {
            ModePaiement::create([
                'code' => ModePaiement::codePour($this->form['libelle'], $cabinetId),
                'libelle' => $this->form['libelle'],
                'actif' => (bool) $this->form['actif'],
                'ordre' => (int) ModePaiement::max('ordre') + 1,
            ]);
            $message = 'Mode de paiement ajouté';
        }

        $this->reset('form');
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function basculer(int $id): void
    {
        $this->authorize('tarifs.manage');
        $mode = ModePaiement::findOrFail($id);
        if ($mode->actif && $this->dernierActif($mode)) {
            $this->dispatch('toast', message: 'Gardez au moins un mode de paiement actif.', type: 'error');

            return;
        }
        $mode->update(['actif' => !$mode->actif]);
    }

    /** Monte (-1) ou descend (+1) un mode dans l'ordre d'affichage des listes. */
    public function deplacer(int $id, int $sens): void
    {
        $this->authorize('tarifs.manage');
        $modes = ModePaiement::orderBy('ordre')->orderBy('libelle')->get()->values();
        $i = $modes->search(fn ($m) => $m->id === $id);
        $j = $i === false ? false : $i + ($sens < 0 ? -1 : 1);
        if ($j === false || !isset($modes[$j])) {
            return;
        }
        [$modes[$i], $modes[$j]] = [$modes[$j], $modes[$i]];
        foreach ($modes as $rang => $m) {
            if ($m->ordre !== $rang + 1) {
                $m->update(['ordre' => $rang + 1]);
            }
        }
    }

    public function supprimer(int $id): void
    {
        $this->authorize('tarifs.manage');
        $mode = ModePaiement::findOrFail($id);
        if ($mode->utilisations() > 0) {
            $this->dispatch('toast', message: "« {$mode->libelle} » est déjà utilisé : désactivez-le plutôt.", type: 'error');

            return;
        }
        if ($mode->actif && $this->dernierActif($mode)) {
            $this->dispatch('toast', message: 'Gardez au moins un mode de paiement actif.', type: 'error');

            return;
        }
        $mode->delete();
        if ($this->form['id'] === $id) {
            $this->reset('form');
        }
        $this->dispatch('toast', message: 'Mode de paiement supprimé', type: 'success');
    }

    private function dernierActif(ModePaiement $mode): bool
    {
        return !ModePaiement::where('actif', true)->whereKeyNot($mode->id)->exists();
    }

    public function render()
    {
        $modes = ModePaiement::orderBy('ordre')->orderBy('libelle')->get();
        $utilisations = $modes->mapWithKeys(fn ($m) => [$m->id => $m->utilisations()]);

        return view('livewire.modes-paiement', compact('modes', 'utilisations'));
    }
}
