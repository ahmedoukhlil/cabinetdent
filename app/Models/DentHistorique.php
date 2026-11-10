<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DentHistorique extends Model
{
    use BelongsToCabinet;

    public const UPDATED_AT = null;

    protected $fillable = ['cabinet_id', 'patient_id', 'numero', 'avant', 'apres', 'auteur_id'];

    protected $casts = ['avant' => 'array', 'apres' => 'array', 'created_at' => 'datetime'];

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }
}
