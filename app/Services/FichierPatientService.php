<?php

namespace App\Services;

use App\Models\FichierPatient;
use App\Models\Patient;
use App\Models\User;
use App\Support\Odontologie;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Fichiers cliniques (radios, photos, comptes rendus). Stockés sur le disque privé
 * sous cabinets/{cabinet}/patients/{patient}/, jamais accessibles par une URL publique.
 */
class FichierPatientService
{
    public function ajouter(User $auteur, Patient $patient, ?UploadedFile $fichier, array $donnees): FichierPatient
    {
        $this->exiger($auteur, 'dossier.edit');
        if ($patient->cabinet_id !== $auteur->cabinet_id) {
            throw new AuthorizationException('Patient introuvable.');
        }

        $dentsValides = Odontologie::toutesLesDents();
        $d = Validator::make([...$donnees, 'fichier' => $fichier], [
            'fichier' => ['required', 'file', 'mimes:'.implode(',', FichierPatient::MIMES), 'max:'.FichierPatient::TAILLE_MAX_KO],
            'categorie' => ['required', Rule::in(array_keys(FichierPatient::CATEGORIES))],
            'titre' => ['nullable', 'string', 'max:150'],
            'date_examen' => ['nullable', 'date', 'before_or_equal:today'],
            'dents' => ['nullable', 'array'],
            'dents.*' => ['integer', Rule::in($dentsValides)],
        ], [
            'fichier.mimes' => 'Formats acceptés : JPG, PNG, WEBP ou PDF.',
            'fichier.max' => 'Le fichier dépasse 10 Mo.',
        ], ['fichier' => 'fichier', 'date_examen' => 'date de l\'examen'])->validate();

        AbonnementService::verifierQuotaStockage($auteur->cabinet, (int) $fichier->getSize());

        $extension = strtolower($fichier->getClientOriginalExtension() ?: $fichier->extension());
        $chemin = $fichier->storeAs(
            "cabinets/{$auteur->cabinet_id}/patients/{$patient->id}",
            Str::uuid().'.'.$extension,
            'local'
        );

        $titre = trim((string) ($d['titre'] ?? ''));

        return FichierPatient::create([
            'cabinet_id' => $auteur->cabinet_id,
            'patient_id' => $patient->id,
            'categorie' => $d['categorie'],
            'titre' => $titre !== '' ? $titre : FichierPatient::CATEGORIES[$d['categorie']],
            'date_examen' => $d['date_examen'] ?? today(),
            'dents' => array_values(array_unique(array_map('intval', $d['dents'] ?? []))) ?: null,
            'chemin' => $chemin,
            'nom_origine' => mb_substr($fichier->getClientOriginalName(), 0, 255),
            'mime' => $fichier->getMimeType() ?: 'application/octet-stream',
            'taille' => $fichier->getSize(),
            'ajoute_par' => $auteur->id,
        ]);
    }

    /** Suppression logique : le fichier physique est conservé pour la traçabilité. */
    public function supprimer(User $auteur, FichierPatient $fichier): void
    {
        $this->exiger($auteur, 'dossier.edit');
        if ($fichier->ajoute_par !== $auteur->id && !$auteur->estProprietaire()) {
            throw new AuthorizationException('Seul l\'auteur ou le propriétaire peut retirer ce fichier.');
        }
        $fichier->delete();
    }

    public function existe(FichierPatient $fichier): bool
    {
        return Storage::disk('local')->exists($fichier->chemin);
    }

    private function exiger(User $user, string $permission): void
    {
        if (!$user->aPermission($permission)) {
            throw new AuthorizationException('Action non autorisée.');
        }
    }
}
