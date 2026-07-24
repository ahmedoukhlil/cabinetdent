<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $operation->MontantOperation < 0 ? 'REÇU DE REMBOURSEMENT' : 'REÇU DE PAIEMENT' }}</title>
    <link rel="stylesheet" href="/css/print.css">
    <style>
        .page { max-width: 580px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5px 16px; border: 1px solid #ccc; padding: 10px 14px; margin-bottom: 14px; }
        .info-row { display: flex; flex-direction: column; }
        .info-label { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #555; margin-bottom: 1px; }
        .info-value { font-size: 11px; font-weight: 500; }
        .info-value.bold { font-weight: 700; }
        @media (max-width: 620px) { .info-grid { grid-template-columns: 1fr; } }
        @media print { .info-grid { border-color: #ccc !important; } }
    </style>
</head>
<body>
<div class="page">
    <div class="print-controls">
        <button onclick="printFormat('A4')" class="btn-primary">A4</button>
        <button onclick="printFormat('A5')" class="btn-primary">A5</button>
        <button onclick="window.print()">Imprimer</button>
    </div>

    <div class="recu-header">@include('partials.recu-header')</div>
    <hr class="header-divider">
    <div class="doc-title">{{ $operation->MontantOperation < 0 ? 'Reçu de Remboursement' : 'Reçu de Paiement' }}</div>

    <div class="info-grid">
        <div class="info-row">
            <span class="info-label">N° Fiche</span>
            <span class="info-value">{{ $patient->IdentifiantPatient ?? '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">N° Facture</span>
            <span class="info-value">{{ $facture->Nfacture ?? '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Patient</span>
            <span class="info-value bold">{{ $patient->NomContact ?? ($patient->Prenom ?? '-') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date</span>
            <span class="info-value">{{ $operation->dateoper ? \Carbon\Carbon::parse($operation->dateoper)->format('d/m/Y H:i') : '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Médecin</span>
            <span class="info-value">Dr. {{ $medecin->Nom ?? '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Téléphone</span>
            <span class="info-value">{{ $patient->Telephone1 ?? '-' }}</span>
        </div>
        <div class="info-row" style="grid-column: span 2;">
            <span class="info-label">Mode de règlement</span>
            <span class="info-value">{{ $mode->LibPaie ?? $operation->TypePAie ?? '-' }}</span>
        </div>
    </div>

    @php
        if (($facture->ISTP ?? 0) == 1 && isset($pourQui) && $pourQui === 'pec') {
            $part = $facture->TotalPEC ?? ($facture->TotFacture * $facture->TXPEC);
            $totalDejaPaye = $facture->ReglementPEC ?? 0;
        } else {
            $part = $facture->TotalfactPatient ?? ($facture->TotFacture * (1-($facture->TXPEC ?? 0)));
            $totalDejaPaye = $facture->TotReglPatient ?? 0;
        }
        $reste = $part - $totalDejaPaye;
    @endphp

    <table class="data-table">
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="right">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Montant réglé</td>
                <td class="right">{{ number_format(abs($operation->MontantOperation), 2) }} MRU</td>
            </tr>
            <tr style="{{ $reste > 0 ? 'font-weight:700;' : '' }}">
                <td>Reste à payer</td>
                <td class="right">{{ number_format($reste, 2) }} MRU</td>
            </tr>
        </tbody>
    </table>

    <div class="signature-block">
        <div class="signature-lbl">Le Caissier</div>
        <div class="signature-line">{{ Auth::user()->NomComplet ?? Auth::user()->name ?? '' }}</div>
    </div>

    <div class="recu-footer">@include('partials.recu-footer')</div>
</div>

<script>
function printFormat(fmt) {
    let s = document.getElementById('_pf'); if (!s) { s = document.createElement('style'); s.id='_pf'; document.head.appendChild(s); }
    s.innerHTML = '@page{size:'+fmt+';margin:'+(fmt==='A4'?'10':'8')+'mm}';
    window.print();
}
</script>
</body>
</html>
