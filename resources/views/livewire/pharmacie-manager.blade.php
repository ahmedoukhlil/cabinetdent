<div class="p-4 md:p-6 space-y-6">

    @if(session()->has('message'))
    <div class="flex items-center gap-2 px-4 py-3 bg-green-50 border-l-4 border-green-500 rounded text-green-800 text-sm">
        <i class="fas fa-check-circle"></i> {{ session('message') }}
    </div>
    @endif
    @if(session()->has('error'))
    <div class="flex items-center gap-2 px-4 py-3 bg-red-50 border-l-4 border-red-500 rounded text-red-800 text-sm">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif

    {{-- Alertes --}}
    @if($alertesStockFaible > 0 || $alertesExpires > 0 || $alertesExpireBientot > 0)
    <div class="space-y-2">
        @if($alertesStockFaible > 0)
        <div class="flex items-center gap-2 px-4 py-3 bg-yellow-50 border-l-4 border-yellow-400 rounded text-yellow-800 text-sm">
            <i class="fas fa-exclamation-triangle"></i>
            <span><strong>{{ $alertesStockFaible }}</strong> médicament(s) en stock faible — quantité inférieure au seuil minimum</span>
        </div>
        @endif
        @if($alertesExpires > 0)
        <div class="flex items-center gap-2 px-4 py-3 bg-red-50 border-l-4 border-red-400 rounded text-red-800 text-sm">
            <i class="fas fa-times-circle"></i>
            <span><strong>{{ $alertesExpires }}</strong> lot(s) expiré(s) — date d'expiration dépassée</span>
        </div>
        @endif
        @if($alertesExpireBientot > 0)
        <div class="flex items-center gap-2 px-4 py-3 bg-orange-50 border-l-4 border-orange-400 rounded text-orange-800 text-sm">
            <i class="fas fa-clock"></i>
            <span><strong>{{ $alertesExpireBientot }}</strong> lot(s) expire(nt) dans les 30 prochains jours</span>
        </div>
        @endif
    </div>
    @endif

    @php $stats = $this->statistiquesDashboard; @endphp

    {{-- Statistiques --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <button wire:click="ouvrirDetailModal('total')" class="bg-primary-light rounded-xl p-4 text-left hover:shadow-md transition-shadow">
            <dt class="text-xs font-semibold text-primary uppercase tracking-wide mb-1">Total médicaments</dt>
            <dd class="text-2xl font-bold text-primary">{{ $stats['totalMedicaments'] }}</dd>
        </button>
        <button wire:click="ouvrirDetailModal('valeur')" class="bg-green-50 rounded-xl p-4 text-left hover:shadow-md transition-shadow">
            <dt class="text-xs font-semibold text-green-700 uppercase tracking-wide mb-1">Valeur du stock</dt>
            <dd class="text-2xl font-bold text-green-700">{{ number_format($stats['valeurStock'], 0, ',', ' ') }} <span class="text-sm font-normal">MRU</span></dd>
        </button>
        <button wire:click="ouvrirDetailModal('quantite')" class="bg-gray-50 rounded-xl p-4 text-left hover:shadow-md transition-shadow">
            <dt class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Unités disponibles</dt>
            <dd class="text-2xl font-bold text-gray-800">{{ number_format($stats['totalQuantiteStock'], 0, ',', ' ') }}</dd>
        </button>
        <button wire:click="ouvrirDetailModal('rupture')" class="bg-red-50 rounded-xl p-4 text-left hover:shadow-md transition-shadow">
            <dt class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-1">En rupture</dt>
            <dd class="text-2xl font-bold text-red-600">{{ $stats['medicamentsRupture'] }}</dd>
        </button>
    </div>

    {{-- Alertes secondaires & mouvements --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <button wire:click="ouvrirDetailModal('faible')" class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-left hover:shadow-md transition-shadow">
            <dt class="text-xs font-semibold text-yellow-700 uppercase tracking-wide mb-1">Stock faible</dt>
            <dd class="text-xl font-bold text-yellow-700">{{ $stats['medicamentsStockFaible'] }}</dd>
        </button>
        <button wire:click="ouvrirDetailModal('expires')" class="bg-red-50 border border-red-200 rounded-xl p-4 text-left hover:shadow-md transition-shadow">
            <dt class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-1">Lots expirés</dt>
            <dd class="text-xl font-bold text-red-600">{{ $stats['lotsExpires'] }}</dd>
        </button>
        <button wire:click="ouvrirDetailModal('expire_bientot')" class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-left hover:shadow-md transition-shadow">
            <dt class="text-xs font-semibold text-orange-700 uppercase tracking-wide mb-1">Expire bientôt</dt>
            <dd class="text-xl font-bold text-orange-600">{{ $stats['lotsExpireBientot'] }}</dd>
        </button>
        <button wire:click="ouvrirDetailModal('entrees')" class="bg-green-50 border border-green-200 rounded-xl p-4 text-left hover:shadow-md transition-shadow">
            <dt class="text-xs font-semibold text-green-700 uppercase tracking-wide mb-1"><i class="fas fa-arrow-down mr-1"></i>Entrées ce mois</dt>
            <dd class="text-xl font-bold text-green-700">{{ $stats['entreesCeMois'] }}</dd>
        </button>
        <button wire:click="ouvrirDetailModal('sorties')" class="bg-red-50 border border-red-200 rounded-xl p-4 text-left hover:shadow-md transition-shadow">
            <dt class="text-xs font-semibold text-red-700 uppercase tracking-wide mb-1"><i class="fas fa-arrow-up mr-1"></i>Sorties ce mois</dt>
            <dd class="text-xl font-bold text-red-600">{{ $stats['sortiesCeMois'] }}</dd>
        </button>
    </div>

    {{-- Bénéfice des ventes --}}
    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 border border-indigo-200 rounded-xl p-4 flex items-center justify-between gap-4">
        <div>
            <dt class="text-xs font-semibold text-indigo-700 uppercase tracking-wide mb-1">
                <i class="fas fa-chart-line mr-1"></i> Bénéfice des ventes encaissées
            </dt>
            <dd class="text-2xl font-bold text-indigo-800">
                {{ number_format($stats['beneficeVentes'] ?? 0, 0, ',', ' ') }}
                <span class="text-sm font-normal">MRU</span>
            </dd>
            <p class="text-xs text-indigo-500 mt-1">Prix de vente facturé − Prix d'achat (lots)</p>
        </div>
        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-coins text-indigo-600 text-xl"></i>
        </div>
    </div>

    <p class="text-xs text-gray-400 text-center"><i class="fas fa-mouse-pointer mr-1"></i>Cliquez sur une carte pour voir les détails</p>

    {{-- Modal de détails --}}
    @if($showDetailModal)
    <div class="modal-overlay" wire:click.self="fermerDetailModal">
        <div class="modal-box max-w-5xl w-full">
            <div class="modal-header">
                <h2>
                    @if($detailType === 'total') Détails — Total médicaments
                    @elseif($detailType === 'valeur') Détails — Valeur du stock
                    @elseif($detailType === 'quantite') Détails — Unités disponibles
                    @elseif($detailType === 'rupture') Détails — Médicaments en rupture
                    @elseif($detailType === 'faible') Détails — Stock faible
                    @elseif($detailType === 'expires') Détails — Lots expirés
                    @elseif($detailType === 'expire_bientot') Détails — Lots expirant bientôt
                    @elseif($detailType === 'entrees') Détails — Entrées ce mois
                    @elseif($detailType === 'sorties') Détails — Sorties ce mois
                    @endif
                </h2>
                <button wire:click="fermerDetailModal" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                @if(count($detailData) > 0)
                @php $filteredCount = count($this->detailDataFiltered); @endphp
                <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
                    <p class="text-sm text-gray-500">
                        @if($filteredCount > 0)
                            {{ (($this->detailPage - 1) * $this->detailPerPage) + 1 }}–{{ min($this->detailPage * $this->detailPerPage, $filteredCount) }} sur {{ $filteredCount }} résultat(s)
                            @if($detailSearch !== '') <span class="text-gray-400">(filtré sur {{ count($detailData) }})</span>@endif
                        @else
                            Aucun résultat pour « {{ $detailSearch }} »
                        @endif
                    </p>
                    <div class="relative flex-1 min-w-[220px] max-w-xs">
                        <input type="text" wire:model.live.debounce.300ms="detailSearch"
                               placeholder="Rechercher…"
                               class="w-full pl-9 pr-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        @if($detailSearch !== '')
                        <button type="button" wire:click="$set('detailSearch', '')"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm">
                            <i class="fas fa-times"></i>
                        </button>
                        @endif
                    </div>
                </div>
                @if($filteredCount > 0)
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                @if(in_array($detailType, ['total', 'valeur', 'quantite', 'rupture', 'faible']))
                                    <th>Médicament</th>
                                    <th>Quantité</th>
                                    @if(in_array($detailType, ['total', 'valeur', 'rupture', 'faible']))<th>Prix d'achat</th>@endif
                                    @if(in_array($detailType, ['total', 'valeur']))<th>Valeur</th>@endif
                                    @if(in_array($detailType, ['total', 'quantite', 'faible']))<th>Seuil min</th>@endif
                                    @if($detailType === 'faible')<th>Déficit</th>@endif
                                    @if($detailType === 'total')<th>Statut</th><th>Action</th>@endif
                                    @if(in_array($detailType, ['faible', 'rupture']))<th></th>@endif
                                @elseif(in_array($detailType, ['expires', 'expire_bientot']))
                                    <th>Médicament</th>
                                    <th>N° Lot</th>
                                    <th>Quantité</th>
                                    <th>Date expiration</th>
                                    <th>{{ $detailType === 'expires' ? 'Jours expirés' : 'Jours restants' }}</th>
                                @elseif(in_array($detailType, ['entrees', 'sorties']))
                                    <th>Date</th>
                                    <th>Médicament</th>
                                    <th>Quantité</th>
                                    <th>{{ $detailType === 'entrees' ? 'Prix d\'achat' : 'Prix de vente' }}</th>
                                    <th>Montant</th>
                                    <th>Utilisateur</th>
                                    @if($detailType === 'sorties')<th>Patient</th><th>Facture</th>@endif
                                    @if($detailType === 'entrees')<th>Référence</th>@endif
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->detailDataPaginated as $item)
                            <tr>
                                @if(in_array($detailType, ['total', 'valeur', 'quantite', 'rupture', 'faible']))
                                    <td class="font-medium">{{ $item['medicament'] }}</td>
                                    <td>{{ number_format($item['quantite'], 0) }}</td>
                                    @if(in_array($detailType, ['total', 'valeur', 'rupture', 'faible']))<td>{{ number_format($item['prix_achat'] ?? 0, 0) }} MRU</td>@endif
                                    @if(in_array($detailType, ['total', 'valeur']))<td class="font-semibold">{{ number_format($item['valeur'] ?? 0, 0) }} MRU</td>@endif
                                    @if(in_array($detailType, ['total', 'quantite', 'faible']))<td>{{ number_format($item['seuil_min'] ?? 0, 0) }}</td>@endif
                                    @if($detailType === 'faible')<td class="text-red-600 font-semibold">{{ number_format($item['difference'] ?? 0, 0) }}</td>@endif
                                    @if($detailType === 'total')
                                    <td>
                                        @if(($item['statut'] ?? '') === 'faible')
                                            <span class="badge badge-warning">Faible</span>
                                        @elseif(($item['statut'] ?? '') === 'rupture')
                                            <span class="badge badge-error">Rupture</span>
                                        @else
                                            <span class="badge badge-success">OK</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($item['medicament_id']))
                                        <button wire:click="openAjustementModal({{ $item['medicament_id'] }})"
                                                class="text-blue-600 hover:text-blue-800 text-xs font-medium"
                                                title="Ajuster la quantité en stock">
                                            <i class="fas fa-edit mr-0.5"></i> Modifier stock
                                        </button>
                                        @endif
                                    </td>
                                    @endif
                                    @if(in_array($detailType, ['faible', 'rupture']))
                                    <td>
                                        @if(!empty($item['medicament_id']))
                                        <button wire:click="openEntreeModalForMedicament({{ $item['medicament_id'] }})"
                                                class="btn-primary text-xs px-3 py-1">
                                            <i class="fas fa-plus-circle"></i> Alimenter
                                        </button>
                                        @endif
                                    </td>
                                    @endif
                                @elseif(in_array($detailType, ['expires', 'expire_bientot']))
                                    <td class="font-medium">{{ $item['medicament'] }}</td>
                                    <td>{{ $item['numero_lot'] ?? 'N/A' }}</td>
                                    <td>{{ number_format($item['quantite'], 0) }}</td>
                                    <td>{{ $item['date_expiration'] }}</td>
                                    @if($detailType === 'expires')
                                        <td class="text-red-600 font-semibold">{{ abs($item['jours_expires'] ?? 0) }} jours</td>
                                    @else
                                        <td class="text-orange-600 font-semibold">{{ $item['jours_restants'] ?? 0 }} jours</td>
                                    @endif
                                @elseif(in_array($detailType, ['entrees', 'sorties']))
                                    <td class="whitespace-nowrap">{{ $item['date'] }}</td>
                                    <td class="font-medium">{{ $item['medicament'] }}</td>
                                    <td>{{ number_format($item['quantite'], 0) }}</td>
                                    <td>{{ number_format($item['prix_unitaire'] ?? 0, 0) }} MRU</td>
                                    <td class="font-semibold">{{ number_format($item['montant'] ?? 0, 0) }} MRU</td>
                                    <td>{{ $item['utilisateur'] }}</td>
                                    @if($detailType === 'sorties')
                                        <td>{{ $item['patient'] ?? 'N/A' }}</td>
                                        <td>{{ $item['facture'] ?? 'N/A' }}</td>
                                    @endif
                                    @if($detailType === 'entrees')
                                        <td>{{ $item['reference'] ?? 'N/A' }}</td>
                                    @endif
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if($this->detailTotalPages > 1)
                <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                    <div class="flex items-center gap-1">
                        <button wire:click="previousDetailPage" @if($this->detailPage <= 1) disabled @endif
                                class="px-3 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-40">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        @php $startPage = max(1, $this->detailPage - 2); $endPage = min($this->detailTotalPages, $this->detailPage + 2); @endphp
                        @if($startPage > 1)
                        <button wire:click="goToDetailPage(1)" class="px-3 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-50">1</button>
                        @if($startPage > 2)<span class="px-1 text-gray-400">...</span>@endif
                        @endif
                        @for($i = $startPage; $i <= $endPage; $i++)
                        <button wire:click="goToDetailPage({{ $i }})" class="px-3 py-1.5 text-sm rounded {{ $i == $this->detailPage ? 'bg-primary text-white' : 'border border-gray-300 hover:bg-gray-50' }}">{{ $i }}</button>
                        @endfor
                        @if($endPage < $this->detailTotalPages)
                        @if($endPage < $this->detailTotalPages - 1)<span class="px-1 text-gray-400">...</span>@endif
                        <button wire:click="goToDetailPage({{ $this->detailTotalPages }})" class="px-3 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-50">{{ $this->detailTotalPages }}</button>
                        @endif
                        <button wire:click="nextDetailPage" @if($this->detailPage >= $this->detailTotalPages) disabled @endif
                                class="px-3 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-40">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <span class="text-sm text-gray-500">Page {{ $this->detailPage }} / {{ $this->detailTotalPages }}</span>
                </div>
                @endif

                @else
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-inbox text-3xl mb-2 block"></i>
                    <p>Aucun détail disponible</p>
                </div>
                @endif

                <div class="flex justify-end mt-4">
                    <button wire:click="fermerDetailModal" class="btn-secondary">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal ajustement de stock (inventaire) --}}
    @if($showAjustementModal)
    <div class="modal-overlay" style="z-index:70;" wire:click.self="closeAjustementModal">
        <div class="modal-box max-w-lg w-full">
            <div class="modal-header">
                <div>
                    <h2><i class="fas fa-edit mr-2"></i>Ajuster le stock</h2>
                    @if($ajustementLibelle)
                        <p class="text-sm text-gray-500 mt-0.5">{{ $ajustementLibelle }}</p>
                    @endif
                </div>
                <button wire:click="closeAjustementModal" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800 flex items-start gap-2">
                    <i class="fas fa-info-circle mt-0.5 flex-shrink-0"></i>
                    <span>Utilisez ce formulaire pour corriger la quantité en stock suite à un inventaire, une casse, une perte, etc.</span>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Quantité actuelle</label>
                        <input type="number" value="{{ $ajustementQuantiteActuelle }}" disabled
                               class="w-full px-3 py-2 border border-gray-200 bg-gray-100 rounded-lg text-sm text-gray-600">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nouvelle quantité <span class="text-red-500">*</span></label>
                        <input type="number" wire:model.live="ajustementNouvelleQuantite" min="0" step="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('ajustementNouvelleQuantite') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>

                @php
                    $ecartQte = (float)$ajustementNouvelleQuantite - (float)$ajustementQuantiteActuelle;
                    $ancienneValeur = (float)$ajustementValeurActuelle;
                    if ($ecartQte < 0) {
                        // Décrément FIFO : retirer les lots les plus anciens
                        $aRetirer = abs($ecartQte);
                        $valeurRetiree = 0;
                        foreach ($ajustementLots as $lot) {
                            if ($aRetirer <= 0) break;
                            $prelever = min($lot['quantite'], $aRetirer);
                            $valeurRetiree += $prelever * $lot['prixAchat'];
                            $aRetirer -= $prelever;
                        }
                        $nouvelleValeur = $ancienneValeur - $valeurRetiree;
                    } elseif ($ecartQte > 0) {
                        $nouvelleValeur = $ancienneValeur + $ecartQte * (float)$ajustementPrixNouveauStock;
                    } else {
                        $nouvelleValeur = $ancienneValeur;
                    }
                    $ecartValeur = $nouvelleValeur - $ancienneValeur;
                @endphp

                @if(count($ajustementLots) > 0)
                <div class="mb-4 border border-gray-200 rounded-lg overflow-hidden">
                    <div class="px-3 py-2 bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-600 uppercase">
                        Lots en stock ({{ count($ajustementLots) }})
                    </div>
                    <div class="max-h-36 overflow-y-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 text-gray-500 sticky top-0">
                                <tr>
                                    <th class="px-3 py-1.5 text-left font-medium">Lot</th>
                                    <th class="px-3 py-1.5 text-right font-medium">Qté</th>
                                    <th class="px-3 py-1.5 text-right font-medium">PA unit.</th>
                                    <th class="px-3 py-1.5 text-right font-medium">Valeur</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($ajustementLots as $lot)
                                <tr>
                                    <td class="px-3 py-1.5">{{ $lot['numeroLot'] ?? '—' }}</td>
                                    <td class="px-3 py-1.5 text-right">{{ number_format($lot['quantite'], 0) }}</td>
                                    <td class="px-3 py-1.5 text-right">{{ number_format($lot['prixAchat'], 2) }}</td>
                                    <td class="px-3 py-1.5 text-right font-medium">{{ number_format($lot['valeur'], 0) }} MRU</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($ecartQte > 0)
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Prix d'achat unitaire du nouveau stock <span class="text-red-500">*</span></label>
                    <input type="number" wire:model.live="ajustementPrixNouveauStock" min="0" step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('ajustementPrixNouveauStock') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    <p class="text-xs text-gray-500 mt-1">Un nouveau lot de {{ number_format($ecartQte, 0) }} unité(s) sera créé à ce prix.</p>
                </div>
                @endif

                <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-gray-600">Valeur actuelle :</span>
                        <span class="font-medium">{{ number_format($ancienneValeur, 0) }} MRU</span>
                    </div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-gray-600">Nouvelle valeur :</span>
                        <span class="font-semibold text-blue-700">{{ number_format($nouvelleValeur, 0) }} MRU</span>
                    </div>
                    @if($ecartValeur != 0)
                    <div class="flex items-center justify-between pt-1 border-t border-gray-200 mt-1">
                        <span class="text-gray-600">Écart :</span>
                        <span class="font-semibold {{ $ecartValeur > 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ $ecartValeur > 0 ? '+' : '' }}{{ number_format($ecartValeur, 0) }} MRU
                        </span>
                    </div>
                    @endif
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Motif <span class="text-red-500">*</span></label>
                    <textarea wire:model="ajustementMotif" rows="3"
                              placeholder="Ex: Inventaire physique, Casse, Perte, Vol, Correction d'erreur..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    @error('ajustementMotif') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button wire:click="closeAjustementModal" class="btn-secondary">Annuler</button>
                    <button wire:click="enregistrerAjustement" class="btn-primary">
                        <i class="fas fa-save mr-1"></i> Enregistrer l'ajustement
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal entrée de stock --}}
    @if($showEntreeModal)
    <div class="modal-overlay" style="z-index:70;">
        <div class="modal-box max-w-2xl w-full">
            <div class="modal-header">
                <div>
                    <h2><i class="fas fa-plus-circle mr-2"></i>Alimenter le stock</h2>
                    @if($entreeLibelleMedic)
                    <p>{{ $entreeLibelleMedic }}</p>
                    @endif
                </div>
                <button wire:click="closeEntreeModal" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="enregistrerEntree" class="space-y-4">

                    {{-- Sélection médicament --}}
                    @if(!$entreeMedicamentId)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Médicament *</label>
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="entreeSearchMedicament"
                                   placeholder="Rechercher un médicament..."
                                   class="form-input">
                            @if($entreeIsSearchingMedicament)
                            <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
                            </div>
                            @endif
                            @if($entreeShowMedicamentResults && count($entreeMedicamentsResults) > 0)
                            <div class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-50 max-h-48 overflow-y-auto">
                                @foreach($entreeMedicamentsResults as $med)
                                <div wire:click="selectEntreeMedicament({{ $med->IDMedic }})"
                                     class="px-4 py-2.5 hover:bg-primary-light cursor-pointer text-sm border-b border-gray-50 last:border-0">
                                    <span class="font-medium">{{ $med->LibelleMedic }}</span>
                                    <span class="text-gray-400 ml-2">{{ number_format($med->PrixRef ?? 0, 0) }} MRU</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="flex items-center justify-between px-4 py-2.5 bg-primary-light rounded-lg border border-primary/20">
                        <span class="text-sm font-semibold text-primary">{{ $entreeLibelleMedic }}</span>
                        <button type="button" wire:click="$set('entreeMedicamentId', null)" class="text-primary hover:text-primary-dark text-xs">
                            <i class="fas fa-times"></i> Changer
                        </button>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantité *</label>
                            <input type="number" wire:model="entreeQuantite" class="form-input" min="1" required>
                            @error('entreeQuantite') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix d'achat unitaire *</label>
                            <input type="number" step="0.01" wire:model="entreePrixAchat" class="form-input" min="0">
                            @error('entreePrixAchat') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Seuil minimum *</label>
                            <input type="number" wire:model="entreeQuantiteMin" class="form-input" min="0" required>
                            @error('entreeQuantiteMin') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Numéro de lot</label>
                            <input type="text" wire:model="entreeNumeroLot" class="form-input" placeholder="Optionnel">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date d'expiration</label>
                            <input type="date" wire:model="entreeDateExpiration" class="form-input">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fournisseur</label>
                            <input type="text" wire:model="entreeFournisseur" class="form-input" placeholder="Optionnel">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Référence facture</label>
                            <input type="text" wire:model="entreeReferenceFacture" class="form-input" placeholder="Optionnel">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <input type="text" wire:model="entreeNotes" class="form-input" placeholder="Optionnel">
                        </div>
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="button" wire:click="closeEntreeModal" class="btn-secondary">Annuler</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-plus-circle"></i> Enregistrer l'entrée
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>
