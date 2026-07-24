<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OptimizeQueries
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Optimiser les requêtes de base de données
        DB::connection()->enableQueryLog();
        
        // Activer le cache des requêtes si configuré
        if (config('app.env') === 'production') {
            DB::connection()->enableQueryCache();
        }

        $response = $next($request);

        // Log des requêtes lentes en développement
        if (config('app.debug')) {
            $queries = DB::getQueryLog();
            $slowQueries = collect($queries)->filter(function ($query) {
                return $query['time'] > 100; // Plus de 100ms
            });

            if ($slowQueries->isNotEmpty()) {
                \Log::warning('Slow queries detected', [
                    'queries' => $slowQueries->toArray(),
                    'url' => $request->fullUrl()
                ]);
            }
        }

        return $response;
    }
}
