<div class="p-4 md:p-6 space-y-6">

    {{-- Filtres --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-3">
            <i class="fas fa-filter mr-1"></i> Filtres
        </h3>

        @if($date_debut || $date_fin)
        <div class="mb-4 flex items-center gap-2 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-sm">
            <i class="fas fa-calendar-alt"></i>
            <span>Période :
                @if($date_debut && $date_fin) Du {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($date_fin)->format('d/m/Y') }}
                @elseif($date_debut) À partir du {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }}
                @elseif($date_fin) Jusqu'au {{ \Carbon\Carbon::parse($date_fin)->format('d/m/Y') }}
                @endif
            </span>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Médecin</label>
                <select wire:model.live="medecin_id" class="form-select">
                    <option value="">Tous les médecins</option>
                    @foreach($medecins as $medecin)
                    <option value="{{ $medecin->idMedecin }}">{{ $medecin->Nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                <input type="date" wire:model.live="date_debut" class="form-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                <input type="date" wire:model.live="date_fin" class="form-input">
            </div>
            <button wire:click="resetFilters" class="btn-secondary">
                <i class="fas fa-undo mr-1"></i> Réinitialiser
            </button>
        </div>
    </div>

    @if($operations->count() > 0)

    {{-- Totaux généraux --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="bg-primary px-6 py-4 flex justify-between items-center">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <i class="fas fa-chart-line"></i> Totaux généraux
                <span class="text-sm font-normal text-white/70">({{ $operations->total() }} opérations)</span>
            </h3>
            @if($canPrintEtat)
            <a href="{{ route('caisse.etat-journalier', ['date' => $date_debut]) }}" target="_blank"
               class="text-sm text-white/80 hover:text-white border border-white/30 px-3 py-1 rounded-lg hover:bg-white/10 transition-colors">
                <i class="fas fa-print mr-1"></i> Imprimer l'état
            </a>
            @endif
        </div>
        <div class="p-6">
            @if(!$date_debut && !$date_fin)
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-calendar-plus text-3xl mb-3 block text-primary/40"></i>
                <p class="font-medium">Sélectionnez une période</p>
                <p class="text-sm mt-1">Choisissez une date de début et/ou de fin pour voir les statistiques.</p>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-primary-light rounded-xl p-5">
                    <dt class="text-xs font-semibold text-primary uppercase tracking-wide mb-1">Total des recettes</dt>
                    <dd class="text-2xl font-bold text-primary">{{ number_format($totalRecettes, 0, ',', ' ') }} MRU</dd>
                </div>
                <div class="bg-red-50 rounded-xl p-5">
                    <dt class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-1">Total des dépenses</dt>
                    <dd class="text-2xl font-bold text-red-600">{{ number_format($totalDepenses, 0, ',', ' ') }} MRU</dd>
                </div>
                <div class="rounded-xl p-5 {{ $solde >= 0 ? 'bg-green-50' : 'bg-red-50' }}">
                    <dt class="text-xs font-semibold {{ $solde >= 0 ? 'text-green-700' : 'text-red-700' }} uppercase tracking-wide mb-1">Bilan</dt>
                    <dd class="text-2xl font-bold {{ $solde >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ number_format($solde, 0, ',', ' ') }} MRU</dd>
                </div>
            </div>

            @if(count($totauxGenerauxParMoyenPaiement) > 0)
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
            @endif
            @endif
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
                <span class="badge badge-warning text-xs ml-2">Mes opérations uniquement</span>
                @endif
            </h3>
            <span class="text-white/70 text-sm">
                <i class="fas fa-calendar mr-1"></i> {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }}
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
                    </tr>
                </thead>
                <tbody>
                    @foreach($operations as $operation)
                    <tr>
                        <td class="whitespace-nowrap">{{ \Carbon\Carbon::parse($operation->dateoper)->format('d/m/Y') }}</td>
                        <td>{{ $medecins->firstWhere('idMedecin', $operation->fkidmedecin)->Nom ?? ($operation->medecin ?? '—') }}</td>
                        <td>{{ $operation->tiers->Nom ?? '—' }}</td>
                        <td>{{ $operation->designation }}</td>
                        <td class="font-semibold whitespace-nowrap {{ $operation->retraitEspece > 0 ? 'text-red-600' : 'text-primary' }}">
                            @if($operation->retraitEspece > 0)
                                <i class="fas fa-arrow-down text-xs mr-1"></i>
                            @else
                                <i class="fas fa-arrow-up text-xs mr-1"></i>
                            @endif
                            {{ number_format($operation->MontantOperation, 0, ',', ' ') }} MRU
                        </td>
                        <td><span class="badge badge-neutral">{{ $operation->TypePAie ?? 'CASH' }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">{{ $operations->links() }}</div>
    </div>

    @else
    <div class="bg-white border border-gray-200 rounded-xl p-12 text-center text-gray-500">
        <i class="fas fa-info-circle text-3xl mb-3 block text-primary/40"></i>
        <p class="font-medium">Aucune opération trouvée</p>
        <p class="text-sm mt-1">Ajustez vos filtres pour voir les opérations.</p>
    </div>
    @endif

</div>
