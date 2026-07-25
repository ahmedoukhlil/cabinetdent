@if(!$etat)
    <div class="text-center py-16 text-gray-400">
        <div class="text-4xl mb-3">📅</div>
        <p>Vous n'avez pas de rendez-vous aujourd'hui.</p>
    </div>
@else
    @php $rdv = $etat['rdv']; @endphp

    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-4 text-center">
        <div class="text-sm text-gray-500 mb-1">Votre position dans la file</div>
        <div class="text-4xl font-bold text-primary">{{ $etat['positionPatient'] }}</div>
        @if($etat['patientsAvantMoi'] > 0)
        <div class="text-sm text-gray-600 mt-2">
            {{ $etat['patientsAvantMoi'] }} patient(s) avant vous
        </div>
        <div class="text-xs text-gray-400 mt-1">
            Temps d'attente estimé : ~{{ $etat['tempsAttenteEstime'] }} min
        </div>
        @else
        <div class="text-sm text-green-600 font-medium mt-2">C'est bientôt votre tour</div>
        @endif
    </div>

    @if($rdv->rdvConfirmer === 'En cours')
    <div class="bg-blue-50 border border-blue-200 text-blue-700 text-sm rounded-lg p-3 mb-4 text-center font-medium">
        Vous êtes actuellement en consultation
    </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm divide-y divide-gray-100">
        <div class="p-3 text-xs font-semibold text-gray-500 uppercase">
            File d'attente — Dr. {{ $rdv->medecin->Nom ?? '' }}
        </div>
        @foreach($etat['fileAttente'] as $r)
        @php
            $estMoi = $r->IDRdv === $rdv->IDRdv;
            $termine = in_array($r->rdvConfirmer, ['Terminé', 'terminé']);
        @endphp
        <div class="p-3 flex items-center gap-3 text-sm {{ $estMoi ? 'bg-blue-50' : '' }}">
            <span class="w-7 h-7 flex items-center justify-center rounded-full text-xs font-bold flex-shrink-0
                {{ $r->rdvConfirmer === 'En cours' ? 'bg-blue-600 text-white' : ($termine ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600') }}">
                {{ $r->OrdreRDV }}
            </span>
            <span class="flex-1 {{ $estMoi ? 'font-semibold text-blue-800' : 'text-gray-700' }}">
                {{ $estMoi ? 'Vous' : ($r->patient->NomContact ?? 'Patient') }}
            </span>
            @if($r->rdvConfirmer === 'En cours')
                <span class="text-xs text-blue-600 font-medium">En cours</span>
            @elseif($termine)
                <span class="text-xs text-green-600">Terminé</span>
            @endif
        </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-400 text-center mt-3">Mise à jour automatique toutes les 20 secondes</p>
@endif
