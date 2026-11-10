<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Déconnecte un utilisateur désactivé ou dont le cabinet est suspendu en cours de session. */
class EnsureCompteActif
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && (!$user->actif || !$user->cabinet || !\App\Services\AbonnementService::accesAutorise($user->cabinet))) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'Votre accès a été suspendu.']);
        }

        return $next($request);
    }
}
