@php
    $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU';
@endphp
<x-impression titre="Statistiques" :cabinet="$cabinet">
    <x-slot:entete>
        <h2>Statistiques par praticien</h2>
        <div>{{ $du->isSameDay($au) ? 'le '.$du->format('d/m/Y') : 'du '.$du->format('d/m/Y').' au '.$au->format('d/m/Y') }}</div>
    </x-slot:entete>

    <table>
        <thead>
            <tr><th>Praticien</th><th class="r">Patients</th><th class="r">Actes</th><th class="r">Facturé</th><th class="r">Moy. / acte</th><th class="r">Encaissé</th><th class="r">Dépenses</th><th class="r">Bilan</th></tr>
        </thead>
        <tbody>
            @foreach ($lignes as $l)
                <tr>
                    <td>{{ $l['nom'] }}</td>
                    <td class="r">{{ $l['patients'] }}</td>
                    <td class="r">{{ $l['actes'] }}</td>
                    <td class="r">{{ $mru($l['facture']) }}</td>
                    <td class="r">{{ $mru($l['moyenne']) }}</td>
                    <td class="r">{{ $mru($l['encaisse']) }}</td>
                    <td class="r">{{ $l['depenses'] ? $mru(-$l['depenses']) : '—' }}</td>
                    <td class="r"><strong>{{ $mru($l['bilan']) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totaux">
        <tr><td>Facturé ({{ $total['actes'] }} actes)</td><td class="r">{{ $mru($total['facture']) }}</td></tr>
        <tr><td>Encaissé net</td><td class="r">{{ $mru($total['encaisse']) }}</td></tr>
        <tr><td>Dépenses</td><td class="r">{{ $mru(-$total['depenses']) }}</td></tr>
        <tr class="fort"><td>Bilan</td><td class="r">{{ $mru($total['bilan']) }}</td></tr>
    </table>

    <p class="muted" style="font-size:11px">Facturé : factures validées datées dans la période. Encaissé : paiements reçus dans la période, remboursements déduits.</p>
</x-impression>
