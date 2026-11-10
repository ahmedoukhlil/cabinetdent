<?php

namespace App\Enums;

enum Role: string
{
    case Proprietaire = 'proprietaire';
    case Dentiste = 'dentiste';
    case Secretaire = 'secretaire';

    public function label(): string
    {
        return match ($this) {
            self::Proprietaire => 'Dentiste propriétaire',
            self::Dentiste => 'Dentiste',
            self::Secretaire => 'Secrétaire',
        };
    }

    public function estDentiste(): bool
    {
        return $this !== self::Secretaire;
    }
}
