<?php

namespace App\Livewire\Plateforme;

use App\Models\Abonnement;
use App\Models\PlanAbonnement;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.plateforme')]
#[Title('Formules')]
class Plans extends Component
{
    public array $plan = ['id' => null, 'nom' => '', 'prix_mensuel' => '', 'max_utilisateurs' => '', 'max_stockage_mo' => ''];

    public function mount(): void
    {
        abort_unless(auth('plateforme')->check(), 403);
    }

    public function modifier(int $id): void
    {
        $p = PlanAbonnement::findOrFail($id);
        $this->resetValidation();
        $this->plan = ['id' => $p->id, 'nom' => $p->nom, 'prix_mensuel' => (string) $p->prix_mensuel, 'max_utilisateurs' => (string) $p->max_utilisateurs, 'max_stockage_mo' => (string) $p->max_stockage_mo];
    }

    public function enregistrer(): void
    {
        abort_unless(auth('plateforme')->check(), 403);
        $this->validate([
            'plan.nom' => ['required', 'string', 'max:100', Rule::unique('plans_abonnement', 'nom')->ignore($this->plan['id'])],
            'plan.prix_mensuel' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'plan.max_utilisateurs' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'plan.max_stockage_mo' => ['nullable', 'integer', 'min:1', 'max:10000000'],
        ], [], ['plan.nom' => 'nom', 'plan.prix_mensuel' => 'prix']);

        $p = $this->plan['id'] ? PlanAbonnement::findOrFail($this->plan['id']) : new PlanAbonnement();
        $p->fill([
            'nom' => $this->plan['nom'],
            'prix_mensuel' => round((float) $this->plan['prix_mensuel'], 2),
            'max_utilisateurs' => $this->plan['max_utilisateurs'] === '' ? null : (int) $this->plan['max_utilisateurs'],
            'max_stockage_mo' => $this->plan['max_stockage_mo'] === '' ? null : (int) $this->plan['max_stockage_mo'],
        ])->save();

        $this->reset('plan');
        $this->dispatch('toast', message: 'Formule enregistrée', type: 'success');
    }

    public function basculer(int $id): void
    {
        abort_unless(auth('plateforme')->check(), 403);
        $p = PlanAbonnement::findOrFail($id);
        $p->update(['actif' => !$p->actif]);
    }

    public function render()
    {
        return view('livewire.plateforme.plans', [
            'plans' => PlanAbonnement::orderByDesc('actif')->orderBy('prix_mensuel')->get(),
            'utilisation' => Abonnement::whereNotIn('statut', ['resilie'])->selectRaw('plan_id, count(*) as n')->groupBy('plan_id')->pluck('n', 'plan_id'),
        ]);
    }
}
