<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCabinet;
use Illuminate\Database\Eloquent\Model;

class ActeTarif extends Model
{
    use BelongsToCabinet;

    protected $table = 'acte_tarifs';

    protected $fillable = ['cabinet_id', 'acte_id', 'assureur_id', 'prix'];

    protected $casts = ['prix' => 'float'];
}
