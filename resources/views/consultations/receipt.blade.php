<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REÇU DE CONSULTATION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/print.css">
    <style>
        /* Spécifique reçu consultation */
        .bloc-patient-table { width: 100%; border-collapse: collapse; table-layout: fixed; word-wrap: break-word; }
        .bloc-patient-table td { padding: 2px 5px; font-size: 11px; vertical-align: middle; }
        .bloc-patient-table .label { font-weight: 600; width: 85px; white-space: nowrap; }
        .bloc-patient-table .ref-cell { text-align: right; }
        .bloc-patient-table .ref-label { font-weight: 600; margin-right: 3px; }
        .patient-divider { border: none; border-top: 1px solid #ccc; margin: 7px 0 10px; }

        /* N° RDV */
        .ordre-rdv {
            display: block; width: fit-content; margin: 0 auto 12px;
            border: 1.5px solid #000; padding: 5px 16px;
            text-align: center; font-weight: 700; font-size: 12px;
            letter-spacing: 1px; text-transform: uppercase;
            page-break-inside: avoid;
        }
        .ordre-rdv > div:first-child { font-size: 0.7em; margin-bottom: 1px; }

        /* Tableaux détails */
        .details-table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        .details-table th { font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 5px 7px; border-bottom: 1.5px solid #000; text-align: center; }
        .details-table th:first-child { text-align: left; }
        .details-table td { font-size: 11px; padding: 4px 7px; border-bottom: 1px solid #ddd; text-align: center; }
        .details-table td:first-child { text-align: left; }
        .details-table tbody tr { page-break-inside: avoid; }
        .details-table thead { display: table-header-group; }

        /* Sections multiples */
        .section-header { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #000; padding-bottom: 3px; margin: 12px 0 6px; page-break-after: avoid; }

        /* QR */
        .qr-code-link { text-decoration: none; color: inherit; display: inline-block; }
        .qr-code-container { display: inline-block; padding: 2px; }
        .qr-code-accessibility { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }

        .print-controls .btn-wa:hover { background: #128C7E; }

        @media print {
            .whatsapp-btn { display: none !important; }
            .details-table tbody tr:nth-child(even) td { background: none !important; }
            * { color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<div class="a4" id="documentContainer">
    <div class="print-controls">
        <select id="pageFormat" onchange="updatePageFormat()">
            <option value="A4">Format A4</option>
            <option value="A5">Format A5</option>
        </select>
        <button onclick="printDocument()" class="btn-primary">
            <i class="fas fa-print"></i> Imprimer
        </button>
        
        <!-- BOUTON WHATSAPP -->
        @if($facture->patient->Telephone1 && $facture->patient->Telephone1 !== 'N/A')
            @php
                $telephoneNettoye = \App\Helpers\QrCodeHelper::formatPhoneForWhatsApp($facture->patient->Telephone1);
            @endphp
            @if($telephoneNettoye)
                <button onclick="envoyerConfirmationWhatsApp()" class="btn-wa">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </button>
            @endif
        @endif
    </div>
    <div class="recu-header">@include('partials.recu-header')</div>
    <hr class="header-divider">
    <div class="content-before-table">
        <div class="facture-title">REÇU DE CONSULTATION</div>
        
        @if($facture->rendezVous && isset($facture->rendezVous->OrdreRDV) && $facture->rendezVous->OrdreRDV > 0)
            <div class="ordre-rdv">
                <div style="font-size: 0.5em; letter-spacing: 0.5px; margin-bottom: 2px; opacity: 0.9;">NUMÉRO DE RENDEZ-VOUS</div>
                <div style="font-size: 1.1em; line-height: 1.2;">N° {{ str_pad($facture->rendezVous->OrdreRDV, 3, '0', STR_PAD_LEFT) }}</div>
            </div>
        @endif
        
        <div class="bloc-patient">
        <table class="bloc-patient-table">
            <tr>
                <td class="label">N° Fiche :</td>
                <td class="value">{{ $facture->patient->IdentifiantPatient ?? 'N/A' }}</td>
                <td class="ref-cell"><span class="ref-label">Réf :</span> <span class="ref-value">{{ $facture->Nfacture ?? 'N/A' }}</span></td>
            </tr>
            <tr>
                <td class="label">Patient :</td>
                <td class="value" style="font-weight:600;">{{ $facture->patient->NomContact ?? 'N/A' }}</td>
                <td class="ref-cell"><span class="ref-label">Date :</span> <span class="ref-value">{{ $facture->DtFacture ? \Carbon\Carbon::parse($facture->DtFacture)->format('d/m/Y') : 'N/A' }}</span></td>
            </tr>
            <tr>
                <td class="label">Téléphone :</td>
                <td class="value">{{ $facture->patient->Telephone1 ?? 'N/A' }}</td>
                <td></td>
            </tr>
            <tr>
                <td class="label">Praticien :</td>
                <td class="value">Dr. {{ trim(($facture->medecin->Nom ?? '') . ' ' . ($facture->medecin->Prenom ?? '')) ?: 'N/A' }}</td>
                <td></td>
            </tr>
        </table>
        <hr class="patient-divider">
    </div>
    </div> <!-- Fin du conteneur content-before-table -->
    @php
        $detailsGroupes = $facture->getDetailsGroupesParType();
    @endphp
    
    @if(count($detailsGroupes) > 1)
        {{-- Affichage par sections si plusieurs types --}}
        @foreach($detailsGroupes as $section => $details)
            <div class="section-header">{{ $section }}</div>
            <table class="details-table" style="margin-bottom: 20px;">
                <thead>
                <tr>
                    <th>Acte</th>
                    <th>Quantité</th>
                    <th>Prix unitaire</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($details as $detail)
                    <tr>
                        <td>{{ $detail->Actes ?? 'N/A' }}</td>
                        <td>{{ $detail->Quantite ?? 1 }}</td>
                        <td>{{ number_format($detail->PrixFacture ?? 0, 2) }} MRU</td>
                        <td>{{ number_format(($detail->PrixFacture ?? 0) * ($detail->Quantite ?? 1), 2) }} MRU</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endforeach
    @else
        {{-- Affichage simple si un seul type ou pas de type --}}
        <table class="details-table">
            <thead>
            <tr>
                <th>Acte</th>
                <th>Quantité</th>
                <th>Prix unitaire</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($facture->details as $detail)
                <tr>
                    <td>{{ $detail->Actes ?? 'N/A' }}</td>
                    <td>{{ $detail->Quantite ?? 1 }}</td>
                    <td>{{ number_format($detail->PrixFacture ?? 0, 2) }} MRU</td>
                    <td>{{ number_format(($detail->PrixFacture ?? 0) * ($detail->Quantite ?? 1), 2) }} MRU</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
    <table class="totaux-table">
        <tr>
            <td>Total consultation</td>
            <td>{{ number_format($facture->TotFacture ?? 0, 2) }} MRU</td>
        </tr>
        @if(($facture->TotalPEC ?? 0) > 0)
            <tr>
                <td>Prise en charge</td>
                <td>{{ number_format($facture->TotalPEC ?? 0, 2) }} MRU</td>
            </tr>
            <tr>
                <td>Reste à payer</td>
                <td>{{ number_format($facture->TotalfactPatient ?? 0, 2) }} MRU</td>
            </tr>
        @endif
    </table>
    <div class="montant-lettres">
        Arrêté la présente consultation à la somme de : <strong>{{ $facture->en_lettres ?? '' }}</strong>
    </div>

    <div class="signature-block">
        <div class="signature-title">Signature</div>
        <div class="signature-line">
            <div class="signature-name">Dr. {{ $facture->medecin->Nom ?? 'Non spécifié' }}</div>
        </div>
    </div>

         <!-- QR Code pour l'interface patient - Positionné en bas à gauche -->
     <div style="position: fixed; bottom: 120px; left: 20px; z-index: 1000;">
         <div style="text-align: center;">
             @php
                 try {
                     // Utiliser la date du rendez-vous associé ou la date de la facture
                     $dateRendezVous = $facture->rendezVous ? $facture->rendezVous->dtPrevuRDV : $facture->DtFacture;
                     $medecinId = $facture->rendezVous ? $facture->rendezVous->fkidMedecin : null;
                     $token = App\Http\Controllers\PatientInterfaceController::generateToken($facture->IDPatient, $dateRendezVous, $medecinId);
                     $patientUrl = route('patient.rendez-vous', ['token' => $token]);
                 } catch (Exception $e) {
                     $patientUrl = '#';
                 }
             @endphp
             <a href="{{ $patientUrl }}" target="_blank" class="qr-code-link" aria-label="Ouvrir l'interface patient pour suivre votre file d'attente">
                 <div class="qr-code-container">
                     <div style="max-width: 100px; height: auto;">
                         @php
                             try {
                                 $qrCode = App\Helpers\QrCodeHelper::generateRendezVousQrCode($facture->IDPatient);
                                 echo $qrCode;
                             } catch (Exception $e) {
                                 echo '<div style="width: 100px; height: 100px; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 8px; color: #666; border-radius: 4px;">QR Code<br>Non disponible</div>';
                             }
                         @endphp
                     </div>
                 </div>
             </a>
             <div style="margin-top: 4px; font-size: 8px; color: #333; font-weight: 600;">
                 Suivez votre file d'attente
             </div>
         </div>
     </div>

    <div class="recu-footer">@include('partials.recu-footer')</div>
</div>

<script>
// Cache des éléments DOM fréquemment utilisés
const elements = {
    pageFormat: document.getElementById('pageFormat'),
    container: document.getElementById('documentContainer')
};

function updatePageFormat() {
    const isA5 = elements.pageFormat.value === 'A5';
    elements.container.classList.toggle('a4', !isA5);
    elements.container.classList.toggle('a5', isA5);
    
    // Mettre à jour la règle @page pour l'impression
    let style = document.getElementById('print-format-style');
    if (!style) {
        style = document.createElement('style');
        style.id = 'print-format-style';
        document.head.appendChild(style);
    }
    
    if (isA5) {
        style.innerHTML = '@media print { @page { size: A5; margin: 8mm; } }';
    } else {
        style.innerHTML = '@media print { @page { size: A4; margin: 10mm; } }';
    }
}

// Fonction pour imprimer avec le bon format
function printDocument() {
    // S'assurer que le format est à jour avant l'impression
    updatePageFormat();
    // Petit délai pour s'assurer que le style est appliqué
    setTimeout(function() {
        window.print();
    }, 100);
}

// Initialiser le format au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    updatePageFormat();
});

// Fonction globale WhatsApp pour cette page
if (typeof window.whatsappWindow === 'undefined') {
    window.whatsappWindow = null;
}

// Fonction globale pour ouvrir WhatsApp de manière centralisée
window.openWhatsApp = function(url, successCallback) {
    console.log('🔗 URL WhatsApp:', url);
    
    try {
        // Vérifier si un onglet WhatsApp est déjà ouvert
        if (window.whatsappWindow && !window.whatsappWindow.closed) {
            console.log('🔄 Onglet WhatsApp déjà ouvert, focus sur l\'onglet existant');
            window.whatsappWindow.focus();
            
            // Mettre à jour l'URL de l'onglet existant
            window.whatsappWindow.location.href = url;
            
            // Appeler le callback de succès
            if (successCallback) successCallback();
            return;
        }
        
        // Ouvrir WhatsApp dans un nouvel onglet
        console.log('🟠 Tentative d\'ouverture WhatsApp dans un nouvel onglet...');
        window.whatsappWindow = window.open(url, '_blank', 'noopener,noreferrer');
        
        if (window.whatsappWindow) {
            console.log('✅ WhatsApp ouvert avec succès dans un nouvel onglet');
            window.whatsappWindow.focus();
            
            // Appeler le callback de succès
            if (successCallback) successCallback();
        } else {
            console.error('❌ Impossible d\'ouvrir WhatsApp - popup bloqué');
            
            // Fallback : essayer d'ouvrir dans un nouvel onglet avec des paramètres différents
            console.log('🔄 Tentative de fallback avec paramètres différents...');
            window.whatsappWindow = window.open(url, '_blank');
            
            if (window.whatsappWindow) {
                console.log('✅ WhatsApp ouvert avec fallback');
                window.whatsappWindow.focus();
                if (successCallback) successCallback();
            } else {
                console.error('❌ Fallback échoué - copier le lien');
                
                // Dernier recours : copier le lien dans le presse-papiers
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).then(function() {
                        alert('Lien WhatsApp copié dans le presse-papiers. Veuillez l\'ouvrir manuellement.');
                        if (successCallback) successCallback();
                    });
                } else {
                    alert('Impossible d\'ouvrir WhatsApp automatiquement. Veuillez copier ce lien: ' + url);
                    if (successCallback) successCallback();
                }
            }
        }
    } catch (error) {
        console.error('❌ Erreur lors de l\'ouverture de WhatsApp:', error);
        
        // Fallback : copier le lien dans le presse-papiers
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                alert('Lien WhatsApp copié dans le presse-papiers. Veuillez l\'ouvrir manuellement.');
                if (successCallback) successCallback();
            });
        } else {
            alert('Impossible d\'ouvrir WhatsApp automatiquement. Veuillez copier ce lien: ' + url);
            if (successCallback) successCallback();
        }
    }
};

