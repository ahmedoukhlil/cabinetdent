<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['cabinet_id', 'name', 'email', 'password', 'role', 'actif'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'actif' => 'boolean',
        ];
    }

    public function cabinet(): BelongsTo
    {
        return $this->belongsTo(Cabinet::class);
    }

    public function aPermission(string $permission): bool
    {
        return in_array($permission, config('permissions.'.$this->role->value, []), true);
    }

    public function estDentiste(): bool
    {
        return $this->role->estDentiste();
    }

    public function estProprietaire(): bool
    {
        return $this->role === Role::Proprietaire;
    }

    /** Dentistes du même cabinet que l'utilisateur donné. */
    public function scopeDentistesDu(Builder $query, User $user): Builder
    {
        return $query->where('cabinet_id', $user->cabinet_id)
            ->whereIn('role', [Role::Proprietaire->value, Role::Dentiste->value])
            ->where('actif', true)
            ->orderBy('name');
    }
}
