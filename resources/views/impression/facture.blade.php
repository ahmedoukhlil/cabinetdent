@php $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU'; @endphp
@php $devis = $facture->statut === 'brouillon'; @endphp
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
        @if ($facture->assureur)<strong>Assurance :</strong> {{ $facture->assureur->nom }}@if ($facture->patient->matricule_assure) · matricule {{ $facture->patient->matricule_assure }}@endif<br>@endif
        @if ($facture->dentiste)<strong>Praticien :</strong> Dr {{ $facture->dentiste->name }}@endif
    </p>

    <table>
        <thead><tr><th>Acte</th><th>Dents</th><th class="r">Qté</th><th class="r">Prix unitaire</th><th class="r">Montant</th></tr></thead>
        <tbody>
            @foreach ($facture->lignes as $l)
                <tr>
                    <td>{{ $l->libelle }}</td>
                    <td>{{ $l->dents ? implode(', ', $l->dents) : '—' }}</td>
                    <td class="r">{{ rtrim(rtrim(number_format($l->quantite, 2, ',', ''), '0'), ',') }}</td>
                    <td class="r">{{ $mru($l->prix_unitaire) }}</td>
                    <td class="r">{{ $mru($l->montant) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totaux">
        <tr class="fort"><td>Total</td><td class="r">{{ $mru($facture->total) }}</td></tr>
        @if ($facture->part_pec > 0)
            <tr><td>Part assureur ({{ rtrim(rtrim(number_format($facture->taux_pec * 100, 2, ',', ''), '0'), ',') }} %)</td><td class="r">{{ $mru($facture->part_pec) }}</td></tr>
            <tr><td>Part patient</td><td class="r">{{ $mru($facture->part_patient) }}</td></tr>
        @endif
        @if (!$devis)
        <tr><td>Réglé par le patient</td><td class="r">{{ $mru($facture->regle_patient) }}</td></tr>
        @if ($facture->part_pec > 0)<tr><td>Réglé par l'assureur</td><td class="r">{{ $mru($facture->regle_pec) }}</td></tr>@endif
        <tr class="fort"><td>Reste à payer</td><td class="r">{{ $mru(max(0, $facture->restePatient()) + max(0, $facture->restePec())) }}</td></tr>
        @endif
    </table>
    @if ($devis)
        <p class="muted" style="margin-top:16px">Devis estimatif, établi selon le plan de traitement proposé. Les montants peuvent évoluer si le traitement est modifié. La part assureur dépend de l'accord de prise en charge.</p>
        <table style="margin-top:32px;border:0"><tr>
            <td style="border:0;width:50%;padding-bottom:70px;vertical-align:top">Signature du praticien</td>
            <td style="border:0;padding-bottom:70px;vertical-align:top">Bon pour accord, date et signature du patient</td>
        </tr></table>
    @endif
</x-impression>
