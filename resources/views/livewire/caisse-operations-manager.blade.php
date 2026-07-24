<div class="p-4 md:p-6 space-y-6">

    {{-- Filtres --}}
    <div class="bg-primary rounded-xl p-4 on-dark">
        <h3 class="text-sm font-semibold text-white/80 uppercase tracking-wide mb-3">
            <i class="fas fa-filter mr-1"></i> Filtres
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            @if($isOwnOnly)
            <div>
                <label class="block text-sm font-medium text-white/80 mb-1">Médecin</label>
                <input type="text" value="{{ Auth::user()->NomComplet ?? '' }}" disabled class="form-input bg-gray-100 cursor-not-allowed">
            </div>
            @else
            <div>
                <label class="block text-sm font-medium text-white/80 mb-1">Médecin</label>
                <select wire:model.live="medecin_id" class="form-select">
                    <option value="">Tous les médecins</option>
                    @foreach($medecins as $medecin)
                    <option value="{{ $medecin->idMedecin }}">{{ $medecin->Nom }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-white/80 mb-1">Date</label>
                <input type="date" wire:model.live="date_debut" class="form-input">
            </div>

            <div class="flex items-end">
                <button wire:click="resetFilters" class="btn-secondary w-full">
                    <i class="fas fa-undo mr-1"></i> Réinitialiser
                </button>
            </div>
        </div>
    </div>

    @if($operations->count() > 0)

    {{-- Totaux généraux --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="bg-primary px-6 py-4 flex justify-between items-center">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <i class="fas fa-chart-line"></i> Totaux généraux
                @if($isOwnOnly)
                <span class="badge badge-warning text-xs">Mes opérations uniquement</span>
                @endif
            </h3>
            @if($canViewAll)
            <a href="{{ route('caisse.etat-journalier', ['date' => $date_debut]) }}" target="_blank"
               class="text-sm text-white/80 hover:text-white border border-white/30 px-3 py-1 rounded-lg hover:bg-white/10 transition-colors">
                <i class="fas fa-print mr-1"></i> Imprimer l'état
            </a>
            @endif
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-primary-light rounded-xl p-5">
                    <dt class="text-xs font-semibold text-primary uppercase tracking-wide mb-1">Total des recettes</dt>
                    <dd class="text-2xl font-bold text-primary">{{ number_format($totalRecettes, 0, ',', ' ') }} MRU</dd>
                </div>
                <div class="bg-red-50 rounded-xl p-5">
                    <dt class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-1">Total des dépenses</dt>
                    <dd class="text-2xl font-bold text-red-600">{{ number_format($totalDepenses, 0, ',', ' ') }} MRU</dd>
                </div>
                <div class="{{ $solde >= 0 ? 'bg-green-50' : 'bg-red-50' }} rounded-xl p-5">
                    <dt class="text-xs font-semibold {{ $solde >= 0 ? 'text-green-700' : 'text-red-700' }} uppercase tracking-wide mb-1">Bilan</dt>
                    <dd class="text-2xl font-bold {{ $solde >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ number_format($solde, 0, ',', ' ') }} MRU</dd>
                </div>
            </div>

            {{-- Ventilation par mode de paiement --}}
            <h4 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">Ventilation par mode de paiement</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($totauxGenerauxParMoyenPaiement as $type => $totaux)
                <div class="border border-gray-200 rounded-xl p-4">
                    <div class="text-sm font-bold text-primary mb-3">{{ $type }}</div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Recettes</span>
                            <span class="font-semibold text-primary">{{ number_format($totaux['recettes'], 0, ',', ' ') }} MRU</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Dépenses</span>
                            <span class="font-semibold text-red-600">{{ number_format($totaux['depenses'], 0, ',', ' ') }} MRU</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-gray-100">
                            <span class="font-semibold text-gray-700">Solde</span>
                            <span class="font-bold {{ $totaux['solde'] >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ number_format($totaux['solde'], 0, ',', ' ') }} MRU</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Totaux par médecin --}}
    @if(count($totauxParMedecin) > 0)
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="bg-primary px-6 py-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <i class="fas fa-user-md"></i> Totaux par médecin
            </h3>
        </div>
        <div class="p-6 space-y-4">
            @foreach($totauxParMedecin as $medecinId => $totaux)
            <div class="border border-gray-200 rounded-xl p-4">
                <h4 class="text-sm font-bold text-primary mb-3">Dr. {{ $totaux['nom'] }}</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-primary-light rounded-lg p-4">
                        <dt class="text-xs text-primary font-semibold uppercase mb-1">Recettes</dt>
                        <dd class="text-xl font-bold text-primary">{{ number_format($totaux['recettes'], 0, ',', ' ') }} MRU</dd>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4">
                        <dt class="text-xs text-red-700 font-semibold uppercase mb-1">Dépenses</dt>
                        <dd class="text-xl font-bold text-red-600">{{ number_format($totaux['depenses'], 0, ',', ' ') }} MRU</dd>
                    </div>
                    <div class="{{ $totaux['solde'] >= 0 ? 'bg-green-50' : 'bg-red-50' }} rounded-lg p-4">
                        <dt class="text-xs {{ $totaux['solde'] >= 0 ? 'text-green-700' : 'text-red-700' }} font-semibold uppercase mb-1">Solde</dt>
                        <dd class="text-xl font-bold {{ $totaux['solde'] >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ number_format($totaux['solde'], 0, ',', ' ') }} MRU</dd>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Liste des opérations --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="bg-primary px-6 py-4 flex justify-between items-center">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <i class="fas fa-list"></i> Liste des opérations
                @if($isOwnOnly)
                <span class="badge badge-warning text-xs">Mes opérations uniquement</span>
                @endif
            </h3>
            <span class="text-white/70 text-sm">
                <i class="fas fa-calendar mr-1"></i>
                {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Médecin</th>
                        <th>Patient</th>
                        <th>Opération</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        @if($canDeleteFinances)
                        <th class="text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($operations as $operation)
                    <tr>
                        <td class="whitespace-nowrap">{{ \Carbon\Carbon::parse($operation->dateoper)->format('d/m/Y') }}</td>
                        <td>{{ $operation->medecin->Nom ?? '—' }}</td>
                        <td>{{ $operation->tiers->Nom ?? '—' }}</td>
                        <td>{{ $operation->designation }}</td>
                        <td class="font-semibold {{ $operation->entreEspece ? 'text-primary' : 'text-red-600' }} whitespace-nowrap">
                            {{ number_format($operation->MontantOperation, 0, ',', ' ') }} MRU
                        </td>
                        <td><span class="badge badge-neutral">{{ $operation->TypePAie ?? 'CASH' }}</span></td>
                        @if($canDeleteFinances)
                        <td class="text-right">
                            @if($operation->entreEspece > 0)
                            <button type="button"
                                    wire:click="confirmSupprimerRecette({{ $operation->cle }}, '{{ addslashes($operation->designation) }}')"
                                    class="btn-danger text-xs px-2 py-1">
                                <i class="fas fa-trash-alt"></i> Supprimer
                            </button>
                            @elseif($operation->retraitEspece > 0 && $canViewDepenses)
                            <button type="button"
                                    wire:click="confirmSupprimerDepense({{ $operation->cle }}, '{{ addslashes($operation->designation) }}')"
                                    class="btn-danger text-xs px-2 py-1">
                                <i class="fas fa-trash-alt"></i> Supprimer
                            </button>
                            @else
                            <span class="text-gray-400 text-sm">—</span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">{{ $operations->links() }}</div>
    </div>

    @else
    <div class="bg-white border border-gray-200 rounded-xl p-12 text-center text-gray-500">
        <i class="fas fa-cash-register text-3xl mb-3 block text-primary/40"></i>
        <p class="font-medium">Aucune opération trouvée</p>
        <p class="text-sm mt-1">Sélectionnez une date pour voir les opérations.</p>
    </div>
    @endif

    {{-- Modal confirmation suppression --}}
    @if($showConfirmDelete)
    <div class="modal-overlay" style="z-index:80">
        <div class="modal-box sm:max-w-md">
            <div class="modal-header" style="background:#dc2626">
                <h3><i class="fas fa-exclamation-triangle mr-2"></i>Confirmer la suppression</h3>
                <button type="button" wire:click="$set('showConfirmDelete', false)" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-trash-alt text-red-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">{{ $confirmDeleteLabel }}</p>
                        @if($confirmDeleteType === 'recette')
                        <p class="text-sm text-gray-500 mt-1">Le règlement sera <strong>annulé</strong> sur la facture associée. Cette action est <strong>irréversible</strong>.</p>
                        @else
                        <p class="text-sm text-gray-500 mt-1">Cette dépense sera supprimée définitivement. Cette action est <strong>irréversible</strong>.</p>
                        @endif
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button wire:click="$set('showConfirmDelete', false)"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                        Annuler
                    </button>
                    <button wire:click="executeDelete"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="executeDelete"><i class="fas fa-trash mr-1"></i> Supprimer</span>
                        <span wire:loading wire:target="executeDelete"><i class="fas fa-spinner fa-spin mr-1"></i> Suppression...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
