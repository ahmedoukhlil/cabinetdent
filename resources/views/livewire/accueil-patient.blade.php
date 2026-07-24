<div class="w-full px-3 sm:px-4 md:px-6 lg:px-8 max-w-7xl mx-auto mt-4 md:mt-8">
@php $u = Auth::user(); @endphp

    {{-- Bannière de bienvenue --}}
    <div class="mb-4 md:mb-6 p-4 md:p-6 rounded-xl bg-primary text-white shadow-lg flex items-center justify-between gap-4">
        <div>
            @php
                $nomCab = Auth::user()->cabinet->NomCabinet ?? null;
                $excluded = ['Cabinet Savwa', 'Cabinet Medical Savwa', 'Cabinet savwa', 'SysMedical'];
                if (!$nomCab || in_array($nomCab, $excluded)) $nomCab = 'SysMedical';
            @endphp
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold mb-0.5 leading-tight">{{ $nomCab }}</h1>
            <p class="text-white/80 text-xs sm:text-sm md:text-base">
                {{ is_array(Auth::user()->typeuser) ? (Auth::user()->typeuser['Libelle'] ?? '') : (is_object(Auth::user()->typeuser) ? Auth::user()->typeuser->Libelle : Auth::user()->typeuser) }}
                — <span class="font-bold">{{ Auth::user()->NomComplet ?? Auth::user()->name ?? '' }}</span>
            </p>
        </div>
        <i class="fas fa-staff-snake text-4xl md:text-5xl opacity-20 flex-shrink-0"></i>
    </div>

    {{-- Recherche patient + actions rapides --}}
    @if($u->hasPermission('patient.view'))
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 md:p-5 flex flex-col lg:flex-row items-stretch lg:items-center gap-3 mb-4 md:mb-6">
        <div class="w-full lg:flex-1">
            <livewire:patient-search />
        </div>
        <div class="flex gap-2 flex-shrink-0">
            @if($u->hasPermission('patient.view'))
            <button type="button" x-data @click="$dispatch('open-gestion-patients-modal')" class="btn-secondary text-sm px-4 py-2.5 justify-center whitespace-nowrap">
                <i class="fas fa-users"></i> Liste patients
            </button>
            @endif
            @if($u->hasPermission('rendez-vous.view'))
            <button type="button" x-data @click="$dispatch('open-create-rdv-modal')" class="btn-secondary text-sm px-4 py-2.5 justify-center whitespace-nowrap relative">
                <i class="fas fa-calendar-plus"></i> Gestion RDV
                @if($rdvARappelerCount > 0)
                    <span class="absolute -top-2 -right-2 inline-flex items-center justify-center min-w-[1.2rem] h-5 px-1 rounded-full bg-red-500 text-white text-xs font-bold shadow">
                        <i class="fas fa-bell text-xs mr-0.5"></i>{{ $rdvARappelerCount }}
                    </span>
                @endif
            </button>
            @endif
        </div>
    </div>
    @endif

    {{-- Barre de navigation principale --}}
    <div class="flex flex-wrap gap-2 mb-4 py-3 px-3 md:px-4 justify-center rounded-xl bg-white border border-gray-100 shadow-sm">

        {{-- Gestion du patient (visible si accès patient) --}}
        @if($u->hasPermission('patient.view'))
        <button type="button" x-data="{ open: false }" @click="open = !open; $dispatch('patient-menu-toggled', { open })"
            @if(!$selectedPatient) disabled title="Sélectionnez un patient d'abord" @endif
            :class="open ? '!bg-primary !text-white !border-primary' : ''"
            class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center
                {{ !$selectedPatient ? 'opacity-50 cursor-not-allowed' : '' }}">
            <i class="fas fa-user-friends"></i>
            <span>Gestion du patient</span>
            <i class="fas fa-chevron-down text-xs opacity-60" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </button>
        @endif

        {{-- Caisse Paie --}}
        @if($u->hasPermission('caisse-operations.view'))
        <button type="button" x-data @click="$dispatch('open-caisse-operations-modal')" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
            <i class="fas fa-cash-register"></i> <span>Caisse Paie</span>
        </button>
        @endif

        {{-- Dépenses --}}
        @if($u->hasPermission('depenses.view'))
        <button type="button" x-data @click="$dispatch('open-depenses-modal')" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
            <i class="fas fa-receipt"></i> <span>Dépenses</span>
        </button>
        @endif

        {{-- Statistiques --}}
        @if($u->hasPermission('statistiques.view'))
        <button type="button" x-data @click="$dispatch('open-statistiques-modal')" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
            <i class="fas fa-chart-bar"></i> <span>Statistiques</span>
        </button>
        @endif

        {{-- Salle d'attente --}}
        @if($u->hasPermission('salle-attente.view'))
        <div class="relative">
            <button type="button" x-data @click="$dispatch('open-salle-attente-modal')"
                class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-couch"></i>
                <span>Salle d'attente</span>
            </button>
            <span id="salle-attente-badge"
                  class="hidden absolute -top-2.5 -right-2.5 items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-red-500 text-white text-xs font-bold shadow-lg z-10 pointer-events-none">
                <i class="fas fa-bell text-[9px]"></i><span id="salle-attente-count">0</span>
            </span>
        </div>
        @endif

        {{-- Gestion du cabinet --}}
        @if($u->hasAnyPermission(['medecin.view','assureur.view','act.view','stock.view','pharmacie.view','user.view','medecin.manage']))
        <button type="button" x-data="{ open: false }" @click="open = !open; $dispatch('cabinet-menu-toggled', { open })"
            :class="open ? '!bg-primary !text-white !border-primary' : ''"
            class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
            <i class="fas fa-cogs"></i>
            <span>Gestion du cabinet</span>
            <i class="fas fa-chevron-down text-xs opacity-60" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </button>
        @endif
    </div>

    {{-- Sous-menu Gestion du patient --}}
    @if($selectedPatient)
    <div x-data="{ open: false }" @patient-menu-toggled.window="open = $event.detail.open" x-show="open" x-cloak class="patient-menu-container mb-4">
        <div class="patient-submenu flex flex-wrap gap-2 justify-center py-3 px-3 md:px-4 bg-blue-50/60 border border-primary/20 rounded-xl show" data-menu="patient">

            @if($u->hasPermission('consultation.view'))
            <button type="button" x-data @click="$dispatch('open-consultation-modal')" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-stethoscope"></i>
                <span>Consultation</span>
            </button>
            @endif

            @if($u->hasPermission('facture.view') || $u->hasPermission('facture.view.own'))
            <button type="button" x-data @click="$dispatch('open-reglement-modal')" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Facture / Devis</span>
            </button>
            @endif

            @if($u->hasPermission('rendez-vous.view'))
            <button type="button" x-data @click="$dispatch('open-rendez-vous-modal')" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-calendar-check"></i>
                <span>Rendez-vous</span>
            </button>
            @endif

            @if($u->hasPermission('plan_traitement.create'))
            <button type="button" x-data @click="$dispatch('open-plan-traitement-modal')" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-tooth"></i>
                <span>Plan de traitement</span>
            </button>
            @endif

            @if($u->hasPermission('ordonnance.create'))
            <button type="button" x-data @click="$dispatch('open-ordonnance-modal')" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-file-prescription"></i>
                <span>Ordonnances</span>
            </button>
            @endif

            @if($u->hasPermission('dossier.view'))
            <button type="button" x-data @click="$dispatch('open-dossier-medical-modal')" class="patient-nav-button nav-button flex items-center gap-2 px-4 py-2.5 min-w-[9rem] border-2 rounded-xl text-sm font-semibold justify-center">
                <i class="fas fa-folder-open"></i>
                <span>Dossier médical</span>
            </button>
            @endif
        </div>
    </div>
    @endif

    {{-- Sous-menu Gestion du cabinet --}}
    <div x-data="{ open: false }" @cabinet-menu-toggled.window="open = $event.detail.open" x-show="open" x-cloak class="mb-4">
        <div class="flex flex-wrap gap-2 justify-center py-3 px-3 md:px-4 bg-gray-50 border border-gray-200 rounded-xl" data-menu="cabinet">

            @if($u->hasPermission('assureur.view'))
            <button type="button" x-data @click="$dispatch('open-assureur-modal')" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-shield-alt"></i> Assurances
            </button>
            @endif

            @if($u->hasPermission('act.view'))
            <button type="button" x-data @click="$dispatch('open-liste-actes-modal')" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-list-alt"></i> Actes / Soins
            </button>
            @endif

            @if($u->hasPermission('pharmacie.view'))
            <button type="button" x-data @click="$dispatch('open-liste-medicaments-modal')" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-pills"></i> Médicaments
            </button>
            @endif

            @if($u->hasPermission('medecin.view'))
            <button type="button" x-data @click="$dispatch('open-medecins-modal')" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-user-md"></i> Médecins
            </button>
            @endif

            @if($u->hasPermission('caisse-operations.view'))
            <button type="button" x-data @click="$dispatch('open-type-paiement-modal')" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-credit-card"></i> Paiements
            </button>
            @endif

            @if($u->hasPermission('user.view'))
            <button type="button" x-data @click="$dispatch('open-users-modal')" class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-users-cog"></i> Utilisateurs
            </button>
            @endif

            @if($u->hasPermission('medecin.manage'))
            <button type="button" x-data @click="$dispatch('open-parametres-cabinet-modal')"
                class="nav-button btn-secondary text-sm px-4 py-2.5 min-w-[9rem] justify-center">
                <i class="fas fa-sliders-h"></i> Paramètres
            </button>
            @endif
        </div>
    </div>

    {{-- Composant HistoriquePaiement toujours présent --}}
    <livewire:historique-paiement wire:key="historique-paiement" lazy="on-load" />

    {{-- Notifications flash --}}
    @if(session()->has('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="fixed bottom-4 right-4 z-50 toast toast-success" role="alert">
        <i class="fas fa-check-circle text-green-600"></i>
        <span>{{ session('success') }}</span>
        <button @click="show = false" class="ml-auto text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
    </div>
    @endif

    @if(session()->has('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="fixed bottom-4 right-4 z-50 toast toast-error" role="alert">
        <i class="fas fa-exclamation-circle text-red-600"></i>
        <span>{{ session('error') }}</span>
        <button @click="show = false" class="ml-auto text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
    </div>
    @endif


    {{-- ═══════════════════════════════════════════════════════
         MODAUX — pattern unifié : .modal-overlay / .modal-box
    ════════════════════════════════════════════════════════ --}}

    @php
$patientNom = $selectedPatient
    ? (is_array($selectedPatient)
        ? ($selectedPatient['NomPatient'] ?? $selectedPatient['Nom'] ?? 'Patient')
        : ($selectedPatient->NomPatient ?? $selectedPatient->Nom ?? 'Patient'))
    : '';
$patientId = $selectedPatient
    ? (is_array($selectedPatient) ? ($selectedPatient['ID'] ?? '') : ($selectedPatient->ID ?? ''))
    : '';
@endphp

{{-- ── GESTION DU PATIENT ── --}}

{{-- Consultation --}}
@if($selectedPatient)
<div x-data="{ open: false }"
     @open-consultation-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <div>
                <h2><i class="fas fa-stethoscope mr-2"></i>Consultation</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:consultation-form wire:key="consultation-modal-{{ $patientId }}" :patient="$selectedPatient" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Facture / Devis --}}
@if($selectedPatient)
<div x-data="{ open: false }"
     @open-reglement-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <div>
                <h2><i class="fas fa-file-invoice-dollar mr-2"></i>Facture / Devis</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:reglement-facture wire:key="reglement-modal-{{ $patientId }}" :selectedPatient="$selectedPatient" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Rendez-vous patient --}}
@if($selectedPatient)
<div x-data="{ open: false }"
     @open-rendez-vous-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <div>
                <h2><i class="fas fa-calendar-check mr-2"></i>Rendez-vous</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:create-rendez-vous wire:key="rendez-vous-modal-{{ $patientId }}" :patient="$selectedPatient" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Plan de traitement dentaire --}}
