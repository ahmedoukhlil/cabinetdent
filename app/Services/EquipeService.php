<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Comptes du cabinet. Un compte n'est jamais supprimé (il reste l'auteur de
 * rendez-vous, soins et paiements) : il est désactivé.
 */
class EquipeService
{
    public function creer(User $admin, array $donnees): User
    {
        $this->exiger($admin, 'user.manage');
        $d = Validator::make($donnees, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['required', 'string', Password::min(8)],
        ], [], ['name' => 'nom', 'password' => 'mot de passe', 'role' => 'rôle'])->validate();

        AbonnementService::verifierQuotaUtilisateurs($admin->cabinet);

        return User::create([...$d, 'cabinet_id' => $admin->cabinet_id, 'actif' => true]);
    }

    public function modifier(User $admin, User $user, array $donnees): User
    {
        $this->exiger($admin, 'user.manage');
        $this->memeCabinet($admin, $user);
        $d = Validator::make($donnees, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['nullable', 'string', Password::min(8)],
        ], [], ['name' => 'nom', 'password' => 'mot de passe', 'role' => 'rôle'])->validate();

        $role = Role::from($d['role']);
        if ($user->is($admin) && $role !== $user->role) {
            throw ValidationException::withMessages(['role' => 'Vous ne pouvez pas changer votre propre rôle.']);
        }
        if ($user->role === Role::Proprietaire && $role !== Role::Proprietaire) {
            $this->garderUnProprietaire($user);
        }

        $user->fill(['name' => $d['name'], 'email' => $d['email'], 'role' => $role]);
        if (!empty($d['password'])) {
            $user->password = $d['password'];
        }
        $user->save();

        return $user;
    }

    public function basculerActif(User $admin, User $user): User
    {
        $this->exiger($admin, 'user.manage');
        $this->memeCabinet($admin, $user);
        if ($user->is($admin)) {
            throw ValidationException::withMessages(['actif' => 'Vous ne pouvez pas désactiver votre propre compte.']);
        }
        if ($user->actif && $user->role === Role::Proprietaire) {
            $this->garderUnProprietaire($user);
        }
        if (!$user->actif) {
            AbonnementService::verifierQuotaUtilisateurs($admin->cabinet);
        }
        $user->update(['actif' => !$user->actif]);

        return $user;
    }

    public function modifierCabinet(User $admin, array $donnees): Cabinet
    {
        $this->exiger($admin, 'cabinet.manage');
        $d = Validator::make($donnees, [
            'nom' => ['required', 'string', 'max:150'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'duree_rdv_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'heure_ouverture' => ['required', 'date_format:H:i'],
            'heure_fermeture' => ['required', 'date_format:H:i', 'after:heure_ouverture'],
            'jours_ouverture' => ['required', 'array', 'min:1'],
            'jours_ouverture.*' => ['integer', 'between:1,7'],
        ], [
            'jours_ouverture.required' => 'Cochez au moins un jour d\'ouverture.',
            'jours_ouverture.min' => 'Cochez au moins un jour d\'ouverture.',
            'heure_fermeture.after' => 'La fermeture doit suivre l\'ouverture.',
        ], ['duree_rdv_minutes' => 'durée d\'un rendez-vous'])->validate();
        $d['jours_ouverture'] = collect($d['jours_ouverture'])->map(fn ($j) => (int) $j)->unique()->sort()->values()->all();

        $cabinet = $admin->cabinet;
        $cabinet->update($d);

        return $cabinet;
    }

    /** Le cabinet doit toujours garder au moins un propriétaire actif. */
    private function garderUnProprietaire(User $sauf): void
    {
        $autres = User::where('cabinet_id', $sauf->cabinet_id)
            ->where('role', Role::Proprietaire->value)
            ->where('actif', true)
            ->whereKeyNot($sauf->id)
            ->exists();
        if (!$autres) {
            throw ValidationException::withMessages(['role' => 'Le cabinet doit garder au moins un propriétaire actif.']);
        }
    }

    private function memeCabinet(User $admin, User $user): void
    {
        if ($user->cabinet_id !== $admin->cabinet_id) {
            throw new AuthorizationException('Utilisateur introuvable.');
        }
    }

    private function exiger(User $user, string $permission): void
    {
        if (!$user->aPermission($permission)) {
            throw new AuthorizationException('Action non autorisée.');
        }
    }
}
