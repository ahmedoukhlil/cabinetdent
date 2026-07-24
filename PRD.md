# PRD — Refonte de l'application de gestion de cabinet (Jasmins)

**Version :** 1.1
**Date :** 2026-07-01 (mise à jour 2026-07-19)
**Statut :** Brouillon pour revue

---

## 0. État de l'upgrade technique (2026-07-19)

L'upgrade technique (Laravel 8 → 12, Livewire 2 → 3, laravel-mix → Vite) décrite comme prérequis en §4.2 est **terminée**, sans changement de comportement métier :

- **Laravel 8.83 → 12.64** : `app/Http/Kernel.php`/`app/Console/Kernel.php` migrés vers `bootstrap/app.php` ; `fruitcake/laravel-cors` retiré (CORS natif) ; `reliese/laravel` retiré (codegen dev-only inutilisé) ; `facade/ignition` → `spatie/laravel-ignition`.
- **Livewire 2.12 → 3.8** : tous les `emit()`/`emitUp()` migrés vers `dispatch()` (33 occurrences, 16 fichiers) ; 3 bugs de composant à racine multiple corrigés (`accueil-patient`, `consultation-form`, `reglement-facture`) ; revue complète des ~150 `wire:model` avec choix explicite `.live` (filtres, recherches, champs pilotant un `@if` conditionnel) vs déféré (formulaires classiques).
- **laravel-mix → Vite** : `vite.config.js` + `@tailwindcss/vite`, `@vite()` dans les 5 vues concernées (layout principal, login, 3 pages publiques QR-code patient).
- **Bugs préexistants corrigés au passage** (indépendants de l'upgrade) : 2 colonnes `double AUTO_INCREMENT` invalides dans le dump SQL de référence (incompatibles MySQL 8.4), ordre de migration incorrect sur 2 fichiers datés 2024 s'exécutant avant les `CREATE TABLE` dont ils dépendent.
- **Vérifié** : les 29 composants Livewire montent sans erreur, génération PDF (ordonnance, dossier médical, export statistiques) fonctionnelle avec DomPDF 3.1, boot HTTP complet testé (login, accueil-patient, assets Vite).
- **Non couvert par cette passe** : le refonte métier/workflow dentaire (§2 et suivants) reste à faire — cette mise à jour ne portait que sur la base technique.

---

## 1. Contexte et objectif

Jasmins est une application interne de gestion de cabinet dentaire/médical (Laravel 12 + Livewire 3, migré depuis Laravel 8 + Livewire 2 — voir §0), couvrant la prise de rendez-vous, les consultations, les ordonnances, la pharmacie/stock, la facturation et la caisse. L'application est en production et évolue par correctifs incrémentaux (ex. gestion des quantités de médicaments dans les ordonnances, séparation analyses/radios, impression forcée en noir).

**Objectif de la refonte :** repartir des besoins métier réels observés dans l'application actuelle pour livrer une version plus robuste, plus maintenable et plus ergonomique, sans perdre de fonctionnalités existantes ni de données historiques.

### Pourquoi refondre plutôt que continuer à patcher
- Accumulation de tables et de champs issus d'un ancien système (nombreuses tables préfixées `t_`, `bordereauxfactures`, `factureaimprimer`, `recuaimprimer`) qui mélangent logique métier et logique d'impression.
- Logique métier dispersée entre contrôleurs classiques et composants Livewire, avec des routes contenant de la logique directement inline dans `routes/web.php`.
- Absence de couverture de tests visible en dehors du socle Laravel par défaut.
- Dette d'UX accumulée (nombreuses pages `test-*`, `modal-demo` en production, corrections ad hoc CSS pour l'impression).

---

## 2. Périmètre fonctionnel actuel (à conserver au minimum)

### 2.0 Vue d'ensemble technique et ampleur

