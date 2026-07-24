<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $facture->Type ?: 'FACTURE' }} — {{ $facture->Nfacture }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', Arial, sans-serif;
            font-size: 12px;
            color: #1f2937;
            background: #f3f4f6;
            line-height: 1.5;
        }

        /* ── Print controls ── */
        .print-controls {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            padding: 12px 20px;
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
        }
        .print-controls select,
        .print-controls button {
            padding: 7px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
            background: #fff;
            color: #374151;
        }
        .print-controls button {
            background: #1e3a8a;
            color: #fff;
            border: none;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .print-controls button:hover { background: #1e40af; }

        /* ── Page ── */
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 24px auto;
            background: #fff;
            padding: 14mm 16mm 20mm 16mm;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        /* ── Header ── */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 14px;
            border-bottom: 2px solid #1e3a8a;
            margin-bottom: 20px;
        }
        .header-fr { flex: 1; font-size: 10.5px; line-height: 1.7; color: #374151; }
        .header-fr .cabinet-name { font-size: 13px; font-weight: 700; color: #1e3a8a; margin-bottom: 2px; }
        .header-fr .doctor-name { font-size: 11.5px; font-weight: 600; }
        .header-fr .specialty { color: #6b7280; font-size: 10px; }
        .header-logo { flex: 0 0 auto; text-align: center; padding: 0 16px; }
        .header-logo img { max-height: 80px; max-width: 140px; object-fit: contain; }
        .header-ar { flex: 1; font-size: 10.5px; line-height: 1.7; color: #374151; text-align: right; direction: rtl; }
        .header-ar .cabinet-name { font-size: 13px; font-weight: 700; color: #1e3a8a; margin-bottom: 2px; }
        .header-ar .doctor-name { font-size: 11.5px; font-weight: 600; }
        .header-ar .specialty { color: #6b7280; font-size: 10px; }

        /* ── Document title ── */
        .doc-title-wrap {
            text-align: center;
            margin-bottom: 18px;
        }
        .doc-title {
            display: inline-block;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 3px;
            color: #1e3a8a;
            border-bottom: 3px solid #1e3a8a;
            padding-bottom: 3px;
        }
        .doc-ref {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* ── Patient info card ── */
        .patient-card {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        .patient-col { flex: 1; }
        .patient-row { display: flex; gap: 6px; margin-bottom: 4px; font-size: 11px; }
        .patient-label { font-weight: 600; color: #64748b; min-width: 80px; }
        .patient-value { color: #1e293b; font-weight: 500; }
        .doc-meta { text-align: right; font-size: 11px; }
        .doc-meta .meta-ref { font-size: 14px; font-weight: 700; color: #1e3a8a; }
        .doc-meta .meta-date { color: #64748b; margin-top: 4px; }

        /* ── Section header ── */
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #1e3a8a;
            background: #eff6ff;
            border-left: 3px solid #1e3a8a;
            padding: 5px 10px;
            margin: 14px 0 6px 0;
        }

        /* ── Details table ── */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin-bottom: 4px;
        }
        .details-table thead th {
            background: #1e3a8a;
            color: #fff;
            padding: 7px 10px;
            font-weight: 600;
            font-size: 10.5px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        .details-table thead th:first-child { text-align: left; border-radius: 0; }
        .details-table thead th:not(:first-child) { text-align: right; }
        .details-table tbody tr:nth-child(even) { background: #f8fafc; }
        .details-table tbody tr:hover { background: #eff6ff; }
        .details-table tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .details-table tbody td:first-child { text-align: left; font-weight: 500; }
        .details-table tbody td:not(:first-child) { text-align: right; }

        /* ── Totaux ── */
        .totaux-wrap {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
        }
        .totaux-table {
            width: 260px;
            border-collapse: collapse;
            font-size: 11.5px;
        }
        .totaux-table tr td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .totaux-table tr td:first-child { color: #64748b; }
        .totaux-table tr td:last-child { text-align: right; font-weight: 600; color: #1e293b; }
        .totaux-table .row-total td {
            background: #1e3a8a;
            color: #fff !important;
            font-size: 13px;
            font-weight: 700;
            border-bottom: none;
        }
        .totaux-table .row-reste td {
            background: #fef2f2;
            color: #dc2626 !important;
            font-weight: 700;
        }
        .totaux-table .row-regle td {
            background: #f0fdf4;
            color: #16a34a !important;
        }

        /* ── Montant en lettres ── */
        .montant-lettres {
            margin-top: 16px;
            padding: 10px 14px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            font-size: 11px;
            color: #374151;
        }
        .montant-lettres strong { color: #1e3a8a; }

        /* ── Signature ── */
        .signature-block {
            display: flex;
            justify-content: flex-end;
            margin-top: 32px;
        }
        .signature-inner {
            text-align: center;
            min-width: 160px;
        }
        .signature-label { font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 32px; }
        .signature-line { border-top: 1px solid #1e3a8a; padding-top: 4px; }
        .signature-name { font-size: 11px; font-style: italic; color: #374151; }

        /* ── Footer ── */
        .doc-footer {
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 9.5px;
            color: #9ca3af;
        }

        /* ── Print ── */
        @media print {
            body { background: #fff; }
            .print-controls { display: none !important; }
            .page {
                margin: 0;
                padding: 10mm 14mm 16mm 14mm;
                box-shadow: none;
                min-height: 0;
            }
            @page { size: A4 portrait; margin: 0; }
            .details-table tbody tr { page-break-inside: avoid; }
            .totaux-wrap, .montant-lettres, .signature-block { page-break-inside: avoid; }
        }

        @media screen {
            .page { box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        }

        /* A5 */
        .page.a5 {
            width: 148mm;
            min-height: 210mm;
            padding: 8mm 10mm 12mm 10mm;
            font-size: 10px;
        }
        .page.a5 .doc-title { font-size: 14px; }
        .page.a5 .details-table { font-size: 10px; }
        .page.a5 .totaux-table { width: 200px; font-size: 10px; }
    </style>
</head>
<body>

<div class="print-controls">
    <select id="documentType" onchange="updateDocumentType()">
        <option value="Facture" {{ ($facture->Type !== 'Devis') ? 'selected' : '' }}>Facture</option>
        <option value="Devis" {{ $facture->Type === 'Devis' ? 'selected' : '' }}>Devis</option>
    </select>
    <select id="pageFormat" onchange="updatePageFormat()">
        <option value="A4">Format A4</option>
        <option value="A5">Format A5</option>
    </select>
    <button onclick="window.print()">⎙ Imprimer</button>
</div>

<div class="page a4" id="documentPage">

    @php
        use App\Models\Infocabinet;
        $cab = Infocabinet::first();
        $logoBase64 = null;
        if ($cab && $cab->logo && file_exists(public_path($cab->logo))) {
            try {
                $ext = strtolower(pathinfo($cab->logo, PATHINFO_EXTENSION));
                $mime = in_array($ext, ['jpg','jpeg']) ? 'image/jpeg' : ($ext === 'svg' ? 'image/svg+xml' : 'image/png');
                $logoBase64 = 'data:'.$mime.';base64,'.base64_encode(file_get_contents(public_path($cab->logo)));
            } catch (\Exception $e) {}
        }
    @endphp

    {{-- ── Header ── --}}
    <div class="doc-header">
        <div class="header-fr">
            @if($cab && $cab->NomCabFr)<div class="cabinet-name">{{ $cab->NomCabFr }}</div>@endif
            @if($cab && $cab->DrFr)<div class="doctor-name">{{ $cab->DrFr }}</div>@endif
            @if($cab && $cab->Specialite1Fr)<div class="specialty">{{ $cab->Specialite1Fr }}</div>@endif
            @if($cab && $cab->Specialite2fr)<div class="specialty">{{ $cab->Specialite2fr }}</div>@endif
            @if($cab && $cab->AdresseFr1)<div>{{ $cab->AdresseFr1 }}</div>@endif
            @if($cab && $cab->AdresseFr2)<div>{{ $cab->AdresseFr2 }}</div>@endif
            @if($cab && $cab->ContactFR)<div>{{ $cab->ContactFR }}</div>@endif
        </div>
        <div class="header-logo">
            @if($logoBase64)<img src="{{ $logoBase64 }}" alt="Logo">@endif
        </div>
        <div class="header-ar">
            @if($cab && $cab->NomCabAr)<div class="cabinet-name">{{ $cab->NomCabAr }}</div>@endif
            @if($cab && $cab->DrAr)<div class="doctor-name">{{ $cab->DrAr }}</div>@endif
            @if($cab && $cab->Specialite1Ar)<div class="specialty">{{ $cab->Specialite1Ar }}</div>@endif
            @if($cab && $cab->Specialite2Ar)<div class="specialty">{{ $cab->Specialite2Ar }}</div>@endif
            @if($cab && $cab->AdresseL1AR)<div>{{ $cab->AdresseL1AR }}</div>@endif
            @if($cab && $cab->AdresseL2AR)<div>{{ $cab->AdresseL2AR }}</div>@endif
            @if($cab && $cab->ContactAR)<div dir="ltr">{{ $cab->ContactAR }}</div>@endif
        </div>
    </div>

    {{-- ── Title ── --}}
    <div class="doc-title-wrap">
        <div class="doc-title" id="documentTitle">{{ strtoupper($facture->Type ?: 'FACTURE') }}</div>
    </div>

    {{-- ── Patient card ── --}}
    <div class="patient-card">
        <div class="patient-col">
            <div class="patient-row">
                <span class="patient-label">N° Fiche</span>
                <span class="patient-value">{{ $facture->patient->IdentifiantPatient ?? '—' }}</span>
            </div>
            <div class="patient-row">
                <span class="patient-label">Patient</span>
                <span class="patient-value">{{ $facture->patient->NomContact ?? $facture->patient->Nom ?? '—' }}</span>
            </div>
            <div class="patient-row">
                <span class="patient-label">Téléphone</span>
                <span class="patient-value">{{ $facture->patient->Telephone1 ?? '—' }}</span>
            </div>
            <div class="patient-row">
                <span class="patient-label">Praticien</span>
                <span class="patient-value">Dr {{ $facture->medecin->Nom ?? '—' }}</span>
            </div>
            @if($facture->patient && $facture->patient->assureur)
            <div class="patient-row">
                <span class="patient-label">Assureur</span>
                <span class="patient-value">
                    {{ $facture->patient->assureur->LibAssurance ?? '—' }}
                    @if($facture->patient->IdentifiantAssurance)
                        — {{ $facture->patient->IdentifiantAssurance }}
                    @endif
                </span>
            </div>
            @endif
        </div>
        <div class="doc-meta">
            <div class="meta-ref">Réf : {{ $facture->Nfacture }}</div>
            <div class="meta-date">{{ $facture->DtFacture ? $facture->DtFacture->format('d/m/Y à H:i') : '—' }}</div>
        </div>
    </div>

    {{-- ── Actes ── --}}
    @php $detailsGroupes = $facture->getDetailsGroupesParType(); @endphp

    @if(count($detailsGroupes) > 1)
        @foreach($detailsGroupes as $section => $details)
            <div class="section-title">{{ $section }}</div>
            <table class="details-table">
                <thead><tr>
                    <th>Traitement</th>
                    <th>Qté</th>
                    <th>P.U (MRU)</th>
                    <th>Sous-total (MRU)</th>
                </tr></thead>
                <tbody>
                @foreach($details as $detail)
                    <tr>
                        <td>{{ $detail->Actes }}</td>
                        <td>{{ $detail->Quantite }}</td>
                        <td>{{ number_format($detail->PrixFacture, 2) }}</td>
                        <td>{{ number_format($detail->PrixFacture * $detail->Quantite, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endforeach
    @else
        <table class="details-table">
            <thead><tr>
                <th>Traitement</th>
                <th>Qté</th>
                <th>P.U (MRU)</th>
                <th>Sous-total (MRU)</th>
            </tr></thead>
            <tbody>
            @foreach($facture->details as $detail)
                <tr>
                    <td>{{ $detail->Actes }}</td>
                    <td>{{ $detail->Quantite }}</td>
                    <td>{{ number_format($detail->PrixFacture, 2) }}</td>
                    <td>{{ number_format($detail->PrixFacture * $detail->Quantite, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    {{-- ── Totaux ── --}}
    <div class="totaux-wrap">
        <table class="totaux-table" id="totauxTable">
            <tr class="row-total">
                <td id="totalLabel">Total facture</td>
                <td>{{ number_format($facture->TotFacture, 2) }} MRU</td>
            </tr>
            <tbody id="detailsFacture" style="display: {{ $facture->Type === 'Devis' ? 'none' : 'table-row-group' }}">
                @if($facture->ISTP == 1)
                <tr>
                    <td>Part assurance (PEC)</td>
                    <td>{{ number_format($facture->TotalPEC, 2) }} MRU</td>
                </tr>
                <tr>
                    <td>Part patient</td>
                    <td>{{ number_format($facture->TotalfactPatient, 2) }} MRU</td>
                </tr>
                @endif
                <tr class="row-regle">
                    <td>Total règlements</td>
                    <td>{{ number_format($facture->TotReglPatient, 2) }} MRU</td>
                </tr>
                <tr class="row-reste">
                    <td>Reste à payer</td>
                    <td>{{ number_format($facture->restePatient ?? ($facture->ISTP == 1 ? ($facture->TotalfactPatient - $facture->TotReglPatient) : ($facture->TotFacture - $facture->TotReglPatient)), 2) }} MRU</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ── Montant en lettres ── --}}
    <div class="montant-lettres" id="montantLettresWrap">
        Arrêté le présent <span id="typeLabel">{{ strtolower($facture->Type ?: 'facture') }}</span> à la somme de :
        <strong>{{ $facture->en_lettres ?? '' }}</strong>
    </div>

    {{-- ── Signature ── --}}
    <div class="signature-block">
        <div class="signature-inner">
            <div class="signature-label">Signature &amp; Cachet</div>
            <div class="signature-line">
                <div class="signature-name">{{ $facture->medecin->Nom ?? '' }}</div>
            </div>
        </div>
    </div>

    {{-- ── Footer ── --}}
    <div class="doc-footer">
        @if($cab && $cab->NomCabFr){{ $cab->NomCabFr }}@endif
        @if($cab && $cab->AdresseFr1) — {{ $cab->AdresseFr1 }}@endif
        @if($cab && $cab->ContactFR) — {{ $cab->ContactFR }}@endif
    </div>

</div>

<script>
const els = {
    docType: document.getElementById('documentType'),
    title: document.getElementById('documentTitle'),
    typeLabel: document.getElementById('typeLabel'),
    totalLabel: document.getElementById('totalLabel'),
    detailsFacture: document.getElementById('detailsFacture'),
    pageFormat: document.getElementById('pageFormat'),
    page: document.getElementById('documentPage'),
};

function updateDocumentType() {
    const isDevis = els.docType.value === 'Devis';
    els.title.textContent = isDevis ? 'DEVIS' : 'FACTURE';
    els.typeLabel.textContent = isDevis ? 'devis' : 'facture';
    els.totalLabel.textContent = isDevis ? 'Total devis' : 'Total facture';
    els.detailsFacture.style.display = isDevis ? 'none' : 'table-row-group';
}

function updatePageFormat() {
    const isA5 = els.pageFormat.value === 'A5';
    els.page.classList.toggle('a4', !isA5);
    els.page.classList.toggle('a5', isA5);
}
</script>
</body>
</html>
