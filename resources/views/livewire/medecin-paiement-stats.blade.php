<div class="min-h-screen bg-gray-50">
    @if($isSecretaire)
        <div class="max-w-2xl mx-auto bg-white shadow-xl rounded-2xl p-8 mt-8">
            <div class="text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-lock text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">Accès restreint</h3>
                <p class="text-gray-600 text-lg">Vous n'avez pas les permissions nécessaires pour accéder à cette section.</p>
            </div>
        </div>
    @else
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-6xl mx-auto space-y-8">
                <!-- Carte des Recettes et Dépenses -->
                <div class="bg-white shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl">
                    <div class="bg-gradient-to-r from-indigo-600 via-blue-500 to-indigo-600 px-8 py-6">
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <i class="fas fa-chart-line mr-3"></i>
                            Recettes et Dépenses
                        </h2>
                    </div>
                    
                    <div class="p-8">
                        <!-- Section de recherche -->
                        <div class="mb-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @if($canSelectMedecin)
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Médecin</label>
                                    <select wire:model.live="medecin_id" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                        <option value="">Tous les médecins</option>
                                        @foreach($medecinsList as $medecin)
                                            <option value="{{ $medecin['idMedecin'] }}">{{ $medecin['Nom'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Période</label>
                                    <select wire:model.live="periode" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                        <option value="all">Toutes les dates</option>
                                        <option value="day">Aujourd'hui</option>
                                        <option value="week">Cette semaine</option>
                                        <option value="month">Ce mois</option>
                                        <option value="year">Cette année</option>
                                        <option value="custom">Personnalisé</option>
                                    </select>
                                </div>
                            </div>

                            @if($periode === 'custom')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date de début</label>
                                    <input type="date" wire:model.live="date_debut" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                </div>
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date de fin</label>
                                    <input type="date" wire:model.live="date_fin" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Résultats -->
                        @if(count($stats) > 0)
                            <!-- Totaux globaux -->
                            <div class="mb-8">
                                <h3 class="text-xl font-bold text-gray-800 mb-6">Totaux globaux</h3>
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-{{ $showExpenses ? '3' : '2' }} mb-8">
                                    <div class="bg-gradient-to-br from-indigo-50 to-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="text-sm font-semibold text-gray-600 mb-2">Total des recettes</dt>
                                        <dd class="text-3xl font-bold text-gray-900">{{ number_format($totalRecettes, 2, '.', ' ') }} UM</dd>
                                    </div>
                                    
                                    @if($showExpenses)
                                    <div class="bg-gradient-to-br from-indigo-50 to-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="text-sm font-semibold text-gray-600 mb-2">Total des dépenses</dt>
                                        <dd class="text-3xl font-bold text-gray-900">{{ number_format($totalDepenses, 2, '.', ' ') }} UM</dd>
                                    </div>
                                    @endif
                                    
                                    <div class="bg-gradient-to-br from-indigo-50 to-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
                                        <dt class="text-sm font-semibold text-gray-600 mb-2">Bilan</dt>
                                        <dd class="text-3xl font-bold {{ $totalRecettes - $totalDepenses >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($totalRecettes - $totalDepenses, 2, '.', ' ') }} UM
                                        </dd>
                                    </div>
                                </div>
                            </div>

                            <!-- Totaux par médecin -->
                            <div class="mb-8">
                                <h3 class="text-xl font-bold text-gray-800 mb-6">Totaux par médecin</h3>
                                <div class="grid grid-cols-1 gap-6">
                                    @foreach($stats as $medecin => $data)
                                        <div class="bg-white border border-gray-100 rounded-xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
                                            <div class="flex items-center justify-between mb-4">
                                                <div class="text-lg font-bold text-indigo-700">{{ $medecin }}</div>
                                                <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm font-semibold">
                                                    {{ $data['pourcentage'] }}%
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-1 sm:grid-cols-{{ $showExpenses ? '3' : '2' }} gap-4">
                                                <div>
                                                    <dt class="text-sm font-semibold text-gray-600 mb-1">Recettes</dt>
                                                    <dd class="text-2xl font-bold text-gray-900">{{ number_format($data['total_recettes'], 2, '.', ' ') }} UM</dd>
                                                </div>
                                                @if($showExpenses)
                                                <div>
                                                    <dt class="text-sm font-semibold text-gray-600 mb-1">Dépenses</dt>
                                                    <dd class="text-2xl font-bold text-gray-900">{{ number_format($data['total_depenses'], 2, '.', ' ') }} UM</dd>
                                                </div>
                                                @endif
                                                <div>
                                                    <dt class="text-sm font-semibold text-gray-600 mb-1">Bilan</dt>
                                                    <dd class="text-2xl font-bold {{ $data['total_recettes'] - $data['total_depenses'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ number_format($data['total_recettes'] - $data['total_depenses'], 2, '.', ' ') }} UM
                                                    </dd>
                                                </div>
                                            </div>
                                            <div class="mt-4 text-sm text-gray-500">
                                                {{ $data['nombre_operations'] }} opération(s)
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-chart-bar text-gray-400 text-2xl"></i>
                                </div>
                                <p class="text-gray-600 text-lg">
                                    @if($isMedecin)
                                        Vous n'avez pas encore de recettes enregistrées pour cette période.
                                    @elseif($isProprietaire)
                                        Aucune donnée disponible pour cette période.
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Carte des Rendez-vous -->
                <div class="bg-white shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-300 hover:shadow-3xl">
                    <div class="bg-gradient-to-r from-emerald-600 via-green-500 to-emerald-600 px-8 py-6">
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <i class="fas fa-calendar-alt mr-3"></i>
                            Rendez-vous du {{ \Carbon\Carbon::parse($rdv_date)->format('d/m/Y') }}
                        </h2>
                    </div>
                    
                    <div class="p-8">
                        <!-- Filtres -->
                        <div class="mb-8">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Médecin</label>
                                    <select wire:model.live="rdv_medecin_id" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" @if(!$canViewAllRdv) disabled @endif>
                                        <option value="">Tous les médecins</option>
                                        @foreach($medecins as $medecin)
                                            <option value="{{ $medecin->idMedecin }}">{{ $medecin->Nom }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
                                    <input type="date" wire:model.live="rdv_date" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Options</label>
                                    <div class="flex items-center mt-2">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" wire:model.live="showPastRdv" class="form-checkbox h-5 w-5 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                                            <span class="ml-2 text-gray-700">Afficher les rendez-vous passés</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tableau des rendez-vous -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <div class="flex items-center">
                                                <input type="checkbox" wire:model.live="selectAll" class="form-checkbox h-4 w-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                                            </div>
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Médecin</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Heure</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acte prévu</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($RendezVous as $rdv)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <input type="checkbox" wire:model.live="selectedRendezVous" value="{{ $rdv->IDRdv }}" class="form-checkbox h-4 w-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $rdv->medecin->Nom ?? 'Non assigné' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $rdv->patient->Prenom ?? 'Non assigné' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    {{ $rdv->HeureRdv ? \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') : '' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $rdv->ActePrevu ?? 'Non spécifié' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $rdv->rdvConfirmer == 'annulé' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                                    {{ $rdv->rdvConfirmer ?? 'Non confirmé' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                @if($canManageRdv)
                                                    <div class="flex items-center justify-center space-x-2">
                                                        @if($rdv->rdvConfirmer !== 'confirmé')
                                                            <button wire:click="confirmerRendezVous({{ $rdv->IDRdv }})" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" title="Confirmer le rendez-vous">
                                                                <i class="fas fa-check mr-1"></i>
                                                                Confirmer
                                                            </button>
                                                        @endif
                                                        @if($rdv->rdvConfirmer !== 'annulé')
                                                            <button wire:click="annulerRendezVous({{ $rdv->IDRdv }})" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500" title="Annuler le rendez-vous">
                                                                <i class="fas fa-times mr-1"></i>
                                                                Annuler
                                                            </button>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-gray-400">
                                                        <i class="fas fa-lock"></i>
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-6 py-12 text-center">
                                                <div class="text-gray-500">
                                                    <i class="fas fa-calendar-times text-4xl mb-4"></i>
                                                    @if($showPastRdv)
                                                        <p class="text-lg font-medium">Aucun rendez-vous trouvé</p>
                                                        <p class="mt-2">Les rendez-vous existants sont datés de 2021-2022.</p>
                                                        <p class="text-sm mt-1">Veuillez sélectionner une date dans cette période.</p>
                                                    @else
                                                        <p class="text-lg font-medium">Aucun rendez-vous futur</p>
                                                        <p class="mt-2">Vous pouvez afficher les rendez-vous passés en activant l'option ci-dessus.</p>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Bouton d'annulation en masse -->
                        @if($canManageRdv && count($selectedRendezVous) > 0)
                            <div class="mt-6">
                                <button wire:click="annulerSelection" class="w-full md:w-auto px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i>
                                    Annuler la sélection
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>