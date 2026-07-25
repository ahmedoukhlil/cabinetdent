@extends('patient-auth.layout')

@section('title', 'Paiements')

@section('content')
<h1 class="text-lg font-bold text-gray-800 mb-4">Historique des paiements</h1>

@forelse($reglements as $reglement)
<div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-3 flex items-center justify-between">
    <div>
        <div class="font-medium text-sm">{{ $reglement->typeReglement ?? 'Paiement' }}</div>
        <div class="text-xs text-gray-500 mt-0.5">
            Facture N° {{ $reglement->facture_numero }}
        </div>
        <div class="text-xs text-gray-400">
            {{ $reglement->dtreglement ? \Carbon\Carbon::parse($reglement->dtreglement)->format('d/m/Y') : '' }}
        </div>
    </div>
    <span class="text-sm font-semibold text-green-700">
        {{ number_format($reglement->MontantRec, 0, ',', ' ') }} MRU
    </span>
</div>
@empty
<div class="text-center py-16 text-gray-400">
    <div class="text-4xl mb-3">💳</div>
    <p>Aucun paiement enregistré pour le moment.</p>
</div>
@endforelse
@endsection
