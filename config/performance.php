<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuration des performances
    |--------------------------------------------------------------------------
    |
    | Ce fichier contient les paramètres d'optimisation des performances
    | pour l'application Cabinet Savwa
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache des requêtes
    |--------------------------------------------------------------------------
    |
    | Configuration du cache des requêtes fréquentes
    |
    */
    'query_cache' => [
        'enabled' => env('QUERY_CACHE_ENABLED', true),
        'ttl' => env('QUERY_CACHE_TTL', 300), // 5 minutes
        'max_queries' => env('QUERY_CACHE_MAX', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Optimisations de base de données
    |--------------------------------------------------------------------------
    |
    | Paramètres pour optimiser les performances de la base de données
    |
    */
    'database' => [
        'enable_query_log' => env('DB_QUERY_LOG', false),
        'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 100), // ms
        'connection_pooling' => env('DB_CONNECTION_POOLING', false),
        'statement_timeout' => env('DB_STATEMENT_TIMEOUT', 30), // secondes
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache des composants Livewire
    |--------------------------------------------------------------------------
    |
    | Configuration du cache pour les composants Livewire
    |
    */
    'livewire' => [
        'cache_components' => env('LIVEWIRE_CACHE_COMPONENTS', true),
        'cache_queries' => env('LIVEWIRE_CACHE_QUERIES', true),
        'lazy_loading' => env('LIVEWIRE_LAZY_LOAD', true),
        'debounce_updates' => env('LIVEWIRE_DEBOUNCE', 150), // ms
    ],

    /*
    |--------------------------------------------------------------------------
    | Optimisations des vues
    |--------------------------------------------------------------------------
    |
    | Configuration pour optimiser le rendu des vues
    |
    */
    'views' => [
        'cache_compiled' => env('VIEWS_CACHE_COMPILED', true),
        'cache_rendered' => env('VIEWS_CACHE_RENDERED', false),
        'minify_html' => env('VIEWS_MINIFY_HTML', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache des modèles
    |--------------------------------------------------------------------------
    |
    | Configuration du cache pour les modèles Eloquent
    |
    */
    'models' => [
        'cache_relations' => env('MODELS_CACHE_RELATIONS', true),
        'cache_queries' => env('MODELS_CACHE_QUERIES', true),
        'cache_ttl' => env('MODELS_CACHE_TTL', 3600), // 1 heure
    ],

    /*
    |--------------------------------------------------------------------------
    | Optimisations des assets
    |--------------------------------------------------------------------------
    |
    | Configuration pour optimiser le chargement des assets
    |
    */
    'assets' => [
        'minify_css' => env('ASSETS_MINIFY_CSS', true),
        'minify_js' => env('ASSETS_MINIFY_JS', true),
        'combine_files' => env('ASSETS_COMBINE', true),
        'version_files' => env('ASSETS_VERSION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring des performances
    |--------------------------------------------------------------------------
    |
    | Configuration du monitoring des performances
    |
    */
    'monitoring' => [
        'enabled' => env('PERFORMANCE_MONITORING', true),
        'log_slow_queries' => env('LOG_SLOW_QUERIES', true),
        'log_memory_usage' => env('LOG_MEMORY_USAGE', false),
        'log_execution_time' => env('LOG_EXECUTION_TIME', true),
        'thresholds' => [
            'slow_query' => env('SLOW_QUERY_THRESHOLD', 100), // ms
            'memory_limit' => env('MEMORY_LIMIT_THRESHOLD', 128), // MB
            'execution_time' => env('EXECUTION_TIME_THRESHOLD', 1000), // ms
        ],
    ],

];
