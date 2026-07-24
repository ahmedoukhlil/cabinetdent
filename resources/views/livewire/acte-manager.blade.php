<div class="p-4 md:p-6">

    @if(session()->has('message'))
    <div class="mb-4 flex items-center gap-2 px-4 py-3 bg-green-50 border-l-4 border-green-500 rounded text-green-800 text-sm">
        <i class="fas fa-check-circle"></i> {{ session('message') }}
    </div>
    @endif

    {{-- Formulaire ajout --}}
    <div class="mb-6 bg-primary-light border-l-4 border-primary rounded-lg p-4">
        <h3 class="text-sm font-semibold text-primary uppercase tracking-wide mb-4">
            <i class="fas fa-plus mr-1"></i> Ajouter un acte
        </h3>
        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'acte *</label>
                <input type="text" wire:model="acteNom" class="form-input" placeholder="Nom de l'acte">
                @error('acteNom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix de référence *</label>
                <input type="number" step="0.01" wire:model="montant" class="form-input" placeholder="0.00">
                @error('montant') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assureur *</label>
                <select wire:model="assureurId" class="form-select">
                    <option value="">Sélectionner un assureur</option>
                    @foreach($assureurs as $assureur)
                    <option value="{{ $assureur->IDAssureur ?? $assureur->ID }}">{{ $assureur->LibAssurance }}</option>
                    @endforeach
                </select>
                @error('assureurId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </form>
        <div class="mt-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom arabe (optionnel)</label>
            <input type="text" wire:model="acteArab" class="form-input md:w-1/2" placeholder="Nom arabe de l'acte">
        </div>
    </div>

    {{-- Filtres --}}
    <div class="mb-4 flex flex-col md:flex-row gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Assureur :</label>
            <select wire:model.live="selectedAssureur" class="form-select">
                <option value="">Tous</option>
                @foreach($assureurs as $assureur)
                <option value="{{ $assureur->IDAssureur ?? $assureur->ID }}">{{ $assureur->LibAssurance }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1">
            <input type="text" wire:model.live="search" class="form-input" placeholder="Rechercher un acte...">
        </div>
    </div>

    {{-- Tableau --}}
    <div class="overflow-x-auto max-h-[50vh] overflow-y-auto rounded-lg border border-gray-200">
        <table>
            <thead>
                <tr>
                    <th>Acte</th>
                    <th>Prix de référence</th>
                    <th>Assureur</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($actes as $acte)
                <tr>
                    <td>
                        {{ $acte->Acte }}
                        @if($acte->ActeArab)
                        <div class="text-xs text-gray-500">{{ $acte->ActeArab }}</div>
                        @endif
                    </td>
                    <td>{{ number_format($acte->PrixRef, 2) }} MRU</td>
                    <td>{{ $acte->assureur->LibAssurance ?? '-' }}</td>
                    <td>
                        <button wire:click="openModal({{ $acte->ID }})" class="text-primary hover:text-primary-dark text-sm font-medium mr-3">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <button wire:click="confirmDelete({{ $acte->ID }})" class="text-red-600 hover:text-red-800 text-sm font-medium">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-gray-400 py-8">
                        <i class="fas fa-list-alt text-2xl mb-2 block"></i> Aucun acte trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $actes->links() }}</div>

    {{-- Modal édition --}}
    @if($showModal)
    <div class="modal-overlay" style="z-index:60;">
        <div class="modal-box max-w-lg w-full">
            <div class="modal-header">
                <h2><i class="fas fa-edit mr-2"></i>Modifier un acte</h2>
                <button wire:click="closeModal" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'acte *</label>
                        <input type="text" wire:model="acteNom" class="form-input">
                        @error('acteNom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prix de référence *</label>
                        <input type="number" step="0.01" wire:model="montant" class="form-input">
                        @error('montant') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assureur *</label>
                        <select wire:model="assureurId" class="form-select">
                            <option value="">Sélectionner un assureur</option>
                            @foreach($assureurs as $assureur)
                            <option value="{{ $assureur->IDAssureur ?? $assureur->ID }}">{{ $assureur->LibAssurance }}</option>
                            @endforeach
                        </select>
                        @error('assureurId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom arabe</label>
                        <input type="text" wire:model="acteArab" class="form-input">
                    </div>
                    <div class="modal-footer px-0 pb-0">
                        <button type="button" wire:click="closeModal" class="btn-secondary">Annuler</button>
                        <button type="submit" class="btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal suppression --}}
    @if($showDeleteModal)
    <div class="modal-overlay" style="z-index:60;">
        <div class="modal-box max-w-md w-full">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle mr-2"></i>Confirmer la suppression</h2>
                <button wire:click="$set('showDeleteModal', false)" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p class="text-gray-600">Êtes-vous sûr de vouloir supprimer cet acte ? Cette action est irréversible.</p>
                <div class="modal-footer px-0 pb-0 mt-6">
                    <button wire:click="$set('showDeleteModal', false)" class="btn-secondary">Annuler</button>
                    <button wire:click="deleteActe" class="btn-danger">Supprimer</button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
