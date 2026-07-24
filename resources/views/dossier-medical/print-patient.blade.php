<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dossier médical — {{ $patient->NomPatient ?? ($patient->Nom.' '.($patient->Prenom ?? '')) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: #fff;
            line-height: 1.5;
        }

        /* ── Mise en page ── */
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 12mm 14mm;
        }

        @media print {
            body { background: #fff; }
            .page { margin: 0; padding: 10mm 12mm; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            .avoid-break { page-break-inside: avoid; }
        }

        /* ── En-tête cabinet ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header-left .cabinet-name {
            font-size: 16px;
            font-weight: 700;
            color: #1e3a8a;
        }
        .header-left .cabinet-specialite {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .header-left .cabinet-contact {
            font-size: 10px;
            color: #64748b;
            margin-top: 4px;
        }
        .header-right {
            text-align: right;
            font-size: 10px;
            color: #64748b;
        }
        .header-right .doc-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e3a8a;
            margin-bottom: 3px;
        }
        .header-right .doc-date {
            font-size: 10px;
            color: #94a3b8;
        }

        /* ── Bandeau patient ── */
        .patient-band {
            background: #1e3a8a;
            color: #fff;
            border-radius: 8px;
            padding: 10px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .patient-band .patient-name {
            font-size: 15px;
            font-weight: 700;
        }
        .patient-band .patient-meta {
            font-size: 10px;
            color: #bfdbfe;
            margin-top: 2px;
        }
        .patient-band .patient-right {
            text-align: right;
            font-size: 10px;
            color: #bfdbfe;
        }

        /* ── Sections ── */
        .section {
            margin-bottom: 14px;
        }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1.5px solid #dbeafe;
            padding-bottom: 4px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .section-title .icon {
            width: 18px;
            height: 18px;
            background: #1e3a8a;
            color: #fff;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
        }

        /* ── Grille infos ── */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .info-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 7px 10px;
        }
        .info-box .label {
            font-size: 9px;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .info-box .value {
            font-size: 11px;
            color: #1e293b;
            font-weight: 500;
        }
        .info-box .value.empty {
            color: #cbd5e1;
            font-style: italic;
        }

        /* ── Groupe sanguin badge ── */
        .badge-blood {
            display: inline-block;
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-allergy {
            display: inline-block;
            background: #fefce8;
            color: #92400e;
            border: 1px solid #fde68a;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 10px;
        }

        /* ── Constantes vitales ── */
        .constantes-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 6px;
        }
        .constante-box {
            text-align: center;
            border-radius: 6px;
            padding: 6px 4px;
            border: 1px solid #e2e8f0;
        }
        .constante-box .c-label { font-size: 8px; color: #94a3b8; text-transform: uppercase; }
        .constante-box .c-value { font-size: 12px; font-weight: 700; margin-top: 2px; }

        /* ── Consultation card ── */
        .consult-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        .consult-header {
            background: #f1f5f9;
            padding: 7px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .consult-header .c-date {
            font-weight: 700;
            font-size: 11px;
            color: #1e3a8a;
        }
        .consult-header .c-medecin {
            font-size: 10px;
            color: #64748b;
        }
        .consult-body { padding: 10px 12px; }
        .consult-section {
            margin-bottom: 8px;
        }
        .consult-section .cs-label {
            font-size: 9px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .consult-section .cs-value {
            font-size: 11px;
            color: #1e293b;
        }

        /* ── Table médicaments ── */
        .med-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 4px;
        }
        .med-table th {
            background: #dbeafe;
            color: #1e3a8a;
            padding: 4px 8px;
            text-align: left;
            font-weight: 600;
        }
        .med-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        .med-table tr:last-child td { border-bottom: none; }

        /* ── Badge examens ── */
        .exam-badge {
            display: inline-block;
            background: #f3e8ff;
            color: #6b21a8;
            border: 1px solid #e9d5ff;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 10px;
            margin: 2px;
        }

        /* ── Analyses ── */
        .analyse-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .analyse-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            text-align: center;
        }
        .analyse-card img {
            width: 100%;
            height: 80px;
            object-fit: cover;
        }
        .analyse-card .a-info {
            padding: 5px 7px;
            background: #f8fafc;
        }
        .analyse-card .a-label { font-size: 10px; font-weight: 600; color: #1e293b; }
        .analyse-card .a-date  { font-size: 9px; color: #94a3b8; }
        .analyse-pdf-icon {
            height: 80px;
            background: #fff5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #dc2626;
        }

        /* ── Pied de page ── */
        .footer {
            margin-top: 20px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #94a3b8;
        }

        /* ── Boutons d'action (non imprimés) ── */
        .print-actions {
            position: fixed;
            top: 16px;
            right: 16px;
            display: flex;
            gap: 8px;
            z-index: 100;
        }
        .btn-print {
            background: #1e3a8a;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-close {
            background: #64748b;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            cursor: pointer;
        }
    </style>
</head>
<body>

{{-- Boutons d'action --}}
<div class="print-actions no-print">
    <button class="btn-print" onclick="window.print()">
        🖨 Imprimer
    </button>
    <button class="btn-close" onclick="window.close()">✕ Fermer</button>
</div>

<div class="page">

    {{-- ── En-tête cabinet ── --}}
    <div class="header">
        <div class="header-left">
            <div class="cabinet-name">{{ $cabinet->NomCabFr ?? 'Cabinet Médical' }}</div>
            @if($cabinet)
                <div class="cabinet-specialite">
                    {{ $cabinet->Specialite1Fr }}
                    @if($cabinet->Specialite2fr) · {{ $cabinet->Specialite2fr }} @endif
                </div>
                <div class="cabinet-contact">
                    @if($cabinet->AdresseFr1) {{ $cabinet->AdresseFr1 }} @endif
                    @if($cabinet->TelephonePublic) &nbsp;·&nbsp; Tél : {{ $cabinet->TelephonePublic }} @endif
                    @if($cabinet->AdresseMail) &nbsp;·&nbsp; {{ $cabinet->AdresseMail }} @endif
                </div>
            @endif
        </div>
        <div class="header-right">
            <div class="doc-title">Dossier Médical</div>
            <div class="doc-date">Imprimé le {{ now()->translatedFormat('d F Y à H:i') }}</div>
            <div style="margin-top:4px;font-size:10px;">N° Fiche : {{ $patient->IdentifiantPatient ?? $patient->ID }}</div>
        </div>
    </div>

    {{-- ── Bandeau patient ── --}}
    @php
        $nomPatient = $patient->NomPatient ?? trim(($patient->Prenom ?? '').' '.($patient->Nom ?? ''));
        $age = $patient->DtNaissance ? \Carbon\Carbon::parse($patient->DtNaissance)->age : null;
    @endphp
    <div class="patient-band">
        <div>
            <div class="patient-name">{{ $nomPatient }}</div>
            <div class="patient-meta">
                @if($patient->Genre) {{ $patient->Genre }} @endif
                @if($age) &nbsp;·&nbsp; {{ $age }} ans @endif
                @if($patient->DtNaissance) &nbsp;·&nbsp; Né(e) le {{ \Carbon\Carbon::parse($patient->DtNaissance)->format('d/m/Y') }} @endif
            </div>
        </div>
        <div class="patient-right">
            @if($patient->Telephone1) 📞 {{ $patient->Telephone1 }}<br> @endif
            @if($patient->NNI) NNI : {{ $patient->NNI }}<br> @endif
            @if($patient->Adresse) {{ $patient->Adresse }} @endif
        </div>
    </div>

    {{-- ── DOSSIER PERMANENT ── --}}
    @if($dossier)
    <div class="section avoid-break">
        <div class="section-title">
            <span class="icon">📋</span> Dossier permanent
        </div>

        {{-- Groupe sanguin + Allergies --}}
        <div style="display:flex;gap:10px;margin-bottom:10px;align-items:center;">
            <div style="min-width:120px;">
                <span style="font-size:9px;color:#94a3b8;text-transform:uppercase;font-weight:600;">Groupe sanguin</span><br>
                @if($dossier->groupe_sanguin)
                    <span class="badge-blood">{{ $dossier->groupe_sanguin }}</span>
                @else
                    <span style="color:#cbd5e1;font-style:italic;font-size:10px;">Non renseigné</span>
                @endif
            </div>
            @if($dossier->allergies)
            <div style="flex:1;">
                <span style="font-size:9px;color:#94a3b8;text-transform:uppercase;font-weight:600;">⚠ Allergies</span><br>
                <span class="badge-allergy">{{ $dossier->allergies }}</span>
            </div>
            @endif
        </div>

        <div class="info-grid-2" style="margin-bottom:8px;">
            @if($dossier->antecedents_personnels)
            <div class="info-box">
                <div class="label">Antécédents personnels</div>
                <div class="value">{{ $dossier->antecedents_personnels }}</div>
            </div>
            @endif
            @if($dossier->antecedents_familiaux)
            <div class="info-box">
                <div class="label">Antécédents familiaux</div>
                <div class="value">{{ $dossier->antecedents_familiaux }}</div>
            </div>
            @endif
            @if($dossier->antecedents_chirurgicaux)
            <div class="info-box">
                <div class="label">Antécédents chirurgicaux</div>
                <div class="value">{{ $dossier->antecedents_chirurgicaux }}</div>
            </div>
            @endif
            @if($dossier->maladies_chroniques)
            <div class="info-box">
                <div class="label">Maladies chroniques</div>
                <div class="value">{{ $dossier->maladies_chroniques }}</div>
            </div>
            @endif
        </div>

        @if($dossier->traitements_permanents)
        <div class="info-box" style="margin-bottom:8px;">
            <div class="label">💊 Traitements au long cours</div>
            <div class="value">{{ $dossier->traitements_permanents }}</div>
        </div>
        @endif

        @if($dossier->alertes_dentaires)
        <div class="info-box" style="margin-bottom:8px;border-color:#fdba74;background:#fff7ed;">
            <div class="label" style="color:#c2410c;">⚠️ Alertes cliniques dentaires</div>
            <div class="value">{{ $dossier->alertes_dentaires }}</div>
            @if($dossier->alertes_dentaires_detail)
            <div class="value" style="margin-top:4px;">{{ $dossier->alertes_dentaires_detail }}</div>
            @endif
        </div>
        @endif

        @if($dossier->notes)
        <div class="info-box">
            <div class="label">Notes</div>
            <div class="value">{{ $dossier->notes }}</div>
        </div>
        @endif
    </div>
    @endif

    {{-- ── HISTORIQUE DES CONSULTATIONS ── --}}
    @if($consultations->count() > 0)
    <div class="section">
        <div class="section-title">
            <span class="icon">🩺</span> Historique des consultations ({{ $consultations->count() }})
        </div>

        @foreach($consultations as $c)
        <div class="consult-card avoid-break">
            <div class="consult-header">
                <div>
                    <span class="c-date">
                        {{ \Carbon\Carbon::parse($c->date_consultation)->translatedFormat('d F Y à H:i') }}
                    </span>
                    @if($c->motif)
                        <span style="color:#64748b;font-size:10px;"> — {{ $c->motif }}</span>
                    @endif
                </div>
                @if($c->medecin)
                <span class="c-medecin">Dr. {{ $c->medecin->Nom }}</span>
                @endif
            </div>
            <div class="consult-body">

                {{-- Constantes & anesthésie --}}
                @php
                    $hasConstantes = $c->tension_arterielle || $c->anesthesie;
                @endphp
                @if($hasConstantes)
                <div class="consult-section">
                    <div class="cs-label">Constantes & anesthésie</div>
                    <div class="constantes-grid">
                        <div class="constante-box" style="background:#eff6ff;border-color:#bfdbfe;">
                            <div class="c-label">Tension</div>
                            <div class="c-value" style="color:#1d4ed8;">{{ $c->tension_arterielle ?: '—' }}</div>
                        </div>
                        <div class="constante-box" style="background:#fff7ed;border-color:#fed7aa;">
                            <div class="c-label">Anesthésie</div>
                            <div class="c-value" style="color:#c2410c;">{{ $c->anesthesie ?: '—' }}</div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Examen + Diagnostic --}}
                @if($c->examen_clinique || $c->diagnostic)
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
                    @if($c->examen_clinique)
                    <div class="consult-section">
                        <div class="cs-label">Examen clinique</div>
                        <div class="cs-value">{{ $c->examen_clinique }}</div>
                    </div>
                    @endif
                    @if($c->diagnostic)
                    <div class="consult-section">
                        <div class="cs-label">Diagnostic</div>
                        <div class="cs-value" style="font-weight:600;">{{ $c->diagnostic }}</div>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Médicaments --}}
                @if($c->medicaments_prescrits && count($c->medicaments_prescrits) > 0)
                <div class="consult-section">
                    <div class="cs-label">💊 Médicaments prescrits</div>
                    <table class="med-table">
                        <thead>
                            <tr>
                                <th>Médicament</th>
                                <th>Posologie</th>
                                <th>Durée</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($c->medicaments_prescrits as $med)
                            <tr>
                                <td style="font-weight:600;">{{ $med['nom'] ?? '' }}</td>
                                <td>{{ $med['posologie'] ?? '' }}</td>
                                <td>{{ $med['duree'] ?? '' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- Examens demandés --}}
                @if($c->examens_demandes && count($c->examens_demandes) > 0)
                <div class="consult-section">
                    <div class="cs-label">🔬 Examens demandés</div>
                    @foreach($c->examens_demandes as $ex)
                        <span class="exam-badge">{{ ucfirst($ex['type'] ?? '') }} : {{ $ex['libelle'] ?? '' }}</span>
                    @endforeach
                </div>
                @endif

                {{-- Conduite à tenir --}}
                @if($c->conduite_a_tenir)
                <div class="consult-section">
                    <div class="cs-label">Conduite à tenir</div>
                    <div class="cs-value">{{ $c->conduite_a_tenir }}</div>
                </div>
                @endif

                @if($c->notes)
                <div style="background:#fefce8;border:1px solid #fde68a;border-radius:5px;padding:6px 9px;font-size:10px;color:#78350f;margin-top:6px;">
                    <strong>Notes :</strong> {{ $c->notes }}
                </div>
                @endif

            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── ANALYSES ── --}}
    @if($analyses->count() > 0)
    <div class="section page-break">
        <div class="section-title">
            <span class="icon">🔬</span> Analyses & examens ({{ $analyses->count() }})
        </div>
        <div class="analyse-grid">
            @foreach($analyses as $a)
            <div class="analyse-card avoid-break">
                @if(str_starts_with($a->fichier_mime ?? '', 'image/'))
                    <img src="{{ public_path('storage/'.str_replace('/storage/', '', $a->url)) }}" alt="{{ $a->libelle }}">
                @else
                    <div class="analyse-pdf-icon">📄</div>
                @endif
                <div class="a-info">
                    <div class="a-label">{{ $a->libelle }}</div>
                    @if($a->date_analyse)
                    <div class="a-date">{{ \Carbon\Carbon::parse($a->date_analyse)->format('d/m/Y') }}</div>
                    @endif
                    @if($a->notes)
                    <div style="font-size:9px;color:#64748b;margin-top:2px;">{{ $a->notes }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Pied de page ── --}}
    <div class="footer">
        <span>{{ $cabinet->NomCabFr ?? 'Cabinet Médical' }} — Dossier confidentiel</span>
        <span>{{ $nomPatient }} — N° {{ $patient->IdentifiantPatient ?? $patient->ID }}</span>
        <span>Imprimé le {{ now()->format('d/m/Y H:i') }}</span>
    </div>

</div>

<script>
    // Auto-print si paramètre ?auto=1
    if (new URLSearchParams(window.location.search).get('auto') === '1') {
        window.onload = () => window.print();
    }
</script>
</body>
</html>
