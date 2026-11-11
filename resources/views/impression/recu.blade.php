@php
    $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU';
    $f = $paiement->facture;
    $remboursement = $paiement->type === 'remboursement';
@endphp
<x-impression :titre="'Reçu '.$paiement->id" :cabinet="$paiement->cabinet">
    <x-slot:entete>
        <h2>{{ $remboursement ? 'Reçu de remboursement' : 'Reçu de paiement' }} N° {{ $paiement->id }}</h2>
        <div>{{ $paiement->created_at->format('d/m/Y H:i') }}</div>
    </x-slot:entete>

    <table>
        <tr><th style="width:40%">Patient</th><td>{{ $paiement->patient->nom_complet }} (fiche N° {{ $paiement->patient->numero_fiche }})</td></tr>
        <tr><th>Facture</th><td>N° {{ $f->numero }} du {{ $f->date_facture->format('d/m/Y') }} · total {{ $mru($f->total) }}</td></tr>
        <tr><th>Payeur</th><td>{{ $paiement->payeur === 'assureur' ? 'Assureur' : 'Patient' }}</td></tr>
        <tr><th>Mode</th><td>{{ \App\Models\ModePaiement::libelles($paiement->cabinet_id)[$paiement->mode] ?? $paiement->mode }}@if ($paiement->reference) · réf. {{ $paiement->reference }}@endif</td></tr>
        <tr><th>{{ $remboursement ? 'Montant remboursé' : 'Montant reçu' }}</th><td style="font-size:18px;font-weight:600">{{ $mru($paiement->montant) }}</td></tr>
        <tr><th>Reste à payer sur la facture</th><td>{{ $mru(max(0, $f->restePatient()) + max(0, $f->restePec())) }} (situation actuelle)</td></tr>
        <tr><th>Encaissé par</th><td>{{ $paiement->auteur?->name }}</td></tr>
    </table>

    <p class="muted" style="margin-top:40px">Signature et cachet</p>
</x-impression>
