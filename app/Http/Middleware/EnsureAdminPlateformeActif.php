<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Déconnecte immédiatement un administrateur plateforme désactivé (sessions déjà ouvertes comprises). */
class EnsureAdminPlateformeActif
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('plateforme')->user();
        if ($admin && !$admin->actif) {
            Auth::guard('plateforme')->logout();
            $request->session()->regenerateToken();

            // Lu (puis effacé) par la page de connexion, même après plusieurs redirections.
            $request->session()->put('plateforme_compte_desactive', true);
            // Requête Livewire (clic, formulaire) : refus net ; navigation normale : retour à la connexion.
            abort_if($request->hasHeader('X-Livewire'), 403, 'Ce compte administrateur est désactivé.');

            // Réponse construite directement : redirect() peut renvoyer le redirecteur de Livewire.
            return new \Illuminate\Http\RedirectResponse(route('plateforme.login'));
        }

        return $next($request);
    }
}
