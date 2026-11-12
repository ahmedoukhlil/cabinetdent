# Sysdental

Application de gestion de cabinet dentaire multi-cabinet : patients, agenda, salle d'attente et accueil « Ma journée » pour les dentistes.

La documentation fonctionnelle de référence est dans [documentation.md](documentation.md). Elle décrit l'ancienne application ; Sysdental en reprend les règles métier module par module.

## Stack

- PHP 8.2+, Laravel 12, Livewire 3 (Alpine inclus)
- Tailwind CSS 4 compilé par Vite, icônes Tabler
- MySQL ou MariaDB (SQLite en mémoire pour les tests)

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Renseignez `DB_*` dans `.env`, créez la base `sysdental`, puis :

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

`--seed` crée un cabinet de démonstration (uniquement hors production). Les comptes de démonstration (cabinet et `plateforme@demo.test` pour l'espace plateforme) sont dans `database/seeders/DatabaseSeeder.php`.

## Rôles

| Rôle | Accès |
|---|---|
| Dentiste propriétaire | tout le cabinet, synthèse, sélecteur « Mes données / Cabinet », suppression de patients |
| Dentiste | ses propres rendez-vous et soins, patients |
| Secrétaire | patients, agenda de tous les dentistes, arrivées et annulations ; ne démarre pas les soins |

Les permissions sont définies dans `config/permissions.php` et utilisables avec `@can` / `$this->authorize()`.

## Règles métier implémentées

- Isolation par cabinet : trait `BelongsToCabinet` (scope global et remplissage de `cabinet_id`).
- Numéro de fiche patient séquentiel par cabinet.
- Rendez-vous (`App\Services\RendezVousService`) : patient et dentiste revérifiés dans le cabinet, refus d'un créneau déjà pris, ordre du jour par dentiste.
- Cycle de vie strict : Planifié → Arrivé → En soins → Terminé, annulation possible avant les soins (`App\Enums\StatutRdv`). Les heures d'arrivée, de début et de fin sont enregistrées.
- Un seul patient au fauteuil par dentiste.
- Agenda : bandeau de la semaine (lundi à dimanche, nombre de RDV, jours fermés grisés) et créneaux libres cliquables calculés à partir des horaires, des jours d'ouverture et de la durée d'un RDV du cabinet (réglés dans Équipe et cabinet) ; les créneaux passés du jour sont exclus. Une réservation qui chevauche un RDV actif du même dentiste (écart inférieur à la durée d'un RDV) est refusée ; l'heure reste saisissable librement.
- Dossier dentaire (`App\Services\DossierDentaireService`, référentiels dans `App\Support\Odontologie`) :
  - odontogramme FDI de 32 dents, sélection simple ou multiple ;
  - par dent : présence, prothèse, endodontie, état de chacune des 5 faces, constatations cliniques, parodontales et orthodontiques, notes ;
  - toute valeur est validée contre un référentiel fermé ; une dent absente perd ses faces et son traitement ;
  - chaque modification réelle est historisée (avant / après, auteur, date) ;
  - plan de traitement : Brouillon → Proposé → Accepté → En cours → Terminé (ou Annulé), transitions imposées ; un soin ne peut être réalisé qu'après acceptation ; un soin réalisé ne peut plus être supprimé ; le plan ne se termine que si tous les soins sont réalisés.
  - saisie d'un soin : recherche dans le catalogue des actes actifs du cabinet (libellé ou code, clavier ↑ ↓ Entrée), libellé et prix de référence remplis et modifiables ; un soin libre reste possible ; l'acte choisi est conservé sur la ligne et repris à la facturation.
- Radios et documents (`App\Services\FichierPatientService`, section du dossier dentaire) :
  - radiographie, scanner, photo, compte rendu, biologie ou autre ; JPG, PNG, WEBP ou PDF de 10 Mo maximum ;
  - date de l'examen et dents concernées (liées à la sélection de l'odontogramme, qui sert aussi de filtre) ;
  - stockage privé `storage/app/private/cabinets/{cabinet}/patients/{patient}/`, nom aléatoire ; lecture uniquement par la route authentifiée `/fichiers/{id}`, limitée au cabinet, `Cache-Control: private, no-store` ;
  - retrait par l'auteur ou le propriétaire : suppression logique, le fichier physique est conservé.
- La secrétaire n'a pas accès au dossier dentaire.
- Facturation (`App\Services\FacturationService`) :
  - devis (brouillon) modifiable, puis facture validée et figée ; numéro `N-AAAA` attribué à la validation, séquentiel par cabinet et par année, sans trou ;
  - actes du catalogue ou libres, et soins du plan de traitement (uniquement après acceptation, une seule fois) ;
  - assurance : taux normalisé (80 ou 0,8 → 0,8), `part assureur = total × taux`, `part patient = total − part assureur`, taux figé à la validation ;
  - encaissements patient ou assureur sans trop-perçu ; remboursements limités au montant reçu et réservés au propriétaire ;
  - annulation d'une facture validée uniquement sans paiement net et avec motif ; ses soins redeviennent facturables ;
  - caisse : journal par période et par dentiste, totaux par mode, état imprimable ; facture et reçus imprimables.
- Dépenses (`App\Services\DepenseService`, dans la caisse) :
  - saisie par la secrétaire ou le propriétaire : date (pas dans le futur), catégorie, libellé, montant, mode, référence, praticien concerné ou frais du cabinet ;
  - solde de caisse = recettes nettes − dépenses, à l'écran et sur l'état imprimé ;
  - suppression réservée au propriétaire, avec motif ; suppression logique, la dépense reste tracée.
- Statistiques (`App\Services\StatistiquesService`, écran `/statistiques`, propriétaire) : par praticien et par période, patients distincts, nombre d'actes, facturé (factures validées uniquement), moyenne par acte, encaissé net, dépenses rattachées et bilan ; les frais non attribués figurent sur une ligne « Cabinet ».
- Équipe et cabinet (`App\Services\EquipeService`, écran `/parametres/equipe`, propriétaire uniquement) :
  - création de comptes (nom, e-mail unique, rôle, mot de passe initial de 8 caractères minimum), changement de rôle et de mot de passe ;
  - un compte n'est jamais supprimé, il est désactivé et ne peut plus se connecter ;
  - on ne peut ni désactiver son propre compte ni changer son propre rôle, et le cabinet garde toujours au moins un propriétaire actif ;
  - coordonnées du cabinet (en-tête des documents imprimés) et durée par défaut d'un rendez-vous.

## Tarifs par assureur et import Excel

- Chaque acte a un **prix sans assurance** et, au besoin, un **prix propre à chaque assureur** (table `acte_tarifs`). Sans tarif propre, le prix sans assurance s'applique.
- Le prix est repris selon l'assurance du patient : plan de traitement (autocomplete du dossier) et lignes de facture.
- **Paramètres › Tarifs** : recherche, onglets Actes / Assurances, une colonne de prix par assurance active.
- **Modèle Excel** (`/parametres/tarifs/modele-actes`) : le catalogue actuel, avec colonnes Code, Libellé, Prix sans assurance, puis une colonne par assureur, plus une feuille « Mode d'emploi ».
- **Import groupé** (`ActeImportService`, OpenSpout) :
  - l'acte est reconnu par son code, sinon par son libellé ; il est mis à jour, sinon créé ;
  - une case assureur vide retire le tarif propre ; une colonne d'assureur inconnu est ignorée avec un avertissement ;
  - l'import est tout ou rien, avec les erreurs listées par ligne ; limite de 2 000 lignes et de 5 Mo.

## Odontogramme anatomique (react-odontogram)

- Le dossier dentaire affiche par défaut l'odontogramme **anatomique** (`react-odontogram`, licence MIT, notation FDI). La vue **Par faces** (SVG maison) reste disponible pour le détail face par face, et le choix est mémorisé dans le navigateur.
- Le composant React est chargé à la demande (`resources/js/odontogramme.js`, environ 59 Ko compressés), via le composant Alpine `odontogramme`. Les données passent par l'attribut `data-odontogramme`, que Livewire met à jour.
- Couleurs par dent (`Odontologie::conditionsGraphe`) : à traiter, obturation, prothèse, endodontie, implant, absente. Une dent saine avec un soin prévu au plan apparaît en **soin prévu**.
- Liens avec le plan et la facture :
  - un clic sur « Dents … » d'une ligne du plan sélectionne ces dents ;
  - le badge « Facturé N° » mène à la facture ;
  - la facture affiche un odontogramme en lecture seule des dents facturées, avec un lien vers le dossier.

## Consultation : facture et caisse en une action

- Un acte peut être de type **Consultation** (Paramètres › Tarifs, ou colonne « Type » du modèle Excel).
- Dans la **salle d'attente**, cliquer sur « Arrivé » propose aussitôt de facturer la consultation. Le bouton « Consultation » reste aussi disponible sur la ligne du patient (en attente, en soins ou terminé).
- `ConsultationService::facturer` crée en une transaction une **facture dédiée** au rendez-vous, au prix de l'assurance du patient. Elle la valide et la numérote, puis encaisse si demandé la **part patient**, qui apparaît aussitôt dans la caisse. Le devis du plan de traitement n'est pas touché.
- Un rendez-vous ne peut être facturé qu'une fois (`rendez_vous.facture_id`), sauf si sa facture est annulée. Le dentiste peut facturer, mais seuls les rôles ayant le droit d'encaisser peuvent encaisser.
- Une pastille indique l'état : « Payée » ou « À encaisser », avec un lien vers la facture.
- **Fenêtre unique** (`ConsultationRapide`, dans la mise en page) ouverte par les événements `consultation-ouvrir` et `consultation-sans-rdv`. Elle est accessible depuis la salle d'attente, l'agenda (« Arrivé »), « Ma journée », la fiche patient (bouton « Consultation ») et l'accueil.
- **Sans rendez-vous** : rechercher ou créer rapidement le patient (nom, prénom, téléphone) et choisir le dentiste. Le patient est inscrit « Arrivé » à l'heure actuelle (`RendezVousService::sansRendezVous`), puis la facture est faite. Depuis la fiche, un rendez-vous du jour non facturé est repris directement.
- **Reçu** : après l'encaissement, un bouton « Imprimer le reçu ».
- **Alerte de l'accueil** (`ConsultationsNonFacturees`) : patients terminés sans consultation facturée, et consultations du jour restant à encaisser.

## Stock de consommables

`App\Services\StockService`, écran `/stock` (tous les rôles) :

- articles par catégorie (protection, anesthésie, restauration, endodontie…), unité, seuil d'alerte ; nom unique par cabinet ;
- la quantité ne change que par un mouvement tracé : entrée (prix d'achat moyen pondéré, date de péremption), sortie (refusée au-delà du stock), inventaire (quantité comptée, motif obligatoire, écart enregistré) ;
- alertes : rupture (≤ 0), stock faible (≤ seuil), périmé, péremption dans les 30 jours ; la date suivie est celle du lot le plus proche ;
- valeur du stock = somme(quantité × prix d'achat) ; pas de médicaments ni de ventes.

## Impressions et export

- Dossier dentaire imprimable (`/patients/{patient}/dossier/imprimer`, dentistes) : odontogramme en couleurs, état des dents non saines, plans de traitement non annulés, liste des radios et documents.
- Statistiques imprimables (`/statistiques/imprimer`, propriétaire), en plus des factures, reçus et états de caisse.
- Export complet du cabinet (`App\Services\CabinetExportService`, bouton « Exporter le cabinet » dans Équipe et cabinet, propriétaire) : archive ZIP avec une table par fichier JSON (éléments supprimés inclus), les radios et documents, et un `LISEZMOI.txt` récapitulatif. Limité au cabinet ; les mots de passe ne sont jamais exportés ; 5 exports par minute au maximum.

## Plateforme et abonnements

Espace séparé `/plateforme` (garde `plateforme`, table `admins_plateforme`) : les comptes de cabinet n'y ont pas accès. Créez un administrateur avec :

```bash
php artisan plateforme:admin admin@exemple.com
```

- Administrateurs (`/plateforme/administrateurs`, menu « Admins ») : création, modification du nom, de l'e-mail et du mot de passe (8 caractères minimum ; vide = inchangé), désactivation et réactivation. Tous les administrateurs ont les mêmes droits. Un compte n'est pas supprimé (les paiements enregistrés y sont rattachés) mais désactivé : connexion refusée et sessions ouvertes fermées. On ne peut pas se désactiver soi-même, et il reste toujours au moins un administrateur actif. En cas de blocage, `php artisan plateforme:admin <email>` réactive le compte et redéfinit son mot de passe.
- Formules (`/plateforme/formules`) : prix mensuel, nombre de comptes actifs et stockage maximum (vide = illimité).
- Cabinets (`/plateforme`) : création d'un cabinet avec son dentiste propriétaire et un essai gratuit, paiements, changement de formule, résiliation, blocage manuel.
- Cycle (`App\Services\AbonnementService`) : essai → actif → impayé (fin d'essai ou de période) → suspendu (après `SUBSCRIPTION_GRACE_DAYS`, 5 par défaut) ; un paiement réactive ; résilié est définitif, reprendre exige un nouvel abonnement.
- Paiement : nouvelle fin = date la plus tardive entre aujourd'hui, fin de période et fin d'essai, plus les mois payés.
- Suspendu ou résilié : connexion refusée et sessions ouvertes déconnectées pour tout le cabinet. Impayé : accès maintenu.
- Bande flottante d'échéance (propriétaire uniquement, en bas de l'écran) : compte à rebours `J-n` avant la fin de l'essai ou de la période payée (`SUBSCRIPTION_ALERT_DAYS`, 7 par défaut), et date de suspension en cas d'impayé. Ambre à 3 jours ou moins et en impayé, bleue sinon. Réductible en pastille ; elle se rouvre chaque jour.
- Quotas : comptes actifs (création et réactivation) et stockage des fichiers patients, suppressions logiques incluses.
- Un cabinet sans abonnement (installation autonome) n'est pas limité.
- Les échéances sont appliquées chaque nuit par `php artisan abonnements:verifier` : ajoutez la tâche planifiée système `php artisan schedule:run` chaque minute (cron ou Planificateur de tâches Windows). Le bouton « Appliquer les échéances » le fait à la demande.

## Application installable (PWA)

- `public/manifest.json` (mode `standalone`, icônes dans `public/icons/`), déclaré sur toutes les pages.
- `public/sw.js` : met en cache uniquement la coque statique (`/build/`, `/icons/`, `offline.html`). Les pages, les réponses Livewire, les fichiers patients et les factures passent toujours par le réseau et ne sont jamais conservés sur l'appareil ; hors connexion, la page `offline.html` s'affiche.
- Bouton d'installation sur Chrome / Edge / Android, aide « Sur l'écran d'accueil » sur iPhone et iPad (masquables).
- Quand une nouvelle version est déployée, un bandeau « Mettre à jour » active le nouveau service worker puis recharge la page. Changez `VERSION` dans `sw.js` quand la coque change.
- Le service worker ne s'enregistre qu'en HTTPS (ou sur `localhost`).

## Tests

```bash
php artisan test
```
