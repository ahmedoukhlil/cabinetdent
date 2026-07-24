<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceService
{
    /**
     * Optimiser les requêtes avec cache intelligent
     */
    public static function cachedQuery($key, $callback, $ttl = null)
    {
        $ttl = $ttl ?? config('app.query_cache_ttl', 300);
        
        return Cache::remember($key, $ttl, function () use ($callback) {
            $startTime = microtime(true);
            $result = $callback();
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            // Log des requêtes lentes
            if ($executionTime > 100) {
                Log::warning('Slow cached query detected', [
                    'key' => $key,
                    'execution_time' => $executionTime . 'ms'
                ]);
            }
            
            return $result;
        });
    }

    /**
     * Optimiser le chargement des relations
     */
    public static function optimizeRelations($query, $relations)
    {
        foreach ($relations as $relation => $columns) {
            if (is_array($columns)) {
                $query->with([$relation => function ($q) use ($columns) {
                    $q->select($columns);
                }]);
            } else {
                $query->with($relation);
            }
        }
        
        return $query;
    }

    /**
     * Nettoyer le cache expiré de manière sécurisée
     */
    public static function cleanExpiredCache()
    {
        try {
            $keys = [
                'rdv_reminders_count_*',
                'medecins_for_reminders_*',
                'facture_*',
                'cabinet_info_*'
            ];

            foreach ($keys as $pattern) {
                // Utiliser une approche plus sûre pour nettoyer le cache
                Cache::flush();
                break; // Flush tout le cache pour éviter les erreurs
            }
        } catch (\Exception $e) {
            Log::warning('Erreur lors du nettoyage du cache', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Optimiser les requêtes de pagination
     */
    public static function optimizePagination($query, $perPage = null)
    {
        $perPage = $perPage ?? config('app.pagination_per_page', 15);
        
        // Ajouter des index hints si nécessaire
        $query->orderBy('created_at', 'desc');
        
        return $query->paginate($perPage);
    }

    /**
     * Précharger les données fréquemment utilisées
     */
    public static function preloadCommonData()
    {
        $userId = auth()->id();
        if (!$userId) return;

        // Précharger les données du cabinet
        Cache::remember("cabinet_info_{$userId}", 3600, function () {
            $user = auth()->user();
            return [
                'NomCabinet' => $user->cabinet->NomCabinet ?? 'SysMedical',
                'Adresse' => $user->cabinet->Adresse ?? '',
                'Telephone' => $user->cabinet->Telephone ?? ''
            ];
        });
    }
}