// Fonction pour créer un short URL avec TinyURL API
async function createShortUrl(longUrl) {
    try {
        const response = await fetch(`https://tinyurl.com/api-create.php?url=${encodeURIComponent(longUrl)}`);
        if (response.ok) {
            return await response.text();
        } else {
            console.error('❌ Erreur lors de la création du short URL');
            return longUrl; // Fallback vers l'URL longue
        }
    } catch (error) {
        console.error('❌ Erreur réseau lors de la création du short URL:', error);
        return longUrl; // Fallback vers l'URL longue
    }
}

// FONCTIONS WHATSAPP
async function envoyerConfirmationWhatsApp() {
    if (!verifierTelephone()) {
        return;
    }
    
    // Données du patient et de la consultation
    const nomPatient = "{{ $facture->patient->NomContact }}";
    const telephone = "{{ $telephoneNettoye ?? '' }}";
    const dateConsultation = "{{ $facture->DtFacture ? \Carbon\Carbon::parse($facture->DtFacture)->format('d/m/Y') : '' }}";
    const medecin = "{{ trim(($facture->medecin->Nom ?? '') . ' ' . ($facture->medecin->Prenom ?? '')) ?: 'Médecin non défini' }}";
    const numeroFacture = "{{ $facture->Nfacture ?? '' }}";
    
    // Récupérer l'heure et le numéro d'ordre depuis le rendez-vous associé
    let heureConsultation = '';
    let ordreConsultation = '';
    
    @if($facture->rendezVous)
        // Debug des valeurs brutes
        console.log('HeureRdv brute:', "{{ $facture->rendezVous->HeureRdv ?? 'null' }}");
        console.log('Type HeureRdv:', typeof("{{ $facture->rendezVous->HeureRdv ?? 'null' }}"));
        
        @php
            $heureRdv = $facture->rendezVous->HeureRdv;
            $heureFormatee = '';
            
            if ($heureRdv) {
                try {
                    // Si c'est déjà un objet Carbon
                    if ($heureRdv instanceof \Carbon\Carbon) {
                        $heureFormatee = $heureRdv->format('H:i');
                    } else {
                        // Essayer de parser la valeur
                        $carbon = \Carbon\Carbon::parse($heureRdv);
                        $heureFormatee = $carbon->format('H:i');
                    }
                } catch (Exception $e) {
                    // Si le parsing échoue, utiliser la valeur brute
                    $heureFormatee = $heureRdv;
                }
            }
            
            // Si toujours pas d'heure, utiliser l'heure de la facture comme fallback
            if (!$heureFormatee && $facture->DtFacture) {
                try {
                    $carbon = \Carbon\Carbon::parse($facture->DtFacture);
                    $heureFormatee = $carbon->format('H:i');
                } catch (Exception $e) {
                    $heureFormatee = '';
                }
            }
        @endphp
        
        heureConsultation = "{{ $heureFormatee ?: 'À définir' }}";
        
        @if(isset($facture->rendezVous->OrdreRDV) && $facture->rendezVous->OrdreRDV > 0)
            ordreConsultation = "{{ str_pad($facture->rendezVous->OrdreRDV, 3, '0', STR_PAD_LEFT) }}";
        @else
            ordreConsultation = "À définir";
        @endif
    @else
        heureConsultation = "Non disponible (pas de RDV associé)";
        ordreConsultation = "Non disponible (pas de RDV associé)";
    @endif
    
    @php
        // Générer le lien de suivi de la file d'attente avec la date du rendez-vous
        try {
            // Utiliser la date du rendez-vous associé ou la date de la facture
            $dateRendezVous = $facture->rendezVous ? $facture->rendezVous->dtPrevuRDV : $facture->DtFacture;
            $medecinId = $facture->rendezVous ? $facture->rendezVous->fkidMedecin : null;
            $token = App\Http\Controllers\PatientInterfaceController::generateToken($facture->IDPatient, $dateRendezVous, $medecinId);
            $patientUrl = route('patient.rendez-vous', ['token' => $token]);
        } catch (Exception $e) {
            $patientUrl = url('/');
        }
    @endphp
    
    const lienSuivi = "{{ $patientUrl }}";
    
    // Créer un short URL pour le lien de suivi
    const shortUrl = await createShortUrl(lienSuivi);
    console.log('🔗 URL longue:', lienSuivi);
    console.log('🔗 URL courte:', shortUrl);
    
    // Debug des données
    console.log('=== DEBUG CONSULTATION ===');
    console.log('Heure:', heureConsultation);
    console.log('Ordre:', ordreConsultation);
    console.log('Médecin:', medecin);
    console.log('==========================');
    
    // Nettoyer le numéro de téléphone
    const phoneClean = telephone.replace(/[\s\-\(\)]/g, '');
    
    // Message bilingue avec format de rendez-vous
    const message = construireMessageBilingue(nomPatient, dateConsultation, heureConsultation, medecin, ordreConsultation, numeroFacture, shortUrl);
    
    // Créer le lien WhatsApp Web
    const whatsappWebUrl = `https://wa.me/${phoneClean}?text=${encodeURIComponent(message)}`;
    
    console.log('🔗 URL WhatsApp Confirmation Consultation:', whatsappWebUrl);
    
    // Utiliser la fonction globale pour ouvrir WhatsApp
    window.openWhatsApp(whatsappWebUrl, function() {
        mostrarNotificationSucces();
    });
}

