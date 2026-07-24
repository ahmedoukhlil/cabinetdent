# Inventaire fonctionnel détaillé — Application Jasmins

**Objectif :** décrire, écran par écran, ce que fait précisément chaque composant/contrôleur de l'application (actions possibles, logique métier, données manipulées), en base de travail pour la refonte (voir [PRD.md](PRD.md)).

**Méthode :** lecture directe du code (composants Livewire `app/Http/Livewire/*.php` et contrôleurs `app/Http/Controllers/*.php`).

---

## 1. Écran d'accueil / Hub central

### `AccueilPatient`
Composant "chef d'orchestre" de toute l'application post-connexion. Ne contient pas de logique métier propre : il pilote l'ouverture/fermeture d'environ **25 fenêtres modales** (une par fonctionnalité : création patient, RDV, ordonnance, règlement, caisse, dépenses, utilisateurs, statistiques, médecins, types de paiement, salle d'attente, salle de soins, paramètres cabinet, actes patient, etc.). Détermine aussi les rôles de l'utilisateur connecté (docteur propriétaire / docteur / secrétaire) et les droits associés (gérer les RDV, voir tous les RDV...), et charge en cache (1h) les référentiels globaux (médecins actifs, assureurs, types de paiement, actes). C'est le point d'entrée unique de l'usage quotidien du cabinet.

### `Auth`
Formulaire de connexion (login + mot de passe + "se souvenir de moi"). Authentifie via Laravel Auth et redirige vers `/accueil-patient`. Affiche un message d'erreur si les identifiants sont invalides.

---

## 2. Patients

### `PatientManager`
Écran de gestion des patients (liste + CRUD complet) :
- **Liste paginée** (10/page) avec recherche par nom/prénom, NNI ou téléphone, tri par colonne (dont tri numérique sur le N° fiche), filtre patients actifs/inactifs.
- **Créer/Modifier** un patient : nom, prénom, NNI, date de naissance, genre, 2 téléphones, adresse, informations d'assurance (assureur + identifiant d'assurance), matricule fonctionnaire, contact, avec validation stricte (regex sur noms, téléphone ≥ 8 caractères, etc.) et génération automatique du numéro de fiche patient (`IdentifiantPatient`).
- **Activer/désactiver** un patient (soft flag `choix`).
- **Supprimer un patient** : suppression en cascade dans une transaction (factures, détails de facture, ordonnances, rendez-vous, mouvements de stock liés, consultations, analyses, dossier médical) — opération irréversible.
- **Historique des paiements** d'un patient (liste des opérations de caisse associées).
- Calcul de l'âge à partir de la date de naissance, historique dentaire (`Dentspatient`).

### `PatientSearch`
Composant satellite de recherche/sélection de patient (autocomplete), utilisé par de nombreux autres composants (RDV, consultation, ordonnance, règlement) pour choisir un patient sans dupliquer la logique de recherche. Émet l'événement `patientSelected`.

---

## 3. Rendez-vous

