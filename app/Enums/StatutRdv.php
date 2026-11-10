<?php

namespace App\Enums;

enum StatutRdv: string
{
    case Planifie = 'planifie';
    case Arrive = 'arrive';
    case EnCours = 'en_cours';
    case Termine = 'termine';
    case Annule = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::Planifie => 'Planifié',
            self::Arrive => 'Arrivé',
            self::EnCours => 'En soins',
            self::Termine => 'Terminé',
            self::Annule => 'Annulé',
        };
    }

    /** Transitions autorisées depuis cet état. */
    public function suivants(): array
    {
        return match ($this) {
            self::Planifie => [self::Arrive, self::Annule],
            self::Arrive => [self::EnCours, self::Annule],
            self::EnCours => [self::Termine],
            self::Termine, self::Annule => [],
        };
    }

    public function peutPasserA(self $cible): bool
    {
        return in_array($cible, $this->suivants(), true);
    }

    public function couleur(): string
    {
        return match ($this) {
            self::Planifie => 'bg-slate-100 text-slate-700',
            self::Arrive => 'bg-amber-100 text-amber-800',
            self::EnCours => 'bg-blue-100 text-blue-800',
            self::Termine => 'bg-emerald-100 text-emerald-800',
            self::Annule => 'bg-red-100 text-red-700',
        };
    }
}
