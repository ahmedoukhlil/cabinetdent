<div class="space-y-4">

    {{-- Flash messages --}}
    @if(session()->has('success'))
        <div class="flex items-center gap-2 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
            <i class="fas fa-check-circle text-green-500"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session()->has('error'))
        <div class="flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
            <i class="fas fa-exclamation-circle text-red-500"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Filtres --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Date du rendez-vous</label>
                <input type="date" wire:model.live="dateFilter" class="form-input">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Médecin</label>
                <select wire:model.live="medecinFilter" class="form-select">
                    <option value="">Tous les médecins</option>
                    @foreach($medecins as $medecin)
                        <option value="{{ $medecin->idMedecin }}">{{ $medecin->Nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Rechercher un patient</label>
                <input type="text" wire:model.live.debounce.300ms="searchPatient"
                       placeholder="Nom, prénom ou téléphone..."
                       class="form-input">
            </div>
        </div>
    </div>

    {{-- Liste --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                <i class="fas fa-bell text-primary"></i>
                Rendez-vous à rappeler
            </h3>
            <span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 rounded-full text-xs font-bold
                {{ $rendezVous->total() > 0 ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-500' }}">
                {{ $rendezVous->total() }}
            </span>
        </div>

        @if($rendezVous->count() > 0)

            {{-- Desktop --}}
            <div class="hidden lg:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left">N°</th>
                            <th class="px-4 py-3 text-left">Date / Heure</th>
                            <th class="px-4 py-3 text-left">Patient</th>
                            <th class="px-4 py-3 text-left">Médecin</th>
                            <th class="px-4 py-3 text-left">Acte prévu</th>
                            <th class="px-4 py-3 text-left">Téléphone</th>
                            <th class="px-4 py-3 text-left">Statut</th>
                            <th class="px-4 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($rendezVous as $rdv)
                        @php
                            $isRappele = $rdv->rdvConfirmer === 'Rappel envoyé';
                            $statusMap = [
                                'En Attente'    => ['bg-yellow-100 text-yellow-800', 'fa-clock'],
                                'Confirmé'      => ['bg-green-100 text-green-800',  'fa-check'],
                                'En cours'      => ['bg-blue-100 text-blue-800',    'fa-user-md'],
                                'Rappel envoyé' => ['bg-orange-100 text-orange-800','fa-bell'],
                            ];
                            [$statusClass, $statusIcon] = $statusMap[$rdv->rdvConfirmer] ?? ['bg-gray-100 text-gray-700', 'fa-circle'];
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors" data-rdv-id="{{ $rdv->IDRdv }}">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold text-white
                                    {{ $rdv->OrdreRDV ? 'bg-primary' : 'bg-gray-400' }}">
                                    {{ str_pad($rdv->OrdreRDV ?? $rdv->IDRdv, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($rdv->dtPrevuRDV)->format('d/m/Y') }}</div>
                                <div class="text-xs text-primary font-semibold">{{ \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-medium text-gray-900 text-sm">{{ $rdv->patient->Nom ?? '—' }}</div>
                                @if($rdv->patient->Prenom ?? null)
                                    <div class="text-xs text-gray-500">{{ $rdv->patient->Prenom }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                {{ $rdv->medecin->Nom ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                {{ $rdv->ActePrevu ?: 'Consultation' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($rdv->patient->Telephone1 ?? null)
                                    <div class="text-sm text-gray-900">{{ $rdv->patient->Telephone1 }}</div>
                                    @if($rdv->patient->Telephone2 ?? null)
                                        <div class="text-xs text-gray-400">{{ $rdv->patient->Telephone2 }}</div>
                                    @endif
                                @else
                                    <span class="text-xs text-red-500"><i class="fas fa-exclamation-triangle mr-1"></i>Aucun</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                    <i class="fas {{ $statusIcon }} text-xs"></i>
                                    {{ $rdv->rdvConfirmer }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                @if($rdv->patient->Telephone1 ?? null)
                                    <button
                                        onclick="sendWhatsAppReminder({{ $rdv->IDRdv }}, '{{ addslashes($rdv->patient->Nom) }}', '{{ $rdv->patient->Telephone1 }}', '{{ \Carbon\Carbon::parse($rdv->dtPrevuRDV)->format('d/m/Y') }}', '{{ \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') }}', '{{ addslashes($rdv->medecin->Nom ?? 'Non assigné') }}', '{{ addslashes($rdv->ActePrevu ?: 'Consultation') }}', {{ $isRappele ? 'true' : 'false' }}, {{ $rdv->patient->ID }}, {{ $rdv->fkidMedecin }})"
                                        id="reminder-btn-desktop-{{ $rdv->IDRdv }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-colors
                                            {{ $isRappele ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-600 hover:bg-green-700' }}">
                                        <i class="fab fa-whatsapp"></i>
                                        <span id="btn-text-desktop-{{ $rdv->IDRdv }}">{{ $isRappele ? 'Relancer' : 'Rappeler' }}</span>
                                        <span id="btn-loading-desktop-{{ $rdv->IDRdv }}" class="items-center gap-1 hidden">
                                            <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-white"></div>
                                            Envoi...
                                        </span>
                                    </button>
                                @else
                                    <span class="text-xs text-red-400"><i class="fas fa-phone-slash"></i></span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile - Cartes --}}
            <div class="block lg:hidden divide-y divide-gray-100">
                @foreach($rendezVous as $rdv)
                @php
                    $isRappele = $rdv->rdvConfirmer === 'Rappel envoyé';
                @endphp
                <div class="p-4" data-rdv-id="{{ $rdv->IDRdv }}">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold text-white
                                {{ $rdv->OrdreRDV ? 'bg-primary' : 'bg-gray-400' }}">
                                {{ str_pad($rdv->OrdreRDV ?? $rdv->IDRdv, 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <div>
                                <div class="font-semibold text-gray-900">{{ $rdv->patient->Nom ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $rdv->medecin->Nom ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($rdv->dtPrevuRDV)->format('d/m/Y') }}</div>
                            <div class="text-base font-bold text-primary">{{ \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') }}</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-phone text-xs mr-1"></i>
                            {{ $rdv->patient->Telephone1 ?? 'Aucun téléphone' }}
                        </div>
                        @if($rdv->patient->Telephone1 ?? null)
                            <button
                                onclick="sendWhatsAppReminder({{ $rdv->IDRdv }}, '{{ addslashes($rdv->patient->Nom) }}', '{{ $rdv->patient->Telephone1 }}', '{{ \Carbon\Carbon::parse($rdv->dtPrevuRDV)->format('d/m/Y') }}', '{{ \Carbon\Carbon::parse($rdv->HeureRdv)->format('H:i') }}', '{{ addslashes($rdv->medecin->Nom ?? 'Non assigné') }}', '{{ addslashes($rdv->ActePrevu ?: 'Consultation') }}', {{ $isRappele ? 'true' : 'false' }}, {{ $rdv->patient->ID }}, {{ $rdv->fkidMedecin }})"
                                id="reminder-btn-{{ $rdv->IDRdv }}"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white
                                    {{ $isRappele ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-600 hover:bg-green-700' }}">
                                <i class="fab fa-whatsapp"></i>
                                <span id="btn-text-{{ $rdv->IDRdv }}">{{ $isRappele ? 'Relancer' : 'Rappeler' }}</span>
                                <span id="btn-loading-{{ $rdv->IDRdv }}" class="items-center gap-1 hidden">
                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                                    Envoi...
                                </span>
                            </button>
                        @else
                            <span class="text-xs text-red-400 flex items-center gap-1">
                                <i class="fas fa-phone-slash"></i> Pas de téléphone
                            </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

        @else
            <div class="py-16 text-center text-gray-400">
                <i class="fas fa-bell-slash text-4xl mb-3 block"></i>
                <p class="text-sm font-medium">Aucun rendez-vous à rappeler</p>
                <p class="text-xs mt-1">pour le {{ $dateFilter ? \Carbon\Carbon::parse($dateFilter)->format('d/m/Y') : 'filtre sélectionné' }}</p>
            </div>
        @endif

        {{-- Pagination --}}
        @if($rendezVous->hasPages())
        <div class="px-5 py-3 border-t border-gray-100">
            {{ $rendezVous->links() }}
        </div>
        @endif
    </div>
</div>

<script>
async function createShortUrl(longUrl) {
    try {
        const response = await fetch(`https://tinyurl.com/api-create.php?url=${encodeURIComponent(longUrl)}`);
        return response.ok ? await response.text() : longUrl;
    } catch { return longUrl; }
}

window.sendWhatsAppReminder = async function(rdvId, patientName, phoneNumber, rdvDate, rdvTime, medecinName, actePrevu, isRelance, patientId, medecinId) {
    const btnMobile  = document.getElementById(`reminder-btn-${rdvId}`);
    const btnDesktop = document.getElementById(`reminder-btn-desktop-${rdvId}`);
    const button     = btnMobile || btnDesktop;

    if (button && button.disabled) return;
    if (button) { button.disabled = true; button.classList.add('opacity-60', 'cursor-not-allowed'); }

    [`btn-text-${rdvId}`, `btn-text-desktop-${rdvId}`].forEach(id => {
        const el = document.getElementById(id); if (el) el.style.display = 'none';
    });
    [`btn-loading-${rdvId}`, `btn-loading-desktop-${rdvId}`].forEach(id => {
        const el = document.getElementById(id); if (el) el.classList.remove('hidden'), el.style.display = 'flex';
    });

    let cleanPhone = phoneNumber.replace(/\D/g, '');
    if (!cleanPhone.startsWith('222')) cleanPhone = '222' + cleanPhone;

    const dateParts = rdvDate.split('/');
    const rdvDateFormatted = `${dateParts[2]}-${dateParts[1].padStart(2, '0')}-${dateParts[0].padStart(2, '0')}`;
    const tokenData  = `${patientId}|${rdvDateFormatted}|${medecinId}`;
    const base64Token = btoa(tokenData);
    const longUrl    = `${window.location.origin}/patient/rendez-vous/${base64Token}`;
    const shortUrl   = await createShortUrl(longUrl);

    const action   = isRelance ? 'Relance' : 'Rappel';
    const message  =
`مرحبا ${patientName}،

🔔 تذكير بموعدك الطبي:
📅 التاريخ: ${rdvDate}
🕐 الوقت: ${rdvTime}
👨‍⚕️ الطبيب: د. ${medecinName}
🏥 العملية: ${actePrevu}

🔗 رابط متابعة موعدك: ${shortUrl}

---
Bonjour ${patientName},

🔔 ${action} de votre rendez-vous :
📅 Date : ${rdvDate}
🕐 Heure : ${rdvTime}
👨‍⚕️ Médecin : Dr. ${medecinName}
🏥 Acte : ${actePrevu}

🔗 Suivi de votre rendez-vous : ${shortUrl}

Merci de confirmer votre présence.`;

    const whatsappUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`;

    window.openWhatsApp(whatsappUrl, function() {
        window.markReminderAsSent(rdvId, patientName, isRelance);
    });
}

window.markReminderAsSent = function(rdvId, patientName, isRelance) {
    Livewire.call('sendReminder', rdvId).then(() => {
        window.showSuccessMessage(isRelance ? `Relance envoyée pour ${patientName}` : `Rappel envoyé pour ${patientName}`);
        setTimeout(() => window.location.reload(), 1800);
    }).catch(() => {
        [`reminder-btn-${rdvId}`, `reminder-btn-desktop-${rdvId}`].forEach(id => {
            const btn = document.getElementById(id);
            if (btn) { btn.disabled = false; btn.classList.remove('opacity-60', 'cursor-not-allowed'); }
        });
        [`btn-text-${rdvId}`, `btn-text-desktop-${rdvId}`].forEach(id => {
            const el = document.getElementById(id); if (el) el.style.display = 'inline';
        });
        [`btn-loading-${rdvId}`, `btn-loading-desktop-${rdvId}`].forEach(id => {
            const el = document.getElementById(id); if (el) el.style.display = 'none';
        });
        alert('Erreur lors de la mise à jour. Veuillez réessayer.');
    });
}

window.showSuccessMessage = function(message) {
    const n = document.createElement('div');
    n.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-3 rounded-lg shadow-xl z-50 flex items-center gap-2 text-sm font-medium';
    const icon = document.createElement('i');
    icon.className = 'fas fa-check-circle';
    n.appendChild(icon);
    n.appendChild(document.createTextNode(' ' + message));
    document.body.appendChild(n);
    setTimeout(() => n.remove(), 3000);
}
</script>
