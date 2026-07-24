<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles Liste des rôles autorisés (1, 2, 3)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Vérifier si l'utilisateur est connecté
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Vérifier si l'utilisateur a un rôle autorisé
        $user = Auth::user();
        
        // Convertir les rôles en entiers pour la comparaison
        $allowedRoles = array_map('intval', $roles);
        
        if (in_array($user->IdClasseUser, $allowedRoles)) {
            return $next($request);
        }

        // Rediriger vers une page d'erreur ou la page d'accueil
        return redirect()->route('accueil.patient')->with('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.');
    }
}