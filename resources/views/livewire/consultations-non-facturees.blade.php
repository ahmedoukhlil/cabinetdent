<div wire:poll.60s>
    @if ($lignes->isNotEmpty())
        @php
            $nonFacturees = $lignes->where('etat', 'non_facturee')->count();
            $aEncaisser = $lignes->where('etat', 'a_encaisser');
        @endphp
        <details class="rounded-xl border border-amber-300 bg-amber-50" {{ $lignes->count() <= 3 ? 'open' : '' }}>
            <summary class="flex cursor-pointer flex-wrap items-center gap-2 p-4 text-sm font-medium text-amber-900">
                <i class="ti ti-alert-triangle text-lg" aria-hidden="true"></i>
                @if ($nonFacturees)
                    {{ $nonFacturees }} consultation{{ $nonFacturees > 1 ? 's' : '' }} non facturée{{ $nonFacturees > 1 ? 's' : '' }}
                @endif
                @if ($nonFacturees && $aEncaisser->isNotEmpty()) · @endif
                @if ($aEncaisser->isNotEmpty())
                    {{ $aEncaisser->count() }} à encaisser ({{ number_format($aEncaisser->sum('reste'), 0, ',', ' ') }} MRU)
                @endif
                <span class="font-normal text-amber-800">aujourd'hui</span>
            </summary>
            <ul class="divide-y divide-amber-200 border-t border-amber-200 px-4 text-sm">
                @foreach ($lignes as $l)
                    <li wire:key="cnf-{{ $l['rdv']->id }}" class="flex flex-wrap items-center gap-2 py-2">
                        <span class="min-w-[11rem] flex-1">
                            {{ $l['rdv']->patient->nom_complet }}
                            <span class="text-xs text-amber-800">· Dr {{ $l['rdv']->dentiste->name }} · {{ $l['rdv']->statut->label() }}</span>
                        </span>
                        @if ($l['etat'] === 'non_facturee')
                            <button type="button" x-on:click="$dispatch('consultation-ouvrir', { rdv: {{ $l['rdv']->id }} })" class="btn-primary !px-3 !py-1.5 text-xs"><i class="ti ti-receipt" aria-hidden="true"></i> Facturer</button>
                        @else
                            <span class="tabular-nums">{{ number_format($l['reste'], 0, ',', ' ') }} MRU</span>
                            @if ($peutEncaisser)
                                <a href="{{ route('factures', ['facture' => $l['rdv']->facture_id]) }}" wire:navigate class="btn-secondary !px-3 !py-1.5 text-xs"><i class="ti ti-cash" aria-hidden="true"></i> Encaisser</a>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </details>
    @endif
</div>