function construireMessageBilingue(nom, date, heure, medecin, ordre, numeroFacture, lienSuivi) {
    // Debug des paramètres reçus
    console.log('=== CONSTRUCTION MESSAGE CONSULTATION ===');
    console.log('Nom:', nom);
    console.log('Date:', date);
    console.log('Heure reçue:', heure);
    console.log('Médecin:', medecin);
    console.log('Ordre reçu:', ordre);
    console.log('Facture:', numeroFacture);
    console.log('Lien:', lienSuivi);
    console.log('==========================================');
    
    // Construction du message avec formatage simple compatible WhatsApp
    const message = `*تم تأكيد الاستشارة*
*Consultation confirmée*

*${nom || 'Nom non défini'}*

التاريخ: ${date || 'تاريخ غير محدد'}
الوقت: ${heure || 'وقت غير محدد'}
الطبيب: د. ${medecin || 'طبيب غير محدد'}
الرقم: ${ordre || 'رقم غير محدد'}
الفاتورة: ${numeroFacture || 'فاتورة غير محددة'}

━━━━━━━━━━━━━━━━━━━━━━

Date: ${date || 'Date non définie'}
Heure: ${heure || 'Heure non définie'}
Médecin: Dr. ${medecin || 'Médecin non défini'}
Numéro: ${ordre || 'Numéro non défini'}
Facture: ${numeroFacture || 'Facture non définie'}

*رابط متابعة طابور الانتظار:*
*Lien de suivi de la file d'attente:*
${lienSuivi || 'Lien non disponible'}

استخدم هذا الرابط لمعرفة رقمك في الطابور ووقت الانتظار المتوقع
Utilisez ce lien pour connaître votre numéro dans la file et le temps d'attente estimé

شكراً - Merci`;

    // Debug du message final
    console.log('=== MESSAGE FINAL CONSULTATION ===');
    console.log(message);
    console.log('===================================');
    
    return message;
}

// Vérification du numéro de téléphone
function verifierTelephone() {
    const telephone = "{{ $telephoneNettoye ?? '' }}";
    
    if (!telephone || telephone.trim() === '' || telephone === 'N/A') {
        alert('❌ Aucun numéro de téléphone disponible pour ce patient.\n\nVeuillez ajouter un numéro dans la fiche patient.');
        return false;
    }
    
    const phoneClean = telephone.replace(/[\s\-\(\)]/g, '');
    if (phoneClean.length < 8) {
        alert('❌ Le numéro de téléphone semble invalide.\n\nVeuillez vérifier le numéro dans la fiche patient.');
        return false;
    }
    
    return true;
}

function mostrarNotificationSucces() {
    const message = 'Message de confirmation envoyé vers WhatsApp !';
    
    // Créer une notification temporaire
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #25D366;
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        font-weight: bold;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Ajouter les animations CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>
</body>
</html> 