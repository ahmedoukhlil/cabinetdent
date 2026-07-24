<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;

class PerformanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Optimiser les performances en production
        if (app()->environment('production')) {
            // Désactiver les logs de requêtes en production
            DB::disableQueryLog();
            
            // Optimiser le cache
            $this->optimizeCache();
        }

        // En développement, logger les requêtes lentes
        if (app()->environment('local', 'development')) {
            $this->logSlowQueries();
        }
    }

    /**
     * Optimiser le cache
     */
    private function optimizeCache(): void
    {
        // Précharger les données fréquemment utilisées
        $this->preloadFrequentData();
    }

    /**
     * Précharger les données fréquemment utilisées
     */
    private function preloadFrequentData(): void
    {
        // Mettre en cache les types de paiement
        Cache::remember('payment_types', 86400, function () {
            return \App\Models\RefTypePaiement::all();
        });

        // Mettre en cache les actes
        Cache::remember('actes', 86400, function () {
            return \App\Models\Acte::all();
        });
    }

    /**
     * Logger les requêtes lentes
     */
    private function logSlowQueries(): void
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
            if ($event->time > 100) { // Plus de 100ms
                \Log::warning('Requête lente détectée', [
                    'sql' => $event->sql,
                    'time' => $event->time,
                    'bindings' => $event->bindings,
                    'connection' => $event->connectionName
                ]);
            }
        });
    }
}