- **Stack :** Laravel 8.75 (PHP 8.1/8.2), Livewire 2.12, Alpine.js 3.14, Tailwind CSS 4, DomPDF (PDF), simple-qrcode (QR codes), MySQL.
- **Ampleur :** 61 modèles Eloquent, 33 composants Livewire (~33 écrans), 79 vues Blade, 71 migrations, ~356 fichiers source, 9 contrôleurs classiques.
- **Rôles utilisateurs (3) :** Secrétaire, Docteur, Docteur Propriétaire (accès complet), avec filtrage des données par médecin pour les docteurs non-propriétaires.
- **Workflow métier central :** Rendez-vous → Consultation → Ordonnance (médicament / analyse / radio) → Facture → Règlement (opération de caisse), avec gestion parallèle du stock pharmacie.
- **Multi-cabinet :** `fkidCabinet` présent sur la quasi-totalité des tables (patients, médecins, utilisateurs, stock...) — architecture déjà pensée pour plusieurs cabinets, à confirmer/exploiter ou simplifier selon le besoin réel.


### 2.1 Modules identifiés

| Module | Description | Permissions associées |
|---|---|---|
| **Authentification & utilisateurs** | Connexion, gestion des comptes, rôles/permissions granulaires (spatie-like, table `permissions`) | `user.view/create/edit/delete` |
| **Rendez-vous** | Création, liste, salle d'attente, impression, rappels (`RdvReminders`) | `rendez-vous.view/create/edit/delete/own` |
| **Patients** | Fiche patient, dossier médical, recherche | `patient.view/create/edit/delete` |
| **Consultations** | Création de consultation, historique, dossier médical, impression fiche/reçu | `consultation.view/create/edit` |
| **Ordonnances** | Prescription de médicaments (avec quantité), analyses, radios ; impression PDF | `ordonnance.view/create` |
| **Salle de soins** | Suivi des soins en attente/en cours pour ordonnances internes | `salle-soins.view` |
| **Pharmacie / Stock** | Gestion des médicaments, alertes stock faible/épuisé, consommables | `pharmacie.view/manage`, `stock.view/edit` |
| **Actes médicaux** | Catalogue d'actes, affectation aux patients, types d'actes | `act.view/create/edit/delete` |
| **Facturation** | Facture patient, détail facture, règlement, bordereaux, état de facture | `facture.view/create/edit/delete` |
| **Caisse / Finances** | Opérations de caisse, état journalier imprimable, soldes, dépenses/recettes | `caisse-operations.view`, `finances.view/create/edit/delete/own` |
| **Assurances** | Compagnies d'assurance, assureurs, conventions (pièces jointes) | `assureur.view/manage` |
| **Médecins** | Gestion des médecins, statistiques de paiement par médecin | `medecin.view/manage` |
| **Statistiques** | Tableau de bord statistiques du cabinet | `statistiques.view` |
| **Interface patient (QR code)** | Accès public tokenisé pour consulter rendez-vous/consultation | public |
| **Dépenses** | Suivi des dépenses/recettes du cabinet | `depenses.view/create/edit/delete` |
| **Paramètres cabinet** | Logo, pied de page, infos du cabinet | — |

### 2.2 Modèle de rôles observé
- Rôle **docteur propriétaire** vs **docteur** (filtrage des données par médecin pour les docteurs non-propriétaires).
- Permissions granulaires par entité et action (`view`, `create`, `edit`, `delete`, `own`, `manage`).
- Multi-cabinet potentiel (`fkidCabinet` présent sur de nombreuses tables) — à confirmer si le multi-tenant est un besoin réel ou un vestige.

### 2.3 Impression / documents
Tous les documents suivants doivent être réimprimables à l'identique ou en mieux :
- Reçu de rendez-vous
- Ordonnance (vierge et remplie), avec téléchargement PDF
- Dossier médical (par facture et par patient)
- Reçu de règlement de facture
- Historique des paiements patient
- État de caisse journalier

---

## 3. Problèmes connus à adresser dans la refonte

