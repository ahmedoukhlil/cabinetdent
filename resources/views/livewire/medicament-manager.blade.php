<div class="p-4 md:p-6">

    @if(session()->has('message'))
    <div class="mb-4 flex items-center gap-2 px-4 py-3 bg-green-50 border-l-4 border-green-500 rounded text-green-800 text-sm">
        <i class="fas fa-check-circle"></i> {{ session('message') }}
    </div>
    @endif

    {{-- Onglets --}}
    <div class="flex border-b border-gray-200 mb-5">
        <button wire:click="switchTab('medicaments')"
            class="flex items-center gap-2 px-5 py-3 text-sm font-semibold border-b-2 transition-colors
                {{ $activeTab === 'medicaments' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <i class="fas fa-pills"></i> Médicaments
        </button>
        <button wire:click="switchTab('analyses')"
            class="flex items-center gap-2 px-5 py-3 text-sm font-semibold border-b-2 transition-colors
                {{ $activeTab === 'analyses' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
            <i class="fas fa-microscope"></i> Analyses & Radios
        </button>
    </div>

    {{-- Formulaire ajout --}}
    <div class="mb-6 bg-primary-light border-l-4 border-primary rounded-lg p-4">
        <h3 class="text-sm font-semibold text-primary uppercase tracking-wide mb-4">
            <i class="fas fa-plus mr-1"></i>
            {{ $activeTab === 'medicaments' ? 'Ajouter un médicament' : 'Ajouter une analyse / radio' }}
        </h3>
        <form wire:submit.prevent="save" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Libellé *</label>
                <input type="text" wire:model="libelleMedic" class="form-input"
                    placeholder="{{ $activeTab === 'medicaments' ? 'Libellé du médicament' : 'Libellé de l\'analyse / radio' }}">
                @error('libelleMedic') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            @if($activeTab === 'medicaments')
            {{-- Type fixé à Médicament, champ caché --}}
            <input type="hidden" wire:model="fkidtype" value="1">
            @else
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                <select wire:model="fkidtype" class="form-select">
                    <option value="">Sélectionner</option>
                    <option value="2">Analyse</option>
                    <option value="3">Radio</option>
                </select>
                @error('fkidtype') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prix de référence</label>
                <input type="number" step="0.01" wire:model="prixRef" class="form-input" placeholder="0.00" min="0">
                @error('prixRef') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </form>
    </div>

    {{-- Filtres --}}
    <div class="mb-4 flex flex-col md:flex-row gap-3">
        @if($activeTab === 'analyses')
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Type :</label>
            <select wire:model.live="selectedType" class="form-select">
                <option value="">Tous</option>
                <option value="2">Analyse</option>
                <option value="3">Radio</option>
            </select>
        </div>
        @endif
        <div class="flex-1">
            <input type="text" wire:model.live="search" class="form-input"
                placeholder="{{ $activeTab === 'medicaments' ? 'Rechercher un médicament...' : 'Rechercher une analyse / radio...' }}">
        </div>
    </div>

    {{-- Tableau --}}
    <div class="overflow-x-auto max-h-[50vh] overflow-y-auto rounded-lg border border-gray-200">
        <table>
            <thead>
                <tr>
                    <th>Libellé</th>
                    @if($activeTab === 'analyses')
                    <th>Type</th>
                    @endif
                    <th>Prix de référence</th>
                    @if($activeTab === 'medicaments')
                    <th>Stock</th>
                    @endif
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($medicaments as $medicament)
                <tr>
                    <td>{{ $medicament->LibelleMedic }}</td>
                    @if($activeTab === 'analyses')
                    <td>
                        <span class="badge {{ $medicament->fkidtype == 2 ? 'badge-success' : 'badge-purple' }}">
                            {{ $medicament->fkidtype == 2 ? 'Analyse' : 'Radio' }}
                        </span>
                    </td>
                    @endif
                    <td>{{ number_format($medicament->PrixRef ?? 0, 2) }} MRU</td>
                    @if($activeTab === 'medicaments')
                    <td>
                        @if($medicament->quantiteStock !== null)
                        <span class="badge {{ $medicament->stockFaible ? 'badge-warning' : ($medicament->quantiteStock == 0 ? 'badge-neutral' : 'badge-success') }}">
                            {{ number_format($medicament->quantiteStock, 0) }}
                            @if($medicament->stockFaible && $medicament->quantiteStock > 0)
                                <i class="fas fa-exclamation-triangle ml-1"></i>
                            @elseif($medicament->quantiteStock == 0)
                                <i class="fas fa-times-circle ml-1"></i>
                            @endif
                        </span>
                        @else
                        <span class="text-gray-400 text-xs">Non en stock</span>
                        @endif
                    </td>
                    @endif
                    <td>
                        <div class="flex items-center gap-3">
                            <button wire:click="openModal({{ $medicament->IDMedic }})" class="text-primary hover:text-primary-dark text-sm font-medium">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            @if($activeTab === 'medicaments')
                            <button wire:click="openStockModal({{ $medicament->IDMedic }})" class="text-green-600 hover:text-green-800 text-sm font-medium">
                                <i class="fas fa-plus-circle"></i> Stock
                            </button>
                            @endif
                            <button wire:click="confirmDelete({{ $medicament->IDMedic }})" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-gray-400 py-8">
                        @if($activeTab === 'medicaments')
                            <i class="fas fa-pills text-2xl mb-2 block"></i> Aucun médicament trouvé
                        @else
                            <i class="fas fa-microscope text-2xl mb-2 block"></i> Aucune analyse / radio trouvée
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $medicaments->links() }}</div>

    {{-- Modal édition --}}
    @if($showModal)
    <div class="modal-overlay" style="z-index:60;">
        <div class="modal-box max-w-lg w-full">
            <div class="modal-header">
                <h2><i class="fas fa-edit mr-2"></i>Modifier</h2>
                <button wire:click="closeModal" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Libellé *</label>
                        <input type="text" wire:model="libelleMedic" class="form-input">
                        @error('libelleMedic') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                        <select wire:model="fkidtype" class="form-select">
                            <option value="">Sélectionner un type</option>
                            @foreach($types as $type)
                            <option value="{{ $type['id'] }}">{{ $type['Type'] }}</option>
                            @endforeach
                        </select>
                        @error('fkidtype') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prix de référence</label>
                        <input type="number" step="0.01" wire:model="prixRef" class="form-input" placeholder="0.00" min="0">
                        @error('prixRef') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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

    {{-- Modal ajout de stock --}}
    @if($showStockModal)
    <div class="modal-overlay" style="z-index:60;">
        <div class="modal-box max-w-2xl w-full">
            <div class="modal-header">
                <h2><i class="fas fa-boxes mr-2"></i>Ajouter du stock</h2>
                <button wire:click="closeStockModal" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="saveStock" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Médicament</label>
                            <input type="text" value="{{ \App\Models\Medicament::find($stockMedicamentId)->LibelleMedic ?? '' }}" disabled class="form-input bg-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantité *</label>
                            <input type="number" wire:model="stockQuantite" class="form-input" min="1" required>
                            @error('stockQuantite') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix d'achat unitaire *</label>
                            <input type="number" step="0.01" wire:model="stockPrixAchat" class="form-input" min="0">
                            @error('stockPrixAchat') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Seuil minimum *</label>
                            <input type="number" wire:model="stockQuantiteMin" class="form-input" min="0" required>
                            @error('stockQuantiteMin') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de lot</label>
                            <input type="text" wire:model="stockNumeroLot" class="form-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date d'expiration</label>
                            <input type="date" wire:model="stockDateExpiration" class="form-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fournisseur</label>
                            <input type="text" wire:model="stockFournisseur" class="form-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Référence facture</label>
                            <input type="text" wire:model="stockReferenceFacture" class="form-input">
                        </div>
                    </div>
                    <div class="modal-footer px-0 pb-0">
                        <button type="button" wire:click="closeStockModal" class="btn-secondary">Annuler</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-plus mr-1"></i> Ajouter au stock
                        </button>
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
                <p class="text-gray-600">Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.</p>
                <div class="modal-footer px-0 pb-0 mt-6">
                    <button wire:click="$set('showDeleteModal', false)" class="btn-secondary">Annuler</button>
                    <button wire:click="deleteMedicament" class="btn-danger">Supprimer</button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
