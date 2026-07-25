@extends('patient-auth.layout')

@section('title', 'Plan de traitement')

@section('content')
<h1 class="text-lg font-bold text-gray-800 mb-4">Mon plan de traitement</h1>

@forelse($lignes as $ligne)
    @php
        $dents = explode(',', $ligne->num_dent);
        $badge = match($ligne->statut) {
            'termine' => ['Terminé', 'bg-green-100 text-green-800'],
            'en_cours' => ['En cours', 'bg-orange-100 text-orange-800'],
            default => ['Planifié', 'bg-gray-100 text-gray-600'],
        };
    @endphp
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-3">
        <div class="flex items-start justify-between">
            <div>
                <div class="font-medium text-sm">{{ $ligne->acte_libelle }}</div>
                <div class="text-xs text-gray-500 mt-0.5">
                    {{ count($dents) > 1 ? count($dents) . ' dents (' . implode(', ', $dents) . ')' : 'Dent ' . $ligne->num_dent }}
                </div>
                @if($ligne->medecin)
                <div class="text-xs text-gray-400 mt-0.5">Dr. {{ $ligne->medecin->Nom }}</div>
                @endif
            </div>
            <span class="text-xs font-medium px-2 py-1 rounded-full {{ $badge[1] }} whitespace-nowrap">
                {{ $badge[0] }}
            </span>
        </div>
        @if($ligne->prix_ref)
        <div class="text-sm text-primary font-semibold mt-2">{{ number_format($ligne->prix_ref, 0, ',', ' ') }} MRU</div>
        @endif
    </div>
@empty
    <div class="text-center py-16 text-gray-400">
        <div class="text-4xl mb-3">🦷</div>
        <p>Aucun traitement enregistré pour le moment.</p>
    </div>
@endforelse
@endsection
