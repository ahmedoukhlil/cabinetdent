<div class="w-full">

    {{-- Onglets --}}
    {{-- Nouvelle consultation / Historique / Analyses restent dans le code (PHP + vues
         ci-dessous) mais sont masqués de la navigation : le Dossier médical se concentre
         sur le dossier permanent (antécédents, alertes, schéma dentaire). --}}
    <div class="flex border-b border-gray-200 mb-6">
        <button wire:click="$set('onglet','dossier')"
            class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors
                {{ $onglet === 'dossier' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            <i class="fas fa-id-card mr-1"></i> Dossier permanent
        </button>
    </div>

    {{-- Flash messages --}}
    @if(session()->has('success_dossier'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-800 p-3 rounded-r text-sm">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success_dossier') }}
        </div>
    @endif
    @if(session()->has('success_consultation'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-800 p-3 rounded-r text-sm">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success_consultation') }}
        </div>
    @endif
    @if(session()->has('error_consultation'))
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-800 p-3 rounded-r text-sm">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error_consultation') }}
        </div>
    @endif
    @if(session()->has('success_analyse'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 text-green-800 p-3 rounded-r text-sm">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success_analyse') }}
        </div>
    @endif
    @if(session()->has('error_analyse'))
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-800 p-3 rounded-r text-sm">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error_analyse') }}
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- ONGLET 1 : Dossier permanent --}}
    {{-- ================================================================ --}}
    @if($onglet === 'dossier')
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Colonne principale : antécédents, alertes, notes --}}
        <div class="lg:col-span-3 space-y-6">

            {{-- Alertes cliniques dentaires --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-exclamation-triangle text-orange-500 mr-1"></i> Alertes cliniques dentaires
                </label>
                <div class="border border-orange-200 rounded-lg p-3 bg-orange-50 grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-1.5">
                    @foreach(\App\Http\Livewire\DossierMedicalManager::ALERTES_DENTAIRES as $alerte)
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox"
                            wire:model="alertes_dentaires_selection"
                            value="{{ $alerte }}"
                            class="rounded border-orange-300 text-orange-600 focus:ring-orange-500">
                        <span>{{ $alerte }}</span>
                    </label>
                    @endforeach
                </div>
                <textarea wire:model="alertes_dentaires_detail" rows="2" placeholder="Détails (ex: nom de l'anticoagulant, terme de grossesse...)"
                    class="mt-2 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-none"></textarea>
            </div>

            {{-- Maladies chroniques + Traitements permanents --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-heartbeat text-red-500 mr-1"></i> Maladies chroniques
                    </label>
                    <div class="border border-gray-200 rounded-lg p-3 bg-gray-50 grid grid-cols-2 gap-x-4 gap-y-1.5">
                        @foreach(\App\Http\Livewire\DossierMedicalManager::MALADIES_FREQUENTES as $maladie)
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox"
                                wire:model="maladies_chroniques_selection"
                                value="{{ $maladie }}"
                                class="rounded border-gray-300 text-primary focus:ring-primary">
                            <span>{{ $maladie }}</span>
                        </label>
                        @endforeach
                        {{-- Autre --}}
                        <label class="flex items-center gap-2 text-sm cursor-pointer col-span-2 mt-1 pt-2 border-t border-gray-200">
                            <i class="fas fa-pen text-gray-400 text-xs"></i>
                            <span class="text-gray-500 font-medium">Autre :</span>
                        </label>
                        <div class="col-span-2">
                            <input type="text"
                                wire:model="maladies_chroniques_autre"
                                placeholder="Ex: Maladie de Crohn, Lupus..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-pills text-blue-500 mr-1"></i> Traitements au long cours
                    </label>
                    <textarea wire:model="traitements_permanents" rows="3" placeholder="Médicaments pris quotidiennement..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-none"></textarea>
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-sticky-note mr-1 text-gray-400"></i> Notes complémentaires
                </label>
                <textarea wire:model="notes_dossier" rows="2" placeholder="Informations complémentaires..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-none"></textarea>
            </div>

            <div class="flex justify-between items-center pt-2">
                @php
                    $patientIdPrint = is_array($patient) ? ($patient['ID'] ?? null) : ($patient->ID ?? null);
                @endphp
                <a href="{{ $patientIdPrint ? route('dossier-medical.patient.print', $patientIdPrint) : '#' }}"
                    target="_blank"
                    class="btn-secondary px-5 py-2 text-sm flex items-center gap-2 {{ !$patientIdPrint ? 'opacity-50 pointer-events-none' : '' }}">
                    <i class="fas fa-print"></i> Imprimer le dossier complet
                </a>
                <button wire:click="sauvegarderDossier" wire:loading.attr="disabled"
                    class="btn-primary px-6 py-2 text-sm flex items-center gap-2">
                    <span wire:loading.remove wire:target="sauvegarderDossier"><i class="fas fa-save mr-1"></i> Enregistrer</span>
                    <span wire:loading wire:target="sauvegarderDossier"><i class="fas fa-spinner fa-spin mr-1"></i> Enregistrement...</span>
                </button>
            </div>
        </div>

        {{-- Colonne latérale : schéma dentaire --}}
        <div class="lg:col-span-2">
            <div class="lg:sticky lg:top-0">
                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-tooth text-primary"></i> Schéma dentaire
                </h4>
                @if($patientId)
                    <livewire:plan-traitement-dentaire wire:key="dossier-plan-traitement-{{ $patientId }}" :patient="$patient" :modeObservationsSeules="true" />
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ================================================================ --}}
    {{-- ONGLET 2 : Nouvelle consultation --}}
    {{-- ================================================================ --}}
    @if($onglet === 'nouvelle_consultation')
    <div class="space-y-6">

        {{-- En-tête : Médecin + Date --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-user-md text-primary mr-1"></i> Médecin <span class="text-red-500">*</span>
                </label>
                <select wire:model="medecinId"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary bg-white">
                    <option value="">-- Sélectionner --</option>
                    @foreach($medecins as $m)
                        <option value="{{ $m['idMedecin'] }}">Dr. {{ $m['Nom'] }}</option>
                    @endforeach
                </select>
                @error('medecinId')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-calendar-alt text-primary mr-1"></i> Date de consultation <span class="text-red-500">*</span>
                </label>
                <input wire:model="date_consultation" type="datetime-local"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary bg-white">
                @error('date_consultation')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
            </div>
        </div>

        {{-- Motif --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                <i class="fas fa-comment-medical text-primary mr-1"></i> Motif de consultation
            </label>
            <input wire:model="motif" type="text" placeholder="Raison de la visite..."
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
        </div>

        {{-- Tension artérielle + Anesthésie --}}
        <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fas fa-syringe text-red-500"></i> Constantes & anesthésie
            </h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div class="bg-blue-50 rounded-lg p-3 border border-blue-100 text-center">
                    <div class="text-xs text-gray-500 mb-1"><i class="fas fa-tachometer-alt text-blue-400"></i> Tension</div>
                    <input wire:model="tension_arterielle" type="text" placeholder="120/80"
                        class="w-full text-center text-sm border border-blue-200 rounded px-2 py-1 focus:ring-1 focus:ring-blue-400 focus:border-blue-400 bg-white">
                </div>
                <div class="bg-orange-50 rounded-lg p-3 border border-orange-100 text-center">
                    <div class="text-xs text-gray-500 mb-1"><i class="fas fa-syringe text-orange-400"></i> Anesthésie</div>
                    <input wire:model="anesthesie" type="text" placeholder="Locale, articaïne 4%..."
                        class="w-full text-center text-sm border border-orange-200 rounded px-2 py-1 focus:ring-1 focus:ring-orange-400 focus:border-orange-400 bg-white">
                </div>
            </div>
        </div>

        {{-- Examen clinique + Diagnostic --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-stethoscope text-primary mr-1"></i> Examen clinique
                </label>
                <textarea wire:model="examen_clinique" rows="4" placeholder="Résultats de l'examen physique..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-diagnoses text-primary mr-1"></i> Diagnostic
                </label>
                <textarea wire:model="diagnostic" rows="4" placeholder="Conclusion diagnostique..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-none"></textarea>
            </div>
        </div>

        {{-- Ordonnances prescrites --}}
        @php
            $ordsUrgence = array_filter($ordonnances, fn($o) => ($o['TypeOrdonnance'] ?? '') === "Traitement d'urgence");
            $ordsSortie  = array_filter($ordonnances, fn($o) => ($o['TypeOrdonnance'] ?? '') !== "Traitement d'urgence");
        @endphp

        {{-- Traitements d'urgence --}}
        <div class="border border-red-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 bg-red-50 border-b border-red-200 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-red-700 flex items-center gap-2">
                    <i class="fas fa-bolt text-red-500"></i>
                    Traitements d'urgence
                </h4>
                <span class="text-xs text-red-400">Médicaments administrés en salle de soin</span>
            </div>
            @if(count($ordsUrgence) === 0)
                <div class="px-4 py-4 text-center text-gray-400 text-sm">
                    <i class="fas fa-bolt text-2xl mb-1 block text-red-200"></i>
                    Aucun traitement d'urgence pour ce patient.
                </div>
            @else
                <div class="divide-y divide-red-50 max-h-56 overflow-y-auto">
                    @foreach($ordsUrgence as $ord)
                    @php $isChecked = in_array($ord['id'], $ordonnancesSelectionnees ?? []); @endphp
                    <label class="flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-red-50 transition-colors {{ $isChecked ? 'bg-red-50' : '' }}">
                        <input type="checkbox"
                            wire:model.live="ordonnancesSelectionnees"
                            value="{{ $ord['id'] }}"
                            class="mt-1 rounded border-red-300 text-red-600 focus:ring-red-500">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-red-700 bg-red-100 px-2 py-0.5 rounded-full">
                                    <i class="fas fa-bolt"></i> Urgence
                                </span>
                                <span class="text-xs font-mono text-red-600 font-semibold">{{ $ord['refOrd'] }}</span>
                                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($ord['dtPrescript'])->format('d/m/Y') }}</span>
                            </div>
                            @if(!empty($ord['ordonnances']))
                            <div class="flex flex-wrap gap-x-3 gap-y-0.5">
                                @foreach($ord['ordonnances'] as $l)
                                <span class="text-xs text-gray-600">
                                    <i class="fas fa-circle text-red-400 text-xs mr-0.5"></i>
                                    {{ $l['Libelle'] }}@if(!empty($l['Utilisation'])) <span class="text-gray-400">— {{ $l['Utilisation'] }}</span>@endif
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Ordonnances de sortie --}}
        <div class="border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h4 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                    <i class="fas fa-file-prescription text-primary"></i>
                    Ordonnances prescrites
                </h4>
                <span class="text-xs text-gray-400">Sélectionnez les ordonnances liées à cette consultation</span>
            </div>
            @if(count($ordsSortie) === 0)
                <div class="px-4 py-4 text-center text-gray-400 text-sm">
                    <i class="fas fa-file-prescription text-2xl mb-1 block"></i>
                    Aucune ordonnance de sortie pour ce patient.
                </div>
            @else
                <div class="divide-y divide-gray-100 max-h-56 overflow-y-auto">
                    @foreach($ordsSortie as $ord)
                    @php
                        $typeIcon = match($ord['TypeOrdonnance'] ?? '') {
                            'Ordonnance Médicale'      => ['icon' => 'fa-pills',       'color' => 'green',  'label' => 'Médicaments'],
                            "Ordonnance d'Analyses"    => ['icon' => 'fa-flask',       'color' => 'blue',   'label' => 'Analyses'],
                            'Ordonnance de Radiologie' => ['icon' => 'fa-x-ray',      'color' => 'purple', 'label' => 'Radiologie'],
                            default                    => ['icon' => 'fa-file-medical','color' => 'gray',   'label' => 'Ordonnance'],
                        };
                        $isChecked = in_array($ord['id'], $ordonnancesSelectionnees ?? []);
                    @endphp
                    <label class="flex items-start gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors {{ $isChecked ? 'bg-primary-light' : '' }}">
                        <input type="checkbox"
                            wire:model.live="ordonnancesSelectionnees"
                            value="{{ $ord['id'] }}"
                            class="mt-1 rounded border-gray-300 text-primary focus:ring-primary">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-{{ $typeIcon['color'] }}-700 bg-{{ $typeIcon['color'] }}-100 px-2 py-0.5 rounded-full">
                                    <i class="fas {{ $typeIcon['icon'] }}"></i> {{ $typeIcon['label'] }}
                                </span>
                                <span class="text-xs font-mono text-primary font-semibold">{{ $ord['refOrd'] }}</span>
                                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($ord['dtPrescript'])->format('d/m/Y') }}</span>
                            </div>
                            @if(!empty($ord['ordonnances']))
                            <div class="flex flex-wrap gap-x-3 gap-y-0.5">
                                @foreach($ord['ordonnances'] as $l)
                                <span class="text-xs text-gray-600">
                                    <i class="fas fa-circle text-{{ $typeIcon['color'] }}-400 text-xs mr-0.5"></i>
                                    {{ $l['Libelle'] }}@if(!empty($l['Utilisation'])) <span class="text-gray-400">— {{ $l['Utilisation'] }}</span>@endif
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        <a href="{{ route('ordonnance.print', ['id' => $ord['id']]) }}" target="_blank"
                           onclick="event.stopPropagation()"
                           class="text-xs text-gray-400 hover:text-primary flex-shrink-0 mt-0.5">
                            <i class="fas fa-print"></i>
                        </a>
                    </label>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Examens demandés --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-semibold text-gray-700">
                    <i class="fas fa-flask text-purple-500 mr-1"></i> Examens demandés
                </label>
                <button wire:click="ajouterExamen" type="button"
                    class="btn-secondary text-xs px-3 py-1 flex items-center gap-1">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>
            @if(count($examens) === 0)
                <p class="text-gray-400 text-sm italic py-2">Aucun examen ajouté.</p>
            @endif
            @foreach($examens as $i => $ex)
            <div class="flex gap-2 mb-2 items-center" wire:key="ex-{{ $i }}">
                <select wire:model="examens.{{ $i }}.type"
                    class="w-36 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="biologie">Biologie</option>
                    <option value="imagerie">Imagerie</option>
                    <option value="autre">Autre</option>
                </select>
                <input wire:model="examens.{{ $i }}.libelle" type="text" placeholder="Libellé de l'examen..."
                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                <button wire:click="supprimerExamen({{ $i }})" type="button"
                    class="text-red-400 hover:text-red-600 p-2">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endforeach
        </div>

        {{-- Conduite à tenir + Notes --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-clipboard-list text-primary mr-1"></i> Conduite à tenir
                </label>
                <textarea wire:model="conduite_a_tenir" rows="3" placeholder="Traitement, recommandations, suivi..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-sticky-note text-gray-400 mr-1"></i> Notes
                </label>
                <textarea wire:model="notes_consultation" rows="3" placeholder="Observations supplémentaires..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-none"></textarea>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button wire:click="sauvegarderConsultation" wire:loading.attr="disabled"
                class="btn-primary px-6 py-2 text-sm flex items-center gap-2">
                <span wire:loading.remove wire:target="sauvegarderConsultation"><i class="fas fa-save mr-1"></i> Enregistrer la consultation</span>
                <span wire:loading wire:target="sauvegarderConsultation"><i class="fas fa-spinner fa-spin mr-1"></i> Enregistrement...</span>
            </button>
        </div>
    </div>
    @endif

    {{-- ================================================================ --}}
    {{-- ONGLET 3 : Historique --}}
    {{-- ================================================================ --}}
    @if($onglet === 'historique')
    <div>
        @if(count($consultations) === 0)
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-history text-4xl mb-3"></i>
                <p class="text-sm">Aucune consultation enregistrée pour ce patient.</p>
                <button wire:click="$set('onglet','nouvelle_consultation')"
                    class="mt-3 btn-primary text-xs px-4 py-2">
                    <i class="fas fa-plus mr-1"></i> Enregistrer une consultation
                </button>
            </div>
        @else
        <div class="space-y-3">
            @foreach($consultations as $c)
            <div class="border border-gray-200 rounded-lg overflow-hidden" wire:key="hist-{{ $c['id'] }}">
                {{-- En-tête accordion --}}
                <button wire:click="toggleConsultation({{ $c['id'] }})"
                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold flex-shrink-0">
                            {{ \Carbon\Carbon::parse($c['date_consultation'])->format('d') }}
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-800">
                                {{ \Carbon\Carbon::parse($c['date_consultation'])->translatedFormat('d F Y') }}
                                <span class="text-gray-400 font-normal text-xs ml-1">
                                    {{ \Carbon\Carbon::parse($c['date_consultation'])->format('H:i') }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                @if(!empty($c['medecin']))
                                    <i class="fas fa-user-md mr-1"></i> Dr. {{ $c['medecin']['Nom'] ?? '' }}
                                @endif
                                @if(!empty($c['motif']))
                                    &nbsp;·&nbsp; <span class="italic">{{ Str::limit($c['motif'], 60) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Badges constantes --}}
                        @if(!empty($c['tension_arterielle']))
                            <span class="hidden sm:inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">
                                <i class="fas fa-tachometer-alt"></i> {{ $c['tension_arterielle'] }}
                            </span>
                        @endif
                        <i class="fas fa-chevron-{{ $consultationOuverte === $c['id'] ? 'up' : 'down' }} text-gray-400 text-xs ml-2"></i>
                    </div>
                </button>

                {{-- Contenu accordion --}}
                @if($consultationOuverte === $c['id'])
                <div class="p-4 border-t border-gray-100 bg-white space-y-4">

                    {{-- Constantes & anesthésie --}}
                    @if(!empty($c['tension_arterielle']) || !empty($c['anesthesie']))
                    <div>
                        <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Constantes & anesthésie</h5>
                        <div class="flex flex-wrap gap-2">
                            @if(!empty($c['tension_arterielle']))
                                <span class="inline-flex items-center gap-1 text-sm bg-blue-50 text-blue-800 px-3 py-1 rounded-full border border-blue-100">
                                    <i class="fas fa-tachometer-alt text-blue-400"></i> {{ $c['tension_arterielle'] }} mmHg
                                </span>
                            @endif
                            @if(!empty($c['anesthesie']))
                                <span class="inline-flex items-center gap-1 text-sm bg-orange-50 text-orange-800 px-3 py-1 rounded-full border border-orange-100">
                                    <i class="fas fa-syringe text-orange-400"></i> {{ $c['anesthesie'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Examen + Diagnostic --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if(!empty($c['examen_clinique']))
                        <div>
                            <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Examen clinique</h5>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $c['examen_clinique'] }}</p>
                        </div>
                        @endif
                        @if(!empty($c['diagnostic']))
                        <div>
                            <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Diagnostic</h5>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $c['diagnostic'] }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- Médicaments prescrits --}}
                    @if(!empty($c['medicaments_prescrits']) && count($c['medicaments_prescrits']) > 0)
                    <div>
                        <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Médicaments prescrits</h5>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm border border-gray-100 rounded-lg overflow-hidden">
                                <thead class="bg-blue-50 text-blue-700 text-xs">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Médicament</th>
                                        <th class="px-3 py-2 text-left">Posologie</th>
                                        <th class="px-3 py-2 text-left">Durée</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($c['medicaments_prescrits'] as $med)
                                    <tr class="border-t border-gray-100 hover:bg-gray-50">
                                        <td class="px-3 py-2 font-medium">{{ $med['nom'] ?? '' }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $med['posologie'] ?? '' }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $med['duree'] ?? '' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- Examens demandés --}}
                    @if(!empty($c['examens_demandes']) && count($c['examens_demandes']) > 0)
                    <div>
                        <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Examens demandés</h5>
                        <div class="flex flex-wrap gap-2">
                            @foreach($c['examens_demandes'] as $ex)
                            <span class="inline-flex items-center gap-1 text-xs bg-purple-50 text-purple-800 px-3 py-1 rounded-full border border-purple-100">
                                <i class="fas fa-flask text-purple-400"></i>
                                <span class="font-medium">{{ ucfirst($ex['type'] ?? '') }}</span> : {{ $ex['libelle'] ?? '' }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Ordonnances liées --}}
                    @if(!empty($c['ordonnances_ids']) && count($c['ordonnances_ids']) > 0)
                    @php
                        $ordsLiees         = array_filter($ordonnances, fn($o) => in_array($o['id'], $c['ordonnances_ids']));
                        $ordsLieesUrgence  = array_filter($ordsLiees, fn($o) => ($o['TypeOrdonnance'] ?? '') === "Traitement d'urgence");
                        $ordsLieesSortie   = array_filter($ordsLiees, fn($o) => ($o['TypeOrdonnance'] ?? '') !== "Traitement d'urgence");
                    @endphp
                    @if(count($ordsLiees) > 0)
                    <div class="space-y-3">

                        {{-- Traitements d'urgence --}}
                        @if(count($ordsLieesUrgence) > 0)
                        <div>
                            <h5 class="text-xs font-semibold text-red-600 uppercase tracking-wide mb-2 flex items-center gap-1">
                                <i class="fas fa-bolt"></i> Traitements d'urgence
                            </h5>
                            <div class="space-y-1">
                            @foreach($ordsLieesUrgence as $ord)
                            <div class="flex items-start gap-2 bg-red-50 rounded-lg px-3 py-2 border border-red-100">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-red-700 bg-red-100 px-2 py-0.5 rounded-full flex-shrink-0">
                                    <i class="fas fa-bolt"></i> Urgence
                                </span>
                                <span class="text-xs font-mono text-red-600">{{ $ord['refOrd'] }}</span>
                                <div class="flex-1 flex flex-wrap gap-x-3 gap-y-0.5">
                                    @foreach($ord['ordonnances'] ?? [] as $l)
                                    <span class="text-xs text-gray-600">{{ $l['Libelle'] }}@if(!empty($l['Utilisation'])) <span class="text-gray-400">— {{ $l['Utilisation'] }}</span>@endif</span>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Ordonnances de sortie --}}
                        @if(count($ordsLieesSortie) > 0)
                        <div>
                            <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 flex items-center gap-1">
                                <i class="fas fa-file-prescription"></i> Ordonnances prescrites
                            </h5>
                            <div class="space-y-1">
                            @foreach($ordsLieesSortie as $ord)
                            @php
                                $meta = match($ord['TypeOrdonnance'] ?? '') {
                                    'Ordonnance Médicale'      => ['icon' => 'fa-pills',        'color' => 'green',  'label' => 'Médicaments'],
                                    "Ordonnance d'Analyses"    => ['icon' => 'fa-flask',        'color' => 'blue',   'label' => 'Analyses'],
                                    'Ordonnance de Radiologie' => ['icon' => 'fa-x-ray',       'color' => 'purple', 'label' => 'Radiologie'],
                                    default                    => ['icon' => 'fa-file-medical', 'color' => 'gray',   'label' => 'Ordonnance'],
                                };
                            @endphp
                            <div class="flex items-start gap-2 bg-gray-50 rounded-lg px-3 py-2 border border-gray-100">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-{{ $meta['color'] }}-700 bg-{{ $meta['color'] }}-100 px-2 py-0.5 rounded-full flex-shrink-0">
                                    <i class="fas {{ $meta['icon'] }}"></i> {{ $meta['label'] }}
                                </span>
                                <span class="text-xs font-mono text-primary">{{ $ord['refOrd'] }}</span>
                                <div class="flex-1 flex flex-wrap gap-x-3 gap-y-0.5">
                                    @foreach($ord['ordonnances'] ?? [] as $l)
                                    <span class="text-xs text-gray-600">{{ $l['Libelle'] }}@if(!empty($l['Utilisation'])) <span class="text-gray-400">— {{ $l['Utilisation'] }}</span>@endif</span>
                                    @endforeach
                                </div>
                                <a href="{{ route('ordonnance.print', ['id' => $ord['id']]) }}" target="_blank"
                                   class="text-xs text-gray-400 hover:text-primary flex-shrink-0">
                                    <i class="fas fa-print"></i>
                                </a>
                            </div>
                            @endforeach
                            </div>
                        </div>
                        @endif

                    </div>
                    @endif
                    @endif

                    {{-- Conduite à tenir --}}
                    @if(!empty($c['conduite_a_tenir']))
                    <div>
                        <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Conduite à tenir</h5>
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $c['conduite_a_tenir'] }}</p>
                    </div>
                    @endif

                    {{-- Notes --}}
                    @if(!empty($c['notes']))
                    <div class="bg-yellow-50 rounded p-3 border border-yellow-100">
                        <h5 class="text-xs font-semibold text-yellow-700 mb-1"><i class="fas fa-sticky-note mr-1"></i> Notes</h5>
                        <p class="text-sm text-gray-700">{{ $c['notes'] }}</p>
                    </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex justify-end pt-2">
                        <button wire:click="confirmSupprimerConsultation({{ $c['id'] }})"
                            class="btn-danger text-xs px-3 py-1.5 flex items-center gap-1">
                            <i class="fas fa-trash-alt"></i> Supprimer
                        </button>
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- ================================================================ --}}
    {{-- ONGLET 4 : Analyses & fichiers --}}
    {{-- ================================================================ --}}
    @if($onglet === 'analyses')
    <div class="space-y-6">

        {{-- Formulaire d'upload --}}
        <div class="border border-dashed border-primary rounded-xl p-5 bg-blue-50">
            <h4 class="text-sm font-semibold text-primary mb-4 flex items-center gap-2">
                <i class="fas fa-upload"></i> Ajouter des analyses
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Libellé <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="analyseLibelle" type="text"
                        placeholder="Ex: NFS, Glycémie, Radio thorax..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary bg-white">
                    @error('analyseLibelle')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select wire:model="analyseType"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary bg-white">
                        <option value="biologie">Biologie</option>
                        <option value="imagerie">Imagerie (Radio, Echo, Scanner...)</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de l'analyse</label>
                    <input wire:model="analyseDate" type="date"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary bg-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <input wire:model="analyseNotes" type="text" placeholder="Résultats, observations..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary bg-white">
                </div>
            </div>

            {{-- Zone de dépôt de fichiers --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Fichiers <span class="text-xs text-gray-400">(JPG, PNG, PDF, DOC — max 10 Mo chacun)</span>
                </label>
                <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-white hover:border-primary hover:bg-blue-50 transition-colors">
                    <div class="flex flex-col items-center justify-center text-gray-400">
                        <i class="fas fa-cloud-upload-alt text-2xl mb-2"></i>
                        <span class="text-sm">Cliquez pour sélectionner ou glissez les fichiers ici</span>
                    </div>
                    <input wire:model="analysesFichiers" type="file" class="hidden" multiple
                        accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                </label>
                @error('analysesFichiers')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                @error('analysesFichiers.*')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror

                {{-- Prévisualisation des fichiers sélectionnés --}}
                @if(count($analysesFichiers) > 0)
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($analysesFichiers as $i => $f)
                    <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
                        <i class="fas {{ str_starts_with($f->getMimeType(), 'image/') ? 'fa-image text-blue-400' : 'fa-file-pdf text-red-400' }}"></i>
                        <span class="text-gray-700 max-w-[140px] truncate">{{ $f->getClientOriginalName() }}</span>
                        <span class="text-gray-400 text-xs">{{ round($f->getSize() / 1024) }} Ko</span>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Indicateur de chargement upload --}}
                <div wire:loading wire:target="analysesFichiers" class="mt-2 text-sm text-blue-600">
                    <i class="fas fa-spinner fa-spin mr-1"></i> Chargement en cours...
                </div>
            </div>

            <div class="flex justify-end">
                <button wire:click="uploadAnalyses" wire:loading.attr="disabled"
                    wire:target="uploadAnalyses,analysesFichiers"
                    class="btn-primary px-5 py-2 text-sm flex items-center gap-2">
                    <span wire:loading.remove wire:target="uploadAnalyses">
                        <i class="fas fa-upload mr-1"></i> Enregistrer
                    </span>
                    <span wire:loading wire:target="uploadAnalyses">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Upload...
                    </span>
                </button>
            </div>
        </div>

        {{-- Liste des analyses --}}
        @if(count($analyses) === 0)
            <div class="text-center py-10 text-gray-400">
                <i class="fas fa-microscope text-4xl mb-3"></i>
                <p class="text-sm">Aucune analyse enregistrée pour ce patient.</p>
            </div>
        @else
        <div class="space-y-4">
            {{-- Grouper par type --}}
            @foreach(['biologie' => ['label' => 'Biologie', 'icon' => 'fa-flask', 'color' => 'purple'],
                      'imagerie' => ['label' => 'Imagerie', 'icon' => 'fa-x-ray', 'color' => 'blue'],
                      'autre'    => ['label' => 'Autres',   'icon' => 'fa-file-medical', 'color' => 'gray']] as $typeKey => $typeMeta)
            @php $groupe = array_filter($analyses, fn($a) => $a['type'] === $typeKey); @endphp
            @if(count($groupe) > 0)
            <div>
                <h5 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 flex items-center gap-2">
                    <i class="fas {{ $typeMeta['icon'] }} text-{{ $typeMeta['color'] }}-500"></i>
                    {{ $typeMeta['label'] }} ({{ count($groupe) }})
                </h5>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($groupe as $a)
                    <div class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm hover:shadow-md transition-shadow"
                         wire:key="analyse-{{ $a['id'] }}">

                        {{-- Prévisualisation image ou icône --}}
                        <div class="relative bg-gray-50 h-36 flex items-center justify-center overflow-hidden cursor-pointer"
                             wire:click="previewAnalyse({{ $a['id'] }})">
                            @if($a['est_image'])
                                <img src="{{ $a['url'] }}" alt="{{ $a['libelle'] }}"
                                    class="w-full h-full object-cover">
                            @elseif($a['est_pdf'])
                                <div class="flex flex-col items-center text-red-400">
                                    <i class="fas fa-file-pdf text-5xl"></i>
                                    <span class="text-xs mt-1 text-gray-500">PDF</span>
                                </div>
                            @else
                                <div class="flex flex-col items-center text-gray-400">
                                    <i class="fas fa-file-alt text-5xl"></i>
                                    <span class="text-xs mt-1">Document</span>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-10 transition-all flex items-center justify-center">
                                <span class="opacity-0 hover:opacity-100 bg-white rounded-full px-2 py-1 text-xs text-gray-700 shadow">
                                    <i class="fas fa-eye mr-1"></i> Voir
                                </span>
                            </div>
                        </div>

                        {{-- Infos --}}
                        <div class="p-3">
                            <div class="font-medium text-gray-800 text-sm truncate" title="{{ $a['libelle'] }}">
                                {{ $a['libelle'] }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5 flex items-center gap-2">
                                @if(!empty($a['date_analyse']))
                                    <span><i class="fas fa-calendar-alt mr-0.5"></i>
                                        {{ \Carbon\Carbon::parse($a['date_analyse'])->format('d/m/Y') }}
                                    </span>
                                @endif
                                @if(!empty($a['fichier_taille']))
                                    <span>{{ round($a['fichier_taille'] / 1024) }} Ko</span>
                                @endif
                            </div>
                            @if(!empty($a['notes']))
                                <p class="text-xs text-gray-500 mt-1 truncate">{{ $a['notes'] }}</p>
                            @endif
                            <div class="flex gap-2 mt-2">
                                <a href="{{ $a['url'] }}" target="_blank"
                                    class="flex-1 text-center btn-secondary text-xs py-1 flex items-center justify-center gap-1">
                                    <i class="fas fa-external-link-alt"></i> Ouvrir
                                </a>
                                <a href="{{ $a['url'] }}" download="{{ $a['fichier_nom'] }}"
                                    class="text-gray-500 hover:text-primary px-2 py-1 rounded border border-gray-200 hover:border-primary transition-colors text-xs">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button wire:click="confirmSupprimerAnalyse({{ $a['id'] }})"
                                    class="text-red-400 hover:text-red-600 px-2 py-1 rounded border border-gray-200 hover:border-red-300 transition-colors text-xs">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach
        </div>
        @endif

        {{-- Modal de prévisualisation plein écran --}}
        @php $analysePreview = $analysePreviewId ? collect($analyses)->firstWhere('id', $analysePreviewId) : null; @endphp
        @if($analysePreview)
        <div class="fixed inset-0 z-[9999] bg-black bg-opacity-80 flex items-center justify-center p-4"
             wire:click.self="$set('analysePreviewId', null)">
            <div class="relative bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-hidden shadow-2xl">
                <div class="flex items-center justify-between p-3 border-b bg-gray-50">
                    <span class="font-semibold text-gray-800 text-sm">
                        <i class="fas fa-microscope text-primary mr-1"></i>
                        {{ $analysePreview['libelle'] }}
                        @if(!empty($analysePreview['date_analyse']))
                            <span class="text-gray-400 font-normal ml-1 text-xs">
                                — {{ \Carbon\Carbon::parse($analysePreview['date_analyse'])->format('d/m/Y') }}
                            </span>
                        @endif
                    </span>
                    <div class="flex items-center gap-2">
                        <a href="{{ $analysePreview['url'] }}" target="_blank"
                            class="btn-secondary text-xs px-3 py-1 flex items-center gap-1">
                            <i class="fas fa-external-link-alt"></i> Ouvrir dans un onglet
                        </a>
                        <a href="{{ $analysePreview['url'] }}" download="{{ $analysePreview['fichier_nom'] }}"
                            class="btn-secondary text-xs px-3 py-1 flex items-center gap-1">
                            <i class="fas fa-download"></i> Télécharger
                        </a>
                        <button wire:click="$set('analysePreviewId', null)"
                            class="text-gray-400 hover:text-gray-700 p-1 rounded hover:bg-gray-200">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="overflow-auto" style="max-height: calc(90vh - 56px);">
                    @if($analysePreview['est_image'])
                        <img src="{{ $analysePreview['url'] }}" alt="{{ $analysePreview['libelle'] }}"
                            class="w-full object-contain">
                    @elseif($analysePreview['est_pdf'])
                        <iframe src="{{ $analysePreview['url'] }}" class="w-full" style="height: calc(90vh - 56px);"></iframe>
                    @else
                        <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                            <i class="fas fa-file-alt text-6xl mb-4"></i>
                            <p class="text-sm">Prévisualisation non disponible pour ce type de fichier.</p>
                            <a href="{{ $analysePreview['url'] }}" download="{{ $analysePreview['fichier_nom'] }}"
                                class="mt-4 btn-primary text-sm px-4 py-2 flex items-center gap-2">
                                <i class="fas fa-download"></i> Télécharger le fichier
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

    </div>
    @endif

    {{-- Modal confirmation suppression consultation --}}
    @if($showConfirmDeleteConsultation)
    <div class="modal-overlay" style="z-index:80">
        <div class="modal-box sm:max-w-md">
            <div class="modal-header" style="background:#dc2626">
                <h3><i class="fas fa-exclamation-triangle mr-2"></i>Supprimer cette consultation ?</h3>
                <button type="button" wire:click="$set('showConfirmDeleteConsultation', false)" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-notes-medical text-red-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Cette consultation et toutes ses données seront supprimées définitivement. Cette action est <strong>irréversible</strong>.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button wire:click="$set('showConfirmDeleteConsultation', false)"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                        Annuler
                    </button>
                    <button wire:click="supprimerConsultation"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="supprimerConsultation"><i class="fas fa-trash mr-1"></i> Supprimer</span>
                        <span wire:loading wire:target="supprimerConsultation"><i class="fas fa-spinner fa-spin mr-1"></i> Suppression...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal confirmation suppression analyse/fichier --}}
    @if($showConfirmDeleteAnalyse)
    <div class="modal-overlay" style="z-index:80">
        <div class="modal-box sm:max-w-md">
            <div class="modal-header" style="background:#dc2626">
                <h3><i class="fas fa-exclamation-triangle mr-2"></i>Supprimer ce fichier ?</h3>
                <button type="button" wire:click="$set('showConfirmDeleteAnalyse', false)" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="flex items-start gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-file-medical text-red-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Ce fichier sera supprimé définitivement du serveur. Cette action est <strong>irréversible</strong>.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button wire:click="$set('showConfirmDeleteAnalyse', false)"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                        Annuler
                    </button>
                    <button wire:click="supprimerAnalyse"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 flex items-center gap-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="supprimerAnalyse"><i class="fas fa-trash mr-1"></i> Supprimer</span>
                        <span wire:loading wire:target="supprimerAnalyse"><i class="fas fa-spinner fa-spin mr-1"></i> Suppression...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
