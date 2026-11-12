@php
    use App\Models\Consommable;
    use App\Models\MouvementStock;
    $mru = fn ($v) => number_format((float) $v, 0, ',', ' ').' MRU';
    $qte = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ' '), '0'), ',');
@endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold">Stock de consommables</h1>
        <button type="button" wire:click="nouvelArticle" class="btn-primary"><i class="ti ti-plus" aria-hidden="true"></i>Nouvel article</button>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="card"><p class="text-sm text-slate-600">Valeur du stock</p><p class="text-xl font-semibold tabular-nums sm:text-2xl">{{ $mru($valeur) }}</p><p class="text-xs text-slate-500">{{ $nbArticles }} articles actifs</p></div>
        <button type="button" wire:click="$set('filtre', 'alertes')" class="card text-left hover:border-red-300"><p class="text-sm text-slate-600">Ruptures</p><p @class(['text-xl font-semibold tabular-nums sm:text-2xl', 'text-red-700' => $compteurs['rupture']])>{{ $compteurs['rupture'] }}</p></button>
        <button type="button" wire:click="$set('filtre', 'alertes')" class="card text-left hover:border-amber-300"><p class="text-sm text-slate-600">Stock faible</p><p @class(['text-xl font-semibold tabular-nums sm:text-2xl', 'text-amber-700' => $compteurs['faible']])>{{ $compteurs['faible'] }}</p></button>
        <button type="button" wire:click="$set('filtre', 'alertes')" class="card text-left hover:border-red-300"><p class="text-sm text-slate-600">Périmés / péremption &lt; 30 j</p><p @class(['text-xl font-semibold tabular-nums sm:text-2xl', 'text-red-700' => $compteurs['perime'] + $compteurs['peremption_proche']])>{{ $compteurs['perime'] }} / {{ $compteurs['peremption_proche'] }}</p></button>
    </div>

    @if ($formulaireArticle)
        <form wire:submit="enregistrerArticle" class="card grid grid-cols-6 gap-2">
            <p class="col-span-6 font-medium">{{ $article['id'] ? 'Modifier l\'article' : 'Nouvel article' }}</p>
            <div class="col-span-6 sm:col-span-2">
                <label class="label" for="a-nom">Nom</label>
                <input id="a-nom" wire:model="article.nom" class="field" placeholder="Gants nitrile M, Composite A2…">
                @error('article.nom')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="col-span-6 sm:col-span-2">
                <label class="label" for="a-cat">Catégorie</label>
                <select id="a-cat" wire:model="article.categorie" class="field">
                    @foreach ($categories as $cle => $libelle)<option value="{{ $cle }}">{{ $libelle }}</option>@endforeach
                </select>
            </div>
            <div class="col-span-3 sm:col-span-1">
                <label class="label" for="a-unite">Unité</label>
                <input id="a-unite" wire:model="article.unite" class="field" placeholder="boîte, seringue…">
                @error('article.unite')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="col-span-3 sm:col-span-1">
                <label class="label" for="a-min">Seuil d'alerte</label>
                <input id="a-min" type="number" min="0" step="any" wire:model="article.quantite_min" class="field">
                @error('article.quantite_min')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <p class="col-span-6 text-xs text-slate-500 sm:col-span-4">La quantité ne se saisit pas ici : enregistrez une entrée après création.</p>
            <div class="col-span-6 flex justify-end gap-2 sm:col-span-2">
                <button type="button" wire:click="$set('formulaireArticle', false)" class="btn-secondary">Annuler</button>
                <button type="submit" class="btn-primary">Enregistrer</button>
            </div>
        </form>
    @endif

    @if ($enMouvement)
        <form wire:submit="enregistrerMouvement" class="card grid grid-cols-6 gap-2 border-primary">
            <p class="col-span-6 font-medium">{{ MouvementStock::TYPES[$mouvement['type']] }} · {{ $enMouvement->nom }} <span class="text-sm font-normal text-slate-500">· stock actuel {{ $qte($enMouvement->quantite) }} {{ $enMouvement->unite }}</span></p>
            <div class="col-span-3 sm:col-span-1">
                <label class="label" for="m-q">{{ $mouvement['type'] === 'ajustement' ? 'Quantité comptée' : 'Quantité' }}</label>
                <input id="m-q" type="number" min="0" step="any" wire:model="mouvement.quantite" class="field" autofocus>
                @error('mouvement.quantite')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            @if ($mouvement['type'] === 'entree')
                <div class="col-span-3 sm:col-span-1">
                    <label class="label" for="m-p">Prix unitaire (MRU)</label>
                    <input id="m-p" type="number" min="0" step="any" wire:model="mouvement.prix_unitaire" class="field" placeholder="{{ $enMouvement->prix_achat ?: '' }}">
                </div>
                <div class="col-span-3 sm:col-span-1">
                    <label class="label" for="m-per">Péremption</label>
                    <input id="m-per" type="date" wire:model="mouvement.peremption" class="field">
                </div>
            @endif
            <div class="col-span-6 {{ $mouvement['type'] === 'entree' ? 'sm:col-span-2' : 'sm:col-span-4' }}">
                <label class="label" for="m-motif">{{ $mouvement['type'] === 'ajustement' ? 'Motif *' : 'Note' }}</label>
                <input id="m-motif" wire:model="mouvement.motif" class="field" placeholder="{{ $mouvement['type'] === 'entree' ? 'Fournisseur, N° facture…' : ($mouvement['type'] === 'sortie' ? 'Salle 1, soin…' : 'Inventaire mensuel, casse…') }}">
                @error('mouvement.motif')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="col-span-6 flex items-end justify-end gap-2 sm:col-span-1">
                <button type="button" wire:click="$set('articleMouvement', null)" class="btn-secondary">Annuler</button>
                <button type="submit" class="btn-primary">OK</button>
            </div>
        </form>
    @endif

    <section class="card space-y-3" aria-label="Articles">
        <div class="flex flex-wrap items-center gap-2">
            <input type="search" wire:model.live.debounce.300ms="recherche" placeholder="Rechercher un article" class="field !w-auto grow" aria-label="Rechercher un article">
            <select wire:model.live="filtre" class="field !w-auto" aria-label="Filtre">
                <option value="tous">Tous les articles</option>
                <option value="alertes">Avec alerte</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="tableau-cartes w-full min-w-[760px] text-sm [&_td]:px-2 [&_th]:px-2">
                <thead class="text-left text-xs text-slate-500">
                    <tr><th class="py-1 font-medium">Article</th><th class="py-1 text-right font-medium">Stock</th><th class="py-1 text-right font-medium">Seuil</th><th class="py-1 font-medium">Péremption</th><th class="py-1 text-right font-medium">Valeur</th><th class="py-1 font-medium">Alertes</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($articles as $a)
                        <tr wire:key="a-{{ $a->id }}" @class(['opacity-50' => !$a->actif])>
                            <td class="py-2"><p class="font-medium">{{ $a->nom }}</p><p class="text-xs text-slate-500">{{ $categories[$a->categorie] ?? $a->categorie }}</p></td>
                            <td class="py-2 text-right font-medium">{{ $qte($a->quantite) }} <span class="font-normal text-slate-500">{{ $a->unite }}</span></td>
                            <td class="py-2 text-right text-slate-500">{{ $qte($a->quantite_min) }}</td>
                            <td class="py-2">{{ $a->peremption?->format('d/m/Y') ?? '—' }}</td>
                            <td class="py-2 text-right whitespace-nowrap">{{ $mru($a->valeur()) }}</td>
                            <td class="py-2">
                                @if ($a->actif)
                                    @foreach ($a->alertes() as $al)<span class="badge {{ Consommable::ALERTES[$al][1] }}">{{ Consommable::ALERTES[$al][0] }}</span> @endforeach
                                @else
                                    <span class="text-xs text-slate-500">Désactivé</span>
                                @endif
                            </td>
                            <td class="py-2 text-right whitespace-nowrap">
                                @if ($a->actif)
                                    <button type="button" wire:click="ouvrirMouvement({{ $a->id }}, 'entree')" class="rounded-md px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50" title="Entrée"><i class="ti ti-plus" aria-hidden="true"></i> Entrée</button>
                                    <button type="button" wire:click="ouvrirMouvement({{ $a->id }}, 'sortie')" class="rounded-md px-2 py-1 text-xs font-medium text-primary hover:bg-primary-light" title="Sortie"><i class="ti ti-minus" aria-hidden="true"></i> Sortie</button>
                                    <button type="button" wire:click="ouvrirMouvement({{ $a->id }}, 'ajustement')" class="rounded-md px-2 py-1 text-xs text-slate-600 hover:bg-slate-100" title="Inventaire"><i class="ti ti-clipboard-check" aria-hidden="true"></i></button>
                                @endif
                                <button type="button" wire:click="modifierArticle({{ $a->id }})" class="px-1 text-slate-500 hover:text-primary" aria-label="Modifier {{ $a->nom }}"><i class="ti ti-edit" aria-hidden="true"></i></button>
                                <button type="button" wire:click="basculerActif({{ $a->id }})" class="px-1 text-xs text-slate-500 hover:underline">{{ $a->actif ? 'Désactiver' : 'Réactiver' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-slate-600">{{ $filtre === 'alertes' ? 'Aucune alerte.' : 'Aucun article. Ajoutez vos consommables habituels.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card" aria-labelledby="mvt-titre">
        <h2 id="mvt-titre" class="mb-2 font-medium">Derniers mouvements</h2>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($historique as $m)
                <li class="flex flex-wrap items-center gap-2 py-2" wire:key="m-{{ $m->id }}">
                    <span class="w-24 text-slate-500">{{ $m->created_at->format('d/m H:i') }}</span>
                    <span class="flex-1">{{ $m->consommable->nom }} <span class="text-slate-500">· {{ MouvementStock::TYPES[$m->type] }}{{ $m->motif ? ' · '.$m->motif : '' }} · {{ $m->auteur?->name }}</span></span>
                    <span @class(['font-medium', 'text-emerald-700' => $m->quantite > 0, 'text-red-700' => $m->quantite < 0])>{{ $m->quantite > 0 ? '+' : '' }}{{ $qte($m->quantite) }}</span>
                    <span class="w-20 text-right text-slate-500">→ {{ $qte($m->stock_apres) }}</span>
                </li>
            @empty
                <li class="py-2 text-slate-600">Aucun mouvement.</li>
            @endforelse
        </ul>
    </section>
</div>
