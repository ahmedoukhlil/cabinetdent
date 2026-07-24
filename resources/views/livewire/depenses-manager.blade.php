<div class="p-4 md:p-6 space-y-6">

    @if(session()->has('message'))
    <div class="flex items-center gap-2 px-4 py-3 bg-green-50 border-l-4 border-green-500 rounded text-green-800 text-sm">
        <i class="fas fa-check-circle"></i> {{ session('message') }}
    </div>
    @endif
    @if(session()->has('error'))
    <div class="flex items-center gap-2 px-4 py-3 bg-red-50 border-l-4 border-red-500 rounded text-red-800 text-sm">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif

    {{-- Statistiques --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Total des dépenses</dt>
            <dd class="text-2xl font-bold text-red-600">{{ number_format($totalDepenses, 0, ',', ' ') }} MRU</dd>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Période</dt>
            <dd class="text-base font-semibold text-primary">
                {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
            </dd>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <dt class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Nombre d'opérations</dt>
            <dd class="text-2xl font-bold text-primary">{{ $depenses->total() }}</dd>
        </div>
    </div>

    {{-- Formulaire ajout/modification --}}
    <div class="bg-primary-light border-l-4 border-primary rounded-lg p-4">
        <h3 class="text-sm font-semibold text-primary uppercase tracking-wide mb-4">
            <i class="fas fa-{{ $isEditing ? 'edit' : 'plus' }} mr-1"></i>
            {{ $isEditing ? 'Modifier la dépense' : 'Nouvelle dépense' }}
        </h3>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de dépense *</label>
                    <input type="date" wire:model="dateDepense" class="form-input">
                    @error('dateDepense') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Montant (MRU) *</label>
                    <input type="number" wire:model="montant" step="0.01" min="0" class="form-input">
                    @error('montant') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type de tiers *</label>
                    <select wire:model="fkIdTypeTiers" class="form-select">
                        <option value="">Sélectionner un type</option>
                        @foreach($typesTiers as $type)
                        <option value="{{ $type->IdTypeTiers }}">{{ $type->LibelleTypeTiers }}</option>
                        @endforeach
                    </select>
                    @error('fkIdTypeTiers') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type de paiement *</label>
                    <select wire:model="TypePAie" class="form-select">
                        <option value="">Sélectionner un type</option>
                        @foreach($typesPaiement as $type)
                        <option value="{{ $type->LibPaie }}">{{ $type->LibPaie }}</option>
                        @endforeach
                    </select>
                    @error('TypePAie') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Motif *</label>
                <textarea wire:model="motif" rows="3" class="form-textarea" placeholder="Décrivez la nature de cette dépense..."></textarea>
                @error('motif') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="flex justify-end gap-3">
                @if($isEditing)
                <button type="button" wire:click="cancelEdit" class="btn-secondary">Annuler</button>
                @endif
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-1"></i> {{ $isEditing ? 'Modifier' : 'Ajouter' }}
                </button>
            </div>
        </form>
    </div>

    {{-- Filtres --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">
            <i class="fas fa-filter mr-1"></i> Filtres
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                <input type="date" wire:model.live="dateDebut" class="form-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                <input type="date" wire:model.live="dateFin" class="form-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type de tiers</label>
                <select wire:model.live="typeTiersFilter" class="form-select">
                    <option value="">Tous les types</option>
                    @foreach($typesTiers as $type)
                    <option value="{{ $type->IdTypeTiers }}">{{ $type->LibelleTypeTiers }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button wire:click="resetFilters" class="btn-secondary text-sm">
                <i class="fas fa-undo mr-1"></i> Réinitialiser
            </button>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Motif</th>
                    <th>Type</th>
                    <th>Montant</th>
                    <th>Mode</th>
                </tr>
            </thead>
            <tbody>
                @forelse($depenses as $depense)
                <tr>
                    <td class="whitespace-nowrap">{{ \Carbon\Carbon::parse($depense->dateoper)->format('d/m/Y') }}</td>
                    <td>{{ $depense->designation }}</td>
                    <td>
                        @php $typeTiers = $typesTiers->firstWhere('IdTypeTiers', $depense->fkIdTypeTiers); @endphp
                        {{ $typeTiers ? $typeTiers->LibelleTypeTiers : '—' }}
                    </td>
                    <td class="font-semibold text-red-600 whitespace-nowrap">
                        {{ number_format($depense->retraitEspece, 0, ',', ' ') }} MRU
                    </td>
                    <td>
                        <span class="badge {{ $depense->TypePAie === 'CASH' ? 'badge-success' : ($depense->TypePAie === 'CARD' ? 'badge-info' : 'badge-neutral') }}">
                            {{ $depense->TypePAie }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-gray-400 py-8">
                        <i class="fas fa-receipt text-2xl mb-2 block"></i>
                        Aucune dépense trouvée pour cette période.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $depenses->links() }}</div>

</div>
