<div class="p-4" wire:poll.15s>

    {{-- Filtres --}}
    <div class="flex flex-wrap gap-3 mb-5 items-center">
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-600">Date :</label>
            <input type="date" wire:model.live="date"
                class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
        </div>
        <button wire:click="$refresh" class="ml-auto flex items-center gap-1.5 px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600">
            <i class="fas fa-sync-alt text-xs"></i> Actualiser
        </button>
    </div>

    {{-- Légende statuts --}}
    <div class="flex items-center gap-4 mb-4 text-xs text-gray-500">
        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-yellow-400"></span> En attente</span>
        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> En cours</span>
        @if($canCreate)
        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500"></span> Terminé</span>
        @endif
    </div>

    {{-- Contenu --}}
    @if($patients->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <i class="fas fa-syringe text-5xl mb-4 opacity-40"></i>
            <p class="text-lg font-medium">Salle de soins vide</p>
            <p class="text-sm mt-1">Aucune ordonnance interne prescrite aujourd'hui</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($patients as $item)
                @php
                    $patient    = $item['patient'];
                    $statut     = $item['statut'];
                    $nomPatient = $patient ? ($patient->NomContact ?? $patient->Prenom ?? 'Patient inconnu') : 'Patient inconnu';
                    $ouvert     = $patientOuvert === ($patient->ID ?? null);

                    $statutStyle = match($statut) {
                        'en_cours'   => ['ring' => 'border-blue-300 ring-2 ring-blue-100', 'header' => 'bg-blue-50', 'icon' => 'bg-blue-500 text-white', 'dot' => 'bg-blue-500', 'label' => 'En cours', 'badge' => 'bg-blue-100 text-blue-700'],
                        'termine'    => ['ring' => 'border-green-300 ring-2 ring-green-100', 'header' => 'bg-green-50', 'icon' => 'bg-green-500 text-white', 'dot' => 'bg-green-500', 'label' => 'Terminé', 'badge' => 'bg-green-100 text-green-700'],
                        default      => ['ring' => 'border-yellow-200 ring-2 ring-yellow-50', 'header' => 'bg-yellow-50', 'icon' => 'bg-yellow-400 text-white', 'dot' => 'bg-yellow-400', 'label' => 'En attente', 'badge' => 'bg-yellow-100 text-yellow-700'],
                    };

                    $nbMedic = $nbAnalyse = $nbRadio = 0;
                    foreach ($item['ordonnances'] as $ord) {
                        foreach ($ord['lignes'] as $l) {
                            if (str_contains($ord['TypeOrd'] ?? '', 'Médic')) $nbMedic++;
                            elseif (str_contains($ord['TypeOrd'] ?? '', 'Analy')) $nbAnalyse++;
                            elseif (str_contains($ord['TypeOrd'] ?? '', 'Radio')) $nbRadio++;
                        }
                    }
                @endphp

                <div class="rounded-xl border shadow-sm overflow-hidden transition-all {{ $statutStyle['ring'] }}">

                    {{-- En-tête patient --}}
                    <div class="flex items-center gap-3 p-3 cursor-pointer {{ $statutStyle['header'] }}"
                        wire:click="togglePatient({{ $patient->ID ?? 'null' }})">

                        {{-- Icône statut --}}
                        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 {{ $statutStyle['icon'] }}">
                            @if($statut === 'en_cours')
                                <i class="fas fa-spinner fa-spin text-sm"></i>
                            @elseif($statut === 'termine')
                                <i class="fas fa-check text-sm"></i>
                            @else
                                <i class="fas fa-user-injured text-sm"></i>
                            @endif
                        </div>

                        {{-- Nom + tél + badges --}}
                        <div class="flex-1 min-w-0">
                            @if($patient)
                            <div class="flex items-center gap-2 flex-wrap">
                                <button wire:click.stop="selectionnerPatient({{ json_encode(['ID' => $patient->ID, 'NomContact' => $patient->NomContact ?? $patient->Prenom, 'action' => 'select']) }})"
                                    class="font-semibold text-sm hover:underline text-left text-primary">
                                    {{ $nomPatient }}
                                </button>
                                @if($patient->Telephone1)
                                <span class="text-xs text-gray-500 flex items-center gap-1">
                                    <i class="fas fa-phone text-[9px]"></i>{{ $patient->Telephone1 }}
                                </span>
                                @endif
                            </div>
                            @else
                            <p class="font-semibold text-gray-800 text-sm">{{ $nomPatient }}</p>
                            @endif

                            {{-- Badges résumé --}}
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full {{ $statutStyle['badge'] }}">
                                    {{ $statutStyle['label'] }}
                                </span>
                                @if($nbMedic > 0)
                                <span class="inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-green-100 text-green-700">
                                    <i class="fas fa-pills text-[9px]"></i> {{ $nbMedic }} méd.
                                </span>
                                @endif
                                @if($nbAnalyse > 0)
                                <span class="inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                    <i class="fas fa-vial text-[9px]"></i> {{ $nbAnalyse }} anal.
                                </span>
                                @endif
                                @if($nbRadio > 0)
                                <span class="inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-700">
                                    <i class="fas fa-x-ray text-[9px]"></i> {{ $nbRadio }} radio
                                </span>
                                @endif
                            </div>
                        </div>

                        {{-- Actions rapides (infirmier) --}}
                        @if(!$canCreate && $patient)
                        <div class="flex items-center gap-1.5 flex-shrink-0" wire:click.stop>
                            @if($statut === 'en_attente')
                            <button wire:click="demarrerSoins({{ $patient->ID }})"
                                title="Prendre en charge"
                                class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-blue-500 text-white hover:bg-blue-600 text-xs font-semibold">
                                <i class="fas fa-play text-[10px]"></i> Démarrer
                            </button>
                            @elseif($statut === 'en_cours')
                            <button wire:click="terminerSoins({{ $patient->ID }})"
                                title="Marquer les soins comme terminés"
                                class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-green-500 text-white hover:bg-green-600 text-xs font-semibold">
                                <i class="fas fa-check text-[10px]"></i> Terminer
                            </button>
                            @endif
                        </div>
                        @endif

                        {{-- Chevron --}}
                        <i class="fas fa-chevron-{{ $ouvert ? 'up' : 'down' }} text-gray-400 text-xs flex-shrink-0"></i>
                    </div>

                    {{-- Détail accordéon --}}
                    @if($ouvert)
                    <div class="border-t border-primary/10 bg-white">
                        @foreach($item['ordonnances'] as $ord)
                            @php
                                $typeColor = match(true) {
                                    str_contains($ord['TypeOrd'] ?? '', 'Médic') => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'icon' => 'fa-pills', 'text' => 'text-green-700', 'badge' => 'bg-green-100 text-green-700'],
                                    str_contains($ord['TypeOrd'] ?? '', 'Analy') => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'icon' => 'fa-vial', 'text' => 'text-blue-700', 'badge' => 'bg-blue-100 text-blue-700'],
                                    str_contains($ord['TypeOrd'] ?? '', 'Radio') => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'icon' => 'fa-x-ray', 'text' => 'text-purple-700', 'badge' => 'bg-purple-100 text-purple-700'],
                                    default => ['bg' => 'bg-gray-50', 'border' => 'border-gray-200', 'icon' => 'fa-file-prescription', 'text' => 'text-gray-700', 'badge' => 'bg-gray-100 text-gray-700'],
                                };
                            @endphp
                            <div class="mx-3 my-2 rounded-lg border {{ $typeColor['border'] }} {{ $typeColor['bg'] }} overflow-hidden">
                                <div class="flex items-center gap-2 px-3 py-2 border-b {{ $typeColor['border'] }}">
                                    <i class="fas {{ $typeColor['icon'] }} text-xs {{ $typeColor['text'] }}"></i>
                                    <span class="text-xs font-semibold {{ $typeColor['text'] }}">{{ $ord['TypeOrd'] }}</span>
                                    <span class="ml-auto text-[10px] text-gray-400">
                                        {{ \Carbon\Carbon::parse($ord['dtPrescript'])->format('H:i') }}
                                        — Dr. {{ $ord['prescripteur']['NomComplet'] ?? 'Inconnu' }}
                                    </span>
                                </div>
                                <div class="divide-y divide-white/60">
                                    @foreach($ord['lignes'] as $ligne)
                                    <div class="flex items-start gap-2 px-3 py-2">
                                        <span class="w-4 h-4 rounded-full {{ $typeColor['badge'] }} flex items-center justify-center flex-shrink-0 mt-0.5 text-[9px] font-bold">
                                            {{ $loop->iteration }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-800">{{ $ligne['libelle'] }}</p>
                                            @if($ligne['posologie'])
                                            <p class="text-xs text-gray-500 mt-0.5"><i class="fas fa-info-circle mr-1"></i>{{ $ligne['posologie'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        {{-- Actions bas de carte --}}
                        @if($patient)
                        <div class="flex items-center gap-2 px-3 pb-3 pt-1 flex-wrap">

                            {{-- Dossier médical (tous) --}}
                            <button wire:click="selectionnerPatient({{ json_encode(['ID' => $patient->ID, 'NomContact' => $patient->NomContact ?? $patient->Prenom, 'action' => 'dossier']) }})"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-xs font-medium">
                                <i class="fas fa-folder-open text-[10px]"></i> Dossier
                            </button>

                            {{-- Ordonnances (médecin/propriétaire uniquement) --}}
                            @if($canCreate)
                            <button wire:click="selectionnerPatient({{ json_encode(['ID' => $patient->ID, 'NomContact' => $patient->NomContact ?? $patient->Prenom, 'action' => 'ordonnance']) }})"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-50 text-green-700 hover:bg-green-100 text-xs font-medium">
                                <i class="fas fa-file-prescription text-[10px]"></i> Ordonnances
                            </button>
                            @endif

                            {{-- Sélectionner patient --}}
                            <button wire:click="selectionnerPatient({{ json_encode(['ID' => $patient->ID, 'NomContact' => $patient->NomContact ?? $patient->Prenom, 'action' => 'select']) }})"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 text-xs font-medium">
                                <i class="fas fa-user-check text-[10px]"></i> Sélectionner
                            </button>

                            {{-- Bouton terminer (infirmier, en_cours) --}}
                            @if(!$canCreate && $statut === 'en_cours')
                            <button wire:click="terminerSoins({{ $patient->ID }})"
                                class="ml-auto flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-500 text-white hover:bg-green-600 text-xs font-semibold">
                                <i class="fas fa-check text-[10px]"></i> Terminer les soins
                            </button>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
