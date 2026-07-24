<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>État de caisse journalier - {{ $date->format('d/m/Y') }}</title>
    <link rel="stylesheet" href="/css/print.css">
    <style>
        .medecin-section { margin-top: 20px; page-break-inside: avoid; }
        .medecin-header { border-bottom: 1.5px solid #000; padding-bottom: 4px; margin-bottom: 8px; font-weight: 700; font-size: 12px; }
        .totaux-section { margin-top: 24px; border-top: 2px solid #000; padding-top: 10px; }
    </style>
</head>
<body>
<div class="a4" id="documentContainer">
    <div class="print-controls">
        <select id="pageFormat" onchange="updatePageFormat()">
            <option value="A4">Format A4</option>
            <option value="A5">Format A5</option>
        </select>
        <button onclick="window.print()" class="btn-primary">Imprimer</button>
    </div>

    <div class="recu-header">@include('partials.recu-header')</div>
    <hr class="header-divider">
    <div class="doc-title">ÉTAT DE CAISSE JOURNALIER</div>

    <table class="info-bloc" style="margin-bottom:12px;">
        <tr>
            <td class="lbl">Date :</td>
            <td class="val"><strong>{{ $date->format('d/m/Y') }}</strong></td>
            <td class="right">Heure : {{ now()->format('H:i') }}</td>
        </tr>
        <tr>
            <td class="lbl">Imprimé par :</td>
            <td class="val">{{ $user->NomComplet ?? 'N/A' }}</td>
            <td></td>
        </tr>
    </table>
    <hr class="info-divider">

    @php $operationsParMedecin = $operations->groupBy('fkidmedecin'); @endphp

    @foreach($operationsParMedecin as $medecinId => $operationsMedecin)
        @php
            $medecin = \App\Models\Medecin::find($medecinId);
            $totalRecettesMedecin = $operationsMedecin->sum('entreEspece');
            $totalDepensesMedecin = $operationsMedecin->sum('retraitEspece');
            $soldeMedecin = $totalRecettesMedecin - $totalDepensesMedecin;
        @endphp
        <div class="medecin-section">
            <div class="medecin-header">Dr. {{ $medecin->Nom ?? 'Médecin non spécifié' }}</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Heure</th>
                        <th>Opération</th>
                        <th>Mode paiement</th>
                        <th class="right">Recettes</th>
                        <th class="right">Dépenses</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($operationsMedecin as $op)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($op->dateoper)->format('H:i') }}</td>
                        <td>{{ $op->designation }}</td>
                        <td>{{ $op->TypePAie }}</td>
                        <td class="right">{{ $op->entreEspece > 0 ? number_format($op->entreEspece, 0, ',', ' ').' MRU' : '—' }}</td>
                        <td class="right">{{ $op->retraitEspece > 0 ? number_format($op->retraitEspece, 0, ',', ' ').' MRU' : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <table class="totaux-table" style="margin-top:6px;">
                <tr><td>Recettes</td><td>{{ number_format($totalRecettesMedecin, 0, ',', ' ') }} MRU</td></tr>
                <tr><td>Dépenses</td><td>{{ number_format($totalDepensesMedecin, 0, ',', ' ') }} MRU</td></tr>
                <tr><td><strong>Solde</strong></td><td><strong>{{ number_format($soldeMedecin, 0, ',', ' ') }} MRU</strong></td></tr>
            </table>
        </div>
    @endforeach

    <div class="totaux-section">
        <table class="totaux-table">
            <tr><td>Total recettes</td><td>{{ number_format($totalRecettes, 0, ',', ' ') }} MRU</td></tr>
            <tr><td>Total dépenses</td><td>{{ number_format($totalDepenses, 0, ',', ' ') }} MRU</td></tr>
            <tr><td><strong>Solde global</strong></td><td><strong>{{ number_format($solde, 0, ',', ' ') }} MRU</strong></td></tr>
        </table>
    </div>

    <div class="signature-block">
        <div class="signature-lbl">Signature</div>
        <div class="signature-line">{{ $user->NomComplet ?? '' }}</div>
    </div>

    <div class="recu-footer">@include('partials.recu-footer')</div>
</div>

<script>
const els = { fmt: document.getElementById('pageFormat'), box: document.getElementById('documentContainer') };
function updatePageFormat() {
    const a5 = els.fmt.value === 'A5';
    els.box.classList.toggle('a4', !a5);
    els.box.classList.toggle('a5', a5);
    let s = document.getElementById('_pf'); if (!s) { s = document.createElement('style'); s.id='_pf'; document.head.appendChild(s); }
    s.innerHTML = a5 ? '@media print{@page{size:A5;margin:8mm}}' : '@media print{@page{size:A4;margin:10mm}}';
}
</script>
</body>
</html>
