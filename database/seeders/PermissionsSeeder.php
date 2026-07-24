<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Rendez-vous
            ['name' => 'rendez-vous.view',   'label' => 'Voir les rendez-vous',     'groupe' => 'Rendez-vous', 'ordre' => 1],
            ['name' => 'rendez-vous.create', 'label' => 'Créer des rendez-vous',    'groupe' => 'Rendez-vous', 'ordre' => 2],
            ['name' => 'rendez-vous.edit',   'label' => 'Modifier des rendez-vous', 'groupe' => 'Rendez-vous', 'ordre' => 3],
            ['name' => 'rendez-vous.delete', 'label' => 'Supprimer des rendez-vous','groupe' => 'Rendez-vous', 'ordre' => 4],
            ['name' => 'rendez-vous.own',    'label' => 'Voir ses propres RDV',     'groupe' => 'Rendez-vous', 'ordre' => 5],

            // Salle d'attente
            ['name' => 'salle-attente.view', 'label' => 'Voir la salle d\'attente', 'groupe' => 'Salle d\'attente', 'ordre' => 6],

            // Patients
            ['name' => 'patient.view',   'label' => 'Voir les patients',      'groupe' => 'Patients', 'ordre' => 10],
            ['name' => 'patient.create', 'label' => 'Créer des patients',     'groupe' => 'Patients', 'ordre' => 11],
            ['name' => 'patient.edit',   'label' => 'Modifier des patients',  'groupe' => 'Patients', 'ordre' => 12],
            ['name' => 'patient.delete', 'label' => 'Supprimer des patients', 'groupe' => 'Patients', 'ordre' => 13],

            // Consultations
            ['name' => 'consultation.view',   'label' => 'Voir les consultations',      'groupe' => 'Consultations', 'ordre' => 20],
            ['name' => 'consultation.create', 'label' => 'Créer des consultations',     'groupe' => 'Consultations', 'ordre' => 21],
            ['name' => 'consultation.edit',   'label' => 'Modifier des consultations',  'groupe' => 'Consultations', 'ordre' => 22],

            // Ordonnances
            ['name' => 'ordonnance.view',   'label' => 'Voir les ordonnances',    'groupe' => 'Ordonnances', 'ordre' => 30],
            ['name' => 'ordonnance.create', 'label' => 'Créer des ordonnances',   'groupe' => 'Ordonnances', 'ordre' => 31],

            // Plan de traitement dentaire
            ['name' => 'plan_traitement.view',   'label' => 'Voir le plan de traitement',   'groupe' => 'Plan de traitement', 'ordre' => 32],
            ['name' => 'plan_traitement.create', 'label' => 'Créer un plan de traitement',  'groupe' => 'Plan de traitement', 'ordre' => 33],

            // Dossier médical
            ['name' => 'dossier.view',   'label' => 'Voir le dossier médical',    'groupe' => 'Dossier médical', 'ordre' => 40],
            ['name' => 'dossier.print',  'label' => 'Imprimer le dossier médical','groupe' => 'Dossier médical', 'ordre' => 41],

            // Factures
            ['name' => 'facture.ligne.delete', 'label' => 'Supprimer un acte de la facture', 'groupe' => 'Factures', 'ordre' => 49],
            ['name' => 'facture.view',     'label' => 'Voir toutes les factures', 'groupe' => 'Factures', 'ordre' => 50],
            ['name' => 'facture.view.own', 'label' => 'Voir ses propres factures','groupe' => 'Factures', 'ordre' => 51],
            ['name' => 'facture.create',   'label' => 'Créer des factures',       'groupe' => 'Factures', 'ordre' => 52],
            ['name' => 'facture.edit',     'label' => 'Modifier des factures',    'groupe' => 'Factures', 'ordre' => 53],
            ['name' => 'facture.delete',   'label' => 'Supprimer des factures',   'groupe' => 'Factures', 'ordre' => 54],

            // Finances / Caisse
            ['name' => 'finances.view',          'label' => 'Voir toutes les finances',  'groupe' => 'Finances', 'ordre' => 60],
            ['name' => 'finances.own',           'label' => 'Voir ses propres finances', 'groupe' => 'Finances', 'ordre' => 61],
            ['name' => 'finances.create',        'label' => 'Créer des opérations',      'groupe' => 'Finances', 'ordre' => 62],
            ['name' => 'finances.edit',          'label' => 'Modifier des opérations',   'groupe' => 'Finances', 'ordre' => 63],
            ['name' => 'finances.delete',        'label' => 'Supprimer des opérations',  'groupe' => 'Finances', 'ordre' => 64],
            ['name' => 'caisse-operations.view', 'label' => 'Voir la caisse',            'groupe' => 'Finances', 'ordre' => 65],

            // Dépenses
            ['name' => 'depenses.view',   'label' => 'Voir les dépenses',     'groupe' => 'Dépenses', 'ordre' => 70],
            ['name' => 'depenses.create', 'label' => 'Créer des dépenses',    'groupe' => 'Dépenses', 'ordre' => 71],
            ['name' => 'depenses.edit',   'label' => 'Modifier des dépenses', 'groupe' => 'Dépenses', 'ordre' => 72],
            ['name' => 'depenses.delete', 'label' => 'Supprimer des dépenses','groupe' => 'Dépenses', 'ordre' => 73],

            // Statistiques
            ['name' => 'statistiques.view', 'label' => 'Voir les statistiques', 'groupe' => 'Statistiques', 'ordre' => 80],

            // Stock / Pharmacie
            ['name' => 'stock.view',   'label' => 'Voir le stock',          'groupe' => 'Stock', 'ordre' => 90],
            ['name' => 'stock.edit',   'label' => 'Modifier le stock',      'groupe' => 'Stock', 'ordre' => 91],
            ['name' => 'pharmacie.view',   'label' => 'Voir la pharmacie',  'groupe' => 'Stock', 'ordre' => 92],
            ['name' => 'pharmacie.manage', 'label' => 'Gérer la pharmacie', 'groupe' => 'Stock', 'ordre' => 93],

            // Actes
            ['name' => 'act.view',   'label' => 'Voir les actes',      'groupe' => 'Actes', 'ordre' => 100],
            ['name' => 'act.create', 'label' => 'Créer des actes',     'groupe' => 'Actes', 'ordre' => 101],
            ['name' => 'act.edit',   'label' => 'Modifier des actes',  'groupe' => 'Actes', 'ordre' => 102],
            ['name' => 'act.delete', 'label' => 'Supprimer des actes', 'groupe' => 'Actes', 'ordre' => 103],

            // Médecins (gestion cabinet)
            ['name' => 'medecin.view',   'label' => 'Voir les médecins',    'groupe' => 'Cabinet', 'ordre' => 110],
            ['name' => 'medecin.manage', 'label' => 'Gérer les médecins',   'groupe' => 'Cabinet', 'ordre' => 111],
            ['name' => 'assureur.view',  'label' => 'Voir les assureurs',   'groupe' => 'Cabinet', 'ordre' => 112],
            ['name' => 'assureur.manage','label' => 'Gérer les assureurs',  'groupe' => 'Cabinet', 'ordre' => 113],

            // Utilisateurs
            ['name' => 'user.view',   'label' => 'Voir les utilisateurs',      'groupe' => 'Utilisateurs', 'ordre' => 120],
            ['name' => 'user.create', 'label' => 'Créer des utilisateurs',     'groupe' => 'Utilisateurs', 'ordre' => 121],
            ['name' => 'user.edit',   'label' => 'Modifier des utilisateurs',  'groupe' => 'Utilisateurs', 'ordre' => 122],
            ['name' => 'user.delete', 'label' => 'Supprimer des utilisateurs', 'groupe' => 'Utilisateurs', 'ordre' => 123],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                array_merge($perm, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // Permissions par rôle par défaut
        $rolePermissions = [
            1 => [ // Secrétaire
                'rendez-vous.view',
                'rendez-vous.create',
                'rendez-vous.edit',
                'patient.view',
                'patient.create',
                'patient.edit',
                'consultation.view',
                'facture.view',
                'facture.create',
                'caisse-operations.view',
                'salle-attente.view',
                'dossier.view',
            ],
            2 => [ // Médecin
                'rendez-vous.view',
                'rendez-vous.own',
                'rendez-vous.create',
                'patient.view',
                'patient.edit',
                'consultation.view',
                'consultation.create',
                'consultation.edit',
                'ordonnance.view',
                'ordonnance.create',
                'plan_traitement.view',
                'plan_traitement.create',
                'dossier.view',
                'dossier.print',
                'facture.view.own',
                'finances.own',
                'act.create',
                'act.view',
                'salle-attente.view',
                'pharmacie.view',
                'stock.view',
            ],
            3 => [ // Propriétaire
                'facture.ligne.delete',
                'rendez-vous.view',
                'rendez-vous.create',
                'rendez-vous.edit',
                'rendez-vous.delete',
                'rendez-vous.own',
                'salle-attente.view',
                'patient.view',
                'patient.create',
                'patient.edit',
                'patient.delete',
                'consultation.view',
                'consultation.create',
                'consultation.edit',
                'ordonnance.view',
                'ordonnance.create',
                'plan_traitement.view',
                'plan_traitement.create',
                'dossier.view',
                'dossier.print',
                'facture.view',
                'facture.create',
                'facture.edit',
                'facture.delete',
                'finances.view',
                'finances.create',
                'finances.edit',
                'finances.delete',
                'caisse-operations.view',
                'depenses.view',
                'depenses.create',
                'depenses.edit',
                'depenses.delete',
                'statistiques.view',
                'stock.view',
                'stock.edit',
                'pharmacie.view',
                'pharmacie.manage',
                'act.view',
                'act.create',
                'act.edit',
                'act.delete',
                'medecin.view',
                'medecin.manage',
                'assureur.view',
                'assureur.manage',
                'user.view',
                'user.create',
                'user.edit',
                'user.delete',
            ],
        ];

        // Ne reconstruire que pour les rôles 1, 2, 3 (défaut) — ne pas écraser les rôles personnalisés
        foreach ($rolePermissions as $roleId => $perms) {
            // Supprimer uniquement les permissions des rôles par défaut
            DB::table('role_permissions')->where('role_id', $roleId)->delete();

            foreach ($perms as $permName) {
                $perm = DB::table('permissions')->where('name', $permName)->first();
                if ($perm) {
                    DB::table('role_permissions')->insertOrIgnore([
                        'role_id'       => $roleId,
                        'permission_id' => $perm->id,
                    ]);
                }
            }
        }

        // Invalider le cache pour les 3 rôles par défaut
        foreach ([1, 2, 3] as $roleId) {
            \Illuminate\Support\Facades\Cache::forget("role_permissions_{$roleId}");
        }
    }
}
