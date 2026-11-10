<?php

/*
 * Permissions par rôle. Le suffixe « .own » limite l'accès aux données du dentiste connecté.
 */
return [
    'proprietaire' => [
        'patient.view', 'patient.create', 'patient.edit', 'patient.delete',
        'rendez-vous.view', 'rendez-vous.create', 'rendez-vous.edit',
        'salle-attente.view', 'stock.manage', 'soins.manage',
        'dossier.view', 'dossier.edit',
        'facture.view', 'facture.edit', 'paiement.create', 'paiement.refund', 'caisse.view', 'depense.create', 'depense.delete', 'statistiques.view', 'tarifs.manage',
        'synthese.view', 'cabinet.manage', 'user.manage',
    ],
    'dentiste' => [
        'patient.view', 'patient.create', 'patient.edit',
        'rendez-vous.view', 'rendez-vous.create', 'rendez-vous.own',
        'salle-attente.view', 'stock.manage', 'soins.manage',
        'dossier.view', 'dossier.edit',
        'facture.view', 'facture.edit',
    ],
    'secretaire' => [
        'patient.view', 'patient.create', 'patient.edit',
        'rendez-vous.view', 'rendez-vous.create', 'rendez-vous.edit',
        'salle-attente.view', 'stock.manage',
        'facture.view', 'facture.edit', 'paiement.create', 'caisse.view', 'depense.create',
    ],
];
