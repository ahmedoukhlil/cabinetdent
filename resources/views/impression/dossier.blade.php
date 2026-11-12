@php
    use App\Support\Odontologie;
    use App\Models\FichierPatient;
    use App\Models\PlanTraitement;
    $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU';
    $constatations = collect(Odontologie::CONSTATATIONS)->collapse();
    $decrire = function ($dent) use ($constatations) {
        $e = $dent->etat();
        $parts = [];
        if ($e['presence'] !== 'presente') $parts[] = Odontologie::PRESENCE[$e['presence']] ?? $e['presence'];
        if ($e['restauration'] !== 'aucune') $parts[] = Odontologie::RESTAURATION[$e['restauration']] ?? $e['restauration'];
        if ($e['endodontie'] !== 'non_traitee') $parts[] = Odontologie::ENDODONTIE[$e['endodontie']] ?? $e['endodontie'];
        $faces = collect($e['faces'])->reject(fn ($v) => $v === 'sain');
        foreach ($faces->groupBy(fn ($v) => $v, true) as $etat => $groupe) {
            $parts[] = (Odontologie::ETATS_FACE[$etat] ?? $etat).' '.implode('', array_keys($groupe->all()));
        }
        foreach ($e['constatations'] as $c) $parts[] = $constatations[$c] ?? $c;
        return implode(' · ', $parts);
    };
@endphp
<x-impression :titre="'Dossier dentaire · '.$patient->nom_complet" :cabinet="$patient->cabinet">
    <x-slot:entete>
        <h2>Dossier dentaire</h2>
        <div>{{ $patient->nom_complet }} · fiche N° {{ $patient->numero_fiche }}</div>
        <div>
            @if ($patient->date_naissance) Né(e) le {{ $patient->date_naissance->format('d/m/Y') }} ({{ $patient->date_naissance->age }} ans) @endif
            @if ($patient->telephone) · {{ $patient->telephone }} @endif
        </div>
    </x-slot:entete>

    @if ($patient->notes)
        <p style="background:#fef3c7;padding:8px;border-radius:4px"><strong>Antécédents / alertes :</strong> {{ $patient->notes }}</p>
    @endif

    <h3>Odontogramme <span class="muted" style="font-weight:normal">· notation FDI</span></h3>
    {{-- Odontogramme anatomique (le même qu'à l'écran), en lecture seule --}}
    <div class="odontogramme-impression" data-odontogramme-impression="{{ json_encode(['conditions' => $conditions]) }}">
        <div data-scene style="max-width:400px;margin:0 auto;min-height:260px">
            <p class="muted" style="text-align:center;padding:60px 0">Chargement de l'odontogramme…</p>
        </div>
    </div>
    <noscript>
        <table style="table-layout:fixed;text-align:center">
            @foreach ([Odontologie::ARCADE_SUPERIEURE, Odontologie::ARCADE_INFERIEURE] as $arcade)
                <tr>
                    @foreach ($arcade as $n)
                        @php $c = Odontologie::CONDITIONS_GRAPHE[$dents->get($n)?->synthese()] ?? null; @endphp
                        <td style="padding:2px;border:0"><div style="border:1.5px solid {{ $c['outlineColor'] ?? '#cbd5e1' }};background:{{ $c['fillColor'] ?? '#fff' }};border-radius:4px;padding:4px 0;font-weight:600;font-size:11px">{{ $n }}</div></td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </noscript>
    <style>
        .odontogramme-impression svg { display: block; width: auto !important; max-width: 100%; max-height: 420px; margin-inline: auto; }
        @media print { .odontogramme-impression svg { max-height: 340px; } .odontogramme-impression { break-inside: avoid; } }
    </style>
    @vite('resources/js/impression.js')

    @php
        $notables = $dents->sortKeys()->filter(fn ($d) => $d->synthese() !== 'saine' || $d->notes || $d->constatations);
    @endphp
    <h3>État des dents</h3>
    @if ($notables->isEmpty())
        <p class="muted">Aucune anomalie enregistrée.</p>
    @else
        <table>
            <thead><tr><th style="width:50px">Dent</th><th>Constat</th><th>Notes</th></tr></thead>
            <tbody>
                @foreach ($notables as $d)
                    <tr><td><strong>{{ $d->numero }}</strong></td><td>{{ $decrire($d) ?: 'Saine' }}</td><td>{{ $d->notes }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @foreach ($plans as $plan)
        <h3>Plan de traitement N° {{ $plan->id }} <span class="muted" style="font-weight:normal">· {{ PlanTraitement::STATUTS[$plan->statut] ?? $plan->statut }} · {{ $plan->created_at->format('d/m/Y') }}</span></h3>
        <table>
            <thead><tr><th>Soin</th><th>Dents</th><th class="r">Qté</th><th class="r">Montant</th><th>Statut</th></tr></thead>
            <tbody>
                @foreach ($plan->lignes as $l)
                    <tr>
                        <td>{{ $l->libelle }}</td>
                        <td>{{ $l->dents ? implode(', ', $l->dents) : '—' }}</td>
                        <td class="r">{{ $l->quantite }}</td>
                        <td class="r">{{ $mru($l->montant()) }}</td>
                        <td>{{ $l->statut === 'realise' ? 'Réalisé le '.$l->realise_at?->format('d/m/Y') : 'Prévu' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table class="totaux"><tr class="fort"><td>Total du plan</td><td class="r">{{ $mru($plan->total()) }}</td></tr></table>
    @endforeach

    @if ($fichiers->isNotEmpty())
        <h3>Radios et documents</h3>
        <table>
            <thead><tr><th>Date</th><th>Type</th><th>Titre</th><th>Dents</th></tr></thead>
            <tbody>
                @foreach ($fichiers as $f)
                    <tr><td>{{ $f->date_examen?->format('d/m/Y') }}</td><td>{{ FichierPatient::CATEGORIES[$f->categorie] ?? $f->categorie }}</td><td>{{ $f->titre }}</td><td>{{ $f->dents ? implode(', ', $f->dents) : '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="muted" style="margin-top:40px;text-align:right">Dr {{ auth()->user()->name }} · signature et cachet</p>
</x-impression>
