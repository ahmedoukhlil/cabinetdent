<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Vérifier si l'utilisateur connecté possède la permission requise (RBAC dynamique).
     */
    /**
     * Supporte une ou plusieurs permissions séparées par | (OU logique).
     * Ex: middleware('permission:facture.view|facture.view.own')
     */
    public function handle($request, Closure $next, $permission)
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $permissions = explode('|', $permission);
        foreach ($permissions as $perm) {
            if (Auth::user()->hasPermission(trim($perm))) {
                return $next($request);
            }
        }

        return response()->view('errors.403', [], 403);
    }
}