<div x-data="{ open: false }"
     @open-plan-traitement-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <div>
                <h2><i class="fas fa-tooth mr-2"></i>Plan de traitement dentaire</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            @if($selectedPatient)
            <livewire:plan-traitement-dentaire wire:key="plan-traitement-modal-{{ $patientId }}" :patient="$selectedPatient" />
            @endif
        </div>
    </div>
</div>

{{-- Ordonnances --}}
@if($selectedPatient)
<div x-data="{ open: false }"
     @open-ordonnance-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <div>
                <h2><i class="fas fa-file-prescription mr-2"></i>Ordonnances</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:ordonnance-manager wire:key="ordonnance-modal-{{ $patientId }}" :patient="$selectedPatient" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Dossier médical --}}
<div x-data="{ open: false }"
     @open-dossier-medical-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <div>
                <h2><i class="fas fa-folder-open mr-2"></i>Dossier médical</h2>
                <p>{{ $patientNom }}</p>
            </div>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            @if($selectedPatient)
            <livewire:dossier-medical-manager wire:key="dossier-medical-{{ $patientId }}" :patient="$selectedPatient" />
            @endif
        </div>
    </div>
</div>

{{-- ── GESTION RDV (général) ── --}}

<div x-data="{ open: false }"
     @open-create-rdv-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-6xl w-full">
        <div class="modal-header">
            <div>
                <h2><i class="fas fa-calendar-alt mr-2"></i>Gestion des Rendez-vous</h2>
            </div>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        {{-- Onglets --}}
        <div class="border-b border-gray-200 px-6 flex gap-6">
            <button wire:click="$set('activeRdvTab', 'create')"
                class="py-3 px-1 border-b-2 font-medium text-sm
                    {{ $activeRdvTab === 'create' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <i class="fas fa-plus mr-1"></i> Gestion RDV
            </button>
            <button wire:click="$set('activeRdvTab', 'reminders')"
                class="py-3 px-1 border-b-2 font-medium text-sm relative
                    {{ $activeRdvTab === 'reminders' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <i class="fas fa-bell mr-1"></i> Rappels RDV
                @if($rdvRemindersCount > 0)
                <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $rdvRemindersCount }}</span>
                @endif
            </button>
        </div>
        <div class="modal-body">
            <div id="modal-loading" class="hidden flex items-center justify-center py-8">
                <div class="spinner spinner-dark"></div>
                <span class="ml-3 text-gray-600 text-sm">Chargement...</span>
            </div>
            @if($activeRdvTab === 'create')
                <livewire:create-rendez-vous wire:key="create-rdv-modal" :patient="$selectedPatient" />
            @elseif($activeRdvTab === 'reminders')
                <livewire:rdv-reminders wire:key="rdv-reminders-modal" />
            @endif
        </div>
    </div>
</div>

{{-- ── GESTION DES PATIENTS ── --}}

@if($showNouveauPatientModal)
<div class="modal-overlay" wire:click.self="closeNouveauPatientModal">
    <div class="modal-box max-w-3xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus mr-2"></i>Nouveau patient</h2>
            <button type="button" wire:click="closeNouveauPatientModal" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:patient-manager wire:key="nouveau-patient-modal" :creationOnly="true" />
        </div>
    </div>
</div>
@endif

<div x-data="{ open: false }"
     @open-gestion-patients-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-6xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-users mr-2"></i>Gestion des patients</h2>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:patient-manager wire:key="gestion-patients-modal" lazy="on-load" />
        </div>
    </div>
</div>

@if($showCreateModal)
<div class="modal-overlay" wire:click.self="closeCreateModal">
    <div class="modal-box max-w-6xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-users mr-2"></i>Gestion des patients</h2>
            <button type="button" wire:click="closeCreateModal" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:patient-manager />
        </div>
    </div>
</div>
@endif

{{-- ── GESTION DU CABINET ── --}}

{{-- Caisse Paie --}}
@if($u->hasPermission('caisse-operations.view'))
<div x-data="{ open: false }"
     @open-caisse-operations-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open"
     x-cloak
     class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-cash-register mr-2"></i>Caisse Paie</h2>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:caisse-operations-manager wire:key="caisse-operations-modal" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Dépenses --}}
@if($u->hasPermission('depenses.view'))
<div x-data="{ open: false }"
     @open-depenses-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-receipt mr-2"></i>Dépenses</h2>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:depenses-manager wire:key="depenses-manager-modal" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Statistiques --}}
@if($u->hasPermission('statistiques.view'))
<div x-data="{ open: false }"
     @open-statistiques-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open"
     x-cloak
     class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-chart-bar mr-2"></i>Statistiques</h2>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:statistiques-manager wire:key="statistiques-manager-modal" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Assurances --}}
@if($u->hasPermission('assureur.view'))
<div x-data="{ open: false }"
     @open-assureur-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-shield-alt mr-2"></i>Gestion des assurances</h2>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:assureur-manager wire:key="assureur-manager-modal" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Actes / Soins --}}
@if($u->hasPermission('act.view'))
<div x-data="{ open: false }"
     @open-liste-actes-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-list-alt mr-2"></i>Liste des actes</h2>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:acte-manager wire:key="acte-manager-modal" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Médicaments --}}
@if($u->hasPermission('pharmacie.view'))
<div x-data="{ open: false }"
     @open-liste-medicaments-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-pills mr-2"></i>Liste des médicaments</h2>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:medicament-manager wire:key="medicament-manager-modal" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Médecins --}}
@if($u->hasPermission('medecin.view'))
<div x-data="{ open: false }"
     @open-medecins-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-user-md mr-2"></i>Gestion des médecins</h2>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:medecin-manager wire:key="medecin-manager-modal" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Modes de paiement --}}
@if($u->hasPermission('caisse-operations.view'))
<div x-data="{ open: false }"
     @open-type-paiement-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-4xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-credit-card mr-2"></i>Modes de paiement</h2>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:type-paiement-manager wire:key="type-paiement-manager-modal" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Salle d'attente --}}
@if($u->hasPermission('salle-attente.view'))
<div x-data="{ open: false }"
     @open-salle-attente-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" style="z-index: 40;" @click.self="open = false">
    <div class="modal-box max-w-4xl w-full">
        <div class="modal-header">
            <div>
                <h2><i class="fas fa-couch mr-2"></i>Salle d'attente</h2>
                <p class="text-sm text-white/70">Rendez-vous du jour — cliquez sur un patient pour accéder à son dossier</p>
            </div>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:salle-attente wire:key="salle-attente-modal" />
        </div>
    </div>
</div>
@endif

{{-- Paramètres cabinet --}}
@if($u->hasPermission('medecin.manage'))
<div x-data="{ open: false }"
     @open-parametres-cabinet-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <div>
                <h2><i class="fas fa-sliders-h mr-2"></i>Paramètres du cabinet</h2>
                <p>Configuration de l'en-tête et du pied de page des imprimés</p>
            </div>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:parametres-cabinet wire:key="parametres-cabinet-modal" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Utilisateurs --}}
