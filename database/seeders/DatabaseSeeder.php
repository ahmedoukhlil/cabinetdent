<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\StatutRdv;
use App\Models\Cabinet;
use App\Models\Patient;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration (environnement local uniquement).
 * Comptes : proprietaire@demo.test, dentiste@demo.test, secretaire@demo.test
 * Mot de passe commun : sysdental-demo
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $cabinet = Cabinet::create(['nom' => 'Cabinet dentaire Démo', 'telephone' => '+222 45 00 00 00', 'adresse' => 'Nouakchott']);

        $comptes = [];
        foreach ([[Role::Proprietaire, 'Salem', 'proprietaire'], [Role::Dentiste, 'Khadija', 'dentiste'], [Role::Secretaire, 'Mariem', 'secretaire']] as [$role, $nom, $login]) {
            $comptes[$login] = User::create([
                'cabinet_id' => $cabinet->id,
                'name' => $nom,
                'email' => "{$login}@demo.test",
                'password' => 'sysdental-demo',
                'role' => $role,
            ]);
        }

        $noms = [['Ahmed', 'Aminetou'], ['Lemine', 'Mohamed'], ['Sidi', 'Fatimetou'], ['Ba', 'Cheikh'], ['Ould Ali', 'Brahim'], ['Diallo', 'Aïssata'], ['Vall', 'Zeinabou'], ['Sy', 'Oumar']];
        $patients = collect($noms)->map(fn ($n, $i) => Patient::create([
            'cabinet_id' => $cabinet->id,
            'nom' => $n[0],
            'prenom' => $n[1],
            'sexe' => $i % 2 ? 'M' : 'F',
            'telephone' => '+222 2'.str_pad((string) (1000000 + $i * 7919), 7, '0'),
            'date_naissance' => now()->subYears(20 + $i * 5)->subDays($i * 40),
        ]));

        $planning = [
            ['proprietaire', 0, '08:30', StatutRdv::Termine, 'Contrôle'],
            ['proprietaire', 1, '09:00', StatutRdv::EnCours, 'Composite 36'],
            ['proprietaire', 2, '09:30', StatutRdv::Arrive, 'Détartrage'],
            ['proprietaire', 3, '10:00', StatutRdv::Arrive, 'Extraction 48'],
            ['proprietaire', 4, '11:30', StatutRdv::Planifie, 'Couronne 21'],
            ['dentiste', 5, '09:00', StatutRdv::Arrive, 'Consultation'],
            ['dentiste', 6, '10:30', StatutRdv::Planifie, 'Dévitalisation 46'],
            ['dentiste', 7, '15:00', StatutRdv::Planifie, 'Contrôle'],
        ];

        foreach ($planning as $ordre => [$login, $p, $heure, $statut, $acte]) {
            RendezVous::create([
                'cabinet_id' => $cabinet->id,
                'patient_id' => $patients[$p]->id,
                'dentiste_id' => $comptes[$login]->id,
                'date' => today(),
                'heure' => "{$heure}:00",
                'ordre' => $ordre + 1,
                'acte_prevu' => $acte,
                'statut' => $statut,
                'arrive_at' => in_array($statut, [StatutRdv::Arrive, StatutRdv::EnCours, StatutRdv::Termine]) ? now()->subMinutes(40 - $ordre * 5) : null,
                'debut_at' => in_array($statut, [StatutRdv::EnCours, StatutRdv::Termine]) ? now()->subMinutes(15) : null,
                'fin_at' => $statut === StatutRdv::Termine ? now()->subMinutes(20) : null,
                'cree_par' => $comptes['secretaire']->id,
            ]);
        }

        // Catalogue d'actes et assurances de démonstration (tarifs fictifs).
        foreach ([['CS', 'Consultation', 1000], ['CSU', 'Consultation urgence', 2000], ['DET', 'Détartrage complet', 2500], ['C1', 'Composite 1 face', 2500], ['C2', 'Composite 2 faces', 3500],
            ['EXT', 'Extraction simple', 2000], ['EXC', 'Extraction chirurgicale', 5000], ['END', 'Traitement endodontique molaire', 9000],
            ['CC', 'Couronne céramo-métallique', 20000], ['RX', 'Radiographie rétro-alvéolaire', 800]] as [$code, $libelle, $prix]) {
            \App\Models\Acte::create(['cabinet_id' => $cabinet->id, 'code' => $code, 'libelle' => $libelle, 'type' => in_array($code, ['CS', 'CSU'], true) ? 'consultation' : 'soin', 'prix' => $prix]);
        }
        foreach ([['Gants nitrile M', 'protection', 'boîte', 5, 12, 450, null], ['Articaïne 4 % adrénalinée', 'anesthesie', 'carpule', 50, 30, 60, '+20 days'], ['Composite A2', 'restauration', 'seringue', 3, 2, 1800, '+8 months'], ['Limes K 25 mm', 'endodontie', 'boîte', 2, 0, 900, null]] as [$nom, $cat, $unite, $min, $q, $prix, $per]) {
            \App\Models\Consommable::create(['cabinet_id' => $cabinet->id, 'nom' => $nom, 'categorie' => $cat, 'unite' => $unite, 'quantite_min' => $min, 'quantite' => $q, 'prix_achat' => $prix, 'peremption' => $per ? now()->modify($per) : null]);
        }
        // Plateforme : formules, abonnement actif du cabinet de démo et administrateur (même mot de passe de démonstration).
        $formule = \App\Models\PlanAbonnement::create(['nom' => 'Cabinet', 'prix_mensuel' => 3000, 'max_utilisateurs' => 6, 'max_stockage_mo' => 5120]);
        \App\Models\PlanAbonnement::create(['nom' => 'Essentiel', 'prix_mensuel' => 1500, 'max_utilisateurs' => 2, 'max_stockage_mo' => 1024]);
        \App\Models\PlanAbonnement::create(['nom' => 'Clinique', 'prix_mensuel' => 6000]);
        \App\Models\Abonnement::create(['cabinet_id' => $cabinet->id, 'plan_id' => $formule->id, 'statut' => 'actif', 'periode_fin' => today()->addMonths(6)]);
        \App\Models\AdminPlateforme::create(['name' => 'Admin Sysdental', 'email' => 'plateforme@demo.test', 'password' => 'sysdental-demo']);

        $assurance = \App\Models\Assureur::create(['cabinet_id' => $cabinet->id, 'nom' => 'Assurance santé (démo)', 'taux_pec' => 0.8]);
        $patients[0]->update(['assureur_id' => $assurance->id, 'matricule_assure' => 'AS-10234']);

        // Dossier dentaire du patient au fauteuil.
        $dossier = app(\App\Services\DossierDentaireService::class);
        $faces = \App\Support\Odontologie::facesSaines();
        $base = ['presence' => 'presente', 'restauration' => 'aucune', 'endodontie' => 'non_traitee', 'constatations' => [], 'notes' => ''];
        $dossier->enregistrerDents($comptes['proprietaire'], $patients[1], [36], ['faces' => ['O' => 'carie', 'D' => 'carie'] + $faces] + $base);
        $dossier->enregistrerDents($comptes['proprietaire'], $patients[1], [16, 26], ['faces' => ['O' => 'obturation'] + $faces] + $base);
        $dossier->enregistrerDents($comptes['proprietaire'], $patients[1], [46], ['faces' => $faces, 'restauration' => 'couronne', 'endodontie' => 'traitee'] + $base);
        $dossier->enregistrerDents($comptes['proprietaire'], $patients[1], [38, 48], ['faces' => $faces, 'presence' => 'absente'] + $base);
        $dossier->enregistrerDents($comptes['proprietaire'], $patients[1], [31, 32, 41, 42], ['faces' => $faces, 'constatations' => ['tartre', 'gingivite']] + $base);
        $dossier->ajouterLigne($comptes['proprietaire'], $patients[1], ['libelle' => 'Composite 2 faces', 'quantite' => 1, 'prix_unitaire' => 3500, 'dents' => [36]]);
        $dossier->ajouterLigne($comptes['proprietaire'], $patients[1], ['libelle' => 'Détartrage complet', 'quantite' => 1, 'prix_unitaire' => 2500, 'dents' => []]);

        // Facture du patient vu ce matin (assuré), partiellement réglée.
        $facturation = app(\App\Services\FacturationService::class);
        $proprietaire = $comptes['proprietaire'];
        $facture = $facturation->brouillonPour($proprietaire, $patients[0]);
        $facturation->ajouterLigne($proprietaire, $facture, ['libelle' => 'Consultation', 'quantite' => 1, 'prix_unitaire' => 1000]);
        $facturation->ajouterLigne($proprietaire, $facture, ['libelle' => 'Détartrage complet', 'quantite' => 1, 'prix_unitaire' => 2500]);
        $facture = $facturation->valider($proprietaire, $facture);
        $facturation->enregistrerPaiement($proprietaire, $facture, ['payeur' => 'patient', 'type' => 'encaissement', 'mode' => 'especes', 'montant' => 700]);

        RendezVous::create([
            'cabinet_id' => $cabinet->id, 'patient_id' => $patients[0]->id, 'dentiste_id' => $comptes['proprietaire']->id,
            'date' => today()->addDay(), 'heure' => '09:00:00', 'ordre' => 1, 'acte_prevu' => 'Suivi', 'statut' => StatutRdv::Planifie,
        ]);
    }
}
