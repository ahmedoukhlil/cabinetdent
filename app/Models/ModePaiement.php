<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Mode de paiement d'un cabinet (espèces, Bankily, chèque…).
 * Paiements et dépenses enregistrent le code : un mode utilisé ne peut qu'être désactivé.
 */
class ModePaiement extends Model
{
    use BelongsToCabinet;

    protected $table = 'modes_paiement';

    protected $fillable = ['cabinet_id', 'code', 'libelle', 'actif', 'ordre'];

    protected $casts = ['actif' => 'boolean', 'ordre' => 'integer'];

    /** Modes créés pour chaque nouveau cabinet. */
    public const DEFAUTS = [
        'especes' => 'Espèces',
        'mobile' => 'Paiement mobile',
        'carte' => 'Carte bancaire',
        'virement' => 'Virement',
        'cheque' => 'Chèque',
    ];

    public static function creerDefauts(Cabinet $cabinet): void
    {
        $ordre = 0;
        foreach (self::DEFAUTS as $code => $libelle) {
            static::withoutGlobalScopes()->firstOrCreate(
                ['cabinet_id' => $cabinet->id, 'code' => $code],
                ['libelle' => $libelle, 'actif' => true, 'ordre' => ++$ordre],
            );
        }
    }

    private static function duCabinet(?int $cabinetId)
    {
        return static::withoutGlobalScopes()
            ->where('cabinet_id', $cabinetId ?? auth()->user()?->cabinet_id)
            ->orderBy('ordre')->orderBy('libelle');
    }

    /** Modes proposés à la saisie : [code => libellé]. */
    public static function actifs(?int $cabinetId = null): array
    {
        return self::duCabinet($cabinetId)->where('actif', true)->pluck('libelle', 'code')->all();
    }

    /** Libellés de tous les modes, désactivés compris, pour l'affichage de l'historique. */
    public static function libelles(?int $cabinetId = null): array
    {
        return self::duCabinet($cabinetId)->pluck('libelle', 'code')->all() + self::DEFAUTS;
    }

    public static function parDefaut(?int $cabinetId = null): string
    {
        return array_key_first(self::actifs($cabinetId)) ?? 'especes';
    }

    /** Code technique unique dans le cabinet, déduit du libellé. */
    public static function codePour(string $libelle, int $cabinetId): string
    {
        $base = Str::limit(Str::slug($libelle, '_'), 16, '') ?: 'mode';
        $code = $base;
        for ($i = 2; static::withoutGlobalScopes()->where('cabinet_id', $cabinetId)->where('code', $code)->exists(); $i++) {
            $code = $base.'_'.$i;
        }

        return $code;
    }

    /** Nombre de paiements et de dépenses enregistrés avec ce mode. */
    public function utilisations(): int
    {
        return Paiement::withoutGlobalScopes()->where('cabinet_id', $this->cabinet_id)->where('mode', $this->code)->count()
            + Depense::withoutGlobalScopes()->where('cabinet_id', $this->cabinet_id)->where('mode', $this->code)->count();
    }
}
