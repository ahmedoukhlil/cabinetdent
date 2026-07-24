@if($show)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div id="historique-paiement-modal" wire:key="historique-paiement-modal-{{ $patient['ID'] ?? '0' }}" class="bg-white rounded-lg shadow-lg w-full max-w-3xl p-4 relative print-modal max-h-[85vh] overflow-y-auto">
        <!-- Bouton X pour fermer -->
        <button type="button" wire:click="fermerModal" class="absolute top-2 right-2 text-gray-500 hover:text-primary text-2xl font-bold focus:outline-none print:hidden" aria-label="Fermer">&times;</button>
        @if (session()->has('message'))
            <div class="mb-4 p-2 bg-primary-light text-primary rounded text-center">
                {{ session('message') }}
            </div>
        @endif
        @include('consultations.entete-facture')

        <!-- Infos patient et bouton imprimer -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
            <div class="mb-2 md:mb-0">
                <div class="font-semibold text-lg">Patient : <span class="font-normal">{{ $patient['NomPatient'] ?? $patient['Nom'] ?? '' }} {{ $patient['Prenom'] ?? '' }}</span></div>
                <div class="text-sm text-gray-600">Téléphone : {{ $patient['Telephone1'] ?? '-' }}</div>
                @if(!empty($patient['IdentifiantPatient']))
                    <div class="text-sm text-gray-600">Identifiant : {{ $patient['IdentifiantPatient'] }}</div>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <div class="text-sm text-gray-600 mr-4">Date d'édition : {{ now()->format('d/m/Y H:i') }}</div>
                <!-- Lien vers la vue d'impression dédiée -->
                <a href="{{ route('reglement-facture.receipt', ['operation' => $operation->id]) }}" target="_blank" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark print:hidden">Imprimer</a>
            </div>
        </div>

        <h2 class="text-xl font-bold mb-4">Historique des paiements</h2>
        <div class="overflow-x-auto">
            <table class="w-full mb-4 border bg-white">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border px-2 py-1">Date</th>
                        <th class="border px-2 py-1">Désignation</th>
                        <th class="border px-2 py-1">Montant</th>
                        <th class="border px-2 py-1">Type</th>
                        <th class="border px-2 py-1">Médecin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentHistory as $payment)
                        <tr>
                            <td class="border px-2 py-1 whitespace-nowrap">{{ \Carbon\Carbon::parse($payment->dateoper)->format('d/m/Y H:i') }}</td>
                            <td class="border px-2 py-1">{{ $payment->designation }}</td>
                            <td class="border px-2 py-1 whitespace-nowrap">{{ number_format(abs($payment->MontantOperation), 0, ',', ' ') }} MRU</td>
                            <td class="border px-2 py-1">{{ $payment->entreEspece ? 'Entrée' : 'Sortie' }}</td>
                            <td class="border px-2 py-1">{{ $payment->medecin }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @include('consultations.pied-facture')

        <div class="flex justify-end gap-2 mt-6 print:hidden">
            <button type="button" wire:click="fermerModal" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Fermer</button>
        </div>
    </div>
</div>
@endif

<style>
@media print {
    body * { visibility: hidden !important; }
    #historique-paiement-modal, #historique-paiement-modal * { visibility: visible !important; }
    #historique-paiement-modal { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; }
    .print\:hidden { display: none !important; }
}
</style> 