<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Export complet d'un cabinet (documentation §14.2) : archive ZIP contenant
 * les données métier en JSON et les fichiers patients, limitée au cabinet.
 * Les éléments supprimés logiquement sont inclus, pour une archive fidèle.
 */
class CabinetExportService
{
    /** Tables portant directement `cabinet_id`. */
    private const TABLES = [
        'users', 'patients', 'rendez_vous', 'dents', 'dent_historiques', 'plans_traitement',
        'actes', 'assureurs', 'acte_tarifs', 'factures', 'paiements', 'depenses', 'fichiers_patient',
    ];

    /** Tables rattachées au cabinet via leur parent : table => [clé étrangère, table parente]. */
    private const ENFANTS = [
        'plan_traitement_lignes' => ['plan_traitement_id', 'plans_traitement'],
        'facture_lignes' => ['facture_id', 'factures'],
    ];

    /** Colonnes jamais exportées. */
    private const MASQUEES = ['users' => ['password', 'remember_token']];

    /** Crée l'archive dans un fichier temporaire et renvoie son chemin. */
    public function exporter(User $admin): string
    {
        if (!$admin->aPermission('cabinet.manage')) {
            throw new AuthorizationException('Action non autorisée.');
        }
        $cabinetId = $admin->cabinet_id;

        $chemin = tempnam(sys_get_temp_dir(), 'sysdental-export-');
        $zip = new ZipArchive();
        if ($zip->open($chemin, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossible de créer l\'archive.');
        }

        $comptes = [];
        $json = fn ($donnees) => json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $zip->addFromString('donnees/cabinet.json', $json(DB::table('cabinets')->where('id', $cabinetId)->first()));

        foreach (self::TABLES as $table) {
            $lignes = DB::table($table)->where('cabinet_id', $cabinetId)->orderBy('id')->get()
                ->map(fn ($l) => $this->masquer($table, (array) $l));
            $zip->addFromString("donnees/{$table}.json", $json($lignes));
            $comptes[$table] = $lignes->count();
        }

        foreach (self::ENFANTS as $table => [$cle, $parent]) {
            $lignes = DB::table($table)
                ->whereIn($cle, DB::table($parent)->where('cabinet_id', $cabinetId)->select('id'))
                ->orderBy('id')->get();
            $zip->addFromString("donnees/{$table}.json", $json($lignes));
            $comptes[$table] = $lignes->count();
        }

        $disque = Storage::disk('local');
        $fichiers = 0;
        foreach (DB::table('fichiers_patient')->where('cabinet_id', $cabinetId)->get(['id', 'chemin']) as $f) {
            // Le chemin est revérifié : uniquement sous le dossier du cabinet.
            if (str_starts_with($f->chemin, "cabinets/{$cabinetId}/") && !str_contains($f->chemin, '..') && $disque->exists($f->chemin)) {
                $zip->addFile($disque->path($f->chemin), 'fichiers/'.$f->chemin);
                $fichiers++;
            }
        }

        $zip->addFromString('LISEZMOI.txt', implode("\n", [
            'Export Sysdental',
            'Cabinet : '.DB::table('cabinets')->where('id', $cabinetId)->value('nom'),
            'Date : '.now()->format('d/m/Y H:i'),
            'Par : '.$admin->name,
            '',
            'donnees/ : une table par fichier JSON (éléments supprimés logiquement inclus, colonne deleted_at).',
            'fichiers/ : radios et documents, chemins identiques à la colonne chemin de fichiers_patient.',
            'Les mots de passe ne sont pas exportés.',
            '',
            ...collect($comptes)->map(fn ($n, $t) => "{$t} : {$n}")->values(),
            "fichiers physiques : {$fichiers}",
        ])."\n");

        $zip->close();

        return $chemin;
    }

    private function masquer(string $table, array $ligne): array
    {
        foreach (self::MASQUEES[$table] ?? [] as $colonne) {
            unset($ligne[$colonne]);
        }

        return $ligne;
    }
}
