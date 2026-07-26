@extends('patient-auth.layout')

@section('title', 'Plan de traitement')

@section('content')
<h1 class="text-lg font-bold text-gray-800 mb-4">Mon plan de traitement</h1>

@forelse($lignes as $ligne)
    @php
        $dents = explode(',', $ligne->num_dent);
        $config = match($ligne->statut) {
            'termine' => ['Terminé', 'bg-green-100 text-green-800', 'bg-green-600', 'fa-check'],
            'en_cours' => ['En cours', 'bg-orange-100 text-orange-800', 'bg-orange-500', 'fa-spinner'],
            default => ['Planifié', 'bg-gray-100 text-gray-600', 'bg-gray-300', 'fa-clock'],
        };
        [$label, $badgeClasses, $pointClasses, $icone] = $config;
    @endphp
    <div class="flex gap-3">
        {{-- Point + ligne de la timeline --}}
        <div class="flex flex-col items-center">
            <span class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs flex-shrink-0 {{ $pointClasses }}">
                <i class="fas {{ $icone }}"></i>
            </span>
            @if(!$loop->last)
            <span class="w-0.5 flex-1 bg-gray-200 my-1"></span>
            @endif
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-4 flex-1">
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
                <span class="text-xs font-medium px-2 py-1 rounded-full {{ $badgeClasses }} whitespace-nowrap">
                    {{ $label }}
                </span>
            </div>
            @if($ligne->prix_ref)
            <div class="text-sm text-primary font-semibold mt-2">{{ number_format($ligne->prix_ref, 0, ',', ' ') }} MRU</div>
            @endif
        </div>
    </div>
@empty
    <div class="text-center py-16 text-gray-400">
        <div class="text-4xl mb-3"><i class="fas fa-tooth"></i></div>
        <p>Aucun traitement enregistré pour le moment.</p>
    </div>
@endforelse
@endsection
