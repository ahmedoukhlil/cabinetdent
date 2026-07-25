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

    {{-- Schéma dentaire (FDI), rendu via le composant React react-odontogram --}}
    <div class="border border-gray-200 rounded-xl p-3 bg-white overflow-x-auto">
        <div class="flex justify-between items-center mb-2 flex-wrap gap-2">
            <div class="flex items-center flex-wrap gap-2">
                @unless($modeObservationsSeules)
                <button type="button" wire:click="toggleModeMultiSelection"
                        class="text-xs flex items-center gap-1 px-2 py-1 rounded-lg
                            {{ $modeMultiSelection ? 'bg-primary text-white' : 'text-gray-500 hover:text-primary' }}">
                    <i class="fas fa-check-double"></i>
                    {{ $modeMultiSelection ? 'Quitter la sélection multiple' : 'Sélection multiple (ex: détartrage)' }}
                </button>

                @unless($modeMultiSelection)
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open"
                            class="text-xs flex items-center gap-1 px-2 py-1 rounded-lg text-gray-500 hover:text-primary">
                        <i class="fas fa-layer-group"></i>
                        Choisir une zone
                        <i class="fas fa-chevron-down text-[10px]"></i>
                    </button>
                    <div x-show="open" x-cloak @click="open = false"
                         class="absolute z-20 mt-1 w-48 bg-white border border-gray-200 rounded-lg shadow-lg py-1">
                        <button type="button" wire:click="ouvrirActeSelectorZone('hemi_sup_droite')"
                                class="w-full text-left px-3 py-1.5 text-xs text-gray-600 hover:bg-primary-light hover:text-primary">
                            Hémi sup. droite
                        </button>
                        <button type="button" wire:click="ouvrirActeSelectorZone('hemi_sup_gauche')"
                                class="w-full text-left px-3 py-1.5 text-xs text-gray-600 hover:bg-primary-light hover:text-primary">
                            Hémi sup. gauche
                        </button>
                        <button type="button" wire:click="ouvrirActeSelectorZone('hemi_inf_droite')"
                                class="w-full text-left px-3 py-1.5 text-xs text-gray-600 hover:bg-primary-light hover:text-primary">
                            Hémi inf. droite
                        </button>
                        <button type="button" wire:click="ouvrirActeSelectorZone('hemi_inf_gauche')"
                                class="w-full text-left px-3 py-1.5 text-xs text-gray-600 hover:bg-primary-light hover:text-primary">
                            Hémi inf. gauche
                        </button>
                        <div class="border-t border-gray-100 my-1"></div>
                        <button type="button" wire:click="ouvrirActeSelectorZone('arcade_sup')"
                                class="w-full text-left px-3 py-1.5 text-xs text-gray-600 hover:bg-primary-light hover:text-primary">
                            Arcade supérieure
                        </button>
                        <button type="button" wire:click="ouvrirActeSelectorZone('arcade_inf')"
                                class="w-full text-left px-3 py-1.5 text-xs text-gray-600 hover:bg-primary-light hover:text-primary">
                            Arcade inférieure
                        </button>
                        <div class="border-t border-gray-100 my-1"></div>
                        <button type="button" wire:click="ouvrirActeSelectorZone('bouche_entiere')"
                                class="w-full text-left px-3 py-1.5 text-xs text-gray-600 hover:bg-primary-light hover:text-primary">
                            Toute la bouche
                        </button>
                    </div>
                </div>
                @endunless
                @endunless
            </div>
            <button type="button" wire:click="basculerDentition"
                    class="text-xs text-gray-500 hover:text-primary flex items-center gap-1">
                <i class="fas fa-baby"></i>
                {{ $dentitionMode === 'lait' ? 'Voir dentition adulte' : 'Voir dentition de lait' }}
            </button>
        </div>

        @if($modeMultiSelection)
        <div class="flex items-center justify-between gap-2 mb-3 p-2 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center gap-2">
                <button type="button" wire:click="selectionnerToutesLesDents" class="text-xs text-primary hover:underline">
                    Toutes les dents
                </button>
                <span class="text-gray-300">|</span>
                <button type="button" wire:click="deselectionnerToutesLesDents" class="text-xs text-gray-500 hover:underline">
                    Aucune
                </button>
                <span class="text-xs text-gray-600">{{ count($dentsSelectionnees) }} dent(s) sélectionnée(s)</span>
            </div>
            <button type="button" wire:click="ouvrirActeSelectorMultiple"
                    @disabled(count($dentsSelectionnees) === 0)
                    class="px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary-dark disabled:opacity-40 disabled:cursor-not-allowed">
                <i class="fas fa-plus mr-1"></i> Ajouter un acte à la sélection
            </button>
        </div>
        @endif

        <div wire:ignore>
            <div
                data-odontogram-root
                data-wire-id="{{ $this->getId() }}"
                data-dentition-mode="{{ $dentitionMode }}"
                data-conditions="{{ json_encode($modeObservationsSeules ? [] : $conditionsParDent) }}"
                data-mode-multi-selection="{{ $modeMultiSelection ? 'true' : 'false' }}"
                data-dents-selectionnees="{{ json_encode($dentsSelectionnees) }}"
                style="width: 100%; max-width: 360px; max-height: 320px; margin: 0 auto;"
            ></div>
        </div>

        @unless($modeObservationsSeules)
        <div class="flex justify-center gap-4 mt-4 text-xs text-gray-500">
            <span><span class="inline-block w-3 h-3 bg-gray-200 border border-gray-500 rounded mr-1"></span>Planifié</span>
            <span><span class="inline-block w-3 h-3 bg-orange-200 border border-orange-600 rounded mr-1"></span>En cours</span>
            <span><span class="inline-block w-3 h-3 bg-green-200 border border-green-600 rounded mr-1"></span>Terminé</span>
        </div>
        @endunless
    </div>

    {{-- Modale d'ajout d'un acte / observation sur la dent sélectionnée --}}
    @if($showActeSelector && !$modeObservationsSeules)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="fermerActeSelector">
    <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto border-l-4 border-primary">
        <div class="sticky top-0 bg-primary-light flex items-center justify-between mb-0 p-4 border-b border-primary/20">
            <h3 class="text-sm font-semibold text-primary uppercase tracking-wide">
                <i class="fas fa-tooth mr-1"></i>
                @if($zoneSelectionnee)
                    Ajouter un acte — {{ \App\Http\Livewire\PlanTraitementDentaire::LIBELLES_ZONE[$zoneSelectionnee] ?? '' }}
                @elseif($modeMultiSelection && count($dentsSelectionnees) > 0)
                    Ajouter un acte — {{ count($dentsSelectionnees) }} dents ({{ implode(', ', $dentsSelectionnees) }})
                @else
                    Ajouter un acte — Dent {{ $dentSelectionnee }}
                    @if($this->nomDent($dentSelectionnee))
                        <span class="text-primary/70 normal-case font-normal">({{ $this->nomDent($dentSelectionnee) }})</span>
                    @endif
                @endif
            </h3>
            <button type="button" wire:click="fermerActeSelector" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-4">
        @unless($modeObservationsSeules)
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="searchActe"
                   placeholder="Rechercher un acte..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">

            @if(count($filteredActes) > 0 && !$selectedActeId)
            <div class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-y-auto">
                @foreach($filteredActes as $acte)
                <button type="button" wire:click="selectActe({{ $acte->ID }})"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-primary-light border-b border-gray-100 last:border-0">
                    <span class="font-medium">{{ $acte->Acte }}</span>
                    <span class="text-gray-500 float-right">{{ number_format($acte->PrixRef, 0, ',', ' ') }} MRU</span>
                </button>
                @endforeach
            </div>
            @endif
        </div>

        @if($selectedActeId)
        <div class="mt-3 flex items-center justify-between bg-white border border-gray-200 rounded-lg px-3 py-2">
            <div>
                <span class="text-xs text-gray-500">Acte sélectionné :</span>
                <span class="font-medium text-sm">{{ \App\Models\Acte::find($selectedActeId)->Acte ?? '' }}</span>
            </div>
            <span class="text-primary font-semibold text-sm">{{ number_format($prixRef, 0, ',', ' ') }} MRU</span>
        </div>
        <div class="mt-3 flex justify-end gap-2">
            <button type="button" wire:click="selectActe(null)" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                Changer
            </button>
            <button type="button" wire:click="ajouterActeAuPlan" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark flex items-center gap-1">
                <i class="fas fa-plus"></i> Ajouter au plan
            </button>
        </div>
        @endif
        @endunless

        {{-- Historique de la dent (actes + observations) — non pertinent en sélection multiple --}}
        @unless($modeMultiSelection && count($dentsSelectionnees) > 1)
        <div class="mt-4 pt-4 border-t border-primary/20">
            <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2 flex items-center gap-1">
                <i class="fas fa-history"></i> Historique de la dent
            </h4>

            @if(count($historiqueDent) === 0)
                <p class="text-sm text-gray-400 italic py-2">Aucun historique pour cette dent.</p>
            @else
                <div class="space-y-2 max-h-56 overflow-y-auto mb-3">
                    @foreach($historiqueDent as $item)
                    <div class="flex items-start gap-2 bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        @if($item['type'] === 'acte')
                            <i class="fas fa-tooth text-primary mt-0.5"></i>
                            <div class="flex-1">
                                <div class="font-medium">{{ $item['libelle'] }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i') }}
                                    @if($item['medecin']) · Dr. {{ $item['medecin'] }} @endif
                                    ·
                                    @php
                                        $badge = match($item['statut']) {
                                            'termine' => ['Terminé', 'text-green-700'],
                                            'en_cours' => ['En cours', 'text-orange-700'],
                                            default => ['Planifié', 'text-gray-600'],
                                        };
                                    @endphp
                                    <span class="{{ $badge[1] }} font-medium">{{ $badge[0] }}</span>
                                </div>
                            </div>
                        @else
                            <i class="fas fa-sticky-note text-yellow-500 mt-0.5"></i>
                            <div class="flex-1">
                                <div class="text-gray-700 whitespace-pre-line">{{ $item['texte'] }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i') }}
                                    @if($item['medecin']) · Dr. {{ $item['medecin'] }} @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif

            <div class="flex gap-2">
                <textarea wire:model="nouvelleObservation" rows="2" placeholder="Ajouter une observation..."
                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-none"></textarea>
                <button type="button" wire:click="ajouterObservation"
                    class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark flex items-center gap-1 self-start">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            @error('nouvelleObservation')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
        </div>
        @endunless
        </div>
    </div>
    </div>
    @endif

    {{-- Liste des lignes du plan de traitement --}}
    @unless($modeObservationsSeules)
    <div class="space-y-2">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Plan de traitement</h3>

        @forelse($lignesPlan as $ligne)
        <div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg px-4 py-3">
            @php $dentsLigne = explode(',', $ligne->num_dent); @endphp
            <div class="flex items-center gap-3">
                @if(count($dentsLigne) > 1)
                <span class="w-10 h-10 rounded-lg bg-primary-light text-primary font-bold flex items-center justify-center text-[10px] text-center leading-tight px-1">
                    {{ count($dentsLigne) }} dents
                </span>
                @else
                <span class="w-10 h-10 rounded-lg bg-primary-light text-primary font-bold flex items-center justify-center text-sm">
                    {{ $ligne->num_dent }}
                </span>
                @endif
                <div>
                    <div class="font-medium text-sm">
                        {{ $ligne->acte_libelle }}
                        @if(count($dentsLigne) > 1)
                            <span class="text-xs text-gray-400 font-normal">({{ implode(', ', $dentsLigne) }})</span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ $ligne->medecin->Nom ?? '—' }} ·
                        @if($ligne->prix_ref) {{ number_format($ligne->prix_ref, 0, ',', ' ') }} MRU @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @php
                    $badgeCouleur = match($ligne->statut) {
                        'termine' => 'bg-green-100 text-green-800',
                        'en_cours' => 'bg-orange-100 text-orange-800',
                        default => 'bg-gray-100 text-gray-600',
                    };
                    $badgeLabel = match($ligne->statut) {
                        'termine' => 'Terminé',
                        'en_cours' => 'En cours',
                        default => 'Planifié',
                    };
                @endphp
                <span class="px-2.5 py-0.5 text-xs rounded-full font-medium {{ $badgeCouleur }}">{{ $badgeLabel }}</span>

                @if($ligne->statut === 'planifie')
                <button type="button" wire:click="demarrerLigne({{ $ligne->id }})" class="text-primary hover:text-primary-dark text-xs font-medium">
                    <i class="fas fa-play mr-0.5"></i> Démarrer
                </button>
                @endif

                @if($ligne->statut !== 'termine')
                <button type="button" wire:click="ouvrirFacturationLigne({{ $ligne->id }})" class="text-primary hover:text-primary-dark text-xs font-medium">
                    <i class="fas fa-check mr-0.5"></i> Terminer et facturer
                </button>
                <button type="button" wire:click="supprimerLigne({{ $ligne->id }})" class="text-red-500 hover:text-red-700 text-xs font-medium">
                    <i class="fas fa-trash"></i>
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-8 text-gray-400 text-sm">
            <i class="fas fa-tooth text-3xl mb-2 block"></i>
            Aucun acte planifié. Cliquez sur une dent pour commencer.
        </div>
        @endforelse
    </div>
    @endunless

    {{-- Modale d'évaluation anatomique de la dent (mode observations seules) --}}
    @if($showEvaluationModal)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="fermerEvaluation">
        <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-primary text-white px-5 py-4 flex items-center justify-between rounded-t-xl">
                <h3 class="font-semibold flex items-center gap-2">
                    <i class="fas fa-tooth"></i>
                    Dent {{ $dentSelectionnee }}
                    @if($this->nomDent($dentSelectionnee))
                        <span class="font-normal text-white/80">({{ $this->nomDent($dentSelectionnee) }})</span>
                    @endif
                </h3>
                <button type="button" wire:click="fermerEvaluation" class="text-white/80 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-5 space-y-5">

                {{-- A. Couronne --}}
                <div>
                    <h4 class="text-sm font-bold text-gray-800 mb-2">A. La couronne (partie visible)</h4>

                    <div class="mb-3">
                        <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Émail (couche superficielle)</div>
                        <div class="space-y-1">
                            @foreach(\App\Http\Livewire\PlanTraitementDentaire::OPTIONS_EMAIL as $valeur => $label)
                            <label class="flex items-start gap-2 text-sm cursor-pointer">
                                <input type="radio" wire:model="evaluationEmail" value="{{ $valeur }}" class="mt-0.5 text-primary focus:ring-primary">
                                <span>{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Dentine (couche moyenne)</div>
                        <div class="space-y-1">
                            @foreach(\App\Http\Livewire\PlanTraitementDentaire::OPTIONS_DENTINE as $valeur => $label)
                            <label class="flex items-start gap-2 text-sm cursor-pointer">
                                <input type="radio" wire:model="evaluationDentine" value="{{ $valeur }}" class="mt-0.5 text-primary focus:ring-primary">
                                <span>{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- B. Complexe pulpaire --}}
                <div class="pt-3 border-t border-gray-100">
                    <h4 class="text-sm font-bold text-gray-800 mb-2">B. Le complexe pulpaire (cœur de la dent)</h4>
                    <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Pulpe (nerfs et vaisseaux)</div>
                    <div class="space-y-1">
                        @foreach(\App\Http\Livewire\PlanTraitementDentaire::OPTIONS_PULPE as $valeur => $label)
                        <label class="flex items-start gap-2 text-sm cursor-pointer">
                            <input type="radio" wire:model="evaluationPulpe" value="{{ $valeur }}" class="mt-0.5 text-primary focus:ring-primary">
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- C. Racine et parodonte --}}
                <div class="pt-3 border-t border-gray-100">
                    <h4 class="text-sm font-bold text-gray-800 mb-2">C. La racine et le parodonte (ancrage de la dent)</h4>

                    <div class="mb-3">
                        <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Cément & racine</div>
                        <div class="space-y-1">
                            @foreach(\App\Http\Livewire\PlanTraitementDentaire::OPTIONS_RACINE as $valeur => $label)
                            <label class="flex items-start gap-2 text-sm cursor-pointer">
                                <input type="radio" wire:model="evaluationRacine" value="{{ $valeur }}" class="mt-0.5 text-primary focus:ring-primary">
                                <span>{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Desmodonte & os (péri-apex)</div>
                        <div class="space-y-1">
                            @foreach(\App\Http\Livewire\PlanTraitementDentaire::OPTIONS_PARODONTE as $valeur => $label)
                            <label class="flex items-start gap-2 text-sm cursor-pointer">
                                <input type="radio" wire:model="evaluationParodonte" value="{{ $valeur }}" class="mt-0.5 text-primary focus:ring-primary">
                                <span>{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                    <button type="button" wire:click="fermerEvaluation" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                        Annuler
                    </button>
                    <button type="button" wire:click="sauvegarderEvaluation" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark flex items-center gap-1">
                        <i class="fas fa-save"></i> Enregistrer l'évaluation
                    </button>
                </div>

                {{-- Historique de la dent --}}
                <div class="pt-4 border-t border-gray-200">
                    <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2 flex items-center gap-1">
                        <i class="fas fa-history"></i> Historique de la dent
                    </h4>

                    @if(count($historiqueDent) === 0)
                        <p class="text-sm text-gray-400 italic py-2">Aucun historique pour cette dent.</p>
                    @else
                        <div class="space-y-2 max-h-56 overflow-y-auto mb-3">
                            @foreach($historiqueDent as $item)
                            <div class="flex items-start gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                @if($item['type'] === 'acte')
                                    <i class="fas fa-tooth text-primary mt-0.5"></i>
                                    <div class="flex-1">
                                        <div class="font-medium">{{ $item['libelle'] }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i') }}
                                            @if($item['medecin']) · Dr. {{ $item['medecin'] }} @endif
                                        </div>
                                    </div>
                                @elseif($item['type'] === 'evaluation')
                                    <i class="fas fa-stethoscope text-blue-500 mt-0.5"></i>
                                    <div class="flex-1">
                                        <div class="text-gray-700 space-y-0.5">
                                            @foreach($item['lignes'] as $ligne)
                                                <div>{{ $ligne }}</div>
                                            @endforeach
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i') }}
                                            @if($item['medecin']) · Dr. {{ $item['medecin'] }} @endif
                                        </div>
                                    </div>
                                @else
                                    <i class="fas fa-sticky-note text-yellow-500 mt-0.5"></i>
                                    <div class="flex-1">
                                        <div class="text-gray-700 whitespace-pre-line">{{ $item['texte'] }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y H:i') }}
                                            @if($item['medecin']) · Dr. {{ $item['medecin'] }} @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex gap-2">
                        <textarea wire:model="nouvelleObservation" rows="2" placeholder="Ajouter une observation..."
                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-none"></textarea>
                        <button type="button" wire:click="ajouterObservation"
                            class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark flex items-center gap-1 self-start">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    @error('nouvelleObservation')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Sélecteur de facture pour terminer/facturer une ligne --}}
    @unless($modeObservationsSeules)
    @if($showFactureSelector)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" wire:click.self="fermerFactureSelector">
        <div class="bg-white rounded-xl p-5 w-full max-w-md">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-800">Choisir une facture</h3>
                <button type="button" wire:click="fermerFactureSelector" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="space-y-2 max-h-64 overflow-y-auto">
                @forelse($facturesEnAttente as $facture)
                <button type="button" wire:click="terminerEtFacturer({{ $facture->Idfacture }})"
                        class="w-full text-left px-3 py-2 border border-gray-200 rounded-lg hover:bg-primary-light text-sm">
                    <span class="font-medium">{{ $facture->Nfacture }}</span>
                    <span class="text-gray-500 float-right">{{ \Carbon\Carbon::parse($facture->DtFacture)->format('d/m/Y') }}</span>
                </button>
                @empty
                <p class="text-sm text-gray-400 text-center py-4">Aucune facture en attente pour ce patient.</p>
                @endforelse
            </div>

            <button type="button" wire:click="creerNouvelleFacturePourLigne"
                    class="w-full mt-3 px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark flex items-center justify-center gap-1">
                <i class="fas fa-plus"></i> Créer une nouvelle facture
            </button>
        </div>
    </div>
    @endif
    @endunless
</div>
