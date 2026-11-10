<?php

namespace App\Services;

use App\Enums\StatutRdv;
use App\Models\Patient;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RendezVousService
{
    /**
     * Crée un rendez-vous après avoir revérifié le patient et le dentiste dans le cabinet courant.
     */
    public function creer(User $auteur, array $donnees): RendezVous
    {
        $patient = Patient::find($donnees['patient_id'] ?? null);
        $dentiste = User::dentistesDu($auteur)->find($donnees['dentiste_id'] ?? null);

        if (!$patient || !$dentiste) {
            throw ValidationException::withMessages(['patient_id' => 'Patient ou dentiste introuvable dans ce cabinet.']);
        }

        if (!$auteur->aPermission('rendez-vous.edit') && $dentiste->id !== $auteur->id) {
            throw new AuthorizationException('Vous ne pouvez planifier que vos propres rendez-vous.');
        }

        $heure = substr($donnees['heure'], 0, 5).':00';

        return DB::transaction(function () use ($auteur, $patient, $dentiste, $donnees, $heure) {
            $memeJour = RendezVous::query()
                ->where('dentiste_id', $dentiste->id)
                ->whereDate('date', $donnees['date'])
                ->lockForUpdate();

            $duree = $this->duree($auteur);
            $debut = self::minutes($heure);
            $chevauche = (clone $memeJour)->actifs()->pluck('heure')
                ->contains(fn ($h) => abs(self::minutes($h) - $debut) < $duree);
            if ($chevauche) {
                throw ValidationException::withMessages(['heure' => "Ce créneau chevauche un rendez-vous existant de ce dentiste ({$duree} min par rendez-vous)."]);
            }

            return RendezVous::create([
                'cabinet_id' => $auteur->cabinet_id,
                'patient_id' => $patient->id,
                'dentiste_id' => $dentiste->id,
                'date' => $donnees['date'],
                'heure' => $heure,
                'ordre' => (int) (clone $memeJour)->max('ordre') + 1,
                'acte_prevu' => $donnees['acte_prevu'] ?? null,
                'statut' => StatutRdv::Planifie,
                'cree_par' => $auteur->id,
            ]);
        });
    }

    /**
     * Patient venu sans rendez-vous : on l'inscrit pour aujourd'hui à l'heure actuelle,
     * directement « Arrivé ». Pas de contrôle de chevauchement : il passera entre deux rendez-vous.
     */
    public function sansRendezVous(User $auteur, Patient $patient, int $dentisteId, ?string $motif = null): RendezVous
    {
        $dentiste = User::dentistesDu($auteur)->find($dentisteId);
        if (!$dentiste || $patient->cabinet_id !== $auteur->cabinet_id) {
            throw ValidationException::withMessages(['dentiste_id' => 'Patient ou dentiste introuvable dans ce cabinet.']);
        }
        if (!$auteur->aPermission('rendez-vous.edit') && $dentiste->id !== $auteur->id) {
            throw new AuthorizationException('Vous ne pouvez inscrire un patient que pour vous-même.');
        }
        $deja = RendezVous::where('patient_id', $patient->id)->duJour()
            ->whereIn('statut', [StatutRdv::Arrive->value, StatutRdv::EnCours->value])->exists();
        if ($deja) {
            throw ValidationException::withMessages(['patient_id' => 'Ce patient est déjà en salle d’attente ou au fauteuil.']);
        }

        return DB::transaction(fn () => RendezVous::create([
            'cabinet_id' => $auteur->cabinet_id,
            'patient_id' => $patient->id,
            'dentiste_id' => $dentiste->id,
            'date' => today(),
            'heure' => now()->format('H:i:00'),
            'ordre' => (int) RendezVous::where('dentiste_id', $dentiste->id)->whereDate('date', today())->lockForUpdate()->max('ordre') + 1,
            'acte_prevu' => $motif ?: 'Sans rendez-vous',
            'statut' => StatutRdv::Arrive,
            'arrive_at' => now(),
            'cree_par' => $auteur->id,
        ]));
    }

    /**
     * Créneaux libres d'un dentiste pour une date, selon les horaires du cabinet et la durée
     * d'un rendez-vous. Renvoie une liste d'heures « HH:MM » ; vide si le cabinet est fermé
     * ce jour-là. Pour aujourd'hui, les créneaux déjà passés sont exclus.
     *
     * @return string[]
     */
    public function creneauxLibres(User $auteur, int $dentisteId, string $date): array
    {
        $cabinet = $auteur->cabinet;
        $jour = Carbon::parse($date)->startOfDay();
        if ($jour->lt(today()) || !$cabinet->estOuvertLe($jour)) {
            return [];
        }

        $duree = $this->duree($auteur);
        $occupes = RendezVous::where('dentiste_id', $dentisteId)->whereDate('date', $jour)->actifs()
            ->pluck('heure')->map(fn ($h) => self::minutes($h));
        $maintenant = $jour->isToday() ? now()->hour * 60 + now()->minute : -1;

        $libres = [];
        $fin = self::minutes($cabinet->heure_fermeture ?: '18:00');
        for ($m = self::minutes($cabinet->heure_ouverture ?: '08:30'); $m + $duree <= $fin; $m += $duree) {
            if ($m > $maintenant && !$occupes->contains(fn ($o) => abs($o - $m) < $duree)) {
                $libres[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            }
        }

        return $libres;
    }

    private function duree(User $auteur): int
    {
        return max(5, (int) ($auteur->cabinet->duree_rdv_minutes ?: 30));
    }

    private static function minutes(string $heure): int
    {
        [$h, $m] = array_map('intval', explode(':', $heure) + [0, 0]);

        return $h * 60 + $m;
    }

    /**
     * Fait avancer un rendez-vous dans son cycle de vie en respectant les transitions autorisées.
     */
    public function changerStatut(User $auteur, RendezVous $rdv, StatutRdv $cible): RendezVous
    {
        $this->autoriser($auteur, $rdv, $cible);

        if (!$rdv->statut->peutPasserA($cible)) {
            throw ValidationException::withMessages([
                'statut' => "Impossible de passer de « {$rdv->statut->label()} » à « {$cible->label()} ».",
            ]);
        }

        if ($cible === StatutRdv::EnCours) {
            $dejaAuFauteuil = RendezVous::where('dentiste_id', $rdv->dentiste_id)
                ->whereDate('date', $rdv->date)
                ->where('statut', StatutRdv::EnCours->value)
                ->where('id', '!=', $rdv->id)
                ->exists();
            if ($dejaAuFauteuil) {
                throw ValidationException::withMessages(['statut' => 'Ce dentiste a déjà un patient en soins. Terminez-le d’abord.']);
            }
        }

        $horodatage = match ($cible) {
            StatutRdv::Arrive => ['arrive_at' => now()],
            StatutRdv::EnCours => ['debut_at' => now()],
            StatutRdv::Termine => ['fin_at' => now()],
            default => [],
        };

        $rdv->update(['statut' => $cible] + $horodatage);

        return $rdv;
    }

    private function autoriser(User $auteur, RendezVous $rdv, StatutRdv $cible): void
    {
        $soins = in_array($cible, [StatutRdv::EnCours, StatutRdv::Termine], true);

        $autorise = $soins
            ? $auteur->aPermission('soins.manage') && ($auteur->aPermission('rendez-vous.edit') || $rdv->dentiste_id === $auteur->id)
            : $auteur->aPermission('rendez-vous.edit') || ($auteur->aPermission('rendez-vous.own') && $rdv->dentiste_id === $auteur->id);

        if (!$autorise) {
            throw new AuthorizationException('Action non autorisée sur ce rendez-vous.');
        }
    }
}
