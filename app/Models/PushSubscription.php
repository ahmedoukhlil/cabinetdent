<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'patient_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'ID');
    }
}
