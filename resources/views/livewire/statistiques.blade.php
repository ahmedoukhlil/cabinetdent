@php
    $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU';
@endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h1 class="text-lg font-semibold">Statistiques par praticien</h1>
        <div class="flex flex-wrap items-end gap-2">
            <button type="button" wire:click="periode('mois')" class="btn-secondary">Ce mois</button>
            <button type="button" wire:click="periode('mois_precedent')" class="btn-secondary">Mois précédent</button>
            <button type="button" wire:click="periode('annee')" class="btn-secondary">Cette année</button>
            <div><label class="label" for="s-du">Du</label><input id="s-du" type="date" wire:model.live="du" class="field"></div>
            <div><label class="label" for="s-au">Au</label><input id="s-au" type="date" wire:model.live="au" class="field"></div>
            <a href="{{ route('statistiques.imprimer', ['du' => $du, 'au' => $au]) }}" target="_blank" class="btn-secondary"><i class="ti ti-printer" aria-hidden="true"></i>Imprimer</a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card"><p class="text-sm text-slate-600">Facturé</p><p class="text-xl font-semibold tabular-nums sm:text-2xl">{{ $mru($total['facture']) }}</p><p class="text-xs text-slate-500">{{ $total['actes'] }} actes</p></div>
        <div class="card"><p class="text-sm text-slate-600">Encaissé</p><p class="text-xl font-semibold tabular-nums sm:text-2xl">{{ $mru($total['encaisse']) }}</p></div>
        <div class="card"><p class="text-sm text-slate-600">Dépenses</p><p class="text-xl font-semibold tabular-nums sm:text-2xl text-red-700">{{ $mru($total['depenses']) }}</p></div>
        <div class="card"><p class="text-sm text-slate-600">Bilan</p><p @class(['text-xl font-semibold tabular-nums sm:text-2xl', 'text-red-700' => $total['bilan'] < 0])>{{ $mru($total['bilan']) }}</p></div>
    </div>

    <section class="card overflow-x-auto" aria-label="Indicateurs par praticien">
        <table class="tableau-cartes w-full min-w-[720px] text-sm">
            <thead class="text-left text-xs text-slate-500">
                <tr>
                    <th class="py-1 font-medium">Praticien</th>
                    <th class="py-1 text-right font-medium">Patients</th>
                    <th class="py-1 text-right font-medium">Actes</th>
                    <th class="py-1 text-right font-medium">Facturé</th>
                    <th class="py-1 text-right font-medium">Moyenne / acte</th>
                    <th class="py-1 text-right font-medium">Encaissé</th>
                    <th class="py-1 text-right font-medium">Dépenses</th>
                    <th class="py-1 text-right font-medium">Bilan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($lignes as $l)
                    <tr wire:key="p-{{ $l['id'] ?? 'cabinet' }}">
                        <td class="py-2 font-medium">{{ $l['nom'] }}</td>
                        <td class="py-2 text-right">{{ $l['patients'] }}</td>
                        <td class="py-2 text-right">{{ $l['actes'] }}</td>
                        <td class="py-2 text-right">{{ $mru($l['facture']) }}</td>
                        <td class="py-2 text-right">{{ $mru($l['moyenne']) }}</td>
                        <td class="py-2 text-right">{{ $mru($l['encaisse']) }}</td>
                        <td class="py-2 text-right text-red-700">{{ $l['depenses'] ? $mru(-$l['depenses']) : '—' }}</td>
                        <td @class(['py-2 text-right font-medium', 'text-red-700' => $l['bilan'] < 0])>{{ $mru($l['bilan']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t border-slate-200 font-semibold">
                <tr>
                    <td class="py-2">Total</td>
                    <td></td>
                    <td class="py-2 text-right">{{ $total['actes'] }}</td>
                    <td class="py-2 text-right">{{ $mru($total['facture']) }}</td>
                    <td class="py-2 text-right">{{ $mru($total['moyenne']) }}</td>
                    <td class="py-2 text-right">{{ $mru($total['encaisse']) }}</td>
                    <td class="py-2 text-right text-red-700">{{ $mru(-$total['depenses']) }}</td>
                    <td class="py-2 text-right">{{ $mru($total['bilan']) }}</td>
                </tr>
            </tfoot>
        </table>
    </section>
    <p class="text-xs text-slate-500">Facturé : factures validées datées dans la période. Encaissé : paiements reçus dans la période, remboursements déduits. Les frais du cabinet non attribués figurent sur la ligne « Cabinet ».</p>
</div>
