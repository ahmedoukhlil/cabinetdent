@php
    $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU';
    $modes = \App\Models\Paiement::MODES;
@endphp
<x-impression titre="État de caisse" :cabinet="$cabinet">
    <x-slot:entete>
        <h2>État de caisse</h2>
        <div>{{ $du->isSameDay($au) ? 'du '.$du->format('d/m/Y') : 'du '.$du->format('d/m/Y').' au '.$au->format('d/m/Y') }}</div>
    </x-slot:entete>

    <table>
        <thead><tr><th>Date</th><th>Patient</th><th>Facture</th><th>Opération</th><th class="r">Montant</th></tr></thead>
        <tbody>
            @forelse ($operations as $op)
                <tr>
                    <td>{{ $op->created_at->format('d/m H:i') }}</td>
                    <td>{{ $op->patient->nom_complet }}</td>
                    <td>{{ $op->facture->numero }}</td>
                    <td>{{ $op->type === 'remboursement' ? 'Remboursement' : 'Encaissement' }} · {{ $modes[$op->mode] }}</td>
                    <td class="r">{{ $mru($op->montant_signe) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Aucune opération.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($depenses->isNotEmpty())
        <h3 style="margin-top:16px">Dépenses</h3>
        <table>
            <thead><tr><th>Date</th><th>Libellé</th><th>Catégorie</th><th>Mode</th><th class="r">Montant</th></tr></thead>
            <tbody>
                @foreach ($depenses as $dep)
                    <tr><td>{{ $dep->date_depense->format('d/m') }}</td><td>{{ $dep->libelle }}</td><td>{{ \App\Models\Depense::CATEGORIES[$dep->categorie] ?? $dep->categorie }}</td><td>{{ $modes[$dep->mode] ?? $dep->mode }}</td><td class="r">{{ $mru(-$dep->montant) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="totaux">
        @foreach ($operations->groupBy('mode') as $mode => $ops)
            <tr><td>{{ $modes[$mode] }}</td><td class="r">{{ $mru($ops->sum('montant_signe')) }}</td></tr>
        @endforeach
        <tr><td>Remboursements</td><td class="r">{{ $mru(-$operations->where('type', 'remboursement')->sum('montant')) }}</td></tr>
        <tr><td>Recettes nettes</td><td class="r">{{ $mru($operations->sum('montant_signe')) }}</td></tr>
        <tr><td>Dépenses</td><td class="r">{{ $mru(-$depenses->sum('montant')) }}</td></tr>
        <tr class="fort"><td>Solde (recettes − dépenses)</td><td class="r">{{ $mru($operations->sum('montant_signe') - $depenses->sum('montant')) }}</td></tr>
    </table>
</x-impression>
