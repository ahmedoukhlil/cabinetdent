@extends('patient-auth.layout')

@section('title', 'Facture ' . $facture->Nfacture)

@section('content')
<a href="{{ route('patient.factures') }}" class="text-sm text-primary hover:underline mb-4 inline-block"><i class="fas fa-arrow-left text-xs"></i> Retour aux factures</a>

<div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-4">
    <h1 class="text-lg font-bold text-gray-800">Facture N° {{ $facture->Nfacture }}</h1>
    <p class="text-sm text-gray-500">
        {{ $facture->DtFacture ? \Carbon\Carbon::parse($facture->DtFacture)->format('d/m/Y') : '' }}
        @if($facture->medecin) · Dr. {{ $facture->medecin->Nom }} @endif
    </p>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm mb-4 divide-y divide-gray-100">
    <div class="p-3 text-xs font-semibold text-gray-500 uppercase">Actes facturés</div>
    @forelse($facture->details as $detail)
    <div class="p-3 flex items-center justify-between text-sm">
        <div>
            <div class="font-medium">{{ $detail->Actes }}</div>
            @if($detail->Dents && $detail->Dents !== 'Dent')
            <div class="text-xs text-gray-400">Dent(s) {{ $detail->Dents }}</div>
            @endif
        </div>
        <span>{{ number_format($detail->PrixFacture * $detail->Quantite, 0, ',', ' ') }} MRU</span>
    </div>
    @empty
    <div class="p-3 text-sm text-gray-400">Aucun détail disponible.</div>
    @endforelse
</div>

<div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-4 space-y-2 text-sm">
    <div class="flex justify-between">
        <span class="text-gray-500">Total facture</span>
        <span class="font-medium">{{ number_format($facture->TotFacture, 0, ',', ' ') }} MRU</span>
    </div>
    @if($facture->TotalPEC > 0)
    <div class="flex justify-between">
        <span class="text-gray-500">Pris en charge assurance</span>
        <span class="font-medium">{{ number_format($facture->TotalPEC, 0, ',', ' ') }} MRU</span>
    </div>
    @endif
    <div class="flex justify-between">
        <span class="text-gray-500">À votre charge</span>
        <span class="font-medium">{{ number_format($facture->TotalfactPatient, 0, ',', ' ') }} MRU</span>
    </div>
    <div class="flex justify-between">
        <span class="text-gray-500">Déjà réglé</span>
        <span class="font-medium">{{ number_format($facture->TotReglPatient, 0, ',', ' ') }} MRU</span>
    </div>
    @php $solde = $facture->TotalfactPatient - $facture->TotReglPatient; @endphp
    <div class="flex justify-between pt-2 border-t border-gray-100">
        <span class="font-semibold {{ $solde > 0 ? 'text-orange-600' : 'text-green-600' }}">
            {{ $solde > 0 ? 'Reste à payer' : 'Soldée' }}
        </span>
        <span class="font-bold {{ $solde > 0 ? 'text-orange-600' : 'text-green-600' }}">
            {{ number_format(max($solde, 0), 0, ',', ' ') }} MRU
        </span>
    </div>
</div>

@if($facture->reglements->isNotEmpty())
<div class="bg-white rounded-xl border border-gray-100 shadow-sm divide-y divide-gray-100">
    <div class="p-3 text-xs font-semibold text-gray-500 uppercase">Paiements liés</div>
    @foreach($facture->reglements as $reglement)
    <div class="p-3 flex items-center justify-between text-sm">
        <div>
            <div class="font-medium">{{ $reglement->typeReglement ?? 'Paiement' }}</div>
            <div class="text-xs text-gray-400">
                {{ $reglement->dtreglement ? \Carbon\Carbon::parse($reglement->dtreglement)->format('d/m/Y') : '' }}
            </div>
        </div>
        <span>{{ number_format($reglement->MontantRec, 0, ',', ' ') }} MRU</span>
    </div>
    @endforeach
</div>
@endif
@endsection
