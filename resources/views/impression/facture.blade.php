@php
    $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU';
    $devis = $facture->statut === 'brouillon';
    $assuree = $facture->estAssuree();
    $parts = $assuree ? $facture->repartitionLignes() : [];
    $tauxPatient = rtrim(rtrim(number_format((1 - $facture->taux_pec) * 100, 2, ',', ''), '0'), ',')."\u{00A0}%";
@endphp
<x-impression :titre="$devis ? 'Devis D-'.$facture->id : 'Facture '.$facture->numero" :cabinet="$facture->cabinet">
    <x-slot:entete>
        @if ($devis)
            <h2>Devis N° D-{{ $facture->id }}</h2>
            <div>du {{ now()->format('d/m/Y') }}</div>
            <div class="muted">Valable 30 jours, jusqu'au {{ now()->addDays(30)->format('d/m/Y') }}</div>
        @else
            <h2>Facture N° {{ $facture->numero }}</h2>
            <div>du {{ $facture->date_facture->format('d/m/Y') }}</div>
        @endif
        @if ($facture->statut === 'annulee')<div style="color:#b91c1c;font-weight:600">ANNULÉE</div>@endif
    </x-slot:entete>

    <p>
        <strong>Patient :</strong> {{ $facture->patient->nom_complet }} (fiche N° {{ $facture->patient->numero_fiche }})<br>
        @if ($facture->patient->telephone)<span class="muted">Tél. {{ $facture->patient->telephone }}</span><br>@endif
        @if ($facture->assureur)<strong>Assurance :</strong> {{ $facture->assureur->nom }}@if ($facture->patient->matricule_assure) · matricule {{ $facture->patient->matricule_assure }}@endif
            @if ($assuree) · prise en charge {{ $facture->tauxLisible() }}@endif<br>@endif
        @if ($facture->dentiste)<strong>Praticien :</strong> Dr {{ $facture->dentiste->name }}@endif
    </p>

    <table>
        <thead><tr>
            <th>Acte</th><th>Dents</th><th class="r">Qté</th><th class="r">Prix unit.</th><th class="r">Montant</th>
            @if ($assuree)<th class="r">Part assurance</th><th class="r">Part patient</th>@endif
        </tr></thead>
        <tbody>
            @foreach ($facture->lignes as $l)
                <tr>
                    <td>{{ $l->libelle }}</td>
                    <td>{{ $l->dents ? implode(', ', $l->dents) : '—' }}</td>
                    <td class="r">{{ rtrim(rtrim(number_format($l->quantite, 2, ',', ''), '0'), ',') }}</td>
                    <td class="r">{{ $mru($l->prix_unitaire) }}</td>
                    <td class="r">{{ $mru($l->montant) }}</td>
                    @if ($assuree)
                        <td class="r">{{ $mru($parts[$l->id]['pec']) }}</td>
                        <td class="r">{{ $mru($parts[$l->id]['patient']) }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($assuree)
        <table class="totaux"><tr class="fort"><td>Total de la facture</td><td class="r">{{ $mru($facture->total) }}</td></tr></table>
        <div class="repartition">
            <div class="bloc assurance">
                <h4>À la charge de l'assurance · {{ $facture->tauxLisible() }}</h4>
                <div class="muted" style="margin-bottom:4px">{{ $facture->assureur?->nom }}</div>
                <table>
                    <tr><td>Montant pris en charge</td><td class="r">{{ $mru($facture->part_pec) }}</td></tr>
                    @unless ($devis)
                        <tr><td>Réglé par l'assurance</td><td class="r">{{ $mru($facture->regle_pec) }}</td></tr>
                        <tr class="fort"><td>Reste dû par l'assurance</td><td class="r">{{ $mru(max(0, $facture->restePec())) }}</td></tr>
                    @endunless
                </table>
            </div>
            <div class="bloc patient">
                <h4>À la charge du patient · {{ $tauxPatient }}</h4>
                <div class="muted" style="margin-bottom:4px">{{ $facture->patient->nom_complet }}</div>
                <table>
                    <tr><td>Montant à payer</td><td class="r">{{ $mru($facture->part_patient) }}</td></tr>
                    @unless ($devis)
                        <tr><td>Réglé par le patient</td><td class="r">{{ $mru($facture->regle_patient) }}</td></tr>
                        <tr class="fort"><td>Reste à payer par le patient</td><td class="r">{{ $mru(max(0, $facture->restePatient())) }}</td></tr>
                    @endunless
                </table>
            </div>
        </div>
        @if ($devis)
            <p class="muted">La part de l'assurance est estimée au taux de {{ $facture->tauxLisible() }} et dépend de son accord de prise en charge.</p>
        @endif
    @else
        <table class="totaux">
            <tr class="fort"><td>Total</td><td class="r">{{ $mru($facture->total) }}</td></tr>
            @unless ($devis)
                <tr><td>Réglé</td><td class="r">{{ $mru($facture->regle_patient) }}</td></tr>
                <tr class="fort"><td>Reste à payer</td><td class="r">{{ $mru(max(0, $facture->restePatient())) }}</td></tr>
            @endunless
        </table>
        <p class="muted" style="font-size:11px">Sans assurance : la totalité est à la charge du patient.</p>
    @endif

    @if ($devis)
        <p class="muted" style="margin-top:16px">Devis estimatif, établi selon le plan de traitement proposé. Les montants peuvent évoluer si le traitement est modifié.</p>
        <table style="margin-top:32px;border:0"><tr>
            <td style="border:0;width:50%;padding-bottom:70px;vertical-align:top">Signature du praticien</td>
            <td style="border:0;padding-bottom:70px;vertical-align:top">Bon pour accord, date et signature du patient</td>
        </tr></table>
    @endif
</x-impression>
