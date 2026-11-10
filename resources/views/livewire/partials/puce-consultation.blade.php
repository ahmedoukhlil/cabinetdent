{{-- État de la consultation d'un rendez-vous : facturée / payée, ou bouton pour la facturer. --}}
@if ($rdv->consultationFacturee())
    @php $reste = $rdv->facture->restePatient(); @endphp
    <a href="{{ route('factures', ['facture' => $rdv->facture_id]) }}" wire:navigate
       @class(['badge whitespace-nowrap', 'bg-emerald-100 text-emerald-800' => $reste <= 0, 'bg-amber-100 text-amber-800' => $reste > 0])
       title="Facture {{ $rdv->facture->numero }}">
        <i class="ti {{ $reste <= 0 ? 'ti-circle-check' : 'ti-cash' }} mr-0.5" aria-hidden="true"></i>{{ $reste <= 0 ? 'Payée' : 'À encaisser' }}
    </a>
@elseif (($consultationsPossibles ?? \App\Models\Acte::consultations()->exists()) && auth()->user()->aPermission('facture.edit') && $rdv->statut !== \App\Enums\StatutRdv::Annule)
    <button type="button" x-on:click="$dispatch('consultation-ouvrir', { rdv: {{ $rdv->id }} })" class="badge whitespace-nowrap bg-sky-50 text-sky-800 ring-1 ring-sky-200 hover:bg-sky-100" title="Facturer la consultation">
        <i class="ti ti-receipt mr-0.5" aria-hidden="true"></i>Consultation
    </button>
@endif
