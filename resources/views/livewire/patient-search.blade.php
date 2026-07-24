<div>
    <div class="relative">
        <div class="flex">
            <select wire:model.live="searchBy" class="rounded-l-md border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm focus:border-primary focus:ring-primary">
                <option value="telephone" selected>Téléphone</option>
                <option value="nom">Nom</option>
                <option value="identifiant">N° Fiche</option>
            </select>
            <div class="relative flex-1">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    wire:keydown.enter="ouvrirCreationSiIntrouvable"
                    placeholder="Rechercher un patient..."
                    class="w-full rounded-r-md border-gray-300 focus:border-primary focus:ring-primary sm:text-sm"
                >
                <!-- Indicateur de chargement -->
                <div wire:loading class="absolute right-3 top-1/2 transform -translate-y-1/2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
                </div>
            </div>
        </div>

    <!-- Résultats de recherche -->
    @if($showResults && count($patients) > 0)
        <div class="absolute left-0 right-0 top-full mt-1 border border-gray-200 rounded-lg shadow-xl bg-white max-h-64 overflow-y-auto z-50">
            @foreach($patients as $patient)
                <div
                    wire:mousedown="selectPatient({{ $patient->ID }})"
                    class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">
                                {{ $patient->Nom }}
                            </div>
                            <div class="text-sm text-gray-600 space-y-1">
                                @if($patient->IdentifiantPatient)
                                    <div class="flex items-center">
                                        <i class="fas fa-id-card w-4 text-gray-400 mr-2"></i>
                                        N° Fiche: {{ $patient->IdentifiantPatient }}
                                    </div>
                                @endif
                                @if($patient->Telephone1)
                                    <div class="flex items-center">
                                        <i class="fas fa-phone w-4 text-gray-400 mr-2"></i>
                                        Tel: {{ $patient->Telephone1 }}
                                    </div>
                                @endif
                                @if($patient->Telephone2)
                                    <div class="flex items-center">
                                        <i class="fas fa-phone w-4 text-gray-400 mr-2"></i>
                                        Tel 2: {{ $patient->Telephone2 }}
                                    </div>
                                @endif
                                @if($patient->DtNaissance)
                                    <div class="flex items-center">
                                        <i class="fas fa-birthday-cake w-4 text-gray-400 mr-2"></i>
                                        Né(e) le: {{ \Carbon\Carbon::parse($patient->DtNaissance)->format('d/m/Y') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Message "Aucun résultat" -->
    @if($showResults && count($patients) === 0 && strlen(trim($search)) > 0)
        <div class="absolute left-0 right-0 top-full mt-1 bg-white rounded-lg border border-gray-200 shadow-xl z-50 overflow-hidden">
            <div class="px-4 py-3 text-sm text-gray-500 border-b border-gray-100">
                <i class="fas fa-search mr-1 text-gray-400"></i>
                Aucun patient trouvé pour <span class="font-medium text-gray-700">"{{ $search }}"</span>
            </div>
            <button wire:click="ouvrirCreationSiIntrouvable"
                    class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-primary hover:bg-primary-light transition-colors text-left">
                <i class="fas fa-user-plus"></i>
                Créer un nouveau patient
            </button>
        </div>
    @endif
    </div>{{-- fin div relative --}}

    <!-- Patient sélectionné -->
    @if($selectedPatient)
        <div class="mt-3 p-3 bg-primary-light rounded-lg border border-primary">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="font-medium text-primary text-lg">
                        {{ $selectedPatient['NomPatient'] }}
                    </div>
                    <div class="text-sm text-primary-dark">
                        Tél: {{ $selectedPatient['Telephone1'] }}
                        @if($selectedPatient['Telephone2'])
                            | Tel 2: {{ $selectedPatient['Telephone2'] }}
                        @endif
                    </div>
                    @if($selectedPatient['IdentifiantPatient'])
                        <div class="text-xs text-primary-dark mt-1">
                            N° Fiche: {{ $selectedPatient['IdentifiantPatient'] }}
                        </div>
                    @endif
                </div>
                <button 
                    type="button"
                    wire:click="clearPatient"
                    class="text-primary hover:text-primary-dark p-2 rounded-full hover:bg-primary-light transition-colors"
                    title="Effacer la sélection"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    <!-- Messages d'erreur -->
    @if(session()->has('error'))
        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-800">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div> 