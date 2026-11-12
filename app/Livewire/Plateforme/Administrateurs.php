<?php

namespace App\Livewire\Plateforme;

use App\Models\AdminPlateforme;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Comptes d'administration de la plateforme : création, modification, mot de passe, (dés)activation.
 * Tous les administrateurs ont les mêmes droits. On ne supprime pas un compte (les paiements
 * enregistrés y sont rattachés) : on le désactive, ce qui ferme aussi ses sessions ouvertes.
 */
#[Layout('components.layouts.plateforme')]
#[Title('Administrateurs')]
class Administrateurs extends Component
{
    public const MOT_DE_PASSE_MIN = 12;

    public array $form = ['id' => null, 'name' => '', 'email' => '', 'password' => ''];

    public bool $edition = false;

    public function mount(): void
    {
        $this->moi();
    }

    /** Administrateur connecté et actif, sinon 403 (couvre aussi les actions Livewire). */
    private function moi(): AdminPlateforme
    {
        $admin = auth('plateforme')->user();
        abort_unless($admin && $admin->actif, 403);

        return $admin;
    }

    public function nouveau(): void
    {
        $this->moi();
        $this->resetValidation();
        $this->reset('form');
        $this->edition = true;
    }

    public function modifier(int $id): void
    {
        $this->moi();
        $a = AdminPlateforme::findOrFail($id);
        $this->resetValidation();
        $this->form = ['id' => $a->id, 'name' => $a->name, 'email' => $a->email, 'password' => ''];
        $this->edition = true;
    }

    public function annuler(): void
    {
        $this->reset('form', 'edition');
        $this->resetValidation();
    }

    public function enregistrer(): void
    {
        $this->moi();
        $id = $this->form['id'];
        $this->form['email'] = mb_strtolower(trim($this->form['email']));
        $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('admins_plateforme', 'email')->ignore($id)],
            // Obligatoire à la création ; à la modification, vide = inchangé.
            'form.password' => [$id ? 'nullable' : 'required', 'string', 'min:'.self::MOT_DE_PASSE_MIN, 'max:255'],
        ], [], ['form.name' => 'nom', 'form.email' => 'e-mail', 'form.password' => 'mot de passe']);

        $a = $id ? AdminPlateforme::findOrFail($id) : new AdminPlateforme();
        $a->fill(['name' => trim($this->form['name']), 'email' => $this->form['email']]);
        if ($this->form['password'] !== '') {
            $a->password = $this->form['password'];
            $a->remember_token = null;
        }
        $a->save();

        $this->annuler();
        $this->dispatch('toast', message: $id ? 'Administrateur mis à jour' : 'Administrateur créé', type: 'success');
    }

    public function basculer(int $id): void
    {
        $moi = $this->moi();
        if ($id === $moi->id) {
            throw ValidationException::withMessages(['liste' => 'Vous ne pouvez pas désactiver votre propre compte.']);
        }

        DB::transaction(function () use ($id) {
            $a = AdminPlateforme::lockForUpdate()->findOrFail($id);
            if ($a->actif && AdminPlateforme::where('actif', true)->lockForUpdate()->count() <= 1) {
                throw ValidationException::withMessages(['liste' => 'Il doit rester au moins un administrateur actif.']);
            }
            $a->forceFill(['actif' => !$a->actif, 'remember_token' => null])->save();
            $this->dispatch('toast', message: $a->actif ? "{$a->name} réactivé" : "{$a->name} désactivé", type: 'success');
        });
    }

    public function render()
    {
        return view('livewire.plateforme.administrateurs', [
            'admins' => AdminPlateforme::orderByDesc('actif')->orderBy('name')->get(),
            'moiId' => auth('plateforme')->id(),
            'min' => self::MOT_DE_PASSE_MIN,
        ]);
    }
}