@if($u->hasPermission('user.view'))
<div x-data="{ open: false }"
     @open-users-modal.window="open = true"
     @keydown.escape.window="open = false"
     @close-all-modals.window="open = false"
     x-show="open" x-cloak class="modal-overlay" @click.self="open = false">
    <div class="modal-box max-w-6xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-users-cog mr-2"></i>Gestion des utilisateurs</h2>
            <button type="button" @click="open = false" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <livewire:user-manager wire:key="user-manager-modal" lazy="on-load" />
        </div>
    </div>
</div>
@endif

{{-- Utilisateurs: page dédiée /users --}}

{{-- Conteneur de notifications flottantes --}}
<div id="salle-notif-container" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none" style="max-width:320px;"></div>

<script>
/* ── Coordination des modales : ferme toutes les modales ouvertes avant
   d'en ouvrir une nouvelle, pour éviter les superpositions (ex: salle
   d'attente + dossier médical en même temps). Intercepte tout clic sur un
   élément déclenchant $dispatch('open-...-modal') en phase de capture,
   pour émettre close-all-modals AVANT que l'ouverture ne se propage. ── */
document.addEventListener('click', function (e) {
    const target = e.target.closest('[x-data]');
    if (!target) return;
    const handler = target.getAttribute('@click') || target.getAttribute('x-on:click');
    if (handler && /\$dispatch\(\s*['"]open-[\w-]+-modal['"]/.test(handler)) {
        window.dispatchEvent(new CustomEvent('close-all-modals'));
    }
}, true);

/* ── Compteur générique badge ── */
function rafraichirCompteur(url, badgeId, countId) {
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById(badgeId);
            const count = document.getElementById(countId);
            if (!badge || !count) return;
            const n = data.count || 0;
            count.textContent = n;
            if (n > 0) {
                badge.classList.remove('hidden');
                badge.classList.add('inline-flex');
            } else {
                badge.classList.add('hidden');
                badge.classList.remove('inline-flex');
            }
        }).catch(() => {});
}

