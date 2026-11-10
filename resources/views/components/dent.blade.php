@props(['numero', 'dent' => null, 'selectionnee' => false])
@php
    $synthese = $dent?->synthese() ?? 'saine';
    $faces = $dent?->faces ?? \App\Support\Odontologie::facesSaines();
    $superieure = in_array(intdiv($numero, 10), [1, 2], true);
    $droitePatient = in_array(intdiv($numero, 10), [1, 4], true);
    // Position affichée de chaque face : haut, bas, gauche, droite.
    $haut = $superieure ? 'V' : 'L';
    $bas = $superieure ? 'L' : 'V';
    $gauche = $droitePatient ? 'D' : 'M';
    $droite = $droitePatient ? 'M' : 'D';
    $remplissage = fn ($face) => match ($faces[$face] ?? 'sain') {
        'carie' => '#dc2626',
        'obturation' => '#2563eb',
        default => '#ffffff',
    };
    $cadre = [
        'saine' => 'border-slate-200 bg-white',
        'a_traiter' => 'border-red-300 bg-red-50',
        'obturee' => 'border-blue-200 bg-blue-50',
        'endo' => 'border-violet-300 bg-violet-50',
        'prothese' => 'border-amber-300 bg-amber-50',
        'implant' => 'border-teal-300 bg-teal-50',
        'absente' => 'border-dashed border-slate-300 bg-slate-100',
    ][$synthese];
    $absente = $synthese === 'absente';
@endphp
<button type="button"
    {{ $attributes->merge(['class' => "group flex flex-col items-center gap-0.5 rounded-lg border p-1 transition {$cadre} ".($selectionnee ? 'ring-2 ring-primary ring-offset-1' : 'hover:border-primary')]) }}
    aria-pressed="{{ $selectionnee ? 'true' : 'false' }}"
    aria-label="Dent {{ $numero }}{{ $dent ? ' · '.str_replace('_', ' ', $synthese) : '' }}">
    @if ($superieure)<span class="text-[11px] font-medium text-slate-600">{{ $numero }}</span>@endif
    <svg viewBox="0 0 30 30" class="h-7 w-7 sm:h-8 sm:w-8" aria-hidden="true">
        @if ($absente)
            <rect x="1" y="1" width="28" height="28" rx="4" fill="none" stroke="#94a3b8" stroke-dasharray="3 2"/>
            <path d="M6 6 L24 24 M24 6 L6 24" stroke="#94a3b8" stroke-width="2"/>
        @else
            <polygon points="1,1 29,1 21,9 9,9" fill="{{ $remplissage($haut) }}" stroke="#64748b" stroke-width="0.8"/>
            <polygon points="1,29 29,29 21,21 9,21" fill="{{ $remplissage($bas) }}" stroke="#64748b" stroke-width="0.8"/>
            <polygon points="1,1 9,9 9,21 1,29" fill="{{ $remplissage($gauche) }}" stroke="#64748b" stroke-width="0.8"/>
            <polygon points="29,1 21,9 21,21 29,29" fill="{{ $remplissage($droite) }}" stroke="#64748b" stroke-width="0.8"/>
            <rect x="9" y="9" width="12" height="12" fill="{{ $remplissage('O') }}" stroke="#64748b" stroke-width="0.8"/>
            @if (in_array($synthese, ['prothese', 'implant']))
                <rect x="1" y="1" width="28" height="28" fill="none" stroke="{{ $synthese === 'implant' ? '#0d9488' : '#d97706' }}" stroke-width="2.5"/>
            @endif
            @if ($dent && $dent->endodontie !== 'non_traitee')
                <line x1="15" y1="11" x2="15" y2="19" stroke="#7c3aed" stroke-width="2.5"/>
            @endif
        @endif
    </svg>
    @unless ($superieure)<span class="text-[11px] font-medium text-slate-600">{{ $numero }}</span>@endunless
</button>
