{{--
    Bande flottante d'échéance d'abonnement, pour le propriétaire uniquement :
    fin d'essai ou fin de période payée proche (config abonnement.alerte_jours), ou suspension à venir (impayé).
    Réductible en pastille ; l'état est mémorisé dans le navigateur pour la journée et l'échéance en cours.
--}}
@php
    $abo = $user->estProprietaire() ? $user->cabinet->abonnement : null;
    $echeance = $abo?->echeanceProche();
@endphp
@if ($echeance)
    @php
        ['type' => $type, 'date' => $date, 'jours' => $jours] = $echeance;
        $quand = match (true) {
            $jours === 0 => "aujourd'hui",
            $jours === 1 => 'demain',
            default => "dans {$jours} jours",
        };
        $urgent = $type === 'suspension' || $jours <= 3;
        $message = match ($type) {
            'essai' => "Votre essai gratuit se termine {$quand}, le <strong>{$date->format('d/m/Y')}</strong>. Contactez Sysdental pour activer votre abonnement « ".e($abo->plan->nom).' ».',
            'periode' => "Votre abonnement « ".e($abo->plan->nom)." » arrive à échéance {$quand}, le <strong>{$date->format('d/m/Y')}</strong>. Contactez Sysdental pour le renouveler.",
            'suspension' => "Abonnement impayé depuis le {$abo->impaye_depuis->format('d/m/Y')}. Sans règlement, l'accès du cabinet sera suspendu le <strong>{$date->format('d/m/Y')}</strong>. Contactez Sysdental pour régulariser.",
        };
        $cle = 'sysdental-abonnement-'.$type.'-'.$date->toDateString().'-'.today()->toDateString();
    @endphp
    <div x-data="{ reduit: localStorage.getItem(@js($cle)) === '1',
                   basculer() { this.reduit = !this.reduit; this.reduit ? localStorage.setItem(@js($cle), '1') : localStorage.removeItem(@js($cle)) } }">
        {{-- Réserve la place sous le contenu pour que la bande ne masque pas le bas de page. --}}
        <div x-show="!reduit" class="h-16" aria-hidden="true"></div>

        <div class="pointer-events-none fixed inset-x-0 bottom-20 z-30 flex justify-center px-4 md:bottom-4 lg:left-64">
            <div x-show="!reduit" x-transition
                 @class(['pointer-events-auto flex w-full max-w-3xl items-center gap-3 rounded-xl px-4 py-3 text-sm shadow-lg ring-1',
                         'bg-amber-50 text-amber-900 ring-amber-300' => $urgent,
                         'bg-sky-50 text-sky-900 ring-sky-200' => !$urgent])
                 role="{{ $urgent ? 'alert' : 'status' }}">
                <span @class(['grid h-10 w-10 shrink-0 place-items-center rounded-full text-center font-semibold leading-none',
                              'bg-amber-200 text-amber-900' => $urgent, 'bg-sky-200 text-sky-900' => !$urgent])
                      title="Jours restants">
                    @if ($jours === 0)<i class="ti ti-alert-triangle text-lg" aria-hidden="true"></i>@else J-{{ $jours }}@endif
                </span>
                <p class="min-w-0 flex-1">{!! $message !!}</p>
                <button type="button" x-on:click="basculer()" class="shrink-0 rounded-lg p-1.5 opacity-70 hover:bg-black/5 hover:opacity-100" aria-label="Réduire l'alerte d'abonnement" title="Réduire">
                    <i class="ti ti-chevron-down text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <button type="button" x-show="reduit" x-cloak x-on:click="basculer()"
                    @class(['pointer-events-auto ml-auto flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold shadow-lg ring-1',
                            'bg-amber-100 text-amber-900 ring-amber-300' => $urgent,
                            'bg-sky-100 text-sky-900 ring-sky-200' => !$urgent])
                    aria-label="Afficher l'alerte d'abonnement">
                <i class="ti ti-clock-exclamation text-sm" aria-hidden="true"></i>
                Abonnement · {{ $jours === 0 ? "aujourd'hui" : 'J-'.$jours }}
            </button>
        </div>
    </div>
@endif
