@extends('patient-auth.layout')

@section('title', 'Factures')

@section('content')
<h1 class="text-lg font-bold text-gray-800 mb-4">Mes factures</h1>

@forelse($factures as $facture)
    @php $solde = $facture->TotalfactPatient - $facture->TotReglPatient; @endphp
    <a href="{{ route('patient.factures.detail', $facture->Idfacture) }}"
       class="block bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-3">
        <div class="flex items-start justify-between">
            <div>
                <div class="font-medium text-sm">Facture N° {{ $facture->Nfacture }}</div>
                <div class="text-xs text-gray-500 mt-0.5">
                    {{ $facture->DtFacture ? \Carbon\Carbon::parse($facture->DtFacture)->format('d/m/Y') : '' }}
                </div>
            </div>
            <span class="text-xs font-medium px-2 py-1 rounded-full whitespace-nowrap
                {{ $solde > 0 ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800' }}">
                {{ $solde > 0 ? 'À régler' : 'Payée' }}
            </span>
        </div>
        <div class="flex items-center justify-between mt-2">
            <span class="text-sm font-semibold">{{ number_format($facture->TotalfactPatient, 0, ',', ' ') }} MRU</span>
            @if($solde > 0)
            <span class="text-xs text-orange-600">Reste {{ number_format($solde, 0, ',', ' ') }} MRU</span>
            @endif
        </div>
    </a>
@empty
    <div class="text-center py-16 text-gray-400">
        <div class="text-4xl mb-3">🧾</div>
        <p>Aucune facture pour le moment.</p>
    </div>
@endforelse
@endsection