function rafraichirCompteurSalleAttente() {
    rafraichirCompteur('/api/salle-attente/count', 'salle-attente-badge', 'salle-attente-count');
}
function rafraichirCompteurSalleSoins() {
    rafraichirCompteur('/api/salle-soins/count', 'salle-soins-badge', 'salle-soins-count');
}
function rafraichirStockAlerts() {
    fetch('/api/stock/alerts', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const bFaible  = document.getElementById('stock-faible-badge');
            const cFaible  = document.getElementById('stock-faible-count');
            const bEpuise  = document.getElementById('stock-epuise-badge');
            const cEpuise  = document.getElementById('stock-epuise-count');
            if (bFaible && cFaible) {
                if (data.faible > 0) {
                    cFaible.textContent = data.faible;
                    bFaible.classList.remove('hidden');
                    bFaible.classList.add('inline-flex');
                } else {
                    bFaible.classList.add('hidden');
                    bFaible.classList.remove('inline-flex');
                }
            }
            if (bEpuise && cEpuise) {
                if (data.epuise > 0) {
                    cEpuise.textContent = data.epuise;
                    bEpuise.classList.remove('hidden');
                    bEpuise.classList.add('inline-flex');
                } else {
                    bEpuise.classList.add('hidden');
                    bEpuise.classList.remove('inline-flex');
                }
            }
        }).catch(() => {});
}

