<?php

namespace App\Livewire;

use App\Enums\Role;
use App\Models\User;
use App\Services\EquipeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Équipe et cabinet')]
class Equipe extends Component
{
    public array $compte = ['id' => null, 'name' => '', 'email' => '', 'role' => 'dentiste', 'password' => ''];

    public array $cabinet = [];

    public function mount(): void
    {
        $this->authorize('user.manage');
        $c = auth()->user()->cabinet;
        $this->cabinet = [
            'nom' => $c->nom,
            'telephone' => (string) $c->telephone,
            'adresse' => (string) $c->adresse,
            'duree_rdv_minutes' => (string) $c->duree_rdv_minutes,
            'heure_ouverture' => $c->heure_ouverture ?: '08:30',
            'heure_fermeture' => $c->heure_fermeture ?: '18:00',
            'jours_ouverture' => array_map('strval', $c->jours_ouverture ?? \App\Models\Cabinet::JOURS_DEFAUT),
        ];
    }

    public function modifier(int $id): void
    {
        $u = $this->membre($id);
        $this->resetValidation();
        $this->compte = ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role->value, 'password' => ''];
    }

    public function nouveau(): void
    {
        $this->resetValidation();
        $this->reset('compte');
    }

    public function enregistrer(EquipeService $service): void
    {
        $donnees = collect($this->compte)->only(['name', 'email', 'role', 'password'])->all();
        $ok = $this->executer(fn () => $this->compte['id']
            ? $service->modifier(auth()->user(), $this->membre($this->compte['id']), $donnees)
            : $service->creer(auth()->user(), $donnees), 'compte.');
        if ($ok) {
            $this->dispatch('toast', message: $this->compte['id'] ? 'Compte mis à jour' : 'Compte créé', type: 'success');
            $this->reset('compte');
        }
    }

    public function basculerActif(int $id, EquipeService $service): void
    {
        if ($u = $this->executer(fn () => $service->basculerActif(auth()->user(), $this->membre($id)))) {
            $this->dispatch('toast', message: $u->actif ? 'Compte réactivé' : 'Compte désactivé', type: 'success');
        }
    }

    public function enregistrerCabinet(EquipeService $service): void
    {
        if ($this->executer(fn () => $service->modifierCabinet(auth()->user(), $this->cabinet), 'cabinet.')) {
            $this->dispatch('toast', message: 'Cabinet mis à jour', type: 'success');
        }
    }

    private function membre(int $id): User
    {
        return User::where('cabinet_id', auth()->user()->cabinet_id)->findOrFail($id);
    }

    private function executer(callable $action, string $prefixe = ''): mixed
    {
        try {
            return $action();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $champ => $messages) {
                $this->addError($prefixe.$champ, $messages[0]);
            }
            $this->dispatch('toast', message: collect($e->errors())->flatten()->first(), type: 'error');
        } catch (AuthorizationException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }

        return null;
    }

    public function render()
    {
        return view('livewire.equipe', [
            'membres' => User::where('cabinet_id', auth()->user()->cabinet_id)->orderByDesc('actif')->orderBy('name')->get(),
            'roles' => Role::cases(),
        ]);
    }
}
