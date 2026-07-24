<div class="p-6">

    {{-- Notifications --}}
    @if (session()->has('message'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-lg border border-green-200 flex items-center gap-2">
            <i class="fas fa-check-circle text-green-600"></i> {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-lg border border-red-200 flex items-center gap-2">
            <i class="fas fa-exclamation-circle text-red-600"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Onglets --}}
    <div class="flex items-center justify-end mb-5">
        <div class="flex gap-1 bg-gray-100 p-1 rounded-lg">
            <button wire:click="switchTab('users')"
                class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors
                       {{ $activeTab === 'users' ? 'bg-white text-primary shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
                <i class="fas fa-users mr-1"></i> Utilisateurs
            </button>
            <button wire:click="switchTab('permissions')"
                class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors
                       {{ $activeTab === 'permissions' ? 'bg-white text-primary shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
                <i class="fas fa-shield-alt mr-1"></i> Permissions
            </button>
            <button wire:click="switchTab('roles')"
                class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors
                       {{ $activeTab === 'roles' ? 'bg-white text-primary shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
                <i class="fas fa-tags mr-1"></i> Rôles
            </button>
        </div>
    </div>

    {{-- ═══════════════════ ONGLET UTILISATEURS ═══════════════════ --}}
    @if($activeTab === 'users')

    {{-- Formulaire d'ajout --}}
    <div class="mb-6 bg-primary-light border-l-4 border-primary rounded-lg p-4">
        <h3 class="text-sm font-semibold text-primary uppercase tracking-wide mb-3 flex items-center gap-2">
            <i class="fas fa-user-plus"></i> Ajouter un utilisateur
        </h3>
        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 items-start">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Nom Complet *</label>
                    <input type="text" wire:model="nomComplet"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary"
                           placeholder="Nom complet">
                    @error('nomComplet') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Identifiant *</label>
                    <input type="text" wire:model="login"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary"
                           placeholder="login">
                    @error('login') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Mot de passe *</label>
                    <input type="password" wire:model="password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary"
                           placeholder="••••••">
                    @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Rôle *</label>
                    <select wire:model="role"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Sélectionner un rôle</option>
                        @foreach($roles as $r)
                            <option value="{{ $r['id'] }}">{{ $r['Role'] }}</option>
                        @endforeach
                    </select>
                    @error('role') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="isActive" class="rounded border-gray-300 text-primary">
                    Compte actif
                </label>
                <button type="submit"
                        class="px-5 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-save mr-1"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>

    {{-- Filtres --}}
    <div class="mb-4 flex flex-col sm:flex-row items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-600">Rôle :</label>
            <select wire:model.live="selectedRole" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                <option value="">Tous</option>
                @foreach($roles as $r)
                    <option value="{{ $r['id'] }}">{{ $r['Role'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1">
            <input type="text" wire:model.live="search" placeholder="Rechercher un utilisateur…"
                   class="w-full px-4 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    {{-- Tableau --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nom</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Identifiant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Rôle</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50 transition-colors {{ $user->ismasquer ? 'opacity-60 bg-gray-50' : '' }}">
                    <td class="px-4 py-3 font-medium text-gray-900">
                        {{ $user->NomComplet }}
                        @if($user->ismasquer)
                            <span class="ml-1 text-xs text-gray-400 italic">(désactivé)</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $user->login }}</td>
                    <td class="px-4 py-3">
                        @php
                            $allRolesMap = \DB::table('typeuser')->pluck('Libelle','IdClasseUser0');
                            $palette2 = ['purple','blue','green','orange','pink','teal','indigo','rose','amber','cyan'];
                            $roleIndex2 = array_search($user->IdClasseUser, array_keys($allRolesMap->toArray()));
                            $rLabel = $allRolesMap[$user->IdClasseUser] ?? '-';
                            $rColor = $roleIndex2 !== false ? $palette2[$roleIndex2 % count($palette2)] : 'gray';
                        @endphp
                        <span class="px-2.5 py-0.5 text-xs rounded-full font-medium
                            bg-{{ $rColor }}-100 text-{{ $rColor }}-800">
                            {{ $rLabel }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <button wire:click="toggleStatus({{ $user->Iduser }})"
                                class="px-2.5 py-0.5 text-xs rounded-full font-medium transition-colors
                                {{ !$user->ismasquer ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                            {{ !$user->ismasquer ? 'Actif' : 'Inactif' }}
                        </button>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3 flex-wrap">
                            @if($user->ismasquer)
                                <button wire:click="reactivateUser({{ $user->Iduser }})"
                                        class="text-green-600 hover:text-green-800 text-xs font-medium">
                                    <i class="fas fa-undo mr-0.5"></i> Réactiver
                                </button>
                                <button wire:click="confirmForceDelete({{ $user->Iduser }})"
                                        class="text-red-700 hover:text-red-900 text-xs font-medium">
                                    <i class="fas fa-trash-alt mr-0.5"></i> Supprimer définitivement
                                </button>
                            @else
                                <button wire:click="openModal({{ $user->Iduser }})"
                                        class="text-primary hover:text-primary-dark text-xs font-medium">
                                    <i class="fas fa-edit mr-0.5"></i> Modifier
                                </button>
                                @if($user->IdClasseUser != 3 || $users->where('IdClasseUser', 3)->where('ismasquer', false)->count() > 1)
                                <button wire:click="confirmForceDelete({{ $user->Iduser }})"
                                        class="text-red-500 hover:text-red-700 text-xs font-medium">
                                    <i class="fas fa-trash mr-0.5"></i> Supprimer
                                </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                        <i class="fas fa-users text-3xl mb-2 block"></i> Aucun utilisateur trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $users->links() }}</div>

    @endif {{-- fin onglet users --}}

    {{-- ═══════════════════ ONGLET PERMISSIONS ═══════════════════ --}}
    @if($activeTab === 'permissions')

    @php
        // Rôles dynamiques depuis la DB
        $allRoles = \DB::table('typeuser')->orderBy('IdClasseUser0')->get();
        $palette  = ['purple','blue','green','orange','pink','teal','indigo','rose','amber','cyan'];
        $roleColors = [];
        foreach ($allRoles as $i => $r) {
            $rKey = is_array($r) ? $r['IdClasseUser0'] : $r->IdClasseUser0;
            $roleColors[$rKey] = $palette[$i % count($palette)];
        }
        $colspan = $allRoles->count() + 1;
    @endphp

    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800 flex items-start gap-2">
        <i class="fas fa-info-circle mt-0.5 flex-shrink-0"></i>
        <span>Cochez/décochez les permissions pour chaque rôle. Les modifications sont appliquées immédiatement et le cache est invalidé automatiquement.</span>
    </div>

    @if(empty($permissionsTab))
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-lock text-4xl mb-3 block"></i>
            <p class="font-medium">Aucune permission trouvée.</p>
        </div>
    @else

    <div class="overflow-x-auto rounded-xl border border-gray-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Permission</th>
                    @foreach($allRoles as $r)
                    @php
                        $rId2  = is_array($r) ? $r['IdClasseUser0'] : $r->IdClasseUser0;
                        $rLib2 = is_array($r) ? $r['Libelle']       : $r->Libelle;
                        $col   = $roleColors[$rId2];
                    @endphp
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase w-28">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-{{ $col }}-100 text-{{ $col }}-800">
                            {{ $rLib2 }}
                        </span>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($permissionsTab as $groupe => $perms)
                <tr class="bg-gray-100 border-y border-gray-200">
                    <td colspan="{{ $colspan }}" class="px-5 py-2 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <i class="fas fa-layer-group mr-1.5 text-gray-400"></i> {{ $groupe }}
                    </td>
                </tr>
                @foreach($perms as $perm)
                @php
                    $permId    = is_array($perm) ? $perm['id']    : $perm->id;
                    $permLabel = is_array($perm) ? $perm['label'] : $perm->label;
                    $permName  = is_array($perm) ? $perm['name']  : $perm->name;
                @endphp
                <tr wire:key="perm-row-{{ $permId }}" class="border-b border-gray-100 hover:bg-blue-50/40 transition-colors">
                    <td class="px-5 py-2.5">
                        <span class="font-medium text-gray-800">{{ $permLabel }}</span>
                        <code class="ml-2 text-xs text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">{{ $permName }}</code>
                    </td>
                    @foreach($allRoles as $r)
                    @php
                        $rId     = is_array($r) ? $r['IdClasseUser0'] : $r->IdClasseUser0;
                        $rLibell = is_array($r) ? $r['Libelle']       : $r->Libelle;
                        $col     = $roleColors[$rId];
                        $checked = !empty($rolePermissions[$rId][$permId]);
                    @endphp
                    <td class="px-4 py-2.5 text-center" wire:key="perm-cell-{{ $rId }}-{{ $permId }}">
                        <button type="button"
                                wire:click="togglePermission({{ $rId }}, {{ $permId }})"
                                title="{{ $checked ? 'Retirer' : 'Accorder' }} — {{ $rLibell }}"
                                class="w-8 h-8 rounded-full flex items-center justify-center mx-auto transition-all border-2
                                       {{ $checked
                                            ? 'bg-'.$col.'-100 border-'.$col.'-400 text-'.$col.'-700 hover:bg-'.$col.'-200'
                                            : 'bg-white border-gray-200 text-gray-300 hover:border-gray-400 hover:text-gray-500' }}">
                            <i class="fas {{ $checked ? 'fa-check' : 'fa-times' }} text-xs"></i>
                        </button>
                    </td>
                    @endforeach
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    @endif {{-- empty permissionsTab --}}
    @endif {{-- fin onglet permissions --}}

    {{-- ═══════════════════ MODAL ÉDITION ═══════════════════ --}}
    @if($showModal)
    <div class="modal-overlay" style="z-index:60">
        <div class="modal-box sm:max-w-lg">
            <div class="modal-header">
                <h3>Modifier un utilisateur</h3>
                <button type="button" wire:click="closeModal" class="modal-close">&times;</button>
            </div>
            <form wire:submit.prevent="save" class="modal-body space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom Complet *</label>
                    <input type="text" wire:model="nomComplet"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('nomComplet') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Identifiant *</label>
                    <input type="text" wire:model="login"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('login') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mot de passe <span class="text-gray-400 text-xs font-normal">(vide = inchangé)</span>
                    </label>
                    <input type="password" wire:model="password"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rôle *</label>
                    <select wire:model.live="role"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Sélectionner un rôle</option>
                        @foreach($roles as $r)
                            <option value="{{ $r['id'] }}">{{ $r['Role'] }}</option>
                        @endforeach
                    </select>
                    @error('role') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- Médecin lié — affiché uniquement pour les rôles Médecin (2) et Propriétaire (3) --}}
                @if(in_array($role, ['2', '3', 2, 3]))
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Médecin lié <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="fkidmedecin"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">— Sélectionner un médecin —</option>
                        @foreach(\App\Models\Medecin::orderBy('Nom')->get() as $med)
                            <option value="{{ $med->idMedecin }}">{{ $med->Nom }}</option>
                        @endforeach
                    </select>
                    @error('fkidmedecin') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                @endif

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" wire:model="isActive" class="rounded border-gray-300 text-primary">
                    Compte actif
                </label>
                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" wire:click="closeModal"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Annuler</button>
                    <button type="submit"
                            class="px-4 py-2 bg-primary text-white rounded-lg text-sm hover:opacity-90">
                        <i class="fas fa-save mr-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ═══════════════════ MODAL SUPPRESSION DÉFINITIVE ═══════════════════ --}}
    <dialog id="force-delete-dialog" class="force-delete-dialog">
        <div class="fdd-header">
            <h3><i class="fas fa-exclamation-triangle mr-2"></i>Suppression définitive</h3>
            <button type="button" onclick="document.getElementById('force-delete-dialog').close()" class="fdd-close">&times;</button>
        </div>
        <div class="fdd-body">
            <p class="text-sm text-gray-700 mb-2 font-semibold">Cette action est irréversible.</p>
            <p class="text-sm text-gray-600 mb-5">L'utilisateur sera supprimé définitivement de la base de données. Êtes-vous certain ?</p>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('force-delete-dialog').close()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Annuler</button>
                <button type="button" wire:click="forceDeleteUser"
                        onclick="document.getElementById('force-delete-dialog').close()"
                        class="px-4 py-2 bg-red-700 text-white rounded-lg text-sm hover:bg-red-800 font-medium">
                    <i class="fas fa-trash-alt mr-1"></i> Supprimer définitivement
                </button>
            </div>
        </div>
    </dialog>

    <style>
        dialog.force-delete-dialog {
            border: none;
            border-radius: 0.75rem;
            padding: 0;
            width: 28rem;
            max-width: 90vw;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
            background: #fff;
            margin: auto;
        }
        dialog.force-delete-dialog::backdrop {
            background: rgba(0,0,0,.5);
        }
        .fdd-header {
            background: #b91c1c;
            color: #fff;
            padding: 0.875rem 1.25rem;
            border-radius: 0.75rem 0.75rem 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .fdd-header h3 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
        }
        .fdd-close {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            padding: 0 0.25rem;
        }
        .fdd-body {
            padding: 1.5rem;
        }
    </style>

    <script>
        (function() {
            if (window.__userManagerDialogInit) return;
            window.__userManagerDialogInit = true;
            window.addEventListener('open-force-delete', () => {
                const dlg = document.getElementById('force-delete-dialog');
                if (dlg && !dlg.open) dlg.showModal();
            });
        })();
    </script>

    {{-- ═══════════════════ ONGLET RÔLES ═══════════════════ --}}
    @if($activeTab === 'roles')

    {{-- Formulaire création / édition --}}
    <div class="mb-6 bg-primary-light border-l-4 border-primary rounded-lg p-4">
        <h3 class="text-sm font-semibold text-primary uppercase tracking-wide mb-3 flex items-center gap-2">
            <i class="fas fa-{{ $roleEditId ? 'edit' : 'plus-circle' }}"></i>
            {{ $roleEditId ? 'Modifier le rôle' : 'Nouveau rôle' }}
        </h3>
        <div class="flex gap-3 items-end">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-700 mb-1">Libellé du rôle *</label>
                <input type="text" wire:model="roleLibelle"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary"
                       placeholder="Ex: Infirmier, Comptable...">
            </div>
            <div class="flex gap-2">
                <button wire:click="saveRole"
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark flex items-center gap-1">
                    <i class="fas fa-save"></i> {{ $roleEditId ? 'Enregistrer' : 'Créer' }}
                </button>
                @if($roleEditId)
                <button wire:click="cancelRoleEdit"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                    Annuler
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Liste des rôles --}}
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Libellé</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Utilisateurs</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rolesList as $role)
                @php
                    $rId      = is_array($role) ? $role['IdClasseUser0'] : $role->IdClasseUser0;
                    $rLibelle = is_array($role) ? $role['Libelle']       : $role->Libelle;
                    $nbUsers  = \App\Models\TUser::where('IdClasseUser', $rId)->where('ismasquer', 0)->count();
                    $isSystem = in_array($rId, [1, 2, 3]);
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ $rId }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-800">{{ $rLibelle }}</span>
                            @if($isSystem)
                                <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">Système</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="text-xs font-medium {{ $nbUsers > 0 ? 'text-primary' : 'text-gray-400' }}">
                            {{ $nbUsers }} utilisateur(s)
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <button wire:click="editRole({{ $rId }})"
                                    class="px-3 py-1.5 bg-primary-light text-primary border border-primary/30 rounded-lg text-xs font-medium hover:bg-primary/10 flex items-center gap-1">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            @if(!$isSystem)
                            <button wire:click="confirmDeleteRole({{ $rId }})"
                                    class="px-3 py-1.5 bg-red-50 text-red-700 border border-red-200 rounded-lg text-xs font-medium hover:bg-red-100 flex items-center gap-1">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                            @else
                            <span class="px-3 py-1.5 text-gray-300 text-xs">Protégé</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">
                        <i class="fas fa-tags text-2xl mb-2 block opacity-30"></i>
                        Aucun rôle défini
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Confirmation suppression rôle --}}
    @if($showRoleDeleteConfirm)
    <div class="modal-overlay" style="z-index:60">
        <div class="modal-box sm:max-w-sm">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle mr-2"></i>Supprimer ce rôle ?</h3>
                <button type="button" wire:click="$set('showRoleDeleteConfirm', false)" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-sm text-gray-600 mb-6">Cette action supprimera également toutes les permissions associées à ce rôle.</p>
                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showRoleDeleteConfirm', false)"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Annuler</button>
                    <button wire:click="deleteRole"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                        <i class="fas fa-trash mr-1"></i> Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @endif

</div>

