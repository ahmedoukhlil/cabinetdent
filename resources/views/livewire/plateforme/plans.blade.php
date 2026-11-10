@php $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU'; @endphp
<div class="space-y-4">
    <h1 class="text-lg font-semibold">Formules d'abonnement</h1>

    <form wire:submit="enregistrer" class="card grid grid-cols-6 gap-2">
        <div class="col-span-6 sm:col-span-2"><label class="label" for="pl-nom">Nom</label><input id="pl-nom" wire:model="plan.nom" class="field" placeholder="Essentiel, Cabinet, Clinique…">@error('plan.nom')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div class="col-span-2 sm:col-span-1"><label class="label" for="pl-prix">Prix / mois (MRU)</label><input id="pl-prix" type="number" min="0" step="any" wire:model="plan.prix_mensuel" class="field">@error('plan.prix_mensuel')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div class="col-span-2 sm:col-span-1"><label class="label" for="pl-u">Comptes max.</label><input id="pl-u" type="number" min="1" wire:model="plan.max_utilisateurs" class="field" placeholder="Illimité"></div>
        <div class="col-span-2 sm:col-span-1"><label class="label" for="pl-s">Stockage max. (Mo)</label><input id="pl-s" type="number" min="1" wire:model="plan.max_stockage_mo" class="field" placeholder="Illimité"></div>
        <div class="col-span-6 flex items-end justify-end gap-2 sm:col-span-1">
            @if ($plan['id'])<button type="button" wire:click="$set('plan', {id: null, nom: '', prix_mensuel: '', max_utilisateurs: '', max_stockage_mo: ''})" class="btn-secondary">Annuler</button>@endif
            <button type="submit" class="btn-primary w-full">{{ $plan['id'] ? 'Mettre à jour' : 'Ajouter' }}</button>
        </div>
    </form>

    <section class="card overflow-x-auto">
        <table class="tableau-cartes w-full min-w-[520px] text-sm [&_td]:px-2 [&_th]:px-2">
            <thead class="text-left text-xs text-slate-500"><tr><th class="py-1 font-medium">Formule</th><th class="py-1 text-right font-medium">Prix / mois</th><th class="py-1 text-right font-medium">Comptes</th><th class="py-1 text-right font-medium">Stockage</th><th class="py-1 text-right font-medium">Cabinets</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($plans as $p)
                    <tr wire:key="pl-{{ $p->id }}" @class(['opacity-50' => !$p->actif])>
                        <td class="py-2 font-medium">{{ $p->nom }}</td>
                        <td class="py-2 text-right">{{ $mru($p->prix_mensuel) }}</td>
                        <td class="py-2 text-right">{{ $p->max_utilisateurs ?? 'Illimité' }}</td>
                        <td class="py-2 text-right">{{ $p->max_stockage_mo ? number_format($p->max_stockage_mo, 0, ',', ' ').' Mo' : 'Illimité' }}</td>
                        <td class="py-2 text-right">{{ $utilisation[$p->id] ?? 0 }}</td>
                        <td class="py-2 text-right whitespace-nowrap">
                            <button type="button" wire:click="modifier({{ $p->id }})" class="px-1 text-slate-500 hover:text-primary" aria-label="Modifier {{ $p->nom }}"><i class="ti ti-edit" aria-hidden="true"></i></button>
                            <button type="button" wire:click="basculer({{ $p->id }})" class="px-1 text-xs text-slate-500 hover:underline">{{ $p->actif ? 'Retirer de la vente' : 'Remettre en vente' }}</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-slate-600">Aucune formule.</td></tr>
                @endforelse
            </tbody>
        </table>
        <p class="mt-2 text-xs text-slate-500">Une formule retirée de la vente reste valable pour les cabinets qui l'ont déjà.</p>
    </section>
</div>
