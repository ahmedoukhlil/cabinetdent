@php
    $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU';
    $f = $paiement->facture;
    $remboursement = $paiement->type === 'remboursement';
    $parAssurance = $paiement->payeur === 'assureur';
    $assuree = $f->estAssuree();
    $mode = \App\Models\ModePaiement::libelles($paiement->cabinet_id)[$paiement->mode] ?? $paiement->mode;
@endphp
<x-impression :titre="'Reçu '.$paiement->id" :cabinet="$paiement->cabinet">
    <x-slot:entete>
        <h2>{{ $remboursement ? 'Reçu de remboursement' : 'Reçu de paiement' }} N° {{ $paiement->id }}</h2>
        <div>{{ $paiement->created_at->format('d/m/Y H:i') }}</div>
    </x-slot:entete>

    <table>
        <tr><th style="width:40%">Patient</th><td>{{ $paiement->patient->nom_complet }} (fiche N° {{ $paiement->patient->numero_fiche }})</td></tr>
        @if ($f->assureur)
            <tr><th>Assurance</th><td>{{ $f->assureur->nom }}@if ($paiement->patient->matricule_assure) · matricule {{ $paiement->patient->matricule_assure }}@endif · prise en charge {{ $f->tauxLisible() }}</td></tr>
        @else
            <tr><th>Assurance</th><td>Aucune · totalité à la charge du patient</td></tr>
        @endif
        <tr><th>Facture</th><td>N° {{ $f->numero }} du {{ $f->date_facture->format('d/m/Y') }} · total {{ $mru($f->total) }}</td></tr>
    </table>

    <div class="encadre">
        <table>
            <tr><th style="width:40%">{{ $remboursement ? 'Remboursé à' : 'Payé par' }}</th><td><strong>{{ $parAssurance ? 'L\'assurance'.($f->assureur ? ' ('.$f->assureur->nom.')' : '') : 'Le patient' }}</strong>@if ($assuree) · au titre de la part {{ $parAssurance ? 'assurance' : 'patient' }}@endif</td></tr>
            <tr><th>Mode</th><td>{{ $mode }}@if ($paiement->reference) · réf. {{ $paiement->reference }}@endif</td></tr>
            <tr><th>{{ $remboursement ? 'Montant remboursé' : 'Montant reçu' }}</th><td style="font-size:18px;font-weight:600;border:0">{{ $mru($paiement->montant) }}</td></tr>
        </table>
    </div>

    <h3>État de paiement de la facture N° {{ $f->numero }} <span class="muted" style="font-weight:normal">· au {{ now()->format('d/m/Y à H:i') }}, ce {{ $remboursement ? 'remboursement' : 'paiement' }} compris</span></h3>
    @if ($assuree)
        <div class="repartition">
            <div class="bloc patient">
                <h4>Part patient</h4>
                <table>
                    <tr><td>À payer</td><td class="r">{{ $mru($f->part_patient) }}</td></tr>
                    <tr><td>Déjà réglé</td><td class="r">{{ $mru($f->regle_patient) }}</td></tr>
                    <tr class="fort"><td>Reste à payer</td><td class="r">{{ $mru(max(0, $f->restePatient())) }}</td></tr>
                </table>
            </div>
            <div class="bloc assurance">
                <h4>Part assurance · {{ $f->tauxLisible() }}</h4>
                <table>
                    <tr><td>Prise en charge</td><td class="r">{{ $mru($f->part_pec) }}</td></tr>
                    <tr><td>Déjà réglé</td><td class="r">{{ $mru($f->regle_pec) }}</td></tr>
                    <tr class="fort"><td>Reste dû</td><td class="r">{{ $mru(max(0, $f->restePec())) }}</td></tr>
                </table>
            </div>
        </div>
        @if (!$parAssurance && $f->restePatient() <= 0 && $f->restePec() > 0)
            <p class="muted">Le patient a réglé toute sa part. Le reste ({{ $mru($f->restePec()) }}) est dû par l'assurance.</p>
        @endif
    @else
        <div class="repartition" style="grid-template-columns:1fr">
            <div class="bloc patient" style="max-width:360px">
                <h4>À la charge du patient · 100&nbsp;%</h4>
                <table>
                    <tr><td>À payer</td><td class="r">{{ $mru($f->part_patient) }}</td></tr>
                    <tr><td>Déjà réglé</td><td class="r">{{ $mru($f->regle_patient) }}</td></tr>
                    <tr class="fort"><td>Reste à payer</td><td class="r">{{ $mru(max(0, $f->restePatient())) }}</td></tr>
                </table>
            </div>
        </div>
        @if ($f->restePatient() <= 0)<p class="muted">Facture entièrement réglée.</p>@endif
    @endif

    <p class="muted" style="margin-top:24px">Encaissé par {{ $paiement->auteur?->name }} · signature et cachet</p>
</x-impression>
