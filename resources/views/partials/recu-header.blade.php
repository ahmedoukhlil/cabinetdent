@php
    use App\Models\Infocabinet;
    $cab = null;
    if (isset($cabinetId)) {
        $cab = Infocabinet::find($cabinetId);
    } elseif (isset($cabinet)) {
        if (is_object($cabinet) && $cabinet instanceof Infocabinet) {
            $cab = $cabinet;
        } elseif (is_numeric($cabinet)) {
            $cab = Infocabinet::find($cabinet);
        }
    }
    if (!$cab) {
        $cab = Infocabinet::first();
    }

    // Logo base64
    $logoBase64 = null;
    if ($cab && $cab->logo && file_exists(public_path($cab->logo))) {
        try {
            $ext = strtolower(pathinfo($cab->logo, PATHINFO_EXTENSION));
            $mime = in_array($ext, ['jpg','jpeg']) ? 'image/jpeg' : ($ext === 'svg' ? 'image/svg+xml' : 'image/png');
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents(public_path($cab->logo)));
        } catch (\Exception $e) {}
    }
@endphp
<div class="header" style="margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 12px;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            {{-- Français (gauche) --}}
            <td style="width: 35%; vertical-align: top; text-align: left; font-size: 11px; line-height: 1.6; color: #374151;">
                @if($cab && $cab->NomCabFr)
                <div style="font-weight: 700; font-size: 13px;">{{ $cab->NomCabFr }}</div>
                @endif
                @if($cab && $cab->DrFr)
                <div>{{ $cab->DrFr }}</div>
                @endif
                @if($cab && $cab->Specialite1Fr)
                <div style="color: #6b7280;">{{ $cab->Specialite1Fr }}</div>
                @endif
                @if($cab && $cab->Specialite2fr)
                <div style="color: #6b7280;">{{ $cab->Specialite2fr }}</div>
                @endif
                @if($cab && $cab->Specialite3Fr)
                <div style="color: #6b7280;">{{ $cab->Specialite3Fr }}</div>
                @endif
                @if($cab && $cab->AdresseFr1)
                <div>{{ $cab->AdresseFr1 }}</div>
                @endif
                @if($cab && $cab->AdresseFr2)
                <div>{{ $cab->AdresseFr2 }}</div>
                @endif
                @if($cab && $cab->ContactFR)
                <div>{{ $cab->ContactFR }}</div>
                @endif
                @if($cab && $cab->AdresseMail)
                <div>{{ $cab->AdresseMail }}</div>
                @endif
            </td>
            {{-- Logo (centre) --}}
            <td style="width: 30%; text-align: center; vertical-align: middle;">
                @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 90px; max-width: 160px; object-fit: contain;">
                @endif
            </td>
            {{-- Arabe (droite) --}}
            <td style="width: 35%; vertical-align: top; text-align: right; font-size: 11px; line-height: 1.6; color: #374151;" dir="rtl">
                @if($cab && $cab->NomCabAr)
                <div style="font-weight: 700; font-size: 13px;">{{ $cab->NomCabAr }}</div>
                @endif
                @if($cab && $cab->DrAr)
                <div>{{ $cab->DrAr }}</div>
                @endif
                @if($cab && $cab->Specialite1Ar)
                <div style="color: #6b7280;">{{ $cab->Specialite1Ar }}</div>
                @endif
                @if($cab && $cab->Specialite2Ar)
                <div style="color: #6b7280;">{{ $cab->Specialite2Ar }}</div>
                @endif
                @if($cab && $cab->Specialite3Ar)
                <div style="color: #6b7280;">{{ $cab->Specialite3Ar }}</div>
                @endif
                @if($cab && $cab->AdresseL1AR)
                <div>{{ $cab->AdresseL1AR }}</div>
                @endif
                @if($cab && $cab->AdresseL2AR)
                <div>{{ $cab->AdresseL2AR }}</div>
                @endif
                @if($cab && $cab->ContactAR)
                <div dir="ltr">{{ $cab->ContactAR }}</div>
                @endif
            </td>
        </tr>
    </table>
    @if($cab && $cab->TelephonePublic)
    <div style="text-align: center; font-size: 10px; color: #6b7280; margin-top: 4px;">
        Tél: {{ $cab->TelephonePublic }}
    </div>
    @endif
</div>
