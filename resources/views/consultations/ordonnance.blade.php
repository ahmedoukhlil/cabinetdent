<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordonnance - {{ $consultation->patient->NomContact ?? 'Patient' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
            padding: 0;
            margin: 0;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 12mm 15mm 10mm 15mm;
            position: relative;
        }

        /* Date */
        .date-section {
            text-align: left;
            margin-bottom: 18px;
            font-size: 10px;
            font-weight: normal;
        }

        /* Informations patient */
        .patient-info {
            margin-bottom: 24px;
            font-size: 13px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            align-items: baseline;
            line-height: 1.5;
        }

        .info-row-rtl {
            direction: rtl;
            text-align: right;
        }

        .info-field {
            flex: 1;
        }

        .info-field-center {
            flex: 1;
            text-align: center;
        }

        .patient-info strong {
            font-weight: bold;
            font-size: 13px;
        }

        /* Titre Ordonnance */
        .title-section {
            text-align: center;
            margin: 20px 0 26px 0;
        }

        .title-ar {
            font-size: 17px;
            font-weight: bold;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }

        .title-fr {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 5px;
        }

        /* Corps de l'ordonnance */
        .ordonnance-body {
            min-height: 460px;
            padding: 10px 20px;
            font-size: 12px;
            line-height: 2.2;
            position: relative;
        }
        

        .medication-item {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .medication-name {
            font-weight: bold;
            font-size: 13px;
        }

        .medication-dosage {
            margin-left: 25px;
            font-style: normal;
            color: #444;
            font-size: 12px;
        }

        /* Pied de page */
        .footer {
            position: absolute;
            bottom: 8mm;
            left: 15mm;
            right: 15mm;
            border-top: 1.5px solid #000;
            padding-top: 5px;
            text-align: center;
            font-size: 9px;
            line-height: 1.6;
        }

        .footer-ar {
            font-weight: normal;
            margin-bottom: 1px;
            font-size: 9px;
        }

        .footer-fr {
            font-style: italic;
            font-size: 9px;
        }

        /* Contrôles d'impression */
        .print-controls {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }

        .print-controls button {
            padding: 10px 25px;
            background: #2c5282;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            margin: 0 5px;
        }

        .print-controls button:hover {
            background: #1a365d;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .page {
                margin: 0;
                padding: 12mm 15mm 10mm 15mm;
                box-shadow: none;
            }

            .print-controls {
                display: none !important;
            }
            * { color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            .footer {
                position: fixed;
                bottom: 8mm;
                left: 15mm;
                right: 15mm;
            }


            @page {
                margin: 0;
                size: A4 portrait;
            }
        }

        @media screen {
            .page {
                box-shadow: 0 0 15px rgba(0,0,0,0.08);
                margin: 20px auto;
            }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button onclick="window.print()">🖨️ Imprimer l'ordonnance</button>
        <button onclick="window.close()">❌ Fermer</button>
    </div>

    <div class="page">
        <!-- En-tête -->
        @include('partials.recu-header')

        <!-- Informations patient -->
        <div class="patient-info">
            <!-- Ligne 1 : Date seule -->
            <div class="info-row" style="margin-bottom: 10px; justify-content: flex-end;">
                <span><strong>Date :.....................</strong></span>
            </div>
            
            <!-- Ligne 2 : Nom et Prénom -->
            <div class="info-row" style="margin-bottom: 10px;">
                <div style="width: 100%; display: flex; justify-content: space-between; align-items: baseline;">
                    <span><strong>Nom et Prénom :</strong></span>
                    <span style="flex: 1; border-bottom: 1px dotted #000; margin: 0 5px;"></span>
                    <span style="direction: rtl;"><strong>الاسم و اللقب :</strong></span>
                </div>
            </div>
            
            <!-- Ligne 3 : Age, Poids, Age (arabe) -->
            <div class="info-row" style="display: flex; justify-content: space-between; align-items: baseline;">
                <span style="flex: 0 0 auto;"><strong>Age :</strong>......</span>
                <span style="flex: 1; text-align: center;"><strong>Poids :</strong>...............<strong>الوزن</strong></span>
                <span style="flex: 0 0 auto; direction: rtl;"><strong>العمر :</strong>.......</span>
            </div>
        </div>

        <!-- Titre -->
        <div class="title-section">
            <div class="title-ar">وصفة طبية</div>
            <div class="title-fr">ORDONNANCE</div>
        </div>

        <!-- Corps de l'ordonnance -->
        <div class="ordonnance-body">
            <!-- Espace vide pour prescription manuscrite -->
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <div class="footer-ar">الرجاء إحضار الوصفة عند الاستشارة القادمة</div>
            <div class="footer-fr">Prier de rapporter l'ordonnance à la prochaine Consultation</div>
        </div>
    </div>

    <script>
        // Auto-print si paramètre autoprint
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autoprint') === '1') {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        }
    </script>
</body>
</html>