1. **Logique métier dans les routes** (`routes/web.php` contient des requêtes Eloquent directement) → doit migrer vers des contrôleurs/services dédiés.
2. **Tables et champs hérités peu clairs** (`recuaimprimer`, `factureaimprimer`, `ficheaimprimerautrefois`) → clarifier s'il s'agit de tables de cache d'impression à remplacer par génération à la volée (PDF via Dompdf, déjà présent).
3. **Pages de test en production** (`/test-api`, `/modal-demo`, `/animation-test`, `/test-modals`) → à retirer ou déplacer hors des routes authentifiées de prod.
4. **Corrections CSS ad hoc pour l'impression** (forcer le texte en noir via `@media print`) → repenser une feuille de style d'impression unifiée dès la conception.
5. **Absence de tests automatisés visibles** → définir une stratégie de tests (unitaires + fonctionnels) dès le début de la refonte.
6. **Gestion des quantités dans les ordonnances** ajoutée récemment par migrations successives → à consolider proprement dans le nouveau modèle de données plutôt que par ALTER TABLE successifs.
7. **Doublon de modèle utilisateur** (`User` et `TUser` coexistent) → à réconcilier en une seule entité utilisateur claire.
8. **Hub monolithique `AccueilPatient`** qui pilote plus de 20 modales Livewire depuis un seul composant → source probable de lenteur/complexité de maintenance, à décomposer en composants indépendants par domaine.
9. **Permissions à double mécanisme** (rôles statiques en fallback codés en dur + table `permissions`/`role_permissions` en base, avec cache d'1h) → unifier sur un seul mécanisme piloté par la base pour plus de flexibilité.
10. **Gestion de stock par lots complexe** (`LotMedicament`, `MouvementStock`, dates de péremption) déjà présente mais à valider : couverture réelle des besoins (alertes péremption, FEFO/FIFO) à confirmer avec le métier.
11. **Interface mêlant français et arabe** (ex. `ArabText` sur les actes) de façon partielle → définir une vraie stratégie d'internationalisation/bilinguisme si c'est un besoin confirmé, plutôt que des champs ad hoc.

---

## 4. Objectifs de la refonte

### 4.1 Objectifs fonctionnels
- Conserver 100% des fonctionnalités listées en §2.1, sans régression.
- Simplifier le cycle **rendez-vous → consultation → ordonnance → facturation → règlement** pour réduire le nombre de clics/écrans.
- Améliorer la traçabilité financière (caisse, factures, règlements) avec un état des lieux clair (rapprochement, exports).
- Améliorer la gestion de stock pharmacie (alertes déjà existantes → historique de mouvements, seuils configurables par cabinet).

### 4.2 Objectifs techniques
- Architecture Laravel propre : Controllers minces, logique dans des Services/Actions, Form Requests pour la validation.
- Remplacer ou consolider l'usage de Livewire vs contrôleurs classiques (choisir une approche cohérente, ex. tout Livewire ou introduire une API + SPA si le besoin de réactivité augmente).
- Couverture de tests : tests fonctionnels sur les parcours critiques (création RDV, consultation, facturation, règlement, impression).
- Nettoyage du schéma de base de données : suppression/fusion des tables obsolètes, migrations propres, seeders à jour.
- Génération de documents PDF unifiée (un seul mécanisme, pas de tables de "cache d'impression").

### 4.3 Objectifs non-fonctionnels
- Performance : temps de chargement des listes (patients, rendez-vous) sous forte volumétrie.
- Sécurité : revue des permissions, protection CSRF/XSS, audit des routes publiques (interface patient QR code).
- Auditabilité : journal des actions sensibles (modifications de factures, règlements, annulations).

---

## 5. Questions ouvertes (à valider avec le porteur métier)

- Le multi-cabinet (`fkidCabinet`) est-il un vrai besoin multi-site à conserver/renforcer, ou un vestige à simplifier ?
- Faut-il migrer vers une nouvelle stack (ex. Laravel + Inertia/Vue) ou rester sur Livewire ?
- Les tables `t_*` (comptabilité : `t_banque`, `t_exercice`, `t_souscompte`, etc.) sont-elles activement utilisées ou héritées d'un module comptable jamais finalisé ?
- Y a-t-il un besoin de gestion des utilisateurs multi-rôles (un même utilisateur avec plusieurs profils) ?
- L'interface patient publique (QR code) doit-elle être étendue (prise de RDV en ligne, paiement en ligne) ?

---

## 6. Prochaines étapes proposées

1. Valider ce périmètre avec les utilisateurs métier (médecins, réceptionnistes, comptable).
2. Prioriser les modules pour un découpage en lots (ex. Lot 1 : Patients/RDV/Consultations, Lot 2 : Ordonnances/Pharmacie, Lot 3 : Facturation/Caisse, Lot 4 : Statistiques/Admin).
3. Définir l'architecture cible (stack, structure de dossiers, stratégie de migration des données).
4. Écrire les spécifications détaillées (user stories) par module avant le développement.