/* ── Afficher une notification flottante ── */
function afficherNotifSalle(icone, titre, message, couleur) {
    const container = document.getElementById('salle-notif-container');
    if (!container) return;

    const notif = document.createElement('div');
    notif.className = 'pointer-events-auto flex items-start gap-3 bg-white border-l-4 rounded-xl shadow-xl px-4 py-3 transition-all duration-500 opacity-0 translate-x-8';
    notif.style.borderColor = couleur;

    const iconWrap = document.createElement('div');
    iconWrap.className = 'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5';
    iconWrap.style.background = couleur + '20';
    const iconEl = document.createElement('i');
    iconEl.className = `fas ${icone} text-sm`;
    iconEl.style.color = couleur;
    iconWrap.appendChild(iconEl);

    const textWrap = document.createElement('div');
    textWrap.className = 'flex-1 min-w-0';
    const titreEl = document.createElement('p');
    titreEl.className = 'text-xs font-semibold text-gray-500 uppercase tracking-wide';
    titreEl.textContent = titre;
    const messageEl = document.createElement('p');
    messageEl.className = 'text-sm font-bold text-gray-800 truncate mt-0.5';
    messageEl.textContent = message;
    textWrap.appendChild(titreEl);
    textWrap.appendChild(messageEl);

    notif.appendChild(iconWrap);
    notif.appendChild(textWrap);
    container.appendChild(notif);

    // Entrée animée
    requestAnimationFrame(() => {
        notif.classList.remove('opacity-0', 'translate-x-8');
        notif.classList.add('opacity-100', 'translate-x-0');
    });

    // Sortie après 5 secondes
    setTimeout(() => {
        notif.classList.remove('opacity-100', 'translate-x-0');
        notif.classList.add('opacity-0', 'translate-x-8');
        setTimeout(() => notif.remove(), 500);
    }, 5000);
}

