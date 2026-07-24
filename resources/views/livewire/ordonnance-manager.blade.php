<div>
    {{-- Messages Flash --}}
    @if (session()->has('message'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('message') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    @php $canCreate = Auth::user()->hasPermission('ordonnance.create'); @endphp

    {{-- Vue lecture seule pour les rôles sans permission de créer (ex: infirmier) --}}
    @if(!$canCreate)
    <div class="space-y-4">
        <div class="flex items-center gap-2 mb-2 px-1">
            <i class="fas fa-bolt text-red-400"></i>
            <span class="text-sm text-gray-600 font-medium">Traitements d'urgence prescrits pour ce patient</span>
        </div>

        {{-- Traitements d'urgence --}}
        <div class="bg-white rounded-xl border border-red-200 overflow-hidden">
            <button wire:click="toggleAccordeon('medicaments')"
                    class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-red-50 to-orange-50 hover:from-red-100 hover:to-orange-100 transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-bolt text-white text-sm"></i>
                    </div>
                    <div class="text-left">
                        <h5 class="font-semibold text-gray-800 text-sm">Traitements d'urgence</h5>
                        <p class="text-xs text-gray-500">{{ count($this->getOrdonnancesInternesByType(1)) }} prescription(s)</p>
                    </div>
                </div>
                <i class="fas fa-chevron-{{ $accordeonOuvert === 'medicaments' ? 'up' : 'down' }} text-gray-400 text-sm"></i>
            </button>
            @if($accordeonOuvert === 'medicaments')
            <div class="p-3 space-y-2">
                @forelse($this->getOrdonnancesInternesByType(1) as $item)
                <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                    <p class="text-xs text-gray-500 mb-2">
                        {{ \Carbon\Carbon::parse($item['ref']['dtPrescript'])->format('d/m/Y à H:i') }}
                        — Dr. {{ $item['ref']['prescripteur']['NomComplet'] ?? 'Inconnu' }}
                    </p>
                    @foreach($item['ordonnances'] as $ord)
                    <div class="text-sm mb-1 flex items-start gap-2">
                        <i class="fas fa-syringe text-red-400 mt-0.5 text-xs"></i>
                        <span>
                            <span class="font-medium text-gray-800">{{ $ord['Libelle'] }}</span>
                            @if($ord['Utilisation'])
                            <span class="text-gray-500 ml-1">— {{ $ord['Utilisation'] }}</span>
                            @endif
                        </span>
                    </div>
                    @endforeach
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-3">Aucun traitement d'urgence prescrit</p>
                @endforelse
            </div>
            @endif
        </div>
    </div>
    @else

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Formulaire de création --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-4 py-3 border-b border-gray-200">
                <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                    <svg class="h-5 w-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouvelle Ordonnance
                </h4>
            </div>

            <div class="p-4">
                <form wire:submit.prevent="sauvegarderOrdonnance">
                    {{-- Choix du mode : masqué si mode forcé --}}
                    @if(!$modeForce)
                    <div class="mb-4">
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button"
                                    wire:click="changerModeOrdonnance('urgence')"
                                    class="flex items-center justify-center gap-2 px-4 py-3 rounded-lg border-2 transition-all
                                        {{ $modeOrdonnance === 'urgence' ? 'border-red-500 bg-red-50 text-red-700 font-semibold' : 'border-gray-300 bg-white text-gray-600 hover:border-red-300' }}">
                                <i class="fas fa-bolt"></i>
                                <span class="text-sm">Traitement d'urgence</span>
                            </button>
                            <button type="button"
                                    wire:click="changerModeOrdonnance('sortie')"
                                    class="flex items-center justify-center gap-2 px-4 py-3 rounded-lg border-2 transition-all
                                        {{ $modeOrdonnance === 'sortie' ? 'border-blue-500 bg-blue-50 text-blue-700 font-semibold' : 'border-gray-300 bg-white text-gray-600 hover:border-blue-300' }}">
                                <i class="fas fa-file-prescription"></i>
                                <span class="text-sm">Ordonnance de sortie</span>
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Choix du type (Médicale / Analyses / Radios) --}}
                    @if(true)
                    <div class="mb-4">
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button"
                                    wire:click="changerTypeOrdonnance(1)"
                                    class="px-3 py-2 rounded-lg border-2 transition-all text-center {{ $typeOrdonnance == 1 ? 'border-green-600 bg-green-50 text-green-700 font-semibold' : 'border-gray-200 bg-white text-gray-600 hover:border-green-300' }}">
                                <i class="fas fa-pills mb-1 block text-sm"></i>
                                <span class="text-xs">Médicale</span>
                            </button>
                            <button type="button"
                                    wire:click="changerTypeOrdonnance(2)"
                                    class="px-3 py-2 rounded-lg border-2 transition-all text-center {{ $typeOrdonnance == 2 ? 'border-blue-600 bg-blue-50 text-blue-700 font-semibold' : 'border-gray-200 bg-white text-gray-600 hover:border-blue-300' }}">
                                <i class="fas fa-vial mb-1 block text-sm"></i>
                                <span class="text-xs">Analyses</span>
                            </button>
                            <button type="button"
                                    wire:click="changerTypeOrdonnance(3)"
                                    class="px-3 py-2 rounded-lg border-2 transition-all text-center {{ $typeOrdonnance == 3 ? 'border-purple-600 bg-purple-50 text-purple-700 font-semibold' : 'border-gray-200 bg-white text-gray-600 hover:border-purple-300' }}">
                                <i class="fas fa-x-ray mb-1 block text-sm"></i>
                                <span class="text-xs">Radios</span>
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Lignes de l'ordonnance --}}
                    <div class="space-y-3">
                        <div class="text-sm font-medium text-gray-700 mb-2">
                            @if($modeOrdonnance === 'urgence')
                                <span class="text-red-600"><i class="fas fa-bolt mr-1"></i>Traitement d'urgence</span>
                                — @if($typeOrdonnance == 1) Médicaments déduits du stock @elseif($typeOrdonnance == 2) Analyses @else Radiologie @endif
                            @else
                                {{ $this->typeOrdonnanceLibelle }} — Ordonnance de sortie
                            @endif
                        </div>
                        
                        @foreach($lignesOrdonnance as $index => $ligne)
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                            <div class="flex items-start space-x-2">
                                <div class="flex-shrink-0 w-8 h-8 {{ $typeOrdonnance == 1 ? 'bg-green-100 text-green-600' : ($typeOrdonnance == 2 ? 'bg-blue-100 text-blue-600' : 'bg-purple-100 text-purple-600') }} rounded-full flex items-center justify-center font-semibold text-sm mt-1">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1 space-y-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">
                                            @if($typeOrdonnance == 1)
                                                Médicament
                                            @elseif($typeOrdonnance == 2)
                                                Analyse
                                            @else
                                                Radio
                                            @endif
                                        </label>
                                        <div class="relative">
                                            @if(!empty($ligne['medicament_id']) && !empty($ligne['medicament_libelle']))
                                                {{-- Affichage du médicament sélectionné --}}
                                                <div class="flex items-center justify-between px-3 py-2 border rounded-lg
                                                    {{ !empty($ligne['libre']) ? 'border-amber-300 bg-amber-50' : (!empty($ligne['estInterne']) ? 'border-green-300 bg-green-50' : 'border-gray-300 bg-gray-50') }}">
                                                    <span class="text-sm text-gray-800 flex items-center gap-2">
                                                        @if(!empty($ligne['libre']))
                                                            <span class="text-xs text-amber-600 font-medium bg-amber-100 px-1.5 py-0.5 rounded">Libre</span>
                                                        @endif
                                                        {{ $ligne['medicament_libelle'] }}
                                                    </span>
                                                    <div class="flex items-center gap-2">
                                                        {{-- Badge mode --}}
                                                        @if($modeOrdonnance === 'urgence')
                                                            <span class="flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full bg-red-100 text-red-700 border border-red-300">
                                                                <i class="fas fa-bolt text-[9px]"></i> Urgence
                                                            </span>
                                                        @else
                                                            <span class="flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full bg-blue-100 text-blue-700 border border-blue-300">
                                                                <i class="fas fa-file-prescription text-[9px]"></i> Sortie
                                                            </span>
                                                        @endif
                                                        <button type="button"
                                                                wire:click="clearMedicamentSearch({{ $index }})"
                                                                class="text-gray-400 hover:text-red-600 transition-colors">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- Indicateur stock (médicaments urgence uniquement) --}}
                                                @if($modeOrdonnance === 'urgence' && $typeOrdonnance == 1 && !empty($ligne['medicament_id']) && empty($ligne['libre']) && isset($ligne['stock_quantite']))
                                                    @php $qte = (int)($ligne['stock_quantite'] ?? 0); @endphp
                                                    @if($qte > 0)
                                                        <div class="mt-1.5 flex items-center gap-1.5 text-xs text-green-700">
                                                            <i class="fas fa-boxes text-[10px]"></i>
                                                            <span>En stock : <strong>{{ $qte }}</strong> unité(s)
                                                                @if(!empty($ligne['stock_prix']))
                                                                    — <strong>{{ number_format($ligne['stock_prix'], 0) }} MRU</strong>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @else
                                                        <div class="mt-1.5 flex items-center gap-1.5 text-xs text-red-600">
                                                            <i class="fas fa-exclamation-triangle text-[10px]"></i>
                                                            <span>Stock épuisé — ne sera pas facturé</span>
                                                        </div>
                                                    @endif
                                                @endif
                                            @else
                                                {{-- Champ de recherche --}}
                                                <input type="text"
                                                       wire:model.live.debounce.300ms="searchTerms.{{ $index }}"
                                                       placeholder="@if($typeOrdonnance == 1)Rechercher un médicament...@elseif($typeOrdonnance == 2)Rechercher une analyse...@else Rechercher une radio...@endif"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 {{ $typeOrdonnance == 1 ? 'focus:ring-green-500 focus:border-green-500' : ($typeOrdonnance == 2 ? 'focus:ring-blue-500 focus:border-blue-500' : 'focus:ring-purple-500 focus:border-purple-500') }} text-sm">
                                                
                                                {{-- Indicateur de chargement --}}
                                                <div wire:loading class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 {{ $typeOrdonnance == 1 ? 'border-green-500' : ($typeOrdonnance == 2 ? 'border-blue-500' : 'border-purple-500') }}"></div>
                                                </div>
                                            @endif

                                            {{-- Résultats de recherche --}}
                                            @if(isset($showSearchResults[$index]) && $showSearchResults[$index] && count($searchResults[$index] ?? []) > 0)
                                                <div class="absolute z-50 w-full mt-1 border border-gray-200 rounded-lg shadow-lg bg-white max-h-60 overflow-y-auto">
                                                    @foreach($searchResults[$index] as $item)
                                                        <div wire:click="selectMedicament({{ $index }}, {{ $item['IDMedic'] }})"
                                                             class="px-3 py-2.5 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors flex items-center justify-between gap-2">
                                                            <span class="font-medium text-gray-900 text-sm">{{ $item['LibelleMedic'] }}</span>
                                                            @if($modeOrdonnance === 'urgence' && ($item['PrixRef'] ?? 0) > 0)
                                                                <span class="text-xs text-gray-400 shrink-0">{{ number_format($item['PrixRef'], 0) }} MRU</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- Aucun résultat : proposer saisie libre --}}
                                            @if(isset($showSearchResults[$index]) && $showSearchResults[$index] && count($searchResults[$index] ?? []) === 0 && strlen(trim($searchTerms[$index] ?? '')) > 0)
                                                <div class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                                    <div class="px-3 py-2 text-xs text-gray-500 border-b border-gray-100 bg-gray-50">
                                                        Aucun résultat trouvé pour <span class="font-medium text-gray-700">"{{ $searchTerms[$index] }}"</span>
                                                    </div>
                                                    <button type="button"
                                                        wire:mousedown="selectLibre({{ $index }})"
                                                        class="w-full flex items-center gap-2 px-3 py-2.5 text-sm font-medium text-primary hover:bg-primary-light transition-colors text-left">
                                                        <i class="fas fa-plus-circle"></i>
                                                        Utiliser "{{ $searchTerms[$index] }}" tel quel
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                        @error('lignesOrdonnance.' . $index . '.medicament_id')
                                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    @if($typeOrdonnance == 1)
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Quantité</label>
                                        <input type="number"
                                               wire:model="lignesOrdonnance.{{ $index }}.quantite"
                                               min="1"
                                               value="1"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm">
                                    </div>
                                    @endif
                                    <div>
                                        @if($typeOrdonnance == 3)
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Demande</label>
                                        <textarea wire:model="lignesOrdonnance.{{ $index }}.posologie"
                                                  placeholder="Ex: Radio du thorax de face et profil..."
                                                  rows="3"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm resize-y"></textarea>
                                        @else
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Posologie / Instructions</label>
                                        <input type="text"
                                               wire:model="lignesOrdonnance.{{ $index }}.posologie"
                                               placeholder="@if($typeOrdonnance == 1)Ex: 1 comprimé matin et soir...@elseif($typeOrdonnance == 2)Ex: À jeun, le matin...@endif"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 {{ $typeOrdonnance == 1 ? 'focus:ring-green-500 focus:border-green-500' : 'focus:ring-blue-500 focus:border-blue-500' }} text-sm">
                                        @endif
                                    </div>
                                </div>
                                <button type="button"
                                        wire:click="supprimerLigne({{ $index }})"
                                        @if(count($lignesOrdonnance) <= 1) disabled @endif
                                        class="flex-shrink-0 text-red-500 hover:text-red-700 disabled:text-gray-400 disabled:cursor-not-allowed transition-colors mt-1">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @endforeach

                        <button type="button"
                                wire:click="ajouterLigneVide"
                                class="w-full px-4 py-2 {{ $typeOrdonnance == 1 ? 'bg-green-50 text-green-700 hover:bg-green-100' : ($typeOrdonnance == 2 ? 'bg-blue-50 text-blue-700 hover:bg-blue-100' : 'bg-purple-50 text-purple-700 hover:bg-purple-100') }} rounded-lg transition-colors flex items-center justify-center space-x-2 text-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Ajouter une ligne</span>
                        </button>
                    </div>

                    {{-- Bouton d'enregistrement --}}
                    <div class="mt-4">
                        <button type="submit"
                                class="w-full px-4 py-2 {{ $typeOrdonnance == 1 ? 'bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800' : ($typeOrdonnance == 2 ? 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800' : 'bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800') }} text-white rounded-lg transition-all shadow-md hover:shadow-lg flex items-center justify-center space-x-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Enregistrer {{ $this->typeOrdonnanceLibelle }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Liste des ordonnances avec accordéons --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-4 py-3 border-b border-gray-200">
                <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                    <svg class="h-5 w-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Ordonnances du Patient
                </h4>
            </div>

            <div class="p-4 max-h-[600px] overflow-y-auto">
                {{-- Accordéon Médicaments --}}
                <div class="mb-3">
                    <button wire:click="toggleAccordeon('medicaments')"
                            class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-green-50 to-emerald-50 hover:from-green-100 hover:to-emerald-100 rounded-lg transition-all border border-green-200">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-pills text-white"></i>
                            </div>
                            <div class="text-left">
                                <h5 class="font-semibold text-gray-800">Ordonnances Médicales</h5>
                                <p class="text-xs text-gray-600">
                                    {{ count($this->getOrdonnancesByType(1)) }} ordonnance(s)
                                </p>
                            </div>
                        </div>
                        <svg class="h-5 w-5 text-gray-600 transform transition-transform {{ $accordeonOuvert === 'medicaments' ? 'rotate-180' : '' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    @if($accordeonOuvert === 'medicaments')
                    <div class="mt-2 space-y-2">
                        @forelse($this->getOrdonnancesByType(1) as $item)
                        <div class="bg-white border border-green-200 rounded-lg p-3 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-2">
                                        {{ \Carbon\Carbon::parse($item['ref']['dtPrescript'])->format('d/m/Y à H:i') }}
                                        - Dr. {{ $item['ref']['prescripteur']['NomComplet'] ?? 'Inconnu' }}
                                        - Ref: {{ $item['ref']['refOrd'] }}
                                    </p>
                                    <div class="space-y-1">
                                        @foreach($item['ordonnances'] as $ord)
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-800">{{ $ord['Libelle'] }}</span>
                                            @if(($ord['Quantite'] ?? 1) > 1)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700 ml-1">x{{ $ord['Quantite'] }}</span>
                                            @endif
                                            @if($ord['Utilisation'])
                                            <span class="text-gray-600 ml-2">- {{ $ord['Utilisation'] }}</span>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <button wire:click="imprimerOrdonnance({{ $item['ref']['id'] }})"
                                        class="ml-3 text-green-600 hover:text-green-800 transition-colors">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500 text-center py-4">Aucune ordonnance médicale</p>
                        @endforelse
                    </div>
                    @endif
                </div>

                {{-- Accordéon Analyses --}}
                <div class="mb-3">
                    <button wire:click="toggleAccordeon('analyses')"
                            class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-blue-50 to-cyan-50 hover:from-blue-100 hover:to-cyan-100 rounded-lg transition-all border border-blue-200">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-vial text-white"></i>
                            </div>
                            <div class="text-left">
                                <h5 class="font-semibold text-gray-800">Ordonnances d'Analyses</h5>
                                <p class="text-xs text-gray-600">
                                    {{ count($this->getOrdonnancesByType(2)) }} ordonnance(s)
                                </p>
                            </div>
                        </div>
                        <svg class="h-5 w-5 text-gray-600 transform transition-transform {{ $accordeonOuvert === 'analyses' ? 'rotate-180' : '' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    @if($accordeonOuvert === 'analyses')
                    <div class="mt-2 space-y-2">
                        @forelse($this->getOrdonnancesByType(2) as $item)
                        <div class="bg-white border border-blue-200 rounded-lg p-3 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-2">
                                        {{ \Carbon\Carbon::parse($item['ref']['dtPrescript'])->format('d/m/Y à H:i') }}
                                        - Dr. {{ $item['ref']['prescripteur']['NomComplet'] ?? 'Inconnu' }}
                                        - Ref: {{ $item['ref']['refOrd'] }}
                                    </p>
                                    <div class="space-y-1">
                                        @foreach($item['ordonnances'] as $ord)
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-800">{{ $ord['Libelle'] }}</span>
                                            @if(($ord['Quantite'] ?? 1) > 1)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700 ml-1">x{{ $ord['Quantite'] }}</span>
                                            @endif
                                            @if($ord['Utilisation'])
                                            <span class="text-gray-600 ml-2">- {{ $ord['Utilisation'] }}</span>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <button wire:click="imprimerOrdonnance({{ $item['ref']['id'] }})"
                                        class="ml-3 text-blue-600 hover:text-blue-800 transition-colors">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500 text-center py-4">Aucune ordonnance d'analyses</p>
                        @endforelse
                    </div>
                    @endif
                </div>

                {{-- Accordéon Radios --}}
                <div class="mb-3">
                    <button wire:click="toggleAccordeon('radios')"
                            class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-purple-50 to-pink-50 hover:from-purple-100 hover:to-pink-100 rounded-lg transition-all border border-purple-200">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-x-ray text-white"></i>
                            </div>
                            <div class="text-left">
                                <h5 class="font-semibold text-gray-800">Ordonnances de Radiologie</h5>
                                <p class="text-xs text-gray-600">
                                    {{ count($this->getOrdonnancesByType(3)) }} ordonnance(s)
                                </p>
                            </div>
                        </div>
                        <svg class="h-5 w-5 text-gray-600 transform transition-transform {{ $accordeonOuvert === 'radios' ? 'rotate-180' : '' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    @if($accordeonOuvert === 'radios')
                    <div class="mt-2 space-y-2">
                        @forelse($this->getOrdonnancesByType(3) as $item)
                        <div class="bg-white border border-purple-200 rounded-lg p-3 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-2">
                                        {{ \Carbon\Carbon::parse($item['ref']['dtPrescript'])->format('d/m/Y à H:i') }}
                                        - Dr. {{ $item['ref']['prescripteur']['NomComplet'] ?? 'Inconnu' }}
                                        - Ref: {{ $item['ref']['refOrd'] }}
                                    </p>
                                    <div class="space-y-1">
                                        @foreach($item['ordonnances'] as $ord)
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-800">{{ $ord['Libelle'] }}</span>
                                            @if(($ord['Quantite'] ?? 1) > 1)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700 ml-1">x{{ $ord['Quantite'] }}</span>
                                            @endif
                                            @if($ord['Utilisation'])
                                            <span class="text-gray-600 ml-2">- {{ $ord['Utilisation'] }}</span>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <button wire:click="imprimerOrdonnance({{ $item['ref']['id'] }})"
                                        class="ml-3 text-purple-600 hover:text-purple-800 transition-colors">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500 text-center py-4">Aucune ordonnance de radiologie</p>
                        @endforelse
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Script pour ouvrir les ordonnances dans un nouvel onglet (identique à la logique du reçu de consultation) --}}
    {{-- L'écouteur open-receipt est géré globalement dans le layout principal pour éviter les doublons --}}
</div>
