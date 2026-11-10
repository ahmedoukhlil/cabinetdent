{{-- Avertit le propriétaire d'un impayé ou d'une fin d'essai proche. --}}
@php
    $abo = $user->estProprietaire() ? $user->cabinet->abonnement : null;
    $finEssai = $abo?->statut === 'essai' ? $abo->essai_fin : null;
@endphp
@if ($abo?->statut === 'impaye')
    <div class="bg-amber-100 text-amber-900" role="alert">
        <p class="mx-auto max-w-7xl px-4 py-2 text-sm"><i class="ti ti-alert-triangle" aria-hidden="true"></i>
            Abonnement impayé depuis le {{ $abo->impaye_depuis->format('d/m/Y') }}. Sans règlement, l'accès du cabinet sera suspendu le <strong>{{ $abo->finGrace()->addDay()->format('d/m/Y') }}</strong>. Contactez Sysdental pour régulariser.</p>
    </div>
@elseif ($finEssai && $finEssai->lte(today()->addDays(7)))
    <div class="bg-sky-100 text-sky-900" role="status">
        <p class="mx-auto max-w-7xl px-4 py-2 text-sm"><i class="ti ti-info-circle" aria-hidden="true"></i>
            Votre essai gratuit se termine le <strong>{{ $finEssai->format('d/m/Y') }}</strong>. Contactez Sysdental pour activer votre abonnement « {{ $abo->plan->nom }} ».</p>
    </div>
@endif