/* ── Notifications : enregistrées immédiatement, pas dans livewire:load ── */
// Éviter les doublons si Livewire re-rend le composant
if (!window._sysmedNotifInit) {
    window._sysmedNotifInit = true;

    window.addEventListener('nouveau-rdv-notif', e => {
        const d = e.detail;
        const msg = d.heure ? `${d.nom} — ${d.heure}` : d.nom;
        afficherNotifSalle('fa-calendar-check', 'Nouveau rendez-vous', msg, '#2563eb');
        rafraichirCompteurSalleAttente();
    });

    window.addEventListener('nouvelle-consultation-notif', e => {
        afficherNotifSalle('fa-stethoscope', 'Consultation enregistrée', e.detail.nom, '#16a34a');
        rafraichirCompteurSalleAttente();
    });

}

/* ── Compteurs polling ── */
if (!window._sysmedPollingInit) {
    window._sysmedPollingInit = true;
    rafraichirCompteurSalleAttente();
    setInterval(rafraichirCompteurSalleAttente, 15000);
}

document.addEventListener('livewire:load', function () {

    // Active state for patient sub-menu buttons
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.patient-nav-button');
        if (btn) {
            document.querySelectorAll('.patient-nav-button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
    });

    // Modal loading indicator
    window.addEventListener('modal-loading', function(e) {
        const loadingDiv = document.getElementById('modal-loading');
        if (!loadingDiv) return;
        if (e.detail.loading) {
            loadingDiv.classList.remove('hidden');
        } else {
            loadingDiv.classList.add('hidden');
        }
    });

    // Keyboard shortcuts (when modal is open)
    document.addEventListener('keydown', function(e) {
        if (!document.querySelector('.modal-overlay')) return;
        if (e.key === 'Escape') {
            const closeBtn = document.querySelector('button[wire\\:click*="fermer"], button[wire\\:click*="close"], button[wire\\:click*="Close"]');
            if (closeBtn) closeBtn.click();
        }
        if (e.ctrlKey && e.key === 'Enter') {
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.click();
        }
    });
});
</script>

</div>{{-- fin div racine --}}
