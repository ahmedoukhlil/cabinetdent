<div class="p-4" wire:poll.15s>

    {{-- Filtres --}}
    <div class="flex flex-wrap gap-3 mb-5 items-center">
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-600">Date :</label>
            <input type="date" wire:model.live="date"
                class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
        </div>

        @if(count($medecins) > 1)
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-600">Médecin :</label>
            <select wire:model.live="medecinFiltre"
                class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                <option value="">Tous les médecins</option>
                @foreach($medecins as $med)
                    <option value="{{ $med->idMedecin }}">Dr. {{ $med->Nom }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <button wire:click="$refresh" class="ml-auto flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600">
            <i class="fas fa-sync-alt text-xs"></i> Actualiser
        </button>
    </div>

    {{-- Contenu --}}
    @if($rdvParMedecin->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <i class="fas fa-couch text-5xl mb-4 opacity-40"></i>
            <p class="text-lg font-medium">Salle d'attente vide</p>
            <p class="text-sm mt-1">Aucun rendez-vous pour ce jour</p>
        </div>
    @else
        @foreach($rdvParMedecin as $medecinId => $rdvs)
            @php $medecin = $rdvs->first()->medecin; @endphp
            <div class="mb-6">
                {{-- En-tête médecin --}}
                <div class="flex items-center gap-2 mb-3 pb-2 border-b-2 border-primary/20">
                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                        <i class="fas fa-user-md text-primary text-sm"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800 text-sm">
                        Dr. {{ $medecin->Nom ?? 'Médecin inconnu' }}
                    </h3>
                    <span class="ml-auto text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                        {{ $rdvs->count() }} patient(s)
                    </span>
                </div>

                {{-- Liste des patients --}}
                <div class="space-y-2">
                    @foreach($rdvs as $index => $rdv)
                        @php
                            $patient = $rdv->patient;
                            $nomPatient = $patient ? ($patient->NomContact ?? ($patient->Prenom ?? 'Patient inconnu')) : 'Patient inconnu';
                            $statut = $rdv->rdvConfirmer ?? 'Prévu';
                            $enCours = strtolower($statut) === 'en cours';
                        @endphp
                        <div class="flex items-center gap-3 p-3 rounded-xl shadow-sm transition-all group border
                            {{ $enCours
                                ? 'bg-blue-50 border-blue-300 ring-2 ring-blue-200'
                                : 'bg-white border-gray-100 hover:shadow-md hover:border-primary/30 cursor-pointer' }}"
                            @if(!$enCours && $patient)
                                wire:click="demarrerRdv({{ $rdv->IDRdv }})"
                                title="Cliquer pour démarrer ce rendez-vous"
                            @endif>

                            {{-- N° d'ordre --}}
                            <div class="w-9 h-9 flex items-center justify-center rounded-full text-sm font-bold flex-shrink-0
                                {{ $enCours ? 'bg-blue-600 text-white' : 'bg-primary-light text-primary' }}">
                                {{ $index + 1 }}
                            </div>

                            {{-- Nom du patient --}}
                            <div class="flex-1 min-w-0">
                                @if($patient)
                                <button wire:click.stop="selectionnerPatient({{ json_encode(['ID' => $patient->ID, 'NomContact' => $patient->NomContact, 'action' => 'select']) }})"
                                    class="font-semibold text-sm truncate hover:underline text-left block w-full {{ $enCours ? 'text-blue-800' : 'text-primary' }}">
                                    {{ $nomPatient }}
                                </button>
                                @else
                                <p class="font-semibold text-gray-800 text-sm truncate">{{ $nomPatient }}</p>
                                @endif
                            </div>

                            {{-- Statut --}}
                            @if($enCours)
                            <span class="text-xs font-medium px-2 py-1 rounded-full border bg-blue-100 text-blue-700 border-blue-200 whitespace-nowrap">
                                <i class="fas fa-spinner fa-spin mr-1 text-[10px]"></i>En cours
                            </span>
                            @endif

                            {{-- Actions --}}
                            @if($patient)
                            <div class="flex items-center gap-1 {{ $enCours ? 'opacity-100' : 'opacity-0 group-hover:opacity-100' }} transition-opacity">

                                {{-- Bouton FIN (uniquement si en cours) --}}
                                @if($enCours)
                                <button wire:click.stop="terminerRdv({{ $rdv->IDRdv }})"
                                    title="Terminer la consultation"
                                    class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-green-500 text-white hover:bg-green-600 text-xs font-semibold">
                                    <i class="fas fa-check text-[10px]"></i> Fin
                                </button>
                                @endif

                                {{-- Dossier médical --}}
                                <button wire:click.stop="selectionnerPatient({{ json_encode(['ID' => $patient->ID, 'NomContact' => $patient->NomContact, 'action' => 'dossier']) }})"
                                    title="Dossier médical"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 text-xs">
                                    <i class="fas fa-folder-open"></i>
                                </button>

                                {{-- Plan de traitement --}}
                                <button wire:click.stop="selectionnerPatient({{ json_encode(['ID' => $patient->ID, 'NomContact' => $patient->NomContact, 'action' => 'plan-traitement']) }})"
                                    title="Plan de traitement"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-primary/10 text-primary hover:bg-primary/20 text-xs">
                                    <i class="fas fa-tooth"></i>
                                </button>
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
