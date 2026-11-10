@props(['titre', 'cabinet'])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $titre }} · Sysdental</title>
    <style>
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
        .actions { text-align: right; margin-bottom: 12px; }
        .actions button { padding: 8px 14px; border: 1px solid #0f4c81; background: #0f4c81; color: #fff; border-radius: 6px; cursor: pointer; }
        footer { margin-top: 32px; font-size: 11px; color: #64748b; text-align: center; }
        @media print { .actions { display: none; } .page { margin: 0 auto; } }
    </style>
</head>
<body>
<div class="page">
    <div class="actions"><button type="button" onclick="window.print()">Imprimer</button></div>
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
