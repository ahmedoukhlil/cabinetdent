@php $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU'; @endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h1 class="text-lg font-semibold">Caisse</h1>
        <div class="flex flex-wrap items-end gap-2">
            <div><label class="label" for="du">Du</label><input id="du" type="date" wire:model.live="du" class="field"></div>
            <div><label class="label" for="au">Au</label><input id="au" type="date" wire:model.live="au" class="field"></div>
            <div>
                <label class="label" for="dentiste">Dentiste</label>
                <select id="dentiste" wire:model.live="dentiste" class="field">
                    <option value="">Tous</option>
                    @foreach ($dentistes as $d)<option value="{{ $d->id }}">Dr {{ $d->name }}</option>@endforeach
                </select>
            </div>
            <a href="{{ route('caisse.imprimer', ['du' => $du, 'au' => $au, 'dentiste' => $dentiste]) }}" target="_blank" class="btn-secondary"><i class="ti ti-printer" aria-hidden="true"></i>État de caisse</a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card"><p class="text-sm text-slate-600">Encaissé</p><p class="text-xl font-semibold tabular-nums sm:text-2xl">{{ $mru($totaux['encaisse']) }}</p></div>
        <div class="card"><p class="text-sm text-slate-600">Remboursé</p><p class="text-xl font-semibold tabular-nums sm:text-2xl text-red-700">{{ $mru($totaux['rembourse']) }}</p></div>
        <div class="card"><p class="text-sm text-slate-600">Dépenses</p><p class="text-xl font-semibold tabular-nums sm:text-2xl text-red-700">{{ $mru($totaux['depenses']) }}</p></div>
        <div class="card"><p class="text-sm text-slate-600">Solde (recettes − dépenses)</p><p @class(['text-xl font-semibold tabular-nums sm:text-2xl', 'text-red-700' => $totaux['solde'] < 0])>{{ $mru($totaux['solde']) }}</p></div>
    </div>

    @if ($parMode->isNotEmpty())
        <div class="card">
            <h2 class="mb-2 text-sm font-medium text-slate-700">Par mode de paiement</h2>
            <dl class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-5">
                @foreach ($parMode as $mode => $montant)
                    <div class="rounded-lg bg-slate-50 p-2"><dt class="text-xs text-slate-600">{{ $modes[$mode] ?? $mode }}</dt><dd class="font-medium">{{ $mru($montant) }}</dd></div>
                @endforeach
            </dl>
        </div>
    @endif

    <section class="card overflow-x-auto" aria-label="Journal des opérations">
        <table class="tableau-cartes w-full min-w-[600px] text-sm">
            <thead class="text-left text-xs text-slate-500">
                <tr><th class="py-1 font-medium">Date</th><th class="py-1 font-medium">Patient</th><th class="py-1 font-medium">Facture</th><th class="py-1 font-medium">Opération</th><th class="py-1 font-medium">Par</th><th class="py-1 text-right font-medium">Montant</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($operations as $op)
                    <tr wire:key="op-{{ $op->id }}">
                        <td class="py-2">{{ $op->created_at->format('d/m H:i') }}</td>
                        <td class="py-2">{{ $op->patient->nom_complet }}</td>
                        <td class="py-2"><a href="{{ route('factures', ['facture' => $op->facture_id]) }}" wire:navigate class="text-primary hover:underline">{{ $op->facture->numero }}</a></td>
                        <td class="py-2">{{ $op->type === 'remboursement' ? 'Remboursement' : 'Encaissement' }} {{ $op->payeur === 'assureur' ? 'assureur' : '' }} · {{ $modes[$op->mode] ?? $op->mode }}</td>
                        <td class="py-2 text-slate-600">{{ $op->auteur?->name }}</td>
                        <td @class(['py-2 text-right font-medium', 'text-red-700' => $op->type === 'remboursement'])>{{ $mru($op->montant_signe) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-slate-600">Aucune opération sur la période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @include('livewire.partials.depenses')
</div>
