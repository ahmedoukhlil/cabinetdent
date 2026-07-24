<?php

namespace App\Http\Livewire;

use App\Traits\HasLazyLoadingPlaceholder;
use Livewire\Component;
use App\Models\TUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class UserManager extends Component
{
    use HasLazyLoadingPlaceholder;

    use WithPagination;

    // Propriétés pour le formulaire
    public $userId;
    public $login;
    public $password;
    public $nomComplet;
    public $role;
    public $fkidmedecin = '';
    public $isActive = true;
    public $isMasquer = false;

    // Propriétés pour la recherche et le tri
    public $search = '';
    public $selectedRole = '';
    public $perPage = 10;

    // Propriétés pour la modal
    public $showModal = false;
    public $showDeleteModal = false;
    public $showForceDeleteModal = false;
    public $userToDelete;

    // Onglet actif
    public $activeTab = 'users'; // 'users' | 'permissions' | 'roles'

    // Permissions RBAC
    public $permissionsTab = [];        // toutes les permissions groupées
    public $rolePermissions = [];       // [roleId => [permId => bool]]
    public $permEditRole = null;        // rôle en cours d'édition

    // Gestion des rôles
    public $rolesList = [];
    public $roleEditId = null;
    public $roleLibelle = '';
    public $showRoleDeleteConfirm = false;
    public $roleToDelete = null;

    protected $listeners = ['confirmDelete'];

    protected $rules = [
        'login' => 'required|min:3|unique:t_user,login',
        'password' => 'required|min:6',
        'nomComplet' => 'required|min:3',
        'role' => 'required|exists:typeuser,IdClasseUser0',
    ];

    protected $messages = [
        'login.required' => 'L\'identifiant est obligatoire.',
        'login.min' => 'L\'identifiant doit contenir au moins 3 caractères.',
        'login.unique' => 'Cet identifiant est déjà utilisé.',
        'password.required' => 'Le mot de passe est obligatoire.',
        'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        'nomComplet.required' => 'Le nom complet est obligatoire.',
        'nomComplet.min' => 'Le nom complet doit contenir au moins 3 caractères.',
        'role.required' => 'Le rôle est obligatoire.',
        'role.in' => 'Le rôle sélectionné est invalide.',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedRole()
    {
        $this->resetPage();
    }

    public function getRolesProperty()
    {
        return DB::table('typeuser')->orderBy('IdClasseUser0')->get()
            ->map(fn($r) => ['id' => (int)$r->IdClasseUser0, 'Role' => $r->Libelle])
            ->all();
    }

    public function render()
    {
        $query = TUser::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('NomComplet', 'like', '%' . $this->search . '%')
                  ->orWhere('login', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->selectedRole) {
            $query->where('IdClasseUser', $this->selectedRole);
        }

        $users = $query->orderBy('NomComplet')->paginate($this->perPage);

        // Charger permissions en direct (évite la sérialisation Livewire qui transforme stdClass en array)
        $allPerms = DB::table('permissions')->orderBy('groupe')->orderBy('ordre')->get();
        $permissionsTab = [];
        foreach ($allPerms as $p) {
            $permissionsTab[$p->groupe][] = $p;
        }

        $assigned = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->get(['role_permissions.role_id', 'permissions.id as perm_id']);
        $rolePermissions = [];
        foreach ($assigned as $row) {
            $rolePermissions[$row->role_id][$row->perm_id] = true;
        }

        return view('livewire.user-manager', [
            'users'          => $users,
            'roles'          => $this->getRolesProperty(),
            'rolesList'      => DB::table('typeuser')->orderBy('IdClasseUser0')->get(),
            'permissionsTab' => $permissionsTab,
            'rolePermissions'=> $rolePermissions,
        ]);
    }

    public function openModal($id = null)
    {
        $this->resetForm();
        if ($id) {
            $user = TUser::find($id);
            if ($user) {
                $this->userId = $user->Iduser;
                $this->login = $user->login;
                $this->nomComplet = $user->NomComplet;
                $this->role = $user->IdClasseUser;
                $this->fkidmedecin = $user->fkidmedecin ?? '';
                $this->isActive = !$user->ismasquer;
                $this->isMasquer = $user->ismasquer;
            }
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->userId = null;
        $this->login = '';
        $this->password = '';
        $this->nomComplet = '';
        $this->role = '';
        $this->fkidmedecin = '';
        $this->isActive = true;
        $this->isMasquer = false;
    }

    public function save()
    {
        if ($this->userId) {
            $this->rules['login'] = 'required|min:3|unique:t_user,login,' . $this->userId . ',Iduser';
            $this->rules['password'] = 'nullable|min:6';
        }

        $this->validate();

        try {
            $fkidmedecin = $this->fkidmedecin ?: 0;

            // Docteur Propriétaire unique par cabinet
            if ($this->role == 3) {
                $existingProp = DB::table('t_user')
                    ->where('IdClasseUser', 3)
                    ->where('fkidcabinet', Auth::user()->fkidcabinet)
                    ->where('ismasquer', 0)
                    ->when($this->userId, fn($q) => $q->where('Iduser', '!=', $this->userId))
                    ->exists();
                if ($existingProp) {
                    $this->dispatch('toast', ['message' => 'Un Docteur Propriétaire existe déjà pour ce cabinet.', 'type' => 'error']);
                    return;
                }
            }

            // Libellé du rôle pour la colonne "fonction"
            $roleLibelle = DB::table('typeuser')->where('IdClasseUser0', $this->role)->value('Libelle') ?? 'Utilisateur';

            $userData = [
                'login'        => $this->login,
                'NomComplet'   => $this->nomComplet,
                'IdClasseUser' => $this->role,
                'ismasquer'    => !$this->isActive,
                'fonction'     => $roleLibelle,
                'fkidmedecin'  => $fkidmedecin,
                'fkidcabinet'  => Auth::user()->fkidcabinet,
                'DtCr'         => now(),
            ];

            if ($this->userId) {
                // Si c'est une modification
                $user = TUser::find($this->userId);
                if (!$user) {
                    throw new \Exception('Utilisateur non trouvé');
                }

                // Mettre à jour les données de base
                $user->update($userData);

                // Mettre à jour le mot de passe si fourni
                if (!empty($this->password)) {
                    $user->password = $this->password;
                    $user->save();
                }

                session()->flash('message', 'Utilisateur modifié avec succès.');
            } else {
                // Si c'est une création
                if (empty($this->password)) {
                    throw new \Exception('Le mot de passe est requis pour la création d\'un utilisateur');
                }

                // Si rôle médecin ou docteur propriétaire → créer un médecin et lier
                if (in_array($this->role, [2, 3]) && !$fkidmedecin) {
                    $medecinId = DB::table('medecins')->insertGetId([
                        'Nom'        => $this->nomComplet,
                        'Contact'    => '',
                        'DtAjout'    => now(),
                        'Masquer'    => 0,
                        'fkidcabinet'=> Auth::user()->fkidcabinet,
                    ]);
                    $userData['fkidmedecin'] = $medecinId;
                }

                $userData['password'] = $this->password;
                TUser::create($userData);
                session()->flash('message', 'Utilisateur créé avec succès.');
            }

            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur est survenue lors de l\'enregistrement de l\'utilisateur : ' . $e->getMessage());
        }
    }

    public function confirmDelete($id)
    {
        $this->userToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function confirmForceDelete($id)
    {
        $this->userToDelete = $id;
        $this->dispatch('open-force-delete');
    }

    public function forceDeleteUser()
    {
        try {
            $user = TUser::find($this->userToDelete);
            if ($user) {
                $user->delete();
                session()->flash('message', 'Utilisateur supprimé définitivement.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la suppression définitive.');
        }
        $this->showForceDeleteModal = false;
        $this->userToDelete = null;
    }

    public function reactivateUser($id)
    {
        try {
            $user = TUser::find($id);
            if ($user) {
                $user->ismasquer = false;
                $user->save();
                session()->flash('message', 'Utilisateur réactivé avec succès.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la réactivation.');
        }
    }

    public function deleteUser()
    {
        try {
            $user = TUser::find($this->userToDelete);
            if ($user) {
                // Vérifier si l'utilisateur n'est pas le dernier propriétaire
                if ($user->IdClasseUser == 3) {
                    $ownerCount = TUser::where('IdClasseUser', 3)
                        ->where('ismasquer', false)
                        ->count();
                    
                    if ($ownerCount <= 1) {
                        session()->flash('error', 'Impossible de supprimer le dernier propriétaire.');
                        $this->showDeleteModal = false;
                        return;
                    }
                }

                $user->ismasquer = true;
                $user->save();
                session()->flash('message', 'Utilisateur supprimé avec succès.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur est survenue lors de la suppression.');
        }
        $this->showDeleteModal = false;
        $this->userToDelete = null;
    }

    public function toggleStatus($userId)
    {
        try {
            $user = TUser::find($userId);
            if ($user) {
                // Vérifier si l'utilisateur n'est pas le dernier propriétaire actif
                if ($user->IdClasseUser == 3 && !$user->ismasquer) {
                    $activeOwnerCount = TUser::where('IdClasseUser', 3)
                        ->where('ismasquer', false)
                        ->count();

                    if ($activeOwnerCount <= 1) {
                        session()->flash('error', 'Impossible de désactiver le dernier propriétaire actif.');
                        return;
                    }
                }

                $user->ismasquer = !$user->ismasquer;
                $user->save();
                session()->flash('message', 'Statut de l\'utilisateur modifié avec succès.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Une erreur est survenue lors de la modification du statut.');
        }
    }

    // ─── RBAC / Permissions ───────────────────────────────────────────────────

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        if ($tab === 'permissions') {
            $this->loadPermissionsTab();
        }
        if ($tab === 'roles') {
            $this->loadRoles();
        }
    }

    // ─── Gestion des rôles ───────────────────────────────────────────────────

    public function loadRoles(): void
    {
        $this->rolesList = DB::table('typeuser')->orderBy('IdClasseUser0')->get()->toArray();
    }

    public function editRole(int $id): void
    {
        $role = DB::table('typeuser')->where('IdClasseUser0', $id)->first();
        if (!$role) return;
        $this->roleEditId = $id;
        $this->roleLibelle = $role->Libelle;
    }

    public function saveRole(): void
    {
        $libelle = trim($this->roleLibelle);
        if (empty($libelle)) {
            session()->flash('error', 'Le libellé du rôle est obligatoire.');
            return;
        }

        if ($this->roleEditId) {
            DB::table('typeuser')
                ->where('IdClasseUser0', $this->roleEditId)
                ->update(['Libelle' => $libelle]);
            session()->flash('message', 'Rôle modifié avec succès.');
        } else {
            DB::table('typeuser')->insert(['Libelle' => $libelle]);
            session()->flash('message', 'Rôle créé avec succès.');
        }

        $this->roleEditId = null;
        $this->roleLibelle = '';
        $this->loadRoles();
    }

    public function cancelRoleEdit(): void
    {
        $this->roleEditId = null;
        $this->roleLibelle = '';
    }

    public function confirmDeleteRole(int $id): void
    {
        // Empêcher la suppression si des utilisateurs ont ce rôle
        $count = DB::table('t_user')->where('IdClasseUser', $id)->count();
        if ($count > 0) {
            session()->flash('error', "Impossible de supprimer ce rôle : {$count} utilisateur(s) l'utilisent.");
            return;
        }
        $this->roleToDelete = $id;
        $this->showRoleDeleteConfirm = true;
    }

    public function deleteRole(): void
    {
        if (!$this->roleToDelete) return;

        // Supprimer les permissions associées à ce rôle
        DB::table('role_permissions')->where('role_id', $this->roleToDelete)->delete();
        DB::table('typeuser')->where('IdClasseUser0', $this->roleToDelete)->delete();

        TUser::clearPermissionsCache($this->roleToDelete);

        $this->roleToDelete = null;
        $this->showRoleDeleteConfirm = false;
        $this->loadRoles();
        session()->flash('message', 'Rôle supprimé avec succès.');
    }

    public function loadPermissionsTab(): void
    {
        // Toutes les permissions groupées
        $allPerms = DB::table('permissions')->orderBy('groupe')->orderBy('ordre')->get();
        $grouped = [];
        foreach ($allPerms as $p) {
            $grouped[$p->groupe][] = $p;
        }
        $this->permissionsTab = $grouped;

        // Permissions attribuées par rôle
        $assigned = DB::table('role_permissions')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->get(['role_permissions.role_id', 'permissions.id as perm_id']);

        $map = [];
        foreach ($assigned as $row) {
            $map[$row->role_id][$row->perm_id] = true;
        }
        $this->rolePermissions = $map;
    }

    public function togglePermission(int $roleId, int $permId): void
    {
        // Le rôle Propriétaire (3) garde toujours user.* — protection minimale
        $perm = DB::table('permissions')->find($permId);
        if ($roleId === 3 && $perm && str_starts_with($perm->name, 'user.')) {
            $this->dispatch('toast', ['message' => 'Le propriétaire doit garder les droits utilisateurs.', 'type' => 'warning']);
            return;
        }

        $exists = DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->where('permission_id', $permId)
            ->exists();

        if ($exists) {
            DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permId)
                ->delete();
        } else {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id'       => $roleId,
                'permission_id' => $permId,
            ]);
        }

        // Invalider le cache pour ce rôle
        TUser::clearPermissionsCache($roleId);

        $this->dispatch('toast', ['message' => 'Permissions mises à jour.', 'type' => 'success']);
    }
} 