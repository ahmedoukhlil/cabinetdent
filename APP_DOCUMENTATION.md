# Cabinet Savwa — Documentation Complète

> Mise à jour : 2026-04-07 | Stack : Laravel 10 · Livewire 2 · Tailwind CSS · Alpine.js

---

## Table des Matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Fonctionnalités et Modules](#2-fonctionnalités-et-modules)
3. [Workflow Complet](#3-workflow-complet)
4. [Inventaire des Composants](#4-inventaire-des-composants)
5. [Routes de l'Application](#5-routes-de-lapplication)
6. [Modèles de Données](#6-modèles-de-données)
7. [Incohérences UI/UX](#7-incohérences-uiux)
8. [Problèmes de Workflow](#8-problèmes-de-workflow)
9. [Corrections Apportées](#9-corrections-apportées)
10. [Récapitulatif des Issues par Priorité](#10-récapitulatif-des-issues-par-priorité)

---

## 1. Vue d'ensemble

**Cabinet Savwa** est un système de gestion de cabinet médical mono-page (SPA-like via Livewire). Toute l'interface est pilotée depuis un unique composant parent **`AccueilPatient`** qui ouvre des sous-modales pour chaque fonctionnalité. Il n'y a **pas** de navigation multi-pages traditionnelle sauf pour l'authentification, les impressions PDF et le portail patient public.

**Modules principaux :**
- Gestion des patients et dossiers
- Planification et file d'attente des rendez-vous
- Consultation et facturation (avec assurance)
- Règlement et caisse journalière
- Prescriptions et ordonnances
- Gestion du stock médicaments/analyses/radios
- Statistiques financières par médecin
- Portail patient public (accès par QR code)
- Gestion des utilisateurs, médecins, assureurs, actes

---

## 2. Fonctionnalités et Modules

### 2.1 Tableau de Bord Principal (AccueilPatient)

**Composant :** `AccueilPatient`
**Fichiers :** `app/Http/Livewire/AccueilPatient.php` / `accueil-patient.blade.php`
**Route :** `GET /accueil-patient`

C'est le **hub central** de l'application. Il gère 20+ états de modales et coordonne la communication entre tous les sous-composants via des événements Livewire.

| État de modale | Sous-composant ouvert |
|---|---|
| `showCreateRdvModal` | `CreateRendezVous` |
| `showRendezVous` | `RendezVousManager` |
| `showConsultation` | `ConsultationForm` |
| `showReglement` | `ReglementFacture` |
| `showOrdonnanceModal` | `OrdonnanceManager` |
| `showCaisseOperations` | `CaisseOperationsManager` |
| `showDepenses` | `DepensesManager` |
| `showStatistiques` | `StatistiquesManager` |
| `showDashboardStock` | `MedicamentManager` / `PharmacieManager` |
| `showUsersModal` | `UserManager` |
| `showMedecinsModal` | `MedecinManager` |
| `showCreateActeModal` | `ActeCreate` |
| `showListeActesModal` | `ListeActes` / `ActeManager` |
| `showAssureurModal` | `AssureurManager` |
| `showTypePaiementModal` | `TypePaiementManager` |

**Rôles et permissions :**
- `isDocteurProprietaire` : accès complet
- `isSecretaire` : gestion patients, RDV, règlements
- `isDocteur` : consultation, ordonnance, dossier médical

---

### 2.2 Gestion des Patients

**Composant :** `PatientManager`
**Fichiers :** `app/Http/Livewire/PatientManager.php` / `patient-manager.blade.php`
**Routes :** `GET /patients`, `GET /patients/create`

| Fonctionnalité | Description |
|---|---|
| Création | Nom, prénom, NNI, date de naissance, genre, téléphones, adresse, matricule |
| Modification | Édition depuis la liste |
| Recherche | Par nom, prénom, téléphone, NNI |
| Assurance | Liaison assureur, identifiant, taux de prise en charge |
| Historique paiements | Modale via `HistoriquePaiement` |
| Activation | Champ `choix` (actif/inactif) |

**Recherche patient rapide :** `PatientSearch` — composant autonome utilisé dans `ReglementFacture` et `ConsultationForm` pour sélectionner un patient via autocomplete.

**Champs du modèle `Patient` :**
`ID, Prenom, Nom, NNI, DtNaissance, Genre, Telephone1, Telephone2, Adresse, MatriculeFonct, NomContact, IdentifiantAssurance, Assureur (FK), TauxPEC, choix, IdentifiantPatient, fkidcabinet`

**Relations (après correction) :**
- `assureur()` → belongsTo Assureur
- `rendezvous()` → hasMany Rendezvou (FK : fkidPatient)
- `factures()` → hasMany Facture (FK : IDPatient)

---

### 2.3 Gestion des Rendez-Vous

**Composants :** `CreateRendezVous`, `RendezVousManager`, `RdvReminders`
**Routes :** `GET /rendez-vous`, `GET /rendez-vous/create`, `GET /rendez-vous/print/{id}`

| Fonctionnalité | Description |
|---|---|
| Prise de RDV | Date, heure, patient, médecin, acte prévu |
| Statuts | En Attente → Confirmé → En cours → Terminé / Annulé |
| Ordre de passage | `generateNextOrderNumber()` — anti-collision avec verrou DB |
| File d'attente | Vue en temps réel triée par `OrdreRDV` |
| QR Code | Token signé HMAC-SHA256 pour le portail patient |
| Filtres | Par médecin, date, statut |
| Rappels RDV | `RdvReminders` — compteur de RDV à rappeler en attente |
| Impression | Bon de RDV (`GET /rendez-vous/print/{id}`) |

**Cycle de vie d'un RDV :**
```
Créé (En Attente)
  └─> Patient arrive      → Confirmé   (bouton bleu)
  └─> Entre chez médecin  → En cours   (bouton vert)
  └─> Consultation finie  → Terminé    (bouton gris)
  └─> Non venu            → Annulé     (bouton rouge)
```

**Modèle `Rendezvou` :** `IDRdv, fkidPatient, fkidMedecin, dtPrevuRDV, HeureRdv, ActePrevu, rdvConfirmer, OrdreRDV, fkidFacture, fkidcabinet`

---

### 2.4 Consultation et Facturation

**Composant :** `ConsultationForm`
**Route :** `GET /consultations/create`

| Fonctionnalité | Description |
|---|---|
| Type | Généraliste / Spécialiste (radio) |
| Sélection acte | Autocomplete depuis la base `Acte`, montant pré-rempli |
| Mode de paiement | Depuis `RefTypePaiement` |
| Assurance | Calcul automatique part patient / part assurance |
| Création en transaction | Facture + DétailFacture + FicheTraitement + CaisseOperation + Rendezvou |
| Numéro facture unique | `generateUniqueFactureNumber()` avec verrou transactionnel |
| Reçu | Ouverture automatique du reçu PDF dans un nouvel onglet après sauvegarde |
| Indicateur de chargement | Spinner + désactivation du bouton pendant `save()` ✅ (corrigé) |

**Structure d'une facture assurée :**
```
TotFacture         = montant total
TotalPEC           = TotFacture × TXPEC     (part assurance)
TotalfactPatient   = TotFacture × (1–TXPEC) (part patient)
Reste patient      = TotalfactPatient – TotReglPatient
Reste assurance    = TotalPEC – ReglementPEC
```

---

### 2.5 Règlement et Paiement des Factures

**Composant :** `ReglementFacture`
**Route :** `GET /reglement-facture`

| Fonctionnalité | Description |
|---|---|
| Recherche de factures | Par patient (via `PatientSearch`) |
| Détails facture | Tableau des actes/médicaments avec total |
| Ajout d'actes | Formulaire modale avec recherche d'acte (`ActeSearch`) |
| Ajout médicaments/analyses/radios | Depuis stock, avec décrémentation automatique |
| Modes de paiement | Espèce, virement, chèque, assurance |
| Suivi des règlements | Part patient (`TotReglPatient`) + Part assurance (`ReglementPEC`) |
| Statuts de facture | Non réglée (rouge) / À rembourser (jaune) / Réglée (vert) |
| Nouvelle facture | Modale sélection médecin → création consultation directe |
| Dossier médical | Accès modale `DossierMedicalManager` depuis la facture |
| Reçu de règlement | `GET /reglement-facture/recu/{operation}` |
| Impression facture | `GET /facture-patient/{facture}` |
| Suppression facture | Réservée `isDocteurProprietaire` — restaure le stock |

---

### 2.6 Caisse Journalière

**Composants :** `CaisseOperationsManager`, `DepensesManager`
**Route :** `GET /caisse-operations`

| Fonctionnalité | Description |
|---|---|
| Entrées | `entreEspece` — paiements patients |
| Sorties | `retraitEspece` — remboursements / ajustements |
| Dépenses | `DepensesManager` — saisie des dépenses du cabinet |
| Solde du jour | Calcul en temps réel |
| Filtres | Par médecin, date |
| État de caisse | Impression PDF journalier (`GET /caisse/etat-journalier/{date?}`) |
| Accès par rôle | Secrétaire / Docteur / Docteur Propriétaire |

---

### 2.7 Ordonnances et Prescriptions

**Composant :** `OrdonnanceManager`
**Routes :** `GET /ordonnances/{id}/print`, `GET /ordonnances/{id}/download`, `GET /ordonnances/blank`

| Fonctionnalité | Description |
|---|---|
| Création | Lignes prescription : médicament, analyses, radios |
| Autocomplete médicaments | Via `MedicamentSearch` |
| Types | Médicament (1) / Analyse (2) / Radio (3) |
| Impression | PDF avec données patient |
| Téléchargement | Export PDF |
| Ordonnance vierge | Template sans données patient |

---

### 2.8 Dossier Médical

**Composant :** `DossierMedicalManager`
**Routes :** `GET /dossier-medical/{factureId}/print`, `GET /dossier-medical/{factureId}/download`

| Fonctionnalité | Description |
|---|---|
| Fiche de traitement | Notes liées à une facture (`Fichetraitement`) |
| Historique | Épisodes de traitement du patient |
| Impression / Téléchargement | Export PDF |

---

### 2.9 Gestion du Stock (Médicaments)

**Composants :** `MedicamentManager`, `PharmacieManager`, `MedicamentSearch`

| Fonctionnalité | Description |
|---|---|
| Types d'articles | Médicament (1) / Analyse (2) / Radio (3) |
| Stock | Quantités par lot, dates de péremption |
| Fournisseurs | Liaison fournisseur |
| Mouvements | Entrées/sorties de stock (`MouvementStock`) |
| Lots | Gestion par numéro de lot (`LotMedicament`) |
| Consommables | Modèle `Consommable` séparé |

**Modèles :** `Medicament`, `StockMedicament`, `LotMedicament`, `MouvementStock`, `Consommable`

---

### 2.10 Statistiques Financières

**Composant :** `StatistiquesManager`, `MedecinPaiementStats`
**Route :** `GET /statistiques`

| Fonctionnalité | Description |
|---|---|
| Tableau de bord | Revenus par période |
| Filtres | Par médecin, plage de dates |
| Export PDF | Rapport statistique |
| Cumul | Statistiques cumulatives |
| Stats par médecin | `MedecinPaiementStats` — détail par praticien |

---

### 2.11 Administration Système

**Composants :** `UserManager`, `MedecinManager`, `AssureurManager`, `AssureurCreate`, `ActeManager`, `ActeCreate`, `ListeActes`, `TypePaiementManager`
**Route :** `GET /users`

| Composant | Rôle |
|---|---|
| `UserManager` | CRUD utilisateurs et rôles |
| `MedecinManager` | CRUD médecins du cabinet |
| `AssureurManager` / `AssureurCreate` | CRUD assureurs / compagnies |
| `ActeManager` / `ActeCreate` / `ListeActes` | CRUD actes médicaux et tarifs |
| `TypePaiementManager` | Gestion des modes de paiement |

---

### 2.12 Portail Patient Public (QR Code)

**Contrôleur :** `PatientInterfaceController`
**Routes publiques (sans authentification) :**

| Route | Description |
|---|---|
| `GET /patient/rendez-vous/{token}` | File d'attente : position, temps estimé, patient en cours |
| `GET /patient/consultation/{token}` | Historique consultations du patient |

**Format du token (après correction S-1) :**
```
token = base64url(payload) + "." + base64url(HMAC-SHA256(payload, APP_KEY))
payload = "patientId|YYYY-MM-DD|medecinId"
```
- Rétrocompatible avec les anciens tokens base64 simples (sans vérification de signature).
- La vérification de signature empêche l'énumération de patients.

---

### 2.13 Historique Paiements

**Composant :** `HistoriquePaiement`
**Route :** `GET /historique-paiement/print/{patient}`

Modale affichant l'intégralité des règlements d'un patient, imprimable en PDF.

---

## 3. Workflow Complet

### 3.1 Parcours Patient Standard

```
┌──────────────────────────────────────────────────────────────────┐
│  1. Enregistrement Patient (PatientManager)                       │
│     └─> Saisie : nom, prénom, téléphone, NNI, assurance           │
│                                                                   │
│  2. Prise de Rendez-Vous (CreateRendezVous)                       │
│     └─> Sélection : patient, médecin, date, heure                 │
│     └─> Statut : "En Attente" — OrdreRDV assigné                  │
│     └─> QR Code généré (token signé HMAC)                         │
│                                                                   │
│  3. Accueil Patient (RendezVousManager)                           │
│     └─> Patient arrive → "Confirmé"                               │
│     └─> Patient entre  → "En cours"                               │
│                                                                   │
│  4. Consultation (ConsultationForm)                               │
│     └─> Type + acte + mode paiement                               │
│     └─> Calcul assurance automatique                              │
│     └─> Transaction : Facture + Détail + FicheTraitement           │
│                    + CaisseOperation + Rendezvou                  │
│     └─> Reçu PDF ouvert automatiquement                           │
│                                                                   │
│  5. [Optionnel] Prescription (OrdonnanceManager)                  │
│     └─> Ajout lignes → Impression / Téléchargement PDF            │
│                                                                   │
│  6. Règlement (ReglementFacture)                                  │
│     └─> Ajout actes/médicaments supplémentaires si besoin         │
│     └─> Enregistrement paiement → Reçu de règlement               │
│     └─> Statut facture → "Réglée"                                 │
│                                                                   │
│  7. Fin de consultation                                           │
│     └─> Statut RDV → "Terminé"                                    │
│     └─> [Optionnel] Impression dossier médical                    │
└──────────────────────────────────────────────────────────────────┘
```

### 3.2 Cycle de Vie d'une Facture

```
ConsultationForm.save()
  │
  └─> Facture créée (TotReglPatient = TotalfactPatient à la création)
        │
        ├─> Ajout actes/médicaments (ReglementFacture)
        │     └─> TotFacture recalculé
        │
        ├─> Règlement(s) enregistrés
        │     ├─> TotReglPatient  += paiement patient
        │     └─> ReglementPEC   += remboursement assurance
        │
        └─> Statut calculé dynamiquement :
              Reste = TotalfactPatient – TotReglPatient
              ├─ Reste > 0  → "Non réglée"   (rouge)
              ├─ Reste = 0  → "Réglée"       (vert)
              └─ Reste < 0  → "À rembourser" (jaune)
```

### 3.3 Flux de la Caisse Journalière

```
Paiements patients    → CaisseOperation.entreEspece
Dépenses cabinet      → CaisseOperation.retraitEspece / DepensesManager

Solde = Σ(entreEspece) – Σ(retraitEspece)

Impression → GET /caisse/etat-journalier/{date?}
```

### 3.4 Architecture de l'Interface (Modale-dans-Modale)

```
/accueil-patient
  └─> AccueilPatient (composant racine)
        ├─> PatientSearch         (sélection patient global)
        ├─> RdvReminders          (compteur de rappels)
        │
        ├── Modales principales (chacune est un composant Livewire)
        │     ├─ ConsultationForm
        │     ├─ RendezVousManager
        │     │    └─ CreateRendezVous (sous-onglet)
        │     ├─ ReglementFacture
        │     │    ├─ ActeSearch
        │     │    └─ MedicamentSearch
        │     ├─ OrdonnanceManager
        │     │    └─ MedicamentSearch
        │     ├─ CaisseOperationsManager
        │     ├─ DepensesManager
        │     ├─ StatistiquesManager
        │     │    └─ MedecinPaiementStats
        │     ├─ MedicamentManager / PharmacieManager
        │     │
        │     └── Modales admin (Propriétaire uniquement)
        │           ├─ UserManager
        │           ├─ MedecinManager
        │           ├─ AssureurManager / AssureurCreate
        │           ├─ ActeManager / ActeCreate / ListeActes
        │           └─ TypePaiementManager
        │
        └── Pages séparées (print/PDF)
              ├─ /consultations/{id}/receipt
              ├─ /facture-patient/{facture}
              ├─ /ordonnances/{id}/print|download
              ├─ /dossier-medical/{id}/print|download
              ├─ /caisse/etat-journalier/{date}
              ├─ /historique-paiement/print/{patient}
              └─ /rendez-vous/print/{id}
```

---

## 4. Inventaire des Composants

### Composants Livewire (28 composants)

| Composant | Fichier PHP | Rôle |
|---|---|---|
| `AccueilPatient` | AccueilPatient.php | Hub central — orchestrateur de toutes les modales |
| `ConsultationForm` | ConsultationForm.php | Créer consultations et factures |
| `PatientManager` | PatientManager.php | CRUD patients |
| `PatientSearch` | PatientSearch.php | Autocomplete sélection patient |
| `RendezVousManager` | RendezVousManager.php | Gérer la file de RDV |
| `CreateRendezVous` | CreateRendezVous.php | Créer un RDV |
| `RdvReminders` | RdvReminders.php | Compteur de rappels de RDV |
| `ReglementFacture` | ReglementFacture.php | Paiements et détails facture |
| `CaisseOperationsManager` | CaisseOperationsManager.php | Caisse journalière |
| `DepensesManager` | DepensesManager.php | Gestion des dépenses |
| `OrdonnanceManager` | OrdonnanceManager.php | Prescriptions |
| `MedicamentManager` | MedicamentManager.php | Stock médicaments |
| `MedicamentSearch` | MedicamentSearch.php | Autocomplete médicaments |
| `PharmacieManager` | PharmacieManager.php | Gestion pharmacie/consommables |
| `DossierMedicalManager` | DossierMedicalManager.php | Dossier médical patient |
| `StatistiquesManager` | StatistiquesManager.php | Statistiques financières |
| `MedecinPaiementStats` | MedecinPaiementStats.php | Stats paiements par médecin |
| `HistoriquePaiement` | HistoriquePaiement.php | Historique paiements patient |
| `UserManager` | UserManager.php | CRUD utilisateurs |
| `MedecinManager` | MedecinManager.php | CRUD médecins |
| `AssureurManager` | AssureurManager.php | CRUD assureurs |
| `AssureurCreate` | AssureurCreate.php | Formulaire création assureur |
| `ActeManager` | ActeManager.php | CRUD actes médicaux |
| `ActeCreate` | ActeCreate.php | Formulaire création acte |
| `ActeSearch` | ActeSearch.php | Autocomplete actes |
| `ListeActes` | ListeActes.php | Liste actes (lecture) |
| `TypePaiementManager` | TypePaiementManager.php | CRUD modes de paiement |
| `Auth` | Auth.php | Formulaire de connexion |

### Contrôleurs (8 contrôleurs)

| Contrôleur | Responsabilité |
|---|---|
| `AuthController` | Authentification (login/logout) |
| `ConsultationController` | Vues consultation, reçus, ordonnances, facture patient |
| `PaiementController` | Historique paiements — impression |
| `OrdonnanceController` | Impression/téléchargement ordonnances |
| `DossierMedicalController` | Impression/téléchargement dossier médical |
| `CaisseController` | État de caisse journalier — impression |
| `ReglementFactureController` | Reçu de règlement |
| `PatientInterfaceController` | Portail patient public (token QR) |

---

## 5. Routes de l'Application

### Routes Authentifiées (middleware `auth`)

| Méthode | Route | Nom | Permission |
|---|---|---|---|
| GET | `/accueil-patient` | `accueil.patient` | auth |
| GET | `/login` | `login` | public |
| POST | `/login` | — | public |
| POST | `/logout` | `logout` | auth |
| GET | `/rendez-vous` | `rendez-vous` | `rendez-vous.view` |
| GET | `/rendez-vous/create` | `rendez-vous.create` | `rendez-vous.create` |
| GET | `/rendez-vous/print/{id}` | `rendez-vous.print` | `rendez-vous.view` |
| GET | `/patients` | `patients.index` | `patient.view` |
| GET | `/patients/create` | `patients.create` | `patient.create` |
| GET | `/consultations/create` | `consultations.create` | `patient.view` |
| POST | `/consultations` | `consultations.store` | `patient.view` |
| GET | `/consultations/{id}` | `consultations.show` | `patient.view` |
| GET | `/consultations/{facture}/receipt` | `consultations.receipt` | `patient.view` |
| GET | `/facture-patient/{facture}` | `consultations.facture-patient` | `patient.view` |
| GET | `/ordonnance/{consultation}` | `consultations.ordonnance` | `patient.view` |
| GET | `/ordonnances/blank` | `ordonnance.blank` | auth |
| GET | `/ordonnances/{id}/print` | `ordonnance.print` | auth |
| GET | `/ordonnances/{id}/download` | `ordonnance.download` | auth |
| GET | `/dossier-medical/{factureId}/print` | `dossier-medical.print` | auth |
| GET | `/dossier-medical/{factureId}/download` | `dossier-medical.download` | auth |
| GET | `/reglement-facture` | `reglement-facture` | auth |
| GET | `/reglement-facture/recu/{operation}` | `reglement-facture.receipt` | auth |
| GET | `/caisse-operations` | `caisse-operations` | `caisse-operations.view` |
| GET | `/caisse/etat-journalier/{date?}` | `caisse.etat-journalier` | auth |
| GET | `/historique-paiement/print/{patient}` | `paiement.print-historique` | auth |
| GET | `/statistiques` | `statistiques` | auth |
| GET | `/users` | `users.index` | `user.view` |

### Routes de Développement (à supprimer en production)

| Route | Nom | Note |
|---|---|---|
| `GET /test-api` | `test.api` | Page de test API — inutile en prod |
| `GET /modal-demo` | `modal.demo` | Démo modales — inutile en prod |
| `GET /animation-test` | `animation.test` | Test animations — inutile en prod |
| `GET /test-modals` | `test.modals` | Test modales harmonisées — inutile en prod |

### Routes Publiques (Portail Patient — sans auth)

| Méthode | Route | Nom |
|---|---|---|
| GET | `/patient/rendez-vous/{token}` | `patient.rendez-vous` |
| GET | `/patient/consultation/{token}` | `patient.consultation` |

---

## 6. Modèles de Données

### Relations Complètes (après corrections)

```
Patient
  ├── belongsTo → Assureur          (FK: Assureur → IDAssureur)
  ├── hasMany   → Rendezvou         (FK: fkidPatient)    ✅ ajouté
  └── hasMany   → Facture           (FK: IDPatient)      ✅ ajouté

Medecin
  ├── belongsTo → Cabinet           (FK: fkidcabinet)
  ├── hasMany   → Rendezvou         (FK: fkidMedecin)    ✅ ajouté
  ├── hasMany   → Facture           (FK: FkidMedecinInitiateur) ✅ ajouté
  └── hasMany   → CaisseOperation   (FK: fkidmedecin)    ✅ ajouté

Rendezvou
  ├── belongsTo → Patient           (FK: fkidPatient)
  ├── belongsTo → Medecin           (FK: fkidMedecin)
  └── belongsTo → Cabinet

Facture
  ├── belongsTo → Patient           (FK: IDPatient)
  ├── belongsTo → Medecin           (FK: FkidMedecinInitiateur)
  ├── hasMany   → Detailfacturepatient (FK: fkidfacture)
  ├── hasMany   → Reglement
  └── belongsTo → Rendezvou (optionnel, FK: IDRdv)

Ordonnanceref
  └── hasMany → Ordonnance

Medicament
  ├── hasMany → StockMedicament
  └── hasMany → LotMedicament

CaisseOperation
  ├── belongsTo → Medecin  (FK: fkidmedecin)
  ├── belongsTo → TUser    (FK: fkiduser)
  ├── belongsTo → Infocabinet (FK: fkidcabinet)
  └── belongsTo → Patient  via tiers() (FK: fkidTiers)
```

### Champs Clés

| Modèle | Champ | Type | Description |
|---|---|---|---|
| `Facture` | `ISTP` | boolean | Prise en charge assurance active |
| `Facture` | `TXPEC` | float | Taux de prise en charge (0–1) |
| `Facture` | `TotFacture` | float | Montant total facturé |
| `Facture` | `TotalPEC` | float | Part assurance |
| `Facture` | `TotalfactPatient` | float | Part patient |
| `Facture` | `TotReglPatient` | float | Total déjà payé par le patient |
| `Facture` | `ReglementPEC` | float | Total remboursé par l'assurance |
| `Rendezvou` | `OrdreRDV` | int | Position dans la file |
| `Rendezvou` | `rdvConfirmer` | string | Statut : En Attente/Confirmé/En cours/Terminé/Annulé |
| `CaisseOperation` | `entreEspece` | float | Entrée caisse |
| `CaisseOperation` | `retraitEspece` | float | Sortie caisse |
| `CaisseOperation` | `fkidTiers` | **integer** | FK Patient ✅ (était float) |
| `CaisseOperation` | `fkidfacturebord` | **integer** | FK Facture ✅ (était float) |

### Rôles Utilisateurs (`IdClasseUser`)

| Valeur | Rôle | Accès |
|---|---|---|
| 1 | Secrétaire | Patients, RDV, règlements |
| 2 | Docteur | Consultation, ordonnance, dossier médical |
| 3 | Docteur Propriétaire | Accès complet + suppression + statistiques |

---

## 7. Incohérences UI/UX

### 7.1 Incohérence des Styles de Boutons

**Sévérité : MOYENNE** | **Statut : Non corrigé**

Les boutons pour des actions de même importance (primaire) utilisent des classes différentes selon les composants :

| Composant | Classe | Problème |
|---|---|---|
| `consultation-form.blade.php:134` | `bg-primary hover:bg-primary-dark` | Standard |
| `reglement-facture.blade.php:157` | `bg-blue-600 hover:bg-blue-700` | Couleur différente |
| `reglement-facture.blade.php:168` | `bg-blue-600 hover:bg-blue-700` | Idem |
| `rendez-vous-manager.blade.php:177` | `bg-blue-500 text-xs py-1 px-2` | Taille réduite |
| `patient-manager.blade.php` | `bg-primary border border-transparent rounded-lg` | Bordure ajoutée |

**Recommandation :** Créer un composant Blade `<x-btn-primary>`, `<x-btn-secondary>` pour uniformiser.

---

### 7.2 Terminologie Incohérente

**Sévérité : MOYENNE** | **Statut : Non corrigé**

| Concept | Termes utilisés | Emplacement |
|---|---|---|
| Facture | "Facture/DEVIS", "Facture", titre modale = "Facture/DEVIS" | `reglement-facture.blade.php:3,208` |
| Paiement | "Règlement", "Paiement", "Payer" | Multiples |
| Acte médical | "Acte", "Service", "Prestation" | Multiples |
| Assurance | "Patient assuré", "ISTP", "PEC", "Assureur" | Multiples |

---

### 7.3 Absence d'Indicateurs de Chargement

**Sévérité : MOYENNE** | **Statut : Partiellement corrigé**

- `consultation-form.blade.php` : ✅ **Corrigé** — spinner + `wire:loading.attr="disabled"` ajouté
- `patient-manager.blade.php` : ❌ Bouton "Enregistrer" sans état de chargement
- `ordonnance-manager.blade.php` : ❌ Bouton "Sauvegarder" sans état de chargement
- `create-rendez-vous.blade.php` : ❌ Bouton "Créer le RDV" sans état de chargement

---

### 7.4 Couleurs de Statuts Incohérentes

**Sévérité : MOYENNE** | **Statut : Non corrigé**

Le vert a deux significations opposées dans la même application :

| Contexte | Vert signifie |
|---|---|
| Boutons RDV (`rendez-vous-manager.blade.php:180`) | "En cours" (consultation active) |
| Badge facture (`reglement-facture.blade.php:98`) | "Réglée" (terminé, payé) |

---

### 7.5 Gestion Incohérente des Modales

**Sévérité : MOYENNE** | **Statut : Non corrigé**

Trois approches différentes coexistent :

| Composant | Approche |
|---|---|
| `PatientManager` | `$showModal` (booléen unique) |
| `ReglementFacture` | 5 booléens séparés (`$showReglementModal`, `$showMedecinModal`, etc.) |
| `AccueilPatient` | 20+ booléens + événements `fermer*Modal` dédiés |
| `CreateRendezVous` | `$keepModalOpen = true` (logique inversée) |

---

### 7.6 Dialogues de Confirmation Natifs du Navigateur

**Sévérité : BASSE** | **Statut : Non corrigé**

```blade
{{-- reglement-facture.blade.php:105 --}}
onclick="return confirm('Êtes-vous sûr...')"

{{-- reglement-facture.blade.php:139 --}}
onclick="event.stopPropagation(); if(confirm('...'))"
```

Problèmes : style incohérent, pas d'accessibilité ARIA, tronqué sur mobile.

---

### 7.7 Absence de Navigation Contextuelle

**Sévérité : BASSE** | **Statut : Non corrigé**

Aucun fil d'Ariane ni indication de la page active. La navigation par modales ne laisse pas de trace d'historique navigateur.

---

### 7.8 Indicateurs de Champs Obligatoires Incohérents

**Sévérité : BASSE** | **Statut : Non corrigé**

- `ConsultationForm` : `*` présent sur certains labels (lignes 24, 64)
- `PatientManager` : pas d'indicateur `*` sur les champs requis
- `CreateRendezVous` : pas d'indicateur `*`

---

## 8. Problèmes de Workflow

### 8.1 Création de Facture Implicite

**Sévérité : HAUTE** | **Statut : Non corrigé**

Lors de `ConsultationForm.save()`, la facture est créée dans une transaction unique sans étape de révision intermédiaire. Un double-clic peut créer deux factures (bien que le numéro soit protégé par verrou).

**Note :** Le reçu s'ouvre automatiquement dans un nouvel onglet, ce qui constitue un feedback partiel, mais pas une confirmation avant création.

---

### 8.2 Absence de Confirmation Post-Paiement dans ReglementFacture

**Sévérité : HAUTE** | **Statut : Non corrigé**

Après `enregistrerReglement()` :
1. Paiement enregistré ✓
2. Message de confirmation visible ✗ (seulement `session('message')` qui peut être manqué)
3. La modale de règlement reste ouverte ✗
4. La liste des factures ne se rafraîchit pas automatiquement ✗

---

### 8.3 Estimation du Temps d'Attente Fixe

**Sévérité : MOYENNE** | **Statut : Non corrigé**

```php
// PatientInterfaceController.php:129
$tempsAttenteEstime = $patientsAvantMoi * 15; // 15 minutes codé en dur
```

Pas configurable, pas basé sur les durées réelles.

---

### 8.4 Logs de Débogage en Production

**Sévérité : MOYENNE** | **Statut : Non corrigé**

`ConsultationForm.php` contient de nombreux `\Log::info()` et `\Log::error()` avec des données sensibles (contenu du patient, IDs) :

```php
// ConsultationForm.php:323-336
\Log::info('Début de la méthode save()');
\Log::info('Données avant validation', [
    'selectedPatient' => $this->selectedPatient, // données patient complètes
    ...
]);
```

De même dans `PatientInterfaceController.php:32-33` :
```php
\Log::info('Date Token: ' . $dateToken);
\Log::info('Date Aujourd\'hui: ' . now()->format('Y-m-d'));
```

**Risque :** Les logs peuvent contenir des données personnelles de santé (DPS) en clair dans les fichiers de log.

---

### 8.5 Pas de Trace d'Audit Financière

**Sévérité : MOYENNE** | **Statut : Non corrigé**

Aucun journal d'audit pour les modifications/suppressions de factures et règlements. La suppression d'une facture est irréversible et non tracée (hormis le log applicatif).

---

### 8.6 Routes de Test Exposées en Production

**Sévérité : MOYENNE** | **Statut : Non corrigé**

Les routes `/test-api`, `/modal-demo`, `/animation-test`, `/test-modals` sont accessibles à tout utilisateur authentifié. Elles doivent être supprimées ou protégées par un middleware `env('APP_ENV') === 'local'`.

---

## 9. Corrections Apportées

### C-1 — CaisseOperation.php : Casts de clés étrangères

**Fichier :** [app/Models/CaisseOperation.php](app/Models/CaisseOperation.php)

```php
// Avant (incorrect — float pour une clé étrangère)
'fkidTiers'       => 'float',
'fkidfacturebord' => 'float',
'fkiduser'        => 'int',
'exercice'        => 'float',

// Après (correct)
'fkidTiers'       => 'integer',
'fkidfacturebord' => 'integer',
'fkiduser'        => 'integer',
'exercice'        => 'integer',
```

---

### C-2 — Medecin.php : Relations hasMany manquantes

**Fichier :** [app/Models/Medecin.php](app/Models/Medecin.php)

Ajout des relations :
- `rendezvous()` → `hasMany(Rendezvou::class, 'fkidMedecin', 'idMedecin')`
- `factures()` → `hasMany(Facture::class, 'FkidMedecinInitiateur', 'idMedecin')`
- `caisseOperations()` → `hasMany(CaisseOperation::class, 'fkidmedecin', 'idMedecin')`

---

### C-3 — Patient.php : Relations hasMany manquantes

**Fichier :** [app/Models/Patient.php](app/Models/Patient.php)

Ajout des relations :
- `rendezvous()` → `hasMany(Rendezvou::class, 'fkidPatient', 'ID')`
- `factures()` → `hasMany(Facture::class, 'IDPatient', 'ID')`

---

### C-4 — PatientInterfaceController.php : Token QR sécurisé

**Fichier :** [app/Http/Controllers/PatientInterfaceController.php](app/Http/Controllers/PatientInterfaceController.php)

Le token base64 simple a été remplacé par un token signé HMAC-SHA256 :

```php
// Nouveau format : base64url(payload) + "." + base64url(HMAC-SHA256(payload, APP_KEY))
$encodedPayload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
$signature      = hash_hmac('sha256', $encodedPayload, config('app.key'));
$token          = $encodedPayload . '.' . base64url($signature);
```

- Rétrocompatible avec les anciens tokens.
- Utilise `hash_equals()` pour la comparaison (protection contre timing attacks).

---

### C-5 — consultation-form.blade.php : Indicateur de chargement

**Fichier :** [resources/views/livewire/consultation-form.blade.php](resources/views/livewire/consultation-form.blade.php)

```blade
{{-- Avant --}}
<button type="button" wire:click="save" class="px-4 py-2 bg-primary ...">
    Créer la consultation
</button>

{{-- Après --}}
<button type="button" wire:click="save"
        wire:loading.attr="disabled"
        wire:loading.class="opacity-60 cursor-wait"
        wire:target="save"
        class="inline-flex items-center gap-2 px-4 py-2 bg-primary ...">
    <span wire:loading.remove wire:target="save">Créer la consultation</span>
    <span wire:loading wire:target="save">
        <svg class="animate-spin h-4 w-4">...</svg> Enregistrement…
    </span>
</button>
```

---

## 10. Récapitulatif des Issues par Priorité

### ✅ Corrigées

| # | Problème | Fichier | Correction |
|---|---|---|---|
| C-1 | Casts `float` sur clés étrangères | `CaisseOperation.php` | Changé en `integer` |
| C-2 | Relations Medecin manquantes | `Medecin.php` | Ajout hasMany Rendezvou, Facture, CaisseOperation |
| C-3 | Relations Patient manquantes | `Patient.php` | Ajout hasMany Rendezvou, Facture |
| C-4 | Token QR Code non sécurisé (base64 brut) | `PatientInterfaceController.php` | HMAC-SHA256 + rétrocompat |
| C-5 | Bouton consultation sans loading state | `consultation-form.blade.php` | Spinner + disable |

### ❌ Non corrigées — Haute priorité

| # | Problème | Fichier | Ligne |
|---|---|---|---|
| W-1 | Création de facture implicite sans confirmation | `ConsultationForm.php` | `save()` |
| W-2 | Aucun feedback / refresh après enregistrement règlement | `reglement-facture.blade.php` | 278-296 |
| W-3 | Logs débogage avec données patient en clair | `ConsultationForm.php` | 323-336 |
| W-4 | Routes de test exposées en production | `routes/web.php` | 38-55 |

### ❌ Non corrigées — Priorité moyenne

| # | Problème | Fichier | Ligne |
|---|---|---|---|
| U-1 | Styles de boutons incohérents | Multiples vues | — |
| U-2 | Terminologie mixte ("Facture/DEVIS", etc.) | `reglement-facture.blade.php` | 3, 208 |
| U-3 | Loading states manquants sur d'autres boutons | `patient-manager`, `ordonnance-manager` | — |
| U-4 | Couleurs de statuts sémantiquement contradictoires | `rendez-vous-manager`, `reglement-facture` | 180, 98 |
| W-5 | Temps d'attente codé en dur (15 min/patient) | `PatientInterfaceController.php` | 129 |
| W-6 | Absence de trace d'audit financière | Toute l'application | — |

### ❌ Non corrigées — Basse priorité

| # | Problème | Fichier | Ligne |
|---|---|---|---|
| U-5 | `confirm()` natif navigateur non stylisé | `reglement-facture.blade.php` | 105, 139 |
| U-6 | Aucun fil d'Ariane / navigation contextuelle | Layout global | — |
| U-7 | Indicateurs `*` champs obligatoires incohérents | Multiples formulaires | — |
| U-8 | Gestion état des modales non standardisée | Multiples composants | — |

---

*Document maintenu manuellement — Cabinet Savwa — Dernière mise à jour : 2026-04-07*



