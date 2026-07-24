<div class="p-4 md:p-6">

    {{-- Formulaire ajout/édition --}}
    <div class="mb-6 bg-primary-light border-l-4 border-primary rounded-lg p-4">
        <h3 class="text-sm font-semibold text-primary uppercase tracking-wide mb-4">
            <i class="fas fa-{{ $editMode ? 'edit' : 'plus' }} mr-1"></i>
            {{ $editMode ? 'Modifier le médecin' : 'Ajouter un médecin' }}
        </h3>
        <form wire:submit.prevent="saveMedecin" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                <input type="text" wire:model="Nom" class="form-input" required>
                @error('Nom') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact *</label>
                <input type="text" wire:model="Contact" class="form-input" required>
                @error('Contact') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1">
                    <i class="fas fa-save"></i> {{ $editMode ? 'Mettre à jour' : 'Enregistrer' }}
                </button>
                @if($editMode)
                <button type="button" wire:click="resetForm" class="btn-secondary">Annuler</button>
                @endif
            </div>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="overflow-x-auto max-h-[55vh] overflow-y-auto rounded-lg border border-gray-200">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nom</th>
                    <th>Contact</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($medecins as $index => $medecin)
                <tr>
                    <td>{{ ($medecins->firstItem() ?? 0) + $index }}</td>
                    <td>{{ $medecin->Nom }}</td>
                    <td>{{ $medecin->Contact }}</td>
                    <td>
                        <button wire:click="editMedecin({{ $medecin->idMedecin }})" class="text-primary hover:text-primary-dark text-sm font-medium mr-3">
                            <i class="fas fa-edit"></i> Modifier
                        </button>
                        <button wire:click="confirmDeleteMedecin({{ $medecin->idMedecin }})"
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-gray-400 py-8">
                        <i class="fas fa-user-md text-2xl mb-2 block"></i> Aucun médecin trouvé
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $medecins->links() }}</div>

    {{-- Modal confirmation suppression médecin --}}
    @if($showDeleteConfirm)
    <div class="modal-overlay" style="z-index:70">
        <div class="modal-box sm:max-w-md">
            <div class="modal-header" style="background:#dc2626">
                <h3><i class="fas fa-exclamation-triangle mr-2"></i>Supprimer ce médecin ?</h3>
                <button type="button" wire:click="$set('showDeleteConfirm', false)" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-user-md text-red-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">{{ $medecinToDeleteNom }}</p>
                        <p class="text-sm text-gray-500 mt-1">Cette action est <strong>irréversible</strong>.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button wire:click="$set('showDeleteConfirm', false)"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                        Annuler
                    </button>
                    <button wire:click="deleteMedecin"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="deleteMedecin"><i class="fas fa-trash mr-1"></i> Supprimer</span>
                        <span wire:loading wire:target="deleteMedecin"><i class="fas fa-spinner fa-spin mr-1"></i> Suppression...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
