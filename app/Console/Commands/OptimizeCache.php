<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\PerformanceService;

class OptimizeCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:optimize {--flush : Vider complètement le cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimiser et nettoyer le cache de l\'application';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Début de l\'optimisation du cache...');

        if ($this->option('flush')) {
            $this->warn('🗑️  Vidage complet du cache...');
            Cache::flush();
            $this->info('✅ Cache vidé avec succès');
            return 0;
        }

        // Nettoyer le cache expiré
        $this->info('🧹 Nettoyage du cache expiré...');
        PerformanceService::cleanExpiredCache();
        $this->info('✅ Cache expiré nettoyé');

        // Précharger les données communes
        $this->info('📦 Préchargement des données communes...');
        PerformanceService::preloadCommonData();
        $this->info('✅ Données communes préchargées');

        // Statistiques du cache
        $this->info('📊 Statistiques du cache:');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Driver', config('cache.default')],
                ['TTL par défaut', config('app.cache_ttl', 3600) . ' secondes'],
                ['TTL requêtes', config('app.query_cache_ttl', 300) . ' secondes'],
            ]
        );

        $this->info('🎉 Optimisation terminée avec succès !');
        return 0;
    }
}
