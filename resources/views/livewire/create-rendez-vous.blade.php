<div class="space-y-4 sm:space-y-6">
    <div class="p-4 sm:p-6 rounded-xl bg-primary text-white shadow-lg z-30 flex flex-col md:flex-row md:items-center md:justify-between gap-3 sm:gap-4">
        <div>
        <h2 class="text-xl sm:text-2xl font-bold">Nouveau Rendez-vous</h2>
        <p class="text-primary-light mt-1 text-sm sm:text-base">Planifiez un nouveau rendez-vous pour le patient sélectionné</p>
        </div>
        <div class="flex items-center gap-2 mt-4 md:mt-0">
            <span class="text-base sm:text-lg font-semibold">Rdv aujourd'hui :</span>
            <span class="inline-flex items-center justify-center px-2 sm:px-3 py-1 rounded-full bg-white text-primary font-bold text-base sm:text-lg shadow">{{ $totalRdvJour }}</span>
        </div>
    </div>

    <!-- Patient sélectionné -->
    @if($selectedPatient && is_array($selectedPatient))
        <div class="p-3 bg-primary-light rounded-lg border border-primary">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="font-medium text-primary text-lg">
                        {{ $selectedPatient['NomPatient'] ?? ($selectedPatient['Nom'] ?? '') }}
                        {{ $selectedPatient['Prenom'] ?? '' }}
                    </div>
                    <div class="text-sm text-primary-dark">
                        @if(!empty($selectedPatient['Telephone1']))Tél: {{ $selectedPatient['Telephone1'] }}@endif
                        @if(!empty($selectedPatient['IdentifiantPatient'])) — N° Fiche: {{ $selectedPatient['IdentifiantPatient'] }}@endif
                    </div>
                </div>
                <button type="button" wire:click="handlePatientCleared"
                    class="text-primary hover:text-primary-dark p-2 rounded-full hover:bg-white transition-colors"
                    title="Changer de patient">
                    <i class="fas fa-times"></i> Changer
                </button>
            </div>
        </div>
    @endif

    <!-- Formulaire de création -->
    <form wire:submit.prevent="createRendezVous" class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        @error('patient_id') <div class="text-red-500 text-sm"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</div> @enderror
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Sélection du médecin -->
            <div>
                <label for="medecin_id" class="block text-sm font-medium text-gray-700 mb-1">Médecin</label>
                <select wire:model="medecin_id" id="medecin_id" class="form-select" {{ $isDocteur ? 'disabled' : '' }}>
                    <option value="">Sélectionner un médecin</option>
                    @foreach($medecins as $medecin)
                        <option value="{{ $medecin->idMedecin }}">{{ $medecin->Nom }}</option>
                    @endforeach
                </select>
                @error('medecin_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Date du rendez-vous -->
            <div>
                <label for="date_rdv" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" wire:model="date_rdv" id="date_rdv" min="{{ date('Y-m-d') }}" class="form-input">
                @error('date_rdv') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Heure du rendez-vous -->
            <div>
                <label for="heure_rdv" class="block text-sm font-medium text-gray-700 mb-1">Heure <span class="text-xs text-gray-400">(créneaux 10 min)</span></label>
                <input type="time" wire:model="heure_rdv" id="heure_rdv" step="600" class="form-input">
                @error('heure_rdv') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Acte prévu -->
            <div>
                <label for="acte_prevu" class="block text-sm font-medium text-gray-700 mb-1">Acte prévu</label>
                <input type="text" wire:model="acte_prevu" id="acte_prevu" class="form-input" placeholder="Ex: Consultation">
                @error('acte_prevu') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Statut -->
            <div>
                <label for="rdv_confirmer" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select wire:model="rdv_confirmer" id="rdv_confirmer" class="form-select">
                    <option value="En Attente">En Attente</option>
                    <option value="Confirmé">Présent au cabinet</option>
                    <option value="En cours">Avec le médecin</option>
                    <option value="Terminé">Terminé</option>
                    <option value="Annulé">Annulé</option>
                </select>
                @error('rdv_confirmer') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    class="btn-primary">
                <i class="fas fa-calendar-plus mr-2"></i>
                <span wire:loading.remove wire:target="createRendezVous">Créer le rendez-vous</span>
                <span wire:loading wire:target="createRendezVous" class="flex items-center gap-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                    Création...
                </span>
            </button>
        </div>
    </form>

    <!-- Liste des rendez-vous -->
    <div class="mt-6">
                 <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4 mb-4">
             <div class="flex items-center gap-3">
                 <h3 class="text-lg sm:text-xl font-semibold text-gray-900">Liste des rendez-vous</h3>
                 @if(!empty($selectedRdvIds))
                     <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                         <i class="fas fa-check-circle mr-1"></i>
                         {{ count($selectedRdvIds) }} sélectionné(s)
                     </span>
                 @endif
             </div>
             @if(!empty($selectedRdvIds))
                 <button wire:click="openBulkEditModal" class="w-full sm:w-auto px-3 sm:px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center justify-center gap-2 text-sm sm:text-base">
                     <i class="fas fa-edit"></i>
                     <span class="hidden xs:inline">Modifier en masse</span>
                     <span class="xs:hidden">Modifier</span>
                 </button>
             @endif
         </div>

        <!-- Version mobile - Cartes -->
        <div class="block lg:hidden space-y-3">
            @forelse($rendezVous as $rdv)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
                                         <!-- En-tête de la carte -->
                     <div class="flex items-start justify-between mb-3">
                         <div class="flex items-center gap-3">
                             <input type="checkbox" wire:model.live="selectedRdvIds" value="{{ $rdv->IDRdv }}" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                             <div class="flex-1">
                                 <div class="flex items-center gap-2 mb-1">
                                     <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold text-white bg-blue-600 rounded-full min-w-[1.5rem]">
                                         {{ str_pad($rdv->OrdreRDV ?? 0, 2, '0', STR_PAD_LEFT) }}
                                     </span>
                                     <h4 class="font-semibold text-gray-900 text-base">{{ $rdv->patient->Nom ?? 'N/A' }}</h4>
                                 </div>
                                 @if(!empty($rdv->patient->Telephone1))
                                     <p class="text-sm text-gray-500">{{ $rdv->patient->Telephone1 }}</p>
                                 @endif
                             </div>
                         </div>
                         <div class="text-right">
                             <div class="text-sm font-medium text-gray-900">
                                 {{ \Carbon\Carbon::parse($rdv->dtPrevuRDV)->format('d/m/Y') }}
                             </div>
                             <div class="text-lg font-bold text-blue-600">
                                 {{ \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') }}
                             </div>
                         </div>
                     </div>

                    <!-- Informations du RDV -->
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Médecin</span>
                            <p class="text-sm font-medium text-gray-900">Dr. {{ $rdv->medecin->Nom ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 uppercase tracking-wide">Acte</span>
                            <p class="text-sm font-medium text-gray-900 truncate" title="{{ $rdv->ActePrevu }}">{{ $rdv->ActePrevu }}</p>
                        </div>
                    </div>

                    <!-- Statut -->
                    <div class="flex items-center justify-between mb-3">
                        @php
                            switch($rdv->rdvConfirmer) {
                                case 'En Attente':
                                case 'En attente':
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                    $statusIcon = 'fas fa-clock';
                                    $statusText = 'En Attente';
                                    break;
                                case 'confirmé':
                                case 'Confirmé':
                                    $statusClass = 'bg-blue-100 text-blue-800';
                                    $statusIcon = 'fas fa-user-check';
                                    $statusText = 'Présent';
                                    break;
                                case 'En cours':
                                    $statusClass = 'bg-green-100 text-green-800';
                                    $statusIcon = 'fas fa-user-md';
                                    $statusText = 'En cours';
                                    break;
                                case 'terminé':
                                case 'Terminé':
                                    $statusClass = 'bg-gray-100 text-gray-800';
                                    $statusIcon = 'fas fa-check-double';
                                    $statusText = 'Terminé';
                                    break;
                                case 'annulé':
                                case 'Annulé':
                                    $statusClass = 'bg-red-100 text-red-800';
                                    $statusIcon = 'fas fa-times';
                                    $statusText = 'Annulé';
                                    break;
                                case 'Consultation':
                                    $statusClass = 'bg-purple-100 text-purple-800';
                                    $statusIcon = 'fas fa-stethoscope';
                                    $statusText = 'Consultation';
                                    break;
                                default:
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                    $statusIcon = 'fas fa-clock';
                                    $statusText = 'En Attente';
                                    break;
                            }
                        @endphp
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                            <i class="{{ $statusIcon }} mr-1"></i>
                            {{ $statusText }}
                        </span>
                    </div>

                    <!-- Actions -->
                    @if($canManageRdv)
                        <div class="flex flex-wrap gap-1 pt-2 border-t border-gray-100">
                            <button type="button" wire:click="changerStatutRendezVous({{ $rdv->IDRdv }}, 'Confirmé')" class="flex-1 inline-flex items-center justify-center px-2 py-1.5 rounded bg-blue-500 text-white text-xs font-semibold hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 touch-friendly" title="Patient présent au cabinet">
                                <i class="fas fa-user-check mr-1"></i>
                                Présent
                            </button>
                            <button type="button" wire:click="changerStatutRendezVous({{ $rdv->IDRdv }}, 'En cours')" class="flex-1 inline-flex items-center justify-center px-2 py-1.5 rounded bg-green-500 text-white text-xs font-semibold hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-400 touch-friendly" title="Avec le médecin">
                                <i class="fas fa-user-md mr-1"></i>
                                En cours
                            </button>
                            <button type="button" wire:click="changerStatutRendezVous({{ $rdv->IDRdv }}, 'Terminé')" class="flex-1 inline-flex items-center justify-center px-2 py-1.5 rounded bg-gray-500 text-white text-xs font-semibold hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 touch-friendly" title="Terminé">
                                <i class="fas fa-check-double mr-1"></i>
                                Terminé
                            </button>
                            <button type="button" wire:click="changerStatutRendezVous({{ $rdv->IDRdv }}, 'Annulé')" class="flex-1 inline-flex items-center justify-center px-2 py-1.5 rounded bg-red-500 text-white text-xs font-semibold hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 touch-friendly" title="Annulé">
                                <i class="fas fa-times mr-1"></i>
                                Annulé
                            </button>
                        </div>
                    @else
                        <div class="pt-2 border-t border-gray-100">
                            <span class="text-gray-400 text-xs">Lecture seule</span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                    <i class="fas fa-calendar-times text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500 text-lg">Aucun rendez-vous à venir</p>
                </div>
            @endforelse
        </div>

        <!-- Version desktop - Table -->
        <div class="hidden lg:block table-responsive overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                                                 <th scope="col" class="px-6 py-3 text-center">
                             <input type="checkbox" wire:model.live="selectAll" wire:click="toggleSelectAll" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                         </th>
                         <th scope="col" class="px-6 py-3 text-center text-sm font-medium text-gray-500 uppercase tracking-wider">
                             Ordre
                         </th>
                         <th scope="col" class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                             Patient
                         </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                            Médecin
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                            Date
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                            Heure
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                            Acte prévu
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                            Statut
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-sm font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($rendezVous as $rdv)
                        <tr class="hover:bg-gray-50">
                             <td class="px-6 py-4 whitespace-nowrap text-center">
                                 <input type="checkbox" wire:model.live="selectedRdvIds" value="{{ $rdv->IDRdv }}" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold text-white bg-blue-600 rounded-full min-w-[2rem]">
                                     {{ str_pad($rdv->OrdreRDV ?? 0, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                             </td>
                             <td class="px-6 py-4 whitespace-nowrap">
                                 <div class="text-sm font-medium text-gray-900">
                                     {{ $rdv->patient->Nom ?? 'N/A' }}
                                 </div>
                                 @if(!empty($rdv->patient->Telephone1))
                                     <div class="text-sm text-gray-500">
                                         {{ $rdv->patient->Telephone1 }}
                                     </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    Dr. {{ $rdv->medecin->Nom ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ \Carbon\Carbon::parse($rdv->dtPrevuRDV)->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $rdv->ActePrevu }}">
                                {{ $rdv->ActePrevu }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    switch($rdv->rdvConfirmer) {
                                        case 'En Attente':
                                        case 'En attente':
                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                            $statusIcon = 'fas fa-clock';
                                            break;
                                        case 'confirmé':
                                        case 'Confirmé':
                                            $statusClass = 'bg-blue-100 text-blue-800';
                                            $statusIcon = 'fas fa-user-check';
                                            break;
                                        case 'En cours':
                                            $statusClass = 'bg-green-100 text-green-800';
                                            $statusIcon = 'fas fa-user-md';
                                            break;
                                        case 'terminé':
                                        case 'Terminé':
                                            $statusClass = 'bg-gray-100 text-gray-800';
                                            $statusIcon = 'fas fa-check-double';
                                            break;
                                        case 'annulé':
                                        case 'Annulé':
                                            $statusClass = 'bg-red-100 text-red-800';
                                            $statusIcon = 'fas fa-times';
                                            break;
                                        case 'Consultation':
                                            $statusClass = 'bg-purple-100 text-purple-800';
                                            $statusIcon = 'fas fa-stethoscope';
                                            break;
                                        default:
                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                            $statusIcon = 'fas fa-clock';
                                            break;
                                    }
                                @endphp
                                
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                    <i class="{{ $statusIcon }} mr-1"></i>
                                    @switch($rdv->rdvConfirmer)
                                        @case('En Attente')
                                        @case('En attente')
                                            En Attente
                                            @break
                                        @case('confirmé')
                                        @case('Confirmé')
                                            Présent au cabinet
                                            @break
                                        @case('En cours')
                                            Avec le médecin
                                            @break
                                        @case('terminé')
                                        @case('Terminé')
                                            Terminé
                                            @break
                                        @case('annulé')
                                        @case('Annulé')
                                            Annulé
                                            @break
                                        @case('Consultation')
                                            Consultation
                                            @break
                                        @default
                                            En Attente
                                    @endswitch
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($canManageRdv)
                                    <div class="flex items-center justify-center space-x-1">
                                        <button type="button" wire:click="changerStatutRendezVous({{ $rdv->IDRdv }}, 'Confirmé')" class="inline-flex items-center justify-center px-2 py-1 rounded bg-blue-500 text-white text-xs font-semibold hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 touch-friendly" title="Patient présent au cabinet">
                                            <i class="fas fa-user-check"></i>
                                            <span class="ml-1">Présent</span>
                                        </button>
                                        <button type="button" wire:click="changerStatutRendezVous({{ $rdv->IDRdv }}, 'En cours')" class="inline-flex items-center justify-center px-2 py-1 rounded bg-green-500 text-white text-xs font-semibold hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-400 touch-friendly" title="Avec le médecin">
                                            <i class="fas fa-user-md"></i>
                                            <span class="ml-1">En cours</span>
                                        </button>
                                        <button type="button" wire:click="changerStatutRendezVous({{ $rdv->IDRdv }}, 'Terminé')" class="inline-flex items-center justify-center px-2 py-1 rounded bg-gray-500 text-white text-xs font-semibold hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 touch-friendly" title="Terminé">
                                            <i class="fas fa-check-double"></i>
                                            <span class="ml-1">Terminé</span>
                                        </button>
                                        <button type="button" wire:click="changerStatutRendezVous({{ $rdv->IDRdv }}, 'Annulé')" class="inline-flex items-center justify-center px-2 py-1 rounded bg-red-500 text-white text-xs font-semibold hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 touch-friendly" title="Annulé">
                                            <i class="fas fa-times"></i>
                                            <span class="ml-1">Annulé</span>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">Lecture seule</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                             <td colspan="9" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                Aucun rendez-vous à venir
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-3 sm:px-6 py-4 border-t border-gray-200">
            {{ $rendezVous->links() }}
        </div>
    </div>

    <!-- Modal de modification groupée -->
    @if($showBulkEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto modal-backdrop animate-backdrop-fade-in">
            <div class="flex items-center justify-center min-h-screen px-2 sm:px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75 backdrop-blur-sm"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-2xl modal-content animate-modal-fade-in">
                    <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-gray-200 rounded-t-2xl modal-header sticky top-0 z-10 bg-gradient-to-r from-blue-600 to-blue-700">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <i class="fas fa-edit header-icon text-white text-lg sm:text-xl"></i>
                            <h2 class="text-lg sm:text-xl font-bold text-white">Modification groupée des rendez-vous</h2>
                        </div>
                        <button wire:click="closeBulkEditModal" 
                                class="text-white hover:text-red-200 text-xl sm:text-2xl flex items-center gap-1 sm:gap-2 modal-close-button fixed top-2 sm:top-4 right-2 sm:right-4 z-20 bg-white bg-opacity-20 backdrop-blur-sm rounded-full p-1 sm:p-2 shadow-lg animate-close-button-appear touch-friendly speed-transition-fast hover:bg-opacity-30">
                            <i class="fas fa-times"></i> <span class="text-sm sm:text-base font-medium hidden sm:inline">Fermer</span>
                        </button>
                    </div>
                    
                    <div class="px-3 sm:px-4 md:px-6 pt-4 sm:pt-5 pb-4 sm:pb-4 modal-body pt-12 sm:pt-16 animate-modal-content-slide-in">
                        <div class="space-y-4 sm:space-y-6" id="bulk-edit-form">
                            <!-- Nouvelle date -->
                            <div class="mb-4 animate-speed-fade-in" style="animation-delay: 0.1s;">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar mr-2 text-blue-600"></i>
                                    Nouvelle date de rendez-vous
                                </label>
                                <input type="date" 
                                       wire:model="bulkEditData.newDate" 
                                       min="{{ date('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 speed-transition-fast speed-focus"
                                       required>
                            </div>

                            <!-- Heure de début -->
                            <div class="mb-4 animate-speed-fade-in" style="animation-delay: 0.2s;">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-clock mr-2 text-blue-600"></i>
                                    Heure de début
                                </label>
                                <input type="time" 
                                       wire:model="bulkEditData.startTime" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 speed-transition-fast speed-focus"
                                       required>
                            </div>

                            <!-- Intervalle entre les rendez-vous -->
                            <div class="mb-4 animate-speed-fade-in" style="animation-delay: 0.3s;">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-stopwatch mr-2 text-blue-600"></i>
                                    Intervalle entre les rendez-vous (minutes)
                                </label>
                                <select wire:model="bulkEditData.interval" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 speed-transition-fast speed-focus">
                                    <option value="15">15 minutes</option>
                                    <option value="20">20 minutes</option>
                                    <option value="30">30 minutes</option>
                                    <option value="45">45 minutes</option>
                                    <option value="60">1 heure</option>
                                </select>
                            </div>

                            <!-- Aperçu des horaires -->
                            @if($bulkEditData['newDate'] && $bulkEditData['startTime'])
                                <div class="mb-4 sm:mb-6 animate-speed-fade-in" style="animation-delay: 0.4s;">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-list mr-2 text-blue-600"></i>
                                        Aperçu des horaires et ordre
                                    </label>
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 sm:p-4 max-h-32 sm:max-h-40 overflow-y-auto speed-transition-fast">
                                        @php
                                            $currentTime = \Carbon\Carbon::parse($bulkEditData['newDate'] . ' ' . $bulkEditData['startTime']);
                                            $interval = (int)($bulkEditData['interval'] ?? 15);
                                            $lastOrderNumber = \App\Models\Rendezvou::whereDate('dtPrevuRDV', $bulkEditData['newDate'])
                                                ->where('fkidcabinet', Auth::user()->fkidcabinet)
                                                ->max('OrdreRDV') ?? 0;
                                            $orderNumber = 1;
                                        @endphp
                                        @foreach($selectedRdvIds as $index => $rdvId)
                                            <div class="flex items-center justify-between py-1 {{ $index > 0 ? 'border-t border-gray-200' : '' }} animate-speed-slide-in" style="animation-delay: {{ 0.5 + ($index * 0.1) }}s;">
                                                <div class="flex items-center gap-2 sm:gap-3">
                                                    <span class="inline-flex items-center justify-center px-1.5 sm:px-2 py-0.5 sm:py-1 text-xs font-bold text-white bg-blue-600 rounded-full min-w-[1.5rem] sm:min-w-[2rem] animate-speed-pulse">
                                                        {{ str_pad($lastOrderNumber + $orderNumber, 2, '0', STR_PAD_LEFT) }}
                                                    </span>
                                                    <span class="text-xs sm:text-sm text-gray-600">RDV #{{ $rdvId }}</span>
                                                </div>
                                                <span class="text-xs sm:text-sm font-medium text-gray-900">
                                                    {{ $currentTime->format('H:i') }}
                                                </span>
                                            </div>
                                            @php
                                                $currentTime->addMinutes($interval);
                                                $orderNumber++;
                                            @endphp
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Boutons d'action -->
                            <div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3 pt-4 border-t border-gray-200 animate-speed-fade-in" style="animation-delay: 0.6s;">
                                <button type="button" 
                                        wire:click="closeBulkEditModal"
                                        class="w-full sm:w-auto px-3 sm:px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 touch-friendly-button speed-transition-fast hover:scale-105">
                                    <i class="fas fa-times mr-2"></i>
                                    Annuler
                                </button>
                                <button type="button" 
                                        wire:click="updateBulkRdv"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        class="w-full sm:w-auto px-3 sm:px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 touch-friendly-button speed-transition-fast hover:scale-105 speed-glow">
                                    <span wire:loading.remove wire:target="updateBulkRdv">
                                        <i class="fas fa-save mr-2"></i>
                                        Mettre à jour
                                    </span>
                                    <span wire:loading wire:target="updateBulkRdv" class="flex items-center">
                                        <div class="animate-speed-loading rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                        Mise à jour...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

         <!-- Messages de notification -->
     @if (session()->has('message'))
        <div class="fixed bottom-4 right-4 bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-2 sm:py-3 rounded shadow-lg text-sm sm:text-base">
             {{ session('message') }}
             @if($showPrintButton)
                 <div class="mt-2">
                    <button wire:click="printRendezVous" class="inline-flex items-center px-2 sm:px-3 py-1 bg-blue-600 text-white text-xs sm:text-sm rounded hover:bg-blue-700 touch-friendly-button">
                         <i class="fas fa-print mr-1"></i>
                         Imprimer le reçu
                     </button>
                 </div>
             @endif
         </div>
     @endif

     @if (session()->has('error'))
        <div class="fixed bottom-4 right-4 bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-2 sm:py-3 rounded shadow-lg text-sm sm:text-base">
             {{ session('error') }}
         </div>
     @endif
</div>

<script>
    {{-- L'écouteur open-receipt est géré globalement dans le layout principal pour éviter les doublons --}}
    
    // Empêcher la fermeture du modal quand un patient est sélectionné
    window.addEventListener('patient-selected', function(e) {
        console.log('Patient sélectionné dans le modal:', e.detail);
        // Empêcher la propagation d'événements qui pourraient fermer le modal
        e.stopPropagation();
    });
    
    // Empêcher la fermeture du modal sur les clics à l'intérieur
    document.addEventListener('click', function(e) {
        if (e.target.closest('.modal-content')) {
            e.stopPropagation();
        }
    });
    
    // Amélioration de l'ouverture du modal de modification RDV
    document.addEventListener('livewire:load', function() {
        // Débogage du formulaire de modification groupée
        document.addEventListener('submit', function(e) {
            if (e.target.id === 'bulk-edit-form') {
                console.log('🔄 Formulaire de modification groupée soumis');
                console.log('📋 Données du formulaire:', {
                    newDate: document.querySelector('input[wire\\:model="bulkEditData.newDate"]')?.value,
                    startTime: document.querySelector('input[wire\\:model="bulkEditData.startTime"]')?.value,
                    interval: document.querySelector('select[wire\\:model="bulkEditData.interval"]')?.value
                });
            }
        });
        
        // Débogage des clics sur le bouton de soumission
        document.addEventListener('click', function(e) {
            if (e.target.closest('button[type="submit"]') && e.target.closest('#bulk-edit-form')) {
                console.log('🔄 Bouton "Mettre à jour" cliqué');
            }
        });
        // Écouter l'ouverture du modal de modification groupée
        Livewire.hook('message.processed', (message, component) => {
            if (message.updateQueue.some(update => update.payload && update.payload.showBulkEditModal)) {
                // Modal de modification groupée ouvert
                setTimeout(() => {
                    // Ajouter des effets visuels supplémentaires
                    const modal = document.querySelector('.modal-content');
                    if (modal) {
                        modal.classList.add('animate-speed-zoom');
                        
                        // Effet de focus sur le premier champ
                        const firstInput = modal.querySelector('input[type="date"]');
                        if (firstInput) {
                            setTimeout(() => {
                                firstInput.focus();
                                firstInput.classList.add('speed-focus');
                            }, 400);
                        }
                        
                        // Animation des éléments du formulaire
                        const formElements = modal.querySelectorAll('.animate-speed-fade-in');
                        formElements.forEach((element, index) => {
                            element.style.animationDelay = `${0.1 + (index * 0.1)}s`;
                        });
                    }
                }, 100);
            }
        });

        // Amélioration de la fermeture du modal
        Livewire.hook('message.processed', (message, component) => {
            if (message.updateQueue.some(update => update.payload && update.payload.showBulkEditModal === false)) {
                // Modal de modification groupée fermé
                const modal = document.querySelector('.modal-content');
                if (modal) {
                    modal.classList.add('animate-modal-fade-out');
                }
            }
        });
    });

    // Effet de pulsation pour les numéros d'ordre
    document.addEventListener('DOMContentLoaded', function() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    const orderNumbers = document.querySelectorAll('.animate-speed-pulse');
                    orderNumbers.forEach(number => {
                        number.addEventListener('mouseenter', function() {
                            this.style.transform = 'scale(1.2)';
                            this.style.transition = 'transform 0.2s ease';
                        });
                        
                        number.addEventListener('mouseleave', function() {
                            this.style.transform = 'scale(1)';
                        });
                    });
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });

    // Amélioration des transitions de focus
    document.addEventListener('focusin', function(e) {
        if (e.target.classList.contains('speed-focus')) {
            e.target.style.transform = 'scale(1.02)';
            e.target.style.boxShadow = '0 0 0 3px rgba(30, 58, 138, 0.3)';
        }
    });

    document.addEventListener('focusout', function(e) {
        if (e.target.classList.contains('speed-focus')) {
            e.target.style.transform = 'scale(1)';
            e.target.style.boxShadow = '';
        }
    });
</script>
