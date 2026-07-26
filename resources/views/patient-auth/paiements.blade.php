@extends('patient-auth.layout')

@section('title', 'Paiements')

@section('content')
<h1 class="text-lg font-bold text-gray-800 mb-4">Historique des paiements</h1>

@forelse($paiements as $paiement)
<div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-3 flex items-center justify-between">
    <div>
        <div class="font-medium text-sm">{{ $paiement->designation ?? ($paiement->TypePAie ?: 'Paiement') }}</div>
        @if($paiement->medecin)
        <div class="text-xs text-gray-500 mt-0.5">Dr. {{ $paiement->medecin }}</div>
        @endif
        <div class="text-xs text-gray-400">
            {{ $paiement->dateoper ? \Carbon\Carbon::parse($paiement->dateoper)->format('d/m/Y H:i') : '' }}
        </div>
    </div>
    <span class="text-sm font-semibold text-green-700">
        {{ number_format(abs($paiement->MontantOperation), 0, ',', ' ') }} MRU
    </span>
</div>
@empty
<div class="text-center py-16 text-gray-400">
    <div class="text-4xl mb-3"><i class="fas fa-credit-card"></i></div>
    <p>Aucun paiement enregistré pour le moment.</p>
</div>
@endforelse
@endsection
