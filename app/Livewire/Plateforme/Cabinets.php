<?php

namespace App\Livewire\Plateforme;

use App\Enums\Role;
use App\Models\Abonnement;
use App\Models\Cabinet;
use App\Models\Paiement;
use App\Models\PlanAbonnement;
use App\Models\User;
use App\Services\AbonnementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.plateforme')]
#[Title('Cabinets')]
class Cabinets extends Component
{
    #[Url]
    public string $filtre = 'tous';

    #[Url]
    public ?int $cabinet = null;

    public bool $creation = false;

    public array $nouveau = ['nom' => '', 'telephone' => '', 'proprietaire' => '', 'email' => '', 'password' => '', 'plan_id' => '', 'essai_jours' => ''];

    public array $paiement = ['mois' => '1', 'montant' => '', 'mode' => 'virement', 'reference' => ''];

    public string $motif = '';

    public string $planId = '';

    public function mount(): void
    {
        $this->garde();
        $this->nouveau['essai_jours'] = (string) config('abonnement.essai_jours');
    }

    private function garde(): void
    {
        abort_unless(auth('plateforme')->check(), 403);
    }

    public function ouvrir(int $id): void
    {
        $this->cabinet = Cabinet::findOrFail($id)->id;
        $this->resetValidation();
        $this->paiement = ['mois' => '1', 'montant' => '', 'mode' => 'virement', 'reference' => ''];
        $this->motif = '';
        $this->planId = (string) ($this->ouvert()->abonnement?->plan_id ?? '');
    }

    private function ouvert(): Cabinet
    {
        return Cabinet::with('abonnement.plan')->findOrFail($this->cabinet);
    }

    /** Crée un cabinet, son dentiste propriétaire et un essai gratuit, en une seule transaction. */
    public function creer(AbonnementService $service): void
    {
        $this->garde();
        $d = Validator::make($this->nouveau, [
            'nom' => ['required', 'string', 'max:150'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'proprietaire' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(8)],
            'plan_id' => ['required', Rule::exists('plans_abonnement', 'id')->where('actif', true)],
            'essai_jours' => ['required', 'integer', 'min:0', 'max:90'],
        ], [], ['proprietaire' => 'nom du propriétaire', 'plan_id' => 'formule', 'password' => 'mot de passe'])->validate();

        $cabinet = DB::transaction(function () use ($d, $service) {
            $cabinet = Cabinet::create(['nom' => $d['nom'], 'telephone' => $d['telephone'] ?: null, 'actif' => true]);
            User::create(['cabinet_id' => $cabinet->id, 'name' => $d['proprietaire'], 'email' => $d['email'], 'password' => $d['password'], 'role' => Role::Proprietaire, 'actif' => true]);
            $service->demarrerEssai($cabinet, PlanAbonnement::findOrFail($d['plan_id']), (int) $d['essai_jours']);

            return $cabinet;
        });

        $this->creation = false;
        $this->reset('nouveau');
        $this->nouveau['essai_jours'] = (string) config('abonnement.essai_jours');
        $this->ouvrir($cabinet->id);
        $this->dispatch('toast', message: "Cabinet {$cabinet->nom} créé avec son essai", type: 'success');
    }

    public function enregistrerPaiement(AbonnementService $service): void
    {
        $this->garde();
        $this->executer(function () use ($service) {
            $abonnement = $this->ouvert()->abonnement ?? throw ValidationException::withMessages(['paiement.mois' => 'Aucun abonnement.']);
            $p = $service->enregistrerPaiement(auth('plateforme')->user(), $abonnement, [
                ...$this->paiement,
                'montant' => $this->paiement['montant'] === '' ? null : $this->paiement['montant'],
            ]);
            $this->paiement = ['mois' => '1', 'montant' => '', 'mode' => 'virement', 'reference' => ''];
            $this->dispatch('toast', message: 'Paiement enregistré · actif jusqu\'au '.$p->periode_fin->format('d/m/Y'), type: 'success');
        }, 'paiement.');
    }

    public function changerPlan(AbonnementService $service): void
    {
        $this->garde();
        $this->executer(function () use ($service) {
            $service->changerPlan($this->ouvert()->abonnement, PlanAbonnement::findOrFail($this->planId));
            $this->dispatch('toast', message: 'Formule modifiée', type: 'success');
        });
    }

    public function resilier(AbonnementService $service): void
    {
        $this->garde();
        $this->executer(function () use ($service) {
            $service->resilier($this->ouvert()->abonnement, $this->motif);
            $this->motif = '';
            $this->dispatch('toast', message: 'Abonnement résilié', type: 'success');
        });
    }

    public function nouvelAbonnement(AbonnementService $service): void
    {
        $this->garde();
        $this->executer(function () use ($service) {
            $service->demarrerEssai($this->ouvert(), PlanAbonnement::findOrFail($this->planId), 0);
            $this->dispatch('toast', message: 'Nouvel abonnement créé : enregistrez un paiement pour l\'activer', type: 'success');
        });
    }

    public function basculerActif(): void
    {
        $this->garde();
        $c = $this->ouvert();
        $c->update(['actif' => !$c->actif]);
        $this->dispatch('toast', message: $c->actif ? 'Cabinet réactivé' : 'Cabinet bloqué manuellement', type: 'success');
    }

    public function verifierEcheances(AbonnementService $service): void
    {
        $this->garde();
        $n = $service->verifierTous();
        $this->dispatch('toast', message: "{$n} abonnement(s) mis à jour", type: 'success');
    }

    private function executer(callable $action, string $prefixe = ''): void
    {
        try {
            $action();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $champ => $messages) {
                $this->addError($prefixe && !str_starts_with($champ, $prefixe) ? $prefixe.$champ : $champ, $messages[0]);
            }
            $this->dispatch('toast', message: collect($e->errors())->flatten()->first(), type: 'error');
        }
    }

    public function render()
    {
        $cabinets = Cabinet::with('abonnement.plan')->withCount(['users as utilisateurs_count' => fn ($q) => $q->where('actif', true)])->orderBy('nom')->get()
            ->when($this->filtre !== 'tous', fn ($c) => $c->filter(fn ($cab) => ($cab->abonnement?->statut ?? 'sans') === $this->filtre))
            ->values();

        $compteurs = Abonnement::query()
            ->whereIn('id', Abonnement::selectRaw('max(id)')->groupBy('cabinet_id'))
            ->selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut');

        $ouvert = $this->cabinet ? $this->ouvert() : null;

        return view('livewire.plateforme.cabinets', [
            'cabinets' => $cabinets,
            'compteurs' => $compteurs,
            'ouvert' => $ouvert,
            'historique' => $ouvert?->abonnement ? $ouvert->abonnement->paiements()->with('admin')->get() : collect(),
            'stockage' => $ouvert ? AbonnementService::stockageUtilise($ouvert) : 0,
            'proprietaires' => $ouvert ? User::where('cabinet_id', $ouvert->id)->where('role', Role::Proprietaire->value)->get() : collect(),
            'plans' => PlanAbonnement::where('actif', true)->orderBy('prix_mensuel')->get(),
            'modes' => Paiement::MODES,
            'statuts' => Abonnement::STATUTS,
        ]);
    }
}