### `CreateRendezVous`
Écran de création et de gestion de la liste des rendez-vous d'un médecin/jour donné :
- **Créer un RDV** : patient (via `PatientSearch`), médecin, date, heure, acte prévu, statut initial. Vérifie les **conflits d'horaire** avant création (`Rendezvou::hasConflict`), calcule le numéro d'ordre du jour, propose automatiquement l'heure actuelle + 10 min ou le **prochain créneau libre** (dernier RDV + 10 min).
- **Impression automatique** du reçu de RDV dans un nouvel onglet après création, avec notification "nouveau RDV" (pour la salle d'attente).
- **Liste des RDV** filtrable par médecin/date, paginée (8/page).
- **Modification groupée** (bulk edit) : sélectionner plusieurs RDV et les replanifier en série sur une nouvelle date/heure de départ avec un intervalle fixe entre chaque (ex. décaler tous les RDV du jour vers demain 9h, toutes les 15 min).
- **Changement de statut** individuel : En Attente / Confirmé / En cours / Terminé / Annulé, avec gestion centralisée des conflits.
- Restrictions par rôle : un docteur (non propriétaire) ne peut créer/modifier que ses propres RDV.

### `RendezVousManager`
Vue de gestion des RDV du jour, complémentaire à `CreateRendezVous` : liste par date avec filtre médecin, recherche patient (nom/NNI/téléphone), option afficher/masquer les RDV passés. Actions rapides : **confirmer**, **annuler**, **changer de statut**, **annulation groupée** (sélection multiple + annulation en masse).

### `SalleAttente`
Vue "tableau de bord" temps réel de la salle d'attente du jour : liste des RDV du jour non annulés/non terminés, **groupés par médecin**. Actions : **démarrer un RDV** (le passe "En cours" et termine automatiquement le RDV "En cours" précédent du même médecin — un seul patient en cours par médecin), **terminer un RDV**, sélectionner un patient pour lancer une consultation directement depuis la salle d'attente.

### `RdvReminders`
Écran de **rappels de rendez-vous** (RDV du lendemain par défaut) : liste filtrable par date/médecin/patient, génération d'un message **WhatsApp bilingue (français + arabe)** pré-rempli incluant un lien/QR code de suivi de file d'attente (`QrCodeHelper`), avec suivi des rappels déjà envoyés pour éviter les doublons.

---

## 4. Consultations & dossier médical

### `ConsultationForm`
Écran central d'enregistrement d'une consultation médicale — génère en une seule transaction :
1. La **facture** (numérotation unique par cabinet, calcul automatique de la part assurance/patient selon le taux de prise en charge de l'assureur du patient).
2. Le **détail de facture** (ligne "Consultation").
3. La **fiche de traitement** (dossier médical basique lié à la facture).
4. L'**opération de caisse** correspondant au paiement.
5. Le **rendez-vous** associé (créé automatiquement avec statut "Confirmé"), lié à la facture.

Gère deux types de consultation (généraliste / spécialiste) qui déterminent l'acte et le tarif appliqués automatiquement (recherche par mot-clé dans le référentiel des actes). Calcule en temps réel la répartition assurance/patient. À la sauvegarde, ouvre automatiquement le **reçu imprimable** dans un nouvel onglet et notifie la salle d'attente.

### `DossierMedicalManager`
Gestion du **dossier médical permanent** du patient, avec upload de fichiers (`WithFileUploads`) :
- **Antécédents** : personnels, familiaux, chirurgicaux, groupe sanguin, allergies, traitements permanents.
- **Maladies chroniques** : sélection dans une liste prédéfinie (diabète, HTA, asthme, VIH, hépatites, etc.) + champ libre "autre".
- **Nouvelle consultation** : formulaire dédié pour ajouter une entrée à l'historique médical.
- **Historique** : consultations passées et analyses/radios du patient, avec suppression (avec confirmation).
- Trois onglets : dossier permanent / nouvelle consultation / historique.

### `ActesPatient`
Composant listant/gérant les actes réalisés sur un patient donné (probablement l'historique des actes facturés, lié au dossier médical).

---

## 5. Ordonnances (médicaments, analyses, radios)

### `OrdonnanceManager`
Écran de prescription, le plus complexe fonctionnellement :
- **3 types d'ordonnance** : Médicaments / Analyses / Radios (recherche autocomplete dans le référentiel correspondant, avec option de saisie **libre** si l'élément n'existe pas au référentiel).
- **2 modes** :
  - **"Urgence" (interne)** : les médicaments sont **déduits du stock** du cabinet et **facturés automatiquement** sur la facture ouverte du patient (vérifie la disponibilité en stock ligne par ligne ; si rupture, la ligne est ignorée et signalée sans bloquer le reste). Génère un **mouvement de stock "sortie"** traçable pour chaque médicament facturé.
  - **"Sortie" (externe)** : ordonnance classique destinée à être imprimée et remise au patient pour achat en pharmacie externe, sans impact sur le stock ni la facture.
- Chaque ligne a une **quantité** et une **posologie** libre.
- Génère une **référence d'ordonnance** unique par cabinet/année (format `ORD-2026-0001`).
- **Impression PDF** de l'ordonnance (nouvel onglet).
- **Suppression** d'une ordonnance (réservée au prescripteur ou au docteur propriétaire).
- Déclenche une **notification "salle de soins"** si des lignes internes sont créées (pour que l'infirmier/soignant les prenne en charge).

### `MedicamentSearch`
Composant de recherche/autocomplete de médicaments, réutilisé par `OrdonnanceManager` et `ReglementFacture` pour ajouter des médicaments à une facture.

---

## 6. Salle de soins

### `SalleSoins`
Tableau de bord des **soins internes en cours** (issus des ordonnances "urgence") pour le jour, regroupés par patient avec un **statut dominant** (en_cours > en_attente > terminé) :
- Un **infirmier** (sans permission de créer des ordonnances) ne voit que les soins non terminés et peut : **démarrer les soins** d'un patient (passe toutes ses ordonnances internes du jour à "en_cours"), **terminer les soins** (passe à "terminé").
- Un **médecin/propriétaire** voit tout, y compris les soins terminés.
- Permet de sélectionner un patient pour rebasculer vers un autre écran (ex. facturation).

---

## 7. Pharmacie / Stock / Médicaments

### `PharmacieManager`
Écran opérationnel de gestion de pharmacie avec plusieurs onglets :
- **Dashboard** : vue d'ensemble du stock.
- **Stock** : recherche/filtre (tous, stock faible, expirés, expire bientôt), consultation des lots.
- **Entrée de stock** : ajout d'un nouveau lot (médicament, quantité, prix d'achat, seuil minimum d'alerte, numéro de lot, date d'expiration, fournisseur, référence facture fournisseur, notes).
- **Vente** : constitution d'un panier de médicaments à vendre à un patient, création automatique de la facture correspondante, avec **déduction du stock en FIFO** (lot le plus ancien épuisé en premier).
- **Historique** des mouvements de stock (entrées/sorties/ajustements) avec recherche et filtres.
- **Ajustement d'inventaire** : correction manuelle d'une quantité en stock avec justification obligatoire (traçabilité).
- Alertes visuelles (badges) : stock faible, rupture, produits expirés/proches de l'expiration.

### `MedicamentManager`
Gestion du **référentiel** médicaments/analyses/radios (catalogue, indépendant du stock physique) :
- CRUD sur le référentiel : libellé, type (1=Médicament, 2=Analyse, 3=Radio), prix de référence.
- Recherche, filtrage par type, pagination.
- Gestion de stock intégrée également ici (ajout de lot avec les mêmes champs que dans `PharmacieManager` — recoupement fonctionnel probable entre les deux composants, à clarifier/fusionner en refonte).

---

## 8. Actes médicaux

### `ActeManager`
CRUD du référentiel des **actes médicaux** facturables : libellé, montant, assureur associé (tarification par assureur), libellé en arabe. Recherche + filtre par assureur, pagination.

### `ActeCreate` / `ActeSearch` / `ListeActes`
Composants satellites : création rapide d'un acte (probablement depuis un contexte de facturation), recherche/autocomplete d'acte, et affichage en liste (consultation) des actes du référentiel — réutilisés dans plusieurs écrans (consultation, règlement de facture).

---

## 9. Facturation & règlement

### `ReglementFacture`
Écran-clé de la gestion financière côté patient :
- Sélection d'un patient → liste de ses **factures** (soldées ou non).
- **Ajout d'un acte** à une facture existante (avec prix de référence modifiable, quantité).
- **Ajout d'un médicament/analyse/radio** à une facture (avec vérification du **stock disponible** en temps réel pour les médicaments).
- **Enregistrement d'un règlement** : montant, mode de paiement, destination du paiement (part patient ou part assurance/PEC si le patient est assuré), avec détection automatique acompte/remboursement selon le signe du montant.
- **Reçu de règlement imprimable**.
- Consultation du **dossier médical** directement depuis la facture (modal dédié).
- Sélection d'un médecin associé à la facture (modal dédié de recherche médecin).
- **Suppression d'une ligne acte/médicament** de la facture : restaure automatiquement le stock et les mouvements associés si c'était un médicament.
- **Suppression complète d'une facture** (réservée au docteur propriétaire) : restaure tous les lots de stock déduits, supprime les opérations de caisse et détails liés — opération sensible à haut risque d'incohérence si mal maîtrisée.

### `HistoriquePaiement`
Affiche l'historique des paiements/règlements d'un patient (probablement réutilisé par `PatientManager` et `ReglementFacture`).

### Contrôleurs de facturation/impression
- **`ConsultationController`** : affichage/impression du **reçu de consultation**, de la **facture patient détaillée** (recalcule les totaux réels à partir des lignes de détail pour éviter les incohérences avec les totaux stockés), et de l'**ordonnance liée à une facture** (extrait uniquement les lignes médicament/analyse/radio).
- **`ReglementFactureController`** : génère le **reçu de règlement** (avec distinction paiement/remboursement selon le signe du montant, montant en toutes lettres).
- **`PaiementController`** : imprime l'**historique complet des paiements** d'un patient.

---

## 10. Caisse & finances

### `CaisseOperationsManager`
Journal des **opérations de caisse** (recettes/dépenses) avec permissions fines :
- `finances.view` = voit toutes les opérations du cabinet ; `finances.own` (sans `finances.view`) = ne voit que ses propres opérations (auto-filtré sur son propre médecin) ; `depenses.view` = accès aux dépenses ; `finances.delete` = droit de suppression.
- Filtres par médecin et plage de dates (par défaut : journée courante).
- Suppression d'une opération avec confirmation.

### `DepensesManager`
Gestion des **dépenses du cabinet** : enregistrement (date, montant, motif, type de tiers, mode de paiement), filtres par date/type de tiers, modification/édition d'une dépense existante.

### `CaisseController`
Génère l'**état de caisse journalier imprimable** : liste des opérations du jour (filtrées selon le rôle : une secrétaire ne voit que ses propres opérations, un médecin les siennes ; seul le propriétaire voit les dépenses), totaux recettes/dépenses/solde, ventilation par mode de paiement.

### `MedecinPaiementStats`
Statistiques financières par médecin : recettes/dépenses sur une période (jour/semaine/mois/personnalisée), regroupement par jour, et onglet secondaire listant les rendez-vous du médecin sur la période — combine finances et activité RDV dans un même écran.

### `StatistiquesManager`
Tableau de bord statistique global du cabinet (finances par médecin/période), avec export PDF (`PDF` = DomPDF) et impression de l'état journalier. Mêmes règles de permission que `CaisseOperationsManager` (`finances.view` / `finances.own`).

---

## 11. Référentiels & administration

### `AssureurManager` / `AssureurCreate`
CRUD des **compagnies d'assurance** : libellé et taux de prise en charge (PEC en %), utilisé pour calculer automatiquement la répartition assurance/patient sur chaque facture.

### `MedecinManager`
CRUD des **médecins** du cabinet : nom, contact. Suppression avec confirmation.

### `TypePaiementManager`
CRUD des **modes de paiement** disponibles (espèces, chèque, carte, etc. — libellé libre), utilisés dans tous les formulaires de règlement/consultation/caisse.

### `UserManager`
Administration des **utilisateurs et des permissions** — écran le plus sensible :
- **Onglet Utilisateurs** : CRUD (login, mot de passe, nom complet, rôle, médecin associé si applicable, statut actif/masqué). Suppression simple ou **suppression forcée**. Si l'utilisateur créé a le rôle médecin/propriétaire, une entrée `Medecin` correspondante est **créée automatiquement** (couplage implicite entre compte utilisateur et fiche médecin, à rendre explicite en refonte). Règle métier : un seul docteur propriétaire actif par cabinet.
- **Onglet Permissions** : matrice rôle × permission (cases à cocher) éditable, permissions groupées par domaine.
- **Onglet Rôles** : création/modification/suppression des rôles eux-mêmes (au-delà des 3 rôles historiques Secrétaire/Docteur/Docteur Propriétaire), avec protection empêchant la suppression d'un rôle encore assigné à des utilisateurs.

### `ParametresCabinet`
Configuration des informations du cabinet, en **français et en arabe** (nom, spécialités, adresse, contact, nom du docteur), plus des champs communs (email, téléphone public, pied de page d'impression) et **upload du logo** (`WithFileUploads`). Ces données alimentent l'en-tête/pied de page de tous les documents imprimés.

---

## 12. Interface publique patient (QR code)

### `PatientInterfaceController`
Point d'accès **public** (sans authentification), via un **token encodé** (probablement généré par `QrCodeHelper` et imprimé sur les documents remis au patient) :
- `showRendezVous($token)` : décode le token pour retrouver le patient (+ date et médecin encodés dans le token), affiche ses rendez-vous. Gère les cas de token invalide/expiré et patient introuvable.
- `showConsultation($token)` : équivalent pour consulter une consultation.

C'est le mécanisme qui permet à un patient de scanner un QR code (reçu papier) pour consulter en ligne ses informations, sans compte utilisateur.

---

## 13. Récapitulatif des documents imprimables (PDF/HTML via DomPDF)

| Document | Généré par |
|---|---|
| Reçu de rendez-vous | route inline (`rendez-vous.print`) |
| Reçu de consultation | `ConsultationController::showReceipt` |
| Facture patient détaillée | `ConsultationController::showFacturePatient` |
| Ordonnance (médicaments/analyses/radios) | `OrdonnanceController::print` / `download` |
| Ordonnance liée à une consultation | `ConsultationController::showOrdonnance` |
| Dossier médical (par facture / par patient) | `DossierMedicalController::print` / `printPatient` |
| Reçu de règlement | `ReglementFactureController::showReceipt` |
| Historique des paiements patient | `PaiementController::printHistorique` |
| État de caisse journalier | `CaisseController::printEtatCaisse` |

---

## 14. Observations transverses utiles pour la refonte

- **Logique métier riche mais concentrée dans les composants Livewire** (peu de Services/Actions séparés) : `ConsultationForm::save()` et `OrdonnanceManager::sauvegarderOrdonnance()` orchestrent chacun à eux seuls la création de 4-5 enregistrements liés (facture, détail, caisse, RDV, stock, mouvement de stock) dans une transaction — logique critique mais difficile à tester unitairement en l'état.
- **Doublon fonctionnel probable** entre `PharmacieManager` et `MedicamentManager` pour la gestion des lots de stock (mêmes champs, deux écrans différents) — à unifier.
- **Permissions à deux niveaux** systématiquement vérifiées dans le code (`hasPermission()` + vérifications manuelles de propriété type "un docteur ne peut modifier que ses propres RDV/factures") répétées dans plusieurs composants (`CreateRendezVous`, `RendezVousManager`, `OrdonnanceManager`) — bon candidat à une policy Laravel centralisée.
- **Sessions utilisées comme état temporaire inter-composants** (`session(['consultation_patient' => ...])`, `session(['rdv_patient' => ...])`) pour faire persister le patient sélectionné entre rechargements Livewire — fragile, à revoir avec une gestion d'état plus explicite (ex. Livewire persistant ou store dédié).
- **Recalcul de totaux de facture à la volée** dans `ConsultationController::showFacturePatient` (au lieu de faire confiance aux colonnes stockées `TotFacture`/`TotalPEC`) confirme une incohérence de données historique à corriger structurellement dans la refonte plutôt que corrigée à l'affichage.
