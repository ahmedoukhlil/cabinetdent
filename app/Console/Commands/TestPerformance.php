<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Facture;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TestPerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:performance {--patient=} {--iterations=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test des performances des requêtes de factures';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Test des performances des factures...');
        
        $patientId = $this->option('patient');
        $iterations = (int) $this->option('iterations');
        
        if (!$patientId) {
            // Prendre le premier patient disponible
            $patient = Patient::first();
            if (!$patient) {
                $this->error('Aucun patient trouvé dans la base de données');
                return 1;
            }
            $patientId = $patient->ID;
        }
        
        $this->info("Patient ID: {$patientId}");
        $this->info("Nombre d'itérations: {$iterations}");
        
        // Test 1: Requête simple sans préchargement
        $this->testSimpleQuery($patientId, $iterations);
        
        // Test 2: Requête avec préchargement
        $this->testEagerLoading($patientId, $iterations);
        
        // Test 3: Requête avec cache
        $this->testCachedQuery($patientId, $iterations);
        
        // Test 4: Analyse des index
        $this->analyzeIndexes();
        
        $this->info('✅ Tests de performance terminés');
        
        return 0;
    }
    
    private function testSimpleQuery($patientId, $iterations)
    {
        $this->info("\n📊 Test 1: Requête simple sans préchargement");
        
        $startTime = microtime(true);
        $totalQueries = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            DB::enableQueryLog();
            
            $factures = Facture::where('IDPatient', $patientId)->get();
            
            foreach ($factures as $facture) {
                // Simuler le chargement des détails (requête N+1)
                $details = DB::table('detailfacturepatient')
                    ->where('fkidfacture', $facture->Idfacture)
                    ->get();
            }
            
            $totalQueries += count(DB::getQueryLog());
            DB::disableQueryLog();
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // en millisecondes
        
        $this->info("   Durée moyenne: " . number_format($duration / $iterations, 2) . "ms");
        $this->info("   Requêtes moyennes: " . number_format($totalQueries / $iterations, 1));
    }
    
    private function testEagerLoading($patientId, $iterations)
    {
        $this->info("\n📊 Test 2: Requête avec préchargement (eager loading)");
        
        $startTime = microtime(true);
        $totalQueries = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            DB::enableQueryLog();
            
            $factures = Facture::where('IDPatient', $patientId)
                ->with(['medecin', 'details'])
                ->get();
            
            $totalQueries += count(DB::getQueryLog());
            DB::disableQueryLog();
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        
        $this->info("   Durée moyenne: " . number_format($duration / $iterations, 2) . "ms");
        $this->info("   Requêtes moyennes: " . number_format($totalQueries / $iterations, 1));
    }
    
    private function testCachedQuery($patientId, $iterations)
    {
        $this->info("\n📊 Test 3: Requête avec cache");
        
        $startTime = microtime(true);
        $totalQueries = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            DB::enableQueryLog();
            
            $factures = Cache::remember("test_factures_{$patientId}", 300, function() use ($patientId) {
                return Facture::where('IDPatient', $patientId)
                    ->with(['medecin', 'details'])
                    ->get();
            });
            
            $totalQueries += count(DB::getQueryLog());
            DB::disableQueryLog();
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        
        $this->info("   Durée moyenne: " . number_format($duration / $iterations, 2) . "ms");
        $this->info("   Requêtes moyennes: " . number_format($totalQueries / $iterations, 1));
    }
    
    private function analyzeIndexes()
    {
        $this->info("\n📊 Test 4: Analyse des index");
        
        $tables = ['facture', 'patients', 'detailfacturepatient', 'medecins'];
        
        foreach ($tables as $table) {
            try {
                $indexes = DB::select("SHOW INDEX FROM {$table}");
                $this->info("   Table {$table}: " . count($indexes) . " index(es)");
                
                foreach ($indexes as $index) {
                    if ($index->Key_name !== 'PRIMARY') {
                        $this->line("     - {$index->Key_name}: {$index->Column_name}");
                    }
                }
            } catch (\Exception $e) {
                $this->warn("   Table {$table}: Erreur lors de l'analyse des index");
            }
        }
    }
}
