<div class="space-y-6">
    <div class="card-header rounded-xl">
        <div>
            <h2 class="text-xl font-bold">Règlement des Factures</h2>
            <p class="text-sm opacity-80 mt-0.5">Sélectionnez un patient pour gérer ses paiements</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-xl overflow-hidden">
        <div class="p-6">
    <!-- Messages de succès/erreur -->
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    <!-- Recherche du patient -->
    @if($selectedPatient)
        <div class="mb-6">
            <div class="p-2 bg-blue-50 rounded border border-blue-200">
                <span class="font-medium text-blue-800">
                    {{ is_array($selectedPatient) ? ($selectedPatient['Prenom'] ?? '') : $selectedPatient->Prenom }}
                </span>
                @if(is_array($selectedPatient) ? ($selectedPatient['Telephone1'] ?? null) : ($selectedPatient->Telephone1 ?? null))
                    <span class="text-sm text-blue-600 ml-2">Tél: {{ is_array($selectedPatient) ? $selectedPatient['Telephone1'] : $selectedPatient->Telephone1 }}</span>
                @endif
            </div>
        </div>
    @else
        <div class="mb-6">
            <livewire:patient-search />
        </div>
    @endif

    <!-- Liste des factures -->
    @if($factures)
    <div class="mb-6" wire:loading.class="opacity-50">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Factures du patient</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Facture</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Médecin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant facturé</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant PEC</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total règlements patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reste à payer patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        @if($canDeleteFacture)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($factures as $facture)
                    @php
                        $isAssure = $facture->ISTP > 0;
                        $resteAPayerPatient = ($isAssure ? ($facture->TotalfactPatient ?? 0) : ($facture->TotFacture ?? 0)) - ($facture->TotReglPatient ?? 0);
                        $resteAPayerPEC = $isAssure ? (($facture->TotalPEC ?? 0) - ($facture->ReglementPEC ?? 0)) : 0;
                    @endphp
                    <tr @if($factureSelectionnee && $factureSelectionnee['id'] == $facture->Idfacture) class="bg-yellow-50" @endif 
                        wire:key="facture-{{ $facture->Idfacture }}"
                        style="cursor:pointer" 
                        wire:click.stop="selectionnerFacture({{ $facture->Idfacture }})">
                        <td class="px-6 py-4 whitespace-nowrap">{{ $facture->Nfacture }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">Dr. {{ $facture->medecin->Nom ?? 'Non spécifié' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($facture->TotFacture ?? 0, 0, '', ' ') }} MRU</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $txpec = $facture->TXPEC ?? $selectedPatient['TauxPEC'] ?? 0;
                                $montantPEC = ($facture->ISTP > 0) ? ($facture->TotFacture * $txpec) : 0;
                            @endphp
                            @if($facture->ISTP > 0)
                                {{ number_format($montantPEC, 0, '', ' ') }} MRU
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($facture->TotReglPatient ?? 0, 0, '', ' ') }} MRU</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($resteAPayerPatient, 0, '', ' ') }} MRU</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($resteAPayerPatient > 0)
                                <span class="inline-block px-3 py-1 rounded-full bg-red-50 text-red-700 border border-red-200 text-xs font-semibold">Non réglée</span>
                            @elseif($resteAPayerPatient < 0)
                                <span class="inline-block px-3 py-1 rounded-full bg-yellow-50 text-yellow-800 border border-yellow-200 text-xs font-semibold">À rembourser</span>
                            @else
                                <span class="inline-block px-3 py-1 rounded-full bg-green-50 text-green-700 border border-green-200 text-xs font-semibold">Réglée</span>
                            @endif
                        </td>
                        @if($canDeleteFacture)
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button
                                onclick="window.confirmAction('Supprimer la facture N°{{ $facture->Nfacture }} ?', 'Cette action est irréversible. Les détails, opérations de caisse et mouvements de stock seront supprimés. Le stock sera restauré.', () => @this.supprimerFacture({{ $facture->Idfacture }}))"
                                class="btn-danger !py-1 !px-2 !text-xs"
                                title="Supprimer la facture">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                        @endif
                    </tr>
                    @if($factureSelectionnee && $factureSelectionnee['id'] == $facture->Idfacture)
                        <tr wire:key="details-{{ $facture->Idfacture }}">
                            <td colspan="{{ $canDeleteFacture ? '8' : '7' }}" class="bg-yellow-50 px-6 py-4">
                                <div class="mb-2 font-semibold text-gray-700">Actes de la facture :</div>
                                <div wire:loading.remove wire:target="selectionnerFacture">
                                    <table class="min-w-full mb-2">
                                        <thead>
                                            <tr class="text-xs text-gray-500 uppercase">
                                                <th class="px-2 py-1 text-left"></th>
                                                <th class="px-2 py-1 text-left">Acte</th>
                                                <th class="px-2 py-1 text-left">Quantité</th>
                                                <th class="px-2 py-1 text-left">Prix unitaire</th>
                                                <th class="px-2 py-1 text-left">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $details = $facture->details ?? collect();
                                                $totalPrix = $details->sum(function($detail) {
                                                    return $detail->PrixFacture * $detail->Quantite;
                                                });
                                            @endphp
                                            @foreach($details as $detail)
                                                <tr wire:key="detail-{{ $detail->idDetfacture }}">
                                                    <td class="px-2 py-1">
                                                        @if(Auth::user()->hasPermission('facture.ligne.delete'))
                                                            <button onclick="event.stopPropagation(); window.confirmAction('Supprimer cet acte ?', '', () => @this.removeActe({{ $detail->idDetfacture }}))"
                                                                class="text-red-500 hover:text-red-700 hover:bg-red-50 p-1 rounded transition-colors"
                                                                title="Supprimer cet acte">
                                                                <i class="fas fa-trash text-xs"></i>
                                                            </button>
                                                        @endif
                                                    </td>
                                                    <td class="px-2 py-1">{{ $detail->Actes }}</td>
                                                    <td class="px-2 py-1">{{ $detail->Quantite }}</td>
                                                    <td class="px-2 py-1">{{ number_format($detail->PrixFacture, 2) }}</td>
                                                    <td class="px-2 py-1">{{ number_format($detail->PrixFacture * $detail->Quantite, 2) }}</td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td colspan="4" class="text-right"><strong>Total&nbsp;:</strong></td>
                                                <td><strong>{{ number_format($totalPrix, 0, '', ' ') }} MRU</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="flex flex-wrap gap-2 justify-between mt-4">
                                        <div class="flex flex-wrap gap-2">
                                            <button wire:click.stop="ouvrirReglementFacture({{ $facture->Idfacture }})" class="btn-primary text-xs">
                                                <i class="fas fa-money-bill-wave"></i> Payer
                                            </button>
                                            <a href="{{ route('consultations.facture-patient', $facture->Idfacture) }}" target="_blank"
                                               class="btn-secondary text-xs !border-gray-400 !text-gray-700 hover:!bg-gray-700 hover:!text-white">
                                                <i class="fas fa-print"></i> Imprimer
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                                                 <div wire:loading wire:target="selectionnerFacture" class="text-center py-4">
                                     <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-300 border-t-primary"></div>
                                 </div>
                            </td>
                        </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="mt-4" wire:key="pagination">
            {{ $factures->links() }}
        </div>
    </div>
    @endif

    <!-- Modal de règlement de facture -->
    @if($showReglementModal && $factureSelectionnee)
    <div class="modal-overlay" style="z-index:60;">
        <div class="modal-box max-w-lg w-full">
            <div class="modal-header">
                <div>
                    <h2><i class="fas fa-money-bill-wave mr-2"></i>Paiement facture</h2>
                    <p>Facture N° {{ $factureSelectionnee['numero'] ?? '---' }}</p>
                </div>
                <button wire:click="closeReglementForm" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                @php
                    $txpec = $factureSelectionnee['TXPEC'] ?? 0;
                    $totalPEC = $factureSelectionnee['montant_pec'] ?? 0;
                    $totalPatient = $factureSelectionnee['part_patient'] ?? 0;
                    $reglPEC = $factureSelectionnee['montant_reglements_pec'] ?? 0;
                    $reglPatient = $factureSelectionnee['montant_reglements_patient'] ?? 0;
                    $restePatient = $totalPatient - $reglPatient;
                @endphp

                {{-- Récapitulatif --}}
                <div class="mb-4 p-4 rounded-lg border border-gray-200 bg-gray-50 grid grid-cols-2 gap-2 text-sm">
                    <div><span class="text-gray-500">Montant facturé</span><div class="font-semibold text-gray-900">{{ number_format($factureSelectionnee['montant_total'] ?? 0, 0, ',', ' ') }} MRU</div></div>
                    <div><span class="text-gray-500">Part assurance</span><div class="font-semibold text-gray-900">{{ number_format($totalPEC, 0, ',', ' ') }} MRU</div></div>
                    <div><span class="text-gray-500">Part patient</span><div class="font-semibold text-gray-900">{{ number_format($totalPatient, 0, ',', ' ') }} MRU</div></div>
                    <div>
                        <span class="text-gray-500">Reste à payer</span>
                        <div class="font-bold {{ $restePatient > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format($restePatient, 0, ',', ' ') }} MRU
                            @if($restePatient < 0) <span class="text-xs font-normal">(Acompte)</span> @endif
                        </div>
                    </div>
                </div>

                <form wire:submit.prevent="enregistrerReglement" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Montant du paiement</label>
                        <input type="number" step="0.01" wire:model="montantReglement" class="form-input"
                               placeholder="Montant à régler">
                        <p class="text-xs text-gray-400 mt-1">
                            @if($factureSelectionnee['est_reglee'] ?? false)
                                Facture déjà réglée — vous pouvez enregistrer un remboursement (montant négatif).
                            @else
                                Restant : {{ number_format($factureSelectionnee['reste_a_payer'] ?? 0, 2) }} MRU
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mode de paiement</label>
                        <select wire:model="modePaiement" class="form-select" required>
                            <option value="">Sélectionnez un mode de paiement</option>
                            @foreach($modesPaiement as $mode)
                                <option value="{{ $mode->idtypepaie }}">{{ $mode->LibPaie }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer px-0 pb-0">
                        <button type="button" wire:click="closeReglementForm" class="btn-secondary">Annuler</button>
                        <button type="button" wire:click="enregistrerReglement" class="btn-primary">
                            <i class="fas fa-check"></i> Payer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal ajout d'acte -->
    @if($showAddActeForm && $factureIdForActe)
    <div class="modal-overlay" style="z-index:60;">
        <div class="modal-box max-w-2xl w-full">
            <div class="modal-header">
                <div>
                    <h2><i class="fas fa-plus mr-2"></i>Ajouter un acte</h2>
                    <p>Facture N° {{ $factures->firstWhere('id', $factureIdForActe)['numero'] ?? '' }}</p>
                </div>
                <button wire:click="closeAddActeForm" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="saveActeToFacture" wire:key="form-add-acte-{{ $factureIdForActe }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Acte</label>
                            <livewire:acte-search :fkidassureur="$selectedPatient['Assureur'] ?? null" :key="'acte-search-'.$factureIdForActe" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix de référence</label>
                            <input type="number" wire:model="prixReference" class="form-input bg-gray-50" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix facturé</label>
                            <input type="number" wire:model.live="prixFacture" class="form-input">
                        </div>
                        @php
                            $isAssure = intval($selectedPatient['Assureur'] ?? 0) > 0;
                            $txpec = floatval($selectedPatient['TauxPEC'] ?? 0);
                            $prix = $prixFacture ?? 0;
                            $partPEC = ($isAssure && $txpec > 0) ? $prix * $txpec : 0;
                            $partPatient = ($isAssure && $txpec > 0) ? $prix * (1 - $txpec) : $prix;
                        @endphp
                        @if($isAssure && $txpec > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Part PEC (assureur)</label>
                            <input type="text" value="{{ number_format($partPEC, 0, '', ' ') }} MRU" class="form-input bg-gray-50" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Part patient</label>
                            <input type="text" value="{{ number_format($partPatient, 0, '', ' ') }} MRU" class="form-input bg-gray-50" readonly>
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantité</label>
                            <input type="number" wire:model="quantite" class="form-input" min="1">
                        </div>
                    </div>
                    <div class="modal-footer px-0 pb-0 mt-4">
                        <button type="button" wire:click="closeAddActeForm" class="btn-secondary">Annuler</button>
                        <button type="submit" class="btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal ajout médicament / analyse / radio -->
    @if($showAddMedicamentForm && $factureIdForActe)
    <div class="modal-overlay" style="z-index:60;">
        <div class="modal-box max-w-2xl w-full">
            <div class="modal-header">
                <div>
                    <h2>
                        <i class="fas {{ $selectedMedicamentType === '1' ? 'fa-pills' : 'fa-microscope' }} mr-2"></i>
                        @if($selectedMedicamentType === '1') Ajouter un médicament
                        @else Ajouter une analyse / radio
                        @endif
                    </h2>
                    <p>Facture N° {{ $factures->firstWhere('id', $factureIdForActe)['numero'] ?? '' }}</p>
                </div>
                <button wire:click="closeAddMedicamentForm" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="saveMedicamentToFacture" wire:key="form-add-medicament-{{ $factureIdForActe }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            @if($selectedMedicamentType === '1')
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <div class="form-input bg-gray-50 flex items-center gap-2 text-gray-700">
                                    <i class="fas fa-pills text-primary"></i> Médicament
                                </div>
                            @else
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select wire:model.live="selectedMedicamentType" class="form-select">
                                    <option value="2">Analyse</option>
                                    <option value="3">Radio</option>
                                </select>
                                @error('selectedMedicamentType') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            @endif
                        </div>
                        @if($selectedMedicamentType)
                        @php $typeLabel = match($selectedMedicamentType) { '1' => 'Médicament', '2' => 'Analyse', '3' => 'Radio', default => 'Item' }; @endphp
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $typeLabel }}</label>
                            <livewire:medicament-search :fkidtype="$selectedMedicamentType" :key="'medicament-search-'.$factureIdForActe.'-'.$selectedMedicamentType" />
                            @error('selectedMedicamentId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        @if($selectedMedicamentId)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix de référence</label>
                            <input type="number" wire:model="prixReferenceMedicament" step="0.01" class="form-input bg-gray-50" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix facturé</label>
                            <input type="number" wire:model.live="prixFactureMedicament" step="0.01" class="form-input">
                        </div>
                        @if($selectedMedicamentType == '1')
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stock disponible</label>
                            <div class="p-3 rounded-md border {{ ($stockDisponibleMedicament ?? 0) > 0 ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50' }}">
                                <span class="text-sm font-semibold {{ ($stockDisponibleMedicament ?? 0) > 0 ? 'text-green-700' : 'text-red-700' }}">
                                    <i class="fas {{ ($stockDisponibleMedicament ?? 0) > 0 ? 'fa-check-circle' : 'fa-exclamation-triangle' }} mr-2"></i>
                                    {{ number_format($stockDisponibleMedicament ?? 0, 0) }} unité(s) disponible(s)
                                    @if(($stockDisponibleMedicament ?? 0) == 0)
                                        <span class="text-xs block mt-1 font-normal">Stock épuisé — impossible d'ajouter</span>
                                    @elseif(($stockDisponibleMedicament ?? 0) < 10)
                                        <span class="text-xs block mt-1 font-normal text-orange-600">Stock faible</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        @endif
                        @php
                            $isAssure = intval($selectedPatient['Assureur'] ?? 0) > 0;
                            $txpec = floatval($selectedPatient['TauxPEC'] ?? 0);
                            $prix = $prixFactureMedicament ?? 0;
                            $partPEC = ($isAssure && $txpec > 0) ? $prix * $txpec : 0;
                            $partPatient = ($isAssure && $txpec > 0) ? $prix * (1 - $txpec) : $prix;
                        @endphp
                        @if($isAssure && $txpec > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Part PEC (assureur)</label>
                            <input type="text" value="{{ number_format($partPEC, 0, '', ' ') }} MRU" class="form-input bg-gray-50" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Part patient</label>
                            <input type="text" value="{{ number_format($partPatient, 0, '', ' ') }} MRU" class="form-input bg-gray-50" readonly>
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantité</label>
                            <input type="number" wire:model="quantiteMedicament" class="form-input" min="1">
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer px-0 pb-0 mt-4">
                        <button type="button" wire:click="closeAddMedicamentForm" class="btn-secondary">Annuler</button>
                        <button type="submit" class="btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de sélection du médecin -->
    @if($showMedecinModal)
    <div class="modal-overlay" style="z-index:60">
        <div class="modal-box sm:max-w-lg">
            <div class="modal-header">
                <h2><i class="fas fa-user-md mr-2"></i>Sélectionner un médecin</h2>
                <button type="button" wire:click="$set('showMedecinModal', false)" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <input type="text" wire:model.live.debounce.300ms="searchMedecin"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3 focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                    placeholder="Rechercher un médecin...">
                <div class="grid grid-cols-1 gap-1 max-h-72 overflow-y-auto">
                    @foreach($medecins as $medecin)
                    <button wire:click="selectMedecin({{ $medecin->idMedecin }})"
                        class="text-left px-4 py-2.5 hover:bg-primary-light rounded-lg transition-colors">
                        <div class="font-medium text-sm text-gray-800">Dr. {{ $medecin->Nom }}</div>
                        @if($medecin->Contact)
                        <div class="text-xs text-gray-500">{{ $medecin->Contact }}</div>
                        @endif
                    </button>
                    @endforeach
                    @if(count($medecins) === 0)
                        <div class="text-center py-6 text-sm text-gray-400">Aucun médecin trouvé</div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" wire:click="$set('showMedecinModal', false)" class="btn-secondary text-sm">Annuler</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Dossier Médical --}}
    @if($showDossierMedicalModal && $patientDossier)
        <div class="overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full bg-black bg-opacity-50">
            <div class="relative p-4 w-full max-w-6xl max-h-full">
                <!-- Modal content -->
                <div class="relative bg-white rounded-lg shadow-sm">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                        <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-folder-medical text-primary"></i>
                            <span>Dossier Médical</span>
                            @if($patientDossier)
                            <span class="text-sm font-normal text-gray-600 ml-2">
                                - {{ is_array($patientDossier) ? ($patientDossier['NomPatient'] ?? $patientDossier['Nom'] ?? 'Patient') : ($patientDossier->NomPatient ?? $patientDossier->Nom ?? 'Patient') }}
                            </span>
                            @endif
                        </h3>
                        <button type="button" 
                                wire:click="closeDossierMedicalModal"
                                class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Fermer</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4 max-h-[calc(100vh-200px)] overflow-y-auto">
                        <livewire:dossier-medical-manager wire:key="dossier-medical-manager-modal-{{ is_array($patientDossier) ? ($patientDossier['ID'] ?? '') : ($patientDossier->ID ?? '') }}-{{ $factureIdForDossier }}" :patient="$patientDossier" :facture="$factureDossier" :medecin="$factureDossier->medecin ?? null" />
                    </div>
                </div>
            </div>
        </div>
    @endif
        </div>
    </div>
</div>

{{-- L'écouteur open-receipt est géré globalement dans le layout principal pour éviter les doublons --}} 