@props(['titre', 'cabinet'])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Format choisi (A4 par défaut) : appliqué avant l'affichage pour éviter un saut de mise en page. --}}
    <script>
        (function () {
            var demande = new URLSearchParams(location.search).get('format');
            var format = (demande || localStorage.getItem('sysdental-format-impression') || 'a4').toLowerCase() === 'a5' ? 'a5' : 'a4';
            document.documentElement.dataset.format = format;
            document.write('<style id="format-page">@page { size: ' + format.toUpperCase() + ' portrait; margin: ' + (format === 'a5' ? '8mm' : '12mm') + '; }</style>');
        })();
    </script>
    <title>{{ $titre }} · Sysdental</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: system-ui, 'Segoe UI', Roboto, sans-serif; color: #0f172a; margin: 0; font-size: 13px; }
        .page { max-width: 760px; margin: 24px auto; padding: 0 24px; }
        header { display: flex; justify-content: space-between; border-bottom: 2px solid #0f4c81; padding-bottom: 12px; margin-bottom: 18px; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #0f4c81; }
        h2 { font-size: 16px; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { padding: 6px 4px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
        th { font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: .03em; }
        .r { text-align: right; }
        .muted { color: #64748b; }
        .totaux { margin-left: auto; width: 280px; }
        .totaux td { border: 0; padding: 3px 4px; }
        .fort td { font-weight: 600; border-top: 1px solid #0f172a; }
        .actions { display: flex; justify-content: flex-end; align-items: center; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
        .actions button { padding: 8px 14px; border: 1px solid #0f4c81; background: #0f4c81; color: #fff; border-radius: 6px; cursor: pointer; font: inherit; }
        .formats { display: inline-flex; border: 1px solid #cbd5e1; border-radius: 6px; padding: 2px; }
        .formats button { background: transparent; color: #334155; border: 0; padding: 6px 12px; border-radius: 4px; }
        html[data-format="a4"] .formats [data-choix="a4"], html[data-format="a5"] .formats [data-choix="a5"] { background: #0f4c81; color: #fff; }
        /* Répartition patient / assurance */
        .repartition { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 14px 0; }
        .bloc { border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 10px; }
        .bloc h4 { margin: 0 0 6px; font-size: 12px; text-transform: uppercase; letter-spacing: .03em; }
        .bloc.assurance { border-color: #7dd3fc; background: #f0f9ff; }
        .bloc.assurance h4 { color: #0369a1; }
        .bloc.patient { border-color: #fcd34d; background: #fffbeb; }
        .bloc.patient h4 { color: #b45309; }
        .bloc table { margin: 0; }
        .bloc td { border: 0; padding: 2px 0; }
        .bloc .fort td { border-top: 1px solid #94a3b8; font-weight: 600; }
        .encadre { border: 1px solid #0f4c81; border-radius: 6px; padding: 8px 10px; margin: 10px 0; }
        /* Format A5 : demi-page, tout est resserré. */
        html[data-format="a5"] body { font-size: 11px; }
        html[data-format="a5"] .page { max-width: 560px; margin: 16px auto; padding: 0 16px; }
        html[data-format="a5"] h1 { font-size: 16px; }
        html[data-format="a5"] h2 { font-size: 13px; }
        html[data-format="a5"] h3 { font-size: 12px; margin: 10px 0 4px; }
        html[data-format="a5"] header { padding-bottom: 8px; margin-bottom: 10px; }
        html[data-format="a5"] th, html[data-format="a5"] td { padding: 3px 3px; }
        html[data-format="a5"] th { font-size: 9px; }
        html[data-format="a5"] .totaux { width: 220px; }
        html[data-format="a5"] .repartition { gap: 6px; }
        html[data-format="a5"] footer { margin-top: 16px; font-size: 9px; }
        html[data-format="a5"] .odontogramme-impression svg { max-height: 250px; }
        footer { margin-top: 32px; font-size: 11px; color: #64748b; text-align: center; }
        @media print {
            .actions { display: none; }
            .page, html[data-format="a5"] .page { margin: 0 auto; padding: 0; max-width: none; }
            .bloc, .encadre, tr { break-inside: avoid; }
        }
        /* Consultation sur téléphone : marges et colonnes resserrées pour éviter le défilement horizontal. */
        @media screen and (max-width: 640px) {
            .page { margin: 12px auto; padding: 0 12px; }
            header { flex-direction: column; gap: 8px; }
            header .r { text-align: left; }
            th, td { padding: 4px 2px; font-size: 11px; }
            th { letter-spacing: 0; }
            .totaux { width: 100%; }
            /* Tableau trop large (8 colonnes) : il défile seul, la page reste fixe. */
            table:has(thead) { display: block; overflow-x: auto; }
        }
        @media screen and (max-width: 420px) { .repartition { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="page">
    <div class="actions">
        <div class="formats" role="group" aria-label="Format du papier">
            <button type="button" data-choix="a4" onclick="choisirFormat('a4')">A4</button>
            <button type="button" data-choix="a5" onclick="choisirFormat('a5')">A5</button>
        </div>
        <button type="button" onclick="window.print()">Imprimer</button>
    </div>
    <script>
        function choisirFormat(format) {
            document.documentElement.dataset.format = format;
            document.getElementById('format-page').textContent = '@page { size: ' + format.toUpperCase() + ' portrait; margin: ' + (format === 'a5' ? '8mm' : '12mm') + '; }';
            localStorage.setItem('sysdental-format-impression', format);
            var url = new URL(location.href); url.searchParams.set('format', format); history.replaceState(null, '', url);
        }
    </script>
    <header>
        <div>
            <h1>{{ $cabinet->nom }}</h1>
            <div class="muted">{{ $cabinet->adresse }}@if ($cabinet->telephone) · {{ $cabinet->telephone }}@endif</div>
        </div>
        <div class="r">{{ $entete ?? '' }}</div>
    </header>
    {{ $slot }}
    <footer>Document édité le {{ now()->format('d/m/Y à H:i') }} avec Sysdental · montants en ouguiya (MRU)</footer>
</div>
</body>
</html>
