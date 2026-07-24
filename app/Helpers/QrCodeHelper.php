<?php

namespace App\Helpers;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Controllers\PatientInterfaceController;

class QrCodeHelper
{
    /**
     * Génère un QR code pour l'interface patient des rendez-vous
     */
    public static function generateRendezVousQrCode($patientId, $dateRendezVous = null, $medecinId = null)
    {
        $token = PatientInterfaceController::generateToken($patientId, $dateRendezVous, $medecinId);
        $url = route('patient.rendez-vous', ['token' => $token]);
        
        return QrCode::format('svg')
            ->size(120)
            ->margin(2)
            ->errorCorrection('M')
            ->backgroundColor(255, 255, 255)
            ->color(0, 0, 0)
            ->generate($url);
    }

    /**
     * Génère un QR code pour l'interface patient des consultations
     */
    public static function generateConsultationQrCode($patientId, $dateConsultation = null, $medecinId = null)
    {
        $token = PatientInterfaceController::generateToken($patientId, $dateConsultation, $medecinId);
        $url = route('patient.consultation', ['token' => $token]);
        
        return QrCode::format('svg')
            ->size(120)
            ->margin(2)
            ->errorCorrection('M')
            ->backgroundColor(255, 255, 255)
            ->color(0, 0, 0)
            ->generate($url);
    }

    /**
     * Génère un QR code avec des paramètres personnalisés
     */
    public static function generateCustomQrCode($data, $size = 120, $format = 'svg')
    {
        return QrCode::format($format)
            ->size($size)
            ->margin(0)
            ->errorCorrection('M')
            ->generate($data);
    }

    /**
     * Nettoie un numéro de téléphone pour WhatsApp
     * Supprime tous les caractères non numériques et valide le format
     * 
     * @param string $telephone Le numéro de téléphone à nettoyer
     * @return string Le numéro nettoyé (8 chiffres) ou chaîne vide si invalide
     */
    public static function cleanPhoneNumber($telephone)
    {
        if (empty($telephone) || $telephone === 'N/A') {
            return '';
        }

        // Supprimer tous les caractères non numériques (lettres, points, tirets, espaces, etc.)
        $cleaned = preg_replace('/[^0-9]/', '', $telephone);
        
        // Vérifier que le numéro fait exactement 8 chiffres
        if (strlen($cleaned) === 8) {
            return $cleaned;
        }
        
        // Si le numéro commence par un indicatif pays (ex: 222), le retirer
        if (strlen($cleaned) > 8) {
            // Pour la Mauritanie, supprimer l'indicatif +222 s'il est présent
            if (str_starts_with($cleaned, '222') && strlen($cleaned) === 11) {
                $cleaned = substr($cleaned, 3);
                if (strlen($cleaned) === 8) {
                    return $cleaned;
                }
            }
            
            // Prendre les 8 derniers chiffres si le numéro est trop long
            if (strlen($cleaned) > 8) {
                $cleaned = substr($cleaned, -8);
                return $cleaned;
            }
        }
        
        // Si le numéro est trop court, retourner vide
        return '';
    }

    /**
     * Formate un numéro de téléphone pour WhatsApp avec l'indicatif pays
     * 
     * @param string $telephone Le numéro de téléphone à formater
     * @param string $countryCode L'indicatif pays (par défaut 222 pour la Mauritanie)
     * @return string Le numéro formaté pour WhatsApp ou chaîne vide si invalide
     */
    public static function formatPhoneForWhatsApp($telephone, $countryCode = '222')
    {
        $cleaned = self::cleanPhoneNumber($telephone);
        
        if (empty($cleaned)) {
            return '';
        }
        
        return $countryCode . $cleaned;
    }
} 