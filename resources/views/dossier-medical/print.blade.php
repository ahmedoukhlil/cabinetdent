<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FICHE MEDICAL DU PATIENT - {{ $facture->Nfacture }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', 'Tahoma', sans-serif; margin: 0; padding: 0; background: #fff; font-size: 12px; direction: ltr; }
        .a4 { width: 210mm; min-height: 297mm; margin: auto; background: #fff; padding: 10mm; position: relative; box-sizing: border-box; }
        
        /* En-tête */
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header img { max-height: 120px; width: auto; margin-bottom: 8px; }
        .cabinet-name { font-size: 18px; font-weight: bold; color: #000; margin-bottom: 5px; }
        .cabinet-details { font-size: 10px; color: #666; }
        
        /* Titre */
        .fiche-title { text-align: center; font-size: 20px; font-weight: bold; margin: 15px 0; text-decoration: underline; color: #000; }
        
        /* Informations patient */
        .patient-info-section { display: flex; justify-content: space-between; margin: 20px 0; gap: 20px; }
        .patient-info-left { flex: 1; }
        .patient-info-right { flex: 1; }
        .patient-field { margin-bottom: 12px; font-size: 12px; display: flex; align-items: baseline; }
        .patient-field-label { font-weight: bold; min-width: 100px; color: #000; }
        .patient-field-value { flex: 1; border-bottom: 1px dashed #333; min-height: 18px; padding: 0 5px; margin-left: 5px; }
        
        /* Section Observation */
        .observation-section { margin-top: 20px; margin-left: 20px; }
        .observation-box { border: 1px solid #000; min-height: 150px; padding: 10px; margin-top: 5px; }
        .observation-label { font-weight: bold; font-size: 12px; margin-bottom: 5px; }
        
        /* Tableau des traitements */
        .traitements-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .traitements-table th { border: 1px solid #000; padding: 8px; text-align: center; font-weight: bold; background: #f0f0f0; text-decoration: underline; }
        .traitements-table td { border: 1px solid #000; padding: 8px; text-align: center; }
        .traitements-table td:first-child { text-align: center; }
        .traitements-table td:nth-child(3) { text-align: left; padding-left: 10px; }
        
        /* Contrôles d'impression */
        .print-controls { display: flex; gap: 10px; justify-content: flex-end; margin: 18px 0; }
        .print-controls select, .print-controls button { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        .print-controls button { background: #2c5282; color: #fff; border: none; cursor: pointer; }
        .print-controls button:hover { background: #1e3a8a; }
        
        @media print {
            .print-controls { display: none !important; }
            .a4 { box-shadow: none; }
            * { color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div id="documentContainer">
        <div class="print-controls">
            <select id="pageFormat" onchange="updatePageFormat()">
                <option value="A4">Format A4</option>
            </select>
            <button onclick="window.print()" class="print-btn">
                Imprimer
            </button>
            <button onclick="downloadPDF()" class="print-btn">
                Télécharger PDF
            </button>
        </div>

        <div id="fiche-content" class="a4">
            {{-- En-tête --}}
            <div class="header">
                @php
                    $headerImagePath = public_path('SysMedical.png');
                    $imageExists = file_exists($headerImagePath);
                    $imageBase64 = null;
                    if ($imageExists) {
                        $imageData = file_get_contents($headerImagePath);
                        $imageBase64 = 'data:image/png;base64,' . base64_encode($imageData);
                    }
                @endphp
                @if($imageBase64)
                <img src="{{ $imageBase64 }}" alt="En-tête du cabinet">
                @endif
                @php
                    $nomCabinet = $cabinet['NomCabinet'] ?? $cabinet->NomCabinet ?? null;
                    $adresse = $cabinet['Adresse'] ?? $cabinet->Adresse ?? null;
                    $telephone = $cabinet['Telephone'] ?? $cabinet->Telephone ?? null;
                    
                    $defaultValues = ['SysMedical'];
                    if (in_array($nomCabinet, $defaultValues)) $nomCabinet = null;
                    if (in_array($adresse, $defaultValues)) $adresse = null;
                    if (in_array($telephone, $defaultValues)) $telephone = null;
                @endphp
                @if($nomCabinet || $adresse || $telephone)
                <div>
                    @if($nomCabinet)
                    <div class="cabinet-name">{{ $nomCabinet }}</div>
                    @endif
                    <div class="cabinet-details">
                        @if($adresse)
                            {{ $adresse }}<br>
                        @endif
                        @if($telephone)
                            Tél: {{ $telephone }}
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- Titre --}}
            <div class="fiche-title">
                FICHE MEDICAL DU PATIENT
            </div>

            {{-- Informations patient --}}
            <div class="patient-info-section">
                <div class="patient-info-left">
                    <div class="patient-field">
                        <span class="patient-field-label">Praticien :</span>
                        <span class="patient-field-value">{{ $facture->medecin->Nom ?? ($fichesTraitement->first()->NomMedecin ?? '') }}</span>
                    </div>
                    <div class="patient-field">
                        <span class="patient-field-label">Nom Patient :</span>
                        <span class="patient-field-value">{{ $facture->patient->Nom ?? '' }}</span>
                    </div>
                    <div class="patient-field">
                        <span class="patient-field-label">Naissance :</span>
                        <span class="patient-field-value">
                            @if($facture->patient->DtNaissance)
                                {{ \Carbon\Carbon::parse($facture->patient->DtNaissance)->format('d/m/Y') }}
                            @endif
                        </span>
                    </div>
                    <div class="patient-field">
                        <span class="patient-field-label">N° Tél :</span>
                        <span class="patient-field-value">{{ $facture->patient->Telephone1 ?? '' }}</span>
                    </div>
                    <div class="patient-field">
                        <span class="patient-field-label">N° Facture :</span>
                        <span class="patient-field-value">{{ $facture->Nfacture ?? '' }}</span>
                    </div>
                    <div class="patient-field">
                        <span class="patient-field-label">N° Dossier :</span>
                        <span class="patient-field-value">{{ $facture->patient->IdentifiantPatient ?? '' }}</span>
                    </div>
                </div>
                
                {{-- Section Observation --}}
                <div class="patient-info-right">
                    <div class="observation-section">
                        <div class="observation-label">OBSERVATION</div>
                        <div class="observation-box">
                            {{-- Zone de texte libre pour les observations --}}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tableau des traitements --}}
            <table class="traitements-table">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Date</th>
                        <th>Traitement</th>
                        <th>M. Total</th>
                        <th>M. Payé</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fichesTraitement as $index => $fiche)
                    <tr>
                        <td>{{ $fiche->Ordre ?? ($index + 1) }}</td>
                        <td>
                            @if($fiche->dateTraite)
                                {{ \Carbon\Carbon::parse($fiche->dateTraite)->format('d/m/Y') }}
                            @else
                                &nbsp;
                            @endif
                        </td>
                        <td>{{ $fiche->Traitement ?? $fiche->Acte ?? '' }}</td>
                        <td>{{ number_format($fiche->Prix ?? 0, 2, ',', ' ') }} MRU</td>
                        <td>
                            @if($index === 0 && $montantPaye > 0)
                                {{ number_format($montantPaye, 2, ',', ' ') }} MRU
                            @else
                                &nbsp;
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px; color: #666;">
                            Aucun traitement enregistré
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function updatePageFormat() {
            const format = document.getElementById('pageFormat').value;
            const content = document.getElementById('fiche-content');
            if (format === 'A5') {
                content.classList.add('a5');
                content.classList.remove('a4');
            } else {
                content.classList.add('a4');
                content.classList.remove('a5');
            }
        }

        function downloadPDF() {
            window.location.href = '{{ route("dossier-medical.download", $facture->Idfacture) }}';
        }
    </script>
</body>
</html>

