<div class="p-4 md:p-6">

    @if(session()->has('message'))
    <div class="mb-4 flex items-center gap-2 px-4 py-3 bg-green-50 border-l-4 border-green-500 rounded text-green-800 text-sm">
        <i class="fas fa-check-circle"></i> {{ session('message') }}
    </div>
    @endif

    {{-- Formulaire ajout --}}
    <div class="mb-6 bg-primary-light border-l-4 border-primary rounded-lg p-4">
        <h3 class="text-sm font-semibold text-primary uppercase tracking-wide mb-4">
            <i class="fas fa-plus mr-1"></i> Ajouter un assureur
        </h3>
        <form wire:submit.prevent="save" class="flex flex-col md:flex-row items-end gap-3">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                <input type="text" wire:model="libAssurance" class="form-input" placeholder="Nom de l'assureur">
                @error('libAssurance') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="w-full md:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Taux de PEC (%) *</label>
                <input type="number" step="0.01" wire:model="tauxdePEC" class="form-input" placeholder="0.00">
                @error('tauxdePEC') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="btn-primary shrink-0">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </form>
    </div>

    {{-- Recherche --}}
    <div class="mb-4">
        <input type="text" wire:model.live="search" class="form-input" placeholder="Rechercher un assureur...">
    </div>

    {{-- Tableau --}}
    <div class="overflow-x-auto max-h-[55vh] overflow-y-auto rounded-lg border border-gray-200">
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Taux de PEC (%)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assureurs as $assureur)
                <tr>
                    <td>{{ $assureur->LibAssurance }}</td>
                    <td>{{ is_numeric($assureur->TauxdePEC) ? number_format($assureur->TauxdePEC, 2).' %' : '-' }}</td>
                    <td>
                        <button wire:click="openModal({{ $assureur->IDAssureur ?? $assureur->ID }})" class="text-primary hover:text-primary-dark text-sm font-medium mr-3">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <button wire:click="confirmDelete({{ $assureur->IDAssureur ?? $assureur->ID }})" class="text-red-600 hover:text-red-800 text-sm font-medium">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-gray-400 py-8">
                        <i class="fas fa-shield-alt text-2xl mb-2 block"></i> Aucun assureur trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $assureurs->links() }}</div>

    {{-- Modal édition --}}
    @if($showModal)
    <div class="modal-overlay" style="z-index:60;">
        <div class="modal-box max-w-lg w-full">
            <div class="modal-header">
                <h2><i class="fas fa-edit mr-2"></i>Modifier un assureur</h2>
                <button wire:click="closeModal" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'assureur *</label>
                        <input type="text" wire:model="libAssurance" class="form-input">
                        @error('libAssurance') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Taux de PEC (%) *</label>
                        <input type="number" step="0.01" wire:model="tauxdePEC" class="form-input">
                        @error('tauxdePEC') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
                <p class="text-gray-600">Êtes-vous sûr de vouloir supprimer cet assureur ? Cette action est irréversible.</p>
                <div class="modal-footer px-0 pb-0 mt-6">
                    <button wire:click="$set('showDeleteModal', false)" class="btn-secondary">Annuler</button>
                    <button wire:click="deleteAssureur" class="btn-danger">Supprimer</button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
