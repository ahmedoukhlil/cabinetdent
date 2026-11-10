<?php

namespace App\Models\Concerns;

use App\Models\Cabinet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Isole les données par cabinet : filtre toutes les requêtes sur le cabinet de
 * l'utilisateur connecté et renseigne cabinet_id à la création.
 */
trait BelongsToCabinet
{
    public static function bootBelongsToCabinet(): void
    {
        static::addGlobalScope('cabinet', function (Builder $query) {
            if ($cabinetId = Auth::user()?->cabinet_id) {
                $query->where($query->getModel()->getTable().'.cabinet_id', $cabinetId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->cabinet_id) && Auth::check()) {
                $model->cabinet_id = Auth::user()->cabinet_id;
            }
        });
    }

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }
}
