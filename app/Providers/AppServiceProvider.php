<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('fr');

        // Les permissions de config/permissions.php sont utilisables via @can / Gate::allows.
        Gate::before(fn (User $user, string $ability) => $user->aPermission($ability) ?: null);
    }
}
