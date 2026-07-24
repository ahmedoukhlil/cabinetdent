<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statistiques - Liste des opérations</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .subtitle { font-size: 13px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Statistiques - Liste des opérations</div>
        <div class="subtitle">Période : {{ $periode[0] }} à {{ $periode[1] }}</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Médecin</th>
                <th>Patient</th>
                <th>Opération</th>
                <th>Montant</th>
                <th>Mode de paiement</th>
            </tr>
        </thead>
        <tbody>
            @foreach($operations as $operation)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($operation->dateoper)->format('d/m/Y') }}</td>
                    <td>{{ $medecins->firstWhere('idMedecin', $operation->fkidmedecin)->Nom ?? 'N/A' }}</td>
                    <td>{{ $operation->tiers->Nom ?? 'N/A' }}</td>
                    <td>{{ $operation->designation }}</td>
                    <td style="color:{{ $operation->entreEspece ? '#15803d' : '#b91c1c' }}; font-weight:bold;">{{ number_format($operation->MontantOperation, 0, ',', ' ') }} MRU</td>
                    <td>{{ $operation->TypePAie ?? 'CASH' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html> 