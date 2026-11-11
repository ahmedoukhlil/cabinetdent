@php
    $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU';
    $badgeStatut = fn ($f) => match (true) {
        $f->statut === 'brouillon' => ['Devis', 'bg-slate-100 text-slate-700'],
        $f->statut === 'annulee' => ['Annulée', 'bg-red-100 text-red-700'],
        $f->estSoldee() => ['Payée', 'bg-emerald-100 text-emerald-800'],
        $f->regle_patient + $f->regle_pec > 0 => ['Partiellement payée', 'bg-amber-100 text-amber-800'],
        default => ['À payer', 'bg-amber-100 text-amber-800'],
    };
@endphp
<div class="space-y-4">
    <div wire:loading.delay class="fixed top-4 left-1/2 z-[70] -translate-x-1/2 rounded-full bg-slate-900 px-4 py-2 text-sm text-white shadow-lg" role="status">Traitement en cours…</div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold">Facturation</h1>
        @can('facture.edit')
            <button type="button" wire:click="nouvelleFacture" class="btn-primary"><i class="ti ti-file-plus" aria-hidden="true"></i>Nouveau devis</button>
        @endcan
    </div>

    @if ($creationOuverte)
        <section class="card" aria-labelledby="nouveau-devis-titre">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h2 id="nouveau-devis-titre" class="font-semibold">Créer un devis</h2>
                    <p class="text-sm text-slate-600">Recherchez le patient concerné.</p>
                </div>
                <button type="button" wire:click="$set('creationOuverte', false)" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Fermer"><i class="ti ti-x" aria-hidden="true"></i></button>
            </div>
            <div class="relative mt-3 max-w-xl">
                <label for="recherche-patient-facture" class="label">Patient</label>
                <input id="recherche-patient-facture" type="search" wire:model.live.debounce.250ms="recherchePatient" class="field" placeholder="Nom, téléphone ou numéro de fiche" autocomplete="off" autofocus>
                @if ($recherchePatient !== '')
                    <ul class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg">
                        @forelse ($patientsProposes as $patient)
                            <li><button type="button" wire:click="creerPourPatient({{ $patient->id }})" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-primary-light"><span class="font-medium">{{ $patient->nom_complet }}</span><span class="text-slate-500">Fiche N° {{ $patient->numero_fiche }}</span></button></li>
                        @empty
                            <li class="px-3 py-3 text-sm text-slate-600">Aucun patient trouvé.</li>
                        @endforelse
                    </ul>
                @endif
            </div>
        </section>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
        <section class="card lg:col-span-2" aria-label="Liste des factures">
            <div class="mb-3 flex flex-wrap gap-1" role="group" aria-label="Filtrer">
                @foreach (['impayees' => 'À encaisser', 'brouillons' => 'Devis', 'validees' => 'Validées', 'annulees' => 'Annulées', 'toutes' => 'Toutes'] as $valeur => $libelle)
                    <button type="button" wire:click="$set('filtre', '{{ $valeur }}')" aria-pressed="{{ $filtre === $valeur ? 'true' : 'false' }}"
                        @class(['rounded-md px-2.5 py-1 text-sm', 'bg-primary text-white' => $filtre === $valeur, 'text-slate-700 hover:bg-slate-100' => $filtre !== $valeur])>{{ $libelle }}</button>
                @endforeach
            </div>
            <input type="search" wire:model.live.debounce.300ms="recherche" placeholder="N° de facture ou patient" class="field mb-2" aria-label="Rechercher une facture">
            <ul class="divide-y divide-slate-100">
                @forelse ($factures as $f)
                    @php [$texte, $classe] = $badgeStatut($f); @endphp
                    <li wire:key="f-{{ $f->id }}">
                        <button type="button" wire:click="ouvrir({{ $f->id }})" @class(['flex w-full items-center gap-2 rounded-lg px-2 py-2.5 text-left text-sm hover:bg-slate-50', 'bg-primary-light' => $facture === $f->id])>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium">{{ $f->patient->nom_complet }}</span>
                                <span class="text-xs text-slate-500">{{ $f->numero ?? 'Devis' }} · {{ ($f->date_facture ?? $f->created_at)->format('d/m/Y') }}</span>
                            </span>
                            <span class="text-right">
                                <span class="block">{{ $mru($f->total) }}</span>
                                <span class="badge {{ $classe }}">{{ $texte }}</span>
                            </span>
                        </button>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-600">Aucune facture dans cette liste.</li>
                @endforelse
            </ul>
            <div class="mt-3">{{ $factures->links() }}</div>
        </section>

        <section class="lg:col-span-3" aria-label="Détail de la facture">
            @if (!$ouverte)
                <div class="card text-sm text-slate-600">
                    Sélectionnez une facture ou utilisez « Nouveau devis ». Un soin peut également être facturé depuis le plan de traitement du patient.
                </div>
            @else
                @php [$texte, $classe] = $badgeStatut($ouverte); $brouillon = $ouverte->statut === 'brouillon'; @endphp
                <div class="card space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h2 class="text-lg font-semibold">{{ $ouverte->libelle }}</h2>
                            <p class="text-sm text-slate-600">
                                <a href="{{ route('patients', ['fiche' => $ouverte->patient_id]) }}" wire:navigate class="text-primary hover:underline">{{ $ouverte->patient->nom_complet }}</a>
                                · fiche N° {{ $ouverte->patient->numero_fiche }}
                                @if ($ouverte->dentiste) · Dr {{ $ouverte->dentiste->name }} @endif
                            </p>
                            @if ($ouverte->assureur)
                                <p class="text-sm text-slate-600">Assurance : {{ $ouverte->assureur->nom }} · {{ rtrim(rtrim(number_format($ouverte->taux_pec * 100, 2, ',', ''), '0'), ',') }} % pris en charge</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="badge {{ $classe }}">{{ $texte }}</span>
                            @if (!$brouillon)
                                <a href="{{ route('factures.imprimer', $ouverte) }}" target="_blank" class="btn-secondary !px-3 !py-1.5"><i class="ti ti-printer" aria-hidden="true"></i>Imprimer</a>
                            @endif
                        </div>
                    </div>

                    @if ($ouverte->statut === 'annulee')
                        <p class="rounded-lg bg-red-50 p-3 text-sm text-red-800">Annulée le {{ $ouverte->annulee_at->format('d/m/Y') }} · {{ $ouverte->motif_annulation }}</p>
                    @endif

                    <table class="tableau-cartes w-full text-sm">
                        <thead class="text-left text-xs text-slate-500">
                            <tr><th class="py-1 font-medium">Acte</th><th class="py-1 text-right font-medium">Qté</th><th class="py-1 text-right font-medium">Prix</th><th class="py-1 text-right font-medium">Montant</th>@if ($brouillon)<th></th>@endif</tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($ouverte->lignes as $l)
                                <tr wire:key="fl-{{ $l->id }}">
                                    <td class="py-2">{{ $l->libelle }}@if ($l->dents)<span class="block text-xs text-slate-500">Dent{{ count($l->dents) > 1 ? 's' : '' }} {{ implode(', ', $l->dents) }}</span>@endif</td>
                                    <td class="py-2 text-right">{{ rtrim(rtrim(number_format($l->quantite, 2, ',', ''), '0'), ',') }}</td>
                                    <td class="py-2 text-right">{{ $mru($l->prix_unitaire) }}</td>
                                    <td class="py-2 text-right">{{ $mru($l->montant) }}</td>
                                    @if ($brouillon)
                                        <td class="py-2 text-right"><button type="button" wire:click="supprimerLigne({{ $l->id }})" class="text-slate-400 hover:text-red-600" aria-label="Retirer {{ $l->libelle }}"><i class="ti ti-trash" aria-hidden="true"></i></button></td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-3 text-slate-600">Aucun acte. Ajoutez-en un ci-dessous.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    @php $dentsFacture = $ouverte->lignes->pluck('dents')->filter()->flatten()->map(fn ($d) => (int) $d)->unique()->sort()->values()->all(); @endphp
                    @if ($dentsFacture)
                        <details class="rounded-lg border border-slate-200" wire:key="odo-facture-{{ $ouverte->id }}">
                            <summary class="cursor-pointer px-3 py-2 text-sm text-slate-600"><i class="ti ti-dental text-primary" aria-hidden="true"></i> Dents concernées : {{ implode(', ', $dentsFacture) }}</summary>
                            <div x-data="odontogramme(true)" class="px-2 pb-2">
                                <span class="hidden" data-odontogramme="{{ json_encode(['selection' => [], 'conditions' => [['label' => $brouillon ? 'Au devis' : 'Facturé', 'teeth' => $dentsFacture, 'fillColor' => '#bfdbfe', 'outlineColor' => '#0f4c81']]]) }}"></span>
                                <div wire:ignore data-scene class="mx-auto min-h-[260px] max-w-[300px]"></div>
                            </div>
                            <a href="{{ route('dossier', $ouverte->patient_id) }}" wire:navigate class="block border-t border-slate-200 px-3 py-2 text-sm text-primary hover:underline">Ouvrir le dossier dentaire</a>
                        </details>
                    @endif

                    @if ($brouillon && $user->can('facture.edit'))
                        <form wire:submit="ajouterLigne" class="space-y-2 rounded-lg bg-slate-50 p-3">
                            <div class="grid grid-cols-6 gap-2">
                                <select wire:model.live="ligne.acte_id" class="field col-span-6 sm:col-span-2" aria-label="Acte du catalogue">
                                    <option value="">Acte libre…</option>
                                    @foreach ($actes as $a)<option value="{{ $a->id }}">{{ $a->libelle }} · {{ $mru($a->prixPour($ouverte->assureur_id)) }}</option>@endforeach
                                </select>
                                <input wire:model="ligne.libelle" placeholder="Libellé" class="field col-span-6 sm:col-span-2" aria-label="Libellé">
                                <input type="number" step="0.01" min="0.01" wire:model="ligne.quantite" class="field col-span-2 sm:col-span-1" aria-label="Quantité">
                                <input type="number" step="1" min="0" wire:model="ligne.prix_unitaire" placeholder="Prix" class="field col-span-4 sm:col-span-1" aria-label="Prix unitaire en MRU">
                            </div>
                            @foreach (['libelle', 'acte_id', 'quantite', 'prix_unitaire'] as $champ)
                                @error($champ)<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            @endforeach
                            <div class="flex justify-end"><button type="submit" class="btn-secondary"><i class="ti ti-plus" aria-hidden="true"></i>Ajouter l'acte</button></div>
                        </form>
                    @endif

                    <dl class="ml-auto w-full max-w-xs space-y-1 text-sm">
                        <div class="flex justify-between font-semibold"><dt>Total</dt><dd>{{ $mru($ouverte->total) }}</dd></div>
                        @if ($ouverte->part_pec > 0)
                            <div class="flex justify-between"><dt class="text-slate-600">Part assureur</dt><dd>{{ $mru($ouverte->part_pec) }}</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-600">Part patient</dt><dd>{{ $mru($ouverte->part_patient) }}</dd></div>
                        @endif
                        @if ($ouverte->statut === 'validee')
                            <div class="flex justify-between border-t border-slate-200 pt-1"><dt class="text-slate-600">Reste patient</dt><dd @class(['font-medium', 'text-red-700' => $ouverte->restePatient() > 0])>{{ $mru($ouverte->restePatient()) }}</dd></div>
                            @if ($ouverte->part_pec > 0)
                                <div class="flex justify-between"><dt class="text-slate-600">Reste assureur</dt><dd @class(['font-medium', 'text-red-700' => $ouverte->restePec() > 0])>{{ $mru($ouverte->restePec()) }}</dd></div>
                            @endif
                        @endif
                    </dl>
                    @error('facture')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                    @if ($brouillon && $user->can('facture.edit'))
                        <div class="flex flex-wrap justify-end gap-2">
                            <button type="button" wire:click="annuler" wire:confirm="Supprimer ce devis ?" class="btn-danger sm:mr-auto">Supprimer le devis</button>
                            @if ($ouverte->lignes->isNotEmpty())
                                <a href="{{ route('factures.imprimer', $ouverte) }}" target="_blank" rel="noopener" class="btn-secondary"><i class="ti ti-printer" aria-hidden="true"></i>Imprimer le devis</a>
                            @endif
                            <button type="button" wire:click="valider" wire:confirm="Valider la facture ? Elle ne pourra plus être modifiée." class="btn-primary"><i class="ti ti-check" aria-hidden="true"></i>Valider la facture</button>
                        </div>
                    @endif

                    @if ($ouverte->statut === 'validee' && $user->can('paiement.create'))
                        <form wire:submit="enregistrerPaiement" class="space-y-2 rounded-lg border border-slate-200 p-3">
                            <p class="text-sm font-medium">{{ $paiement['type'] === 'remboursement' ? 'Rembourser' : 'Encaisser' }}</p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @if ($user->can('paiement.refund'))
                                    <select wire:model.live="paiement.type" class="field" aria-label="Type d'opération"><option value="encaissement">Encaissement</option><option value="remboursement">Remboursement</option></select>
                                @endif
                                @if ($ouverte->part_pec > 0)
                                    <select wire:model.live="paiement.payeur" class="field" aria-label="Payeur"><option value="patient">Patient</option><option value="assureur">Assureur</option></select>
                                @endif
                                <select wire:model="paiement.mode" class="field" aria-label="Mode de paiement">
                                    @foreach ($modesActifs as $valeur => $libelle)<option value="{{ $valeur }}">{{ $libelle }}</option>@endforeach
                                </select>
                                <input type="number" step="1" min="1" wire:model="paiement.montant" placeholder="Montant" class="field" aria-label="Montant en MRU">
                                <input wire:model="paiement.reference" placeholder="Référence (facultatif)" class="field col-span-2" aria-label="Référence">
                            </div>
                            @foreach (['montant', 'payeur', 'mode'] as $champ)
                                @error($champ)<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            @endforeach
                            <div class="flex justify-end">
                                <button type="submit" @class(['btn-primary' => $paiement['type'] === 'encaissement', 'btn-danger' => $paiement['type'] === 'remboursement']) wire:loading.attr="disabled">
                                    <i class="ti ti-cash" aria-hidden="true"></i>{{ $paiement['type'] === 'remboursement' ? 'Rembourser' : 'Encaisser' }}
                                </button>
                            </div>
                        </form>
                    @endif

                    @if ($ouverte->paiements->isNotEmpty())
                        <div>
                            <h3 class="mb-1 text-sm font-medium">Paiements</h3>
                            <ul class="divide-y divide-slate-100 text-sm">
                                @foreach ($ouverte->paiements as $p)
                                    <li class="flex flex-wrap items-center gap-2 py-1.5">
                                        <span class="w-28 text-slate-600">{{ $p->created_at->format('d/m/Y H:i') }}</span>
                                        <span class="flex-1">{{ $p->type === 'remboursement' ? 'Remboursement' : 'Encaissement' }} {{ $p->payeur === 'assureur' ? 'assureur' : 'patient' }} · {{ $modes[$p->mode] ?? $p->mode }}@if ($p->reference) · {{ $p->reference }} @endif</span>
                                        <span @class(['font-medium', 'text-red-700' => $p->type === 'remboursement'])>{{ $mru($p->montant_signe) }}</span>
                                        <a href="{{ route('paiements.recu', $p) }}" target="_blank" class="text-primary hover:underline">Reçu</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($ouverte->statut === 'validee' && $user->can('paiement.refund'))
                        <details class="text-sm">
                            <summary class="cursor-pointer text-slate-600">Annuler la facture</summary>
                            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                                <input wire:model="motif" placeholder="Motif d'annulation" class="field flex-1" aria-label="Motif d'annulation">
                                <button type="button" wire:click="annuler" wire:confirm="Annuler définitivement cette facture ?" class="btn-danger">Annuler la facture</button>
                            </div>
                            @error('motif')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </details>
                    @endif
                </div>
            @endif
        </section>
    </div>
</div>
