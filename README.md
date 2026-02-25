# Warrantycheck

Plugin GLPI de vérification de garantie matérielle et de détection de références documentaires (BL/BC/FA/DE) dans les tickets.

Le plugin permet de:
- détecter automatiquement des numéros de série dans le contenu d'un ticket (description, tâches, suivis, validation, solution)
- interroger des services constructeurs (HP, Dell, Lenovo, Dynabook, Terra) pour remonter un statut de garantie
- enregistrer les informations détectées dans une table GLPI dédiée
- afficher une popup d'aide au technicien dans le ticket
- proposer un écran de recherche manuel (`Vérification de garantie`)
- gérer les numéros de série enregistrés (liste, recherche, export CSV, suppression)
- (optionnel) lier automatiquement des BL Sage via le plugin `gestion`

## Architecture (résumé court)

- `inc/ticket.class.php` : onglet Ticket + popup de détection + affichage des garanties déjà enregistrées.
- `front/checkwarranty_ticket.php` : endpoint JSON interne qui analyse le ticket et renvoie résultats/alertes.
- `front/warranty_functions.php` : logique de détection marque + appels fournisseurs + insert/update en base.
- `inc/generatecri.class.php` + `front/generatecri*.php` : écran de recherche manuel et affichage des résultats détaillés.
- `inc/config.class.php` : configuration globale (préfixes, blacklist, clés API constructeurs, droits de modification).
- `inc/preference.class.php` : préférences utilisateur (popup, volume, affichage, SageLocal, etc.).
- `ajax/ajax_*` : outils d'administration des numéros de série (liste, export CSV, suppression).

## Prérequis

- GLPI `11.0.0` à `11.2.0` (déclaré dans `setup.php`)
- PHP compatible avec votre version GLPI
- Extension `curl` (HP / Terra notamment)
- Accès réseau Internet vers les sites / APIs constructeurs si vous utilisez la vérification de garantie
- (Optionnel) plugin `gestion` actif si vous voulez l'association automatique des BL Sage détectés
- (Optionnel) extension `sodium` pour le chiffrement renforcé des secrets API HP/Dell en configuration

## Menus / Onglets / Écrans

- `Outils > Vérification de garantie` (menu survey manuel, selon droit `plugin_warrantycheck_survey`)
- Onglet Ticket `Garantie` (liste des garanties détectées / enregistrées)
- Préférences utilisateur GLPI > onglet `Vérification de la garantie`
- Configuration > Plugins > onglet config du plugin Warrantycheck
- Profil > onglet droits Warrantycheck

## Fonctionnement (vue d'ensemble)

### 1. Détection automatique dans un ticket

Quand un technicien ouvre un ticket (selon préférences), le plugin peut:
- lire le contenu du ticket + tâches + suivis + validation + solution
- extraire des chaînes candidates (numéros de série / références)
- filtrer selon la blacklist et les préfixes configurés
- interroger les APIs/sites constructeurs (si option activée)
- afficher un toast avec les résultats (fabricant, statut de garantie, lien de recherche manuelle)

Le plugin peut aussi enregistrer les numéros détectés dans la table `glpi_plugin_warrantycheck_tickets`.

### 2. Recherche manuelle (écran “Vérification de garantie”)

L'écran manuel permet:
- de choisir explicitement un fabricant (`HP`, `Dell`, `Lenovo`, `Terra`, `Dynabook`) ou `Auto`
- de saisir un numéro de série / référence document
- d'afficher les détails de garantie (si API/site constructeur répond)
- de voir les tickets déjà liés à cet élément
- d'enregistrer/réutiliser les données récupérées dans la base du plugin

### 3. Détection de documents associés (BL / BC / FA / DE)

Si `related_elements` est activé:
- le plugin reconnaît des préfixes de documents (Bon de livraison, Bon de commande, Facture, Devis)
- il peut les afficher dans la popup ticket
- il peut les stocker comme éléments associés
- il peut, en complément, interagir avec le plugin `gestion` pour lier certains BL (mode Sage local, selon préférences utilisateur)

### 4. Gestion admin des numéros de série

Depuis la configuration plugin, une modal de gestion permet:
- recherche paginée
- recherche ciblée par syntaxe (`id=123`, `ticket=456`, `sn=ABC`, ou recherche globale)
- sélection multiple
- suppression de lignes
- export CSV

## Configuration plugin (globale)

Le README donne la logique générale. Le fichier `Documentation_Fonctionnement.docx` détaille les options et des exemples de paramétrage.

### A. Activation de la détection de documents liés

- `related_elements` (oui/non)

Active la reconnaissance des références de type:
- devis
- facture
- bon de commande
- bon de livraison

À activer si vous utilisez ces références dans les tickets et souhaitez qu'elles soient reconnues automatiquement.

### B. Clés API constructeurs (HP / Dell)

Le plugin peut stocker des identifiants/secret pour interroger les APIs:
- `ClientID_Dell`
- `ClientSecret_Dell`
- `ClientID_HP`
- `ClientSecret_HP`

Ces champs servent uniquement à l'interrogation des APIs constructeur.

### C. Préfixes de détection (numéros de série / fabricants)

Préfixes configurables par marque:
- `Filtre_HP`
- `Filtre_Lenovo`
- `Filtre_Dell`
- `Filtre_Dynabook`
- `Filtre_Terra`
- `Filtre_IIyama`
- `Filtre_Autres`

But:
- améliorer la détection automatique
- orienter la marque quand le mode `Auto` est utilisé
- éviter des appels API inutiles

### D. Préfixes de documents métiers

Si `related_elements` est actif:
- `Filtre_Devis`
- `Filtre_Facture`
- `Filtre_BonDeLivraison`
- `Filtre_BonDeCommande`

Ces préfixes servent à reconnaître les références documents dans le texte des tickets.

### E. Blacklists (filtrage des faux positifs)

- `blacklist`
- `prefix_blacklist`

Ces listes servent à éviter de traiter comme numéro de série:
- des mots courants
- des horaires / fragments de texte
- des codes techniques non pertinents

### F. Droits de modification des filtres par les utilisateurs

Le plugin permet de gérer ce que les utilisateurs peuvent faire sur les filtres:
- `whitelistuser_read`
- `whitelistuser_update`
- `whitelistuser_delete`

Cela agit sur l'onglet de préférences utilisateur (lecture seule / modification partielle selon réglage).

## Préférences utilisateur (fonctionnement)

L'onglet de préférences permet à chaque utilisateur de régler son comportement d'affichage.

Principales options:
- `warrantypopup` : active la popup de recherche automatique dans les tickets
- `repeatpopup` : évite de réafficher la popup pendant un délai (15 min) après ouverture du ticket
- `checkvalidate` : affiche/masque le message “Aucun numéro de série trouvé”
- `statuswarranty` : inclut le statut de garantie dans la popup (plus d'appels API = léger ralentissement possible)
- `maxserial` : limite le nombre d'éléments affichés
- `toastdelay` : durée d'affichage de la popup
- `positioning` : position de la popup (haut/bas, gauche/droite)
- `viewdoc` : affiche les documents détectés (si `related_elements` est activé)
- `SageLocal` : active l'association automatique des BL depuis Sage (si plugin `gestion` actif)

## Droits / profils

Le plugin déclare notamment:
- `plugin_warrantycheck` : lecture / mise à jour / purge des données de garantie
- `plugin_warrantycheck_survey` : accès à l'écran de recherche manuelle (menu outils)

Ces droits pilotent:
- l'affichage des onglets
- l'accès à la popup ticket
- l'accès au menu de vérification manuelle
- certaines actions d'administration

## Services externes supportés (garantie)

Le plugin contient des connecteurs / parseurs pour:
- HP (API)
- Dell (API)
- Lenovo (site / données JS embarquées)
- Dynabook (endpoint JSON)
- Terra / Wortmann (parsing HTML)

Important:
- la disponibilité dépend des services externes
- les temps de réponse peuvent varier
- certains constructeurs peuvent modifier leur API/site sans préavis

## Sécurité / Maintenance / Optimisations (résumé)

Travaux récents appliqués sur le plugin:
- durcissement des endpoints AJAX (droits + validation + JSON propre)
- renforcement CSRF sur handlers de formulaires
- protections supplémentaires sur entrées GET/POST (sanitisation)
- chiffrement des secrets config via `sodium` avec compatibilité legacy
- optimisation de certaines requêtes et caches locaux (détection marque / résultats de garantie)
- réduction d'appels inutiles dans certains workflows (ex: vérification de fichier avant récupération d'URL)

## Vérifications rapides après mise à jour

1. Ouvrir la configuration plugin et enregistrer les paramètres.
2. Vérifier les préférences utilisateur (popup, maxserial, positioning, statuswarranty).
3. Ouvrir un ticket contenant un numéro de série et vérifier la popup.
4. Tester la recherche manuelle (`Outils > Vérification de garantie`).
5. Tester l'onglet Ticket `Garantie`.
6. Ouvrir la modal de gestion des numéros de série (config) : liste / recherche / export CSV / suppression.
7. Si `gestion` est installé: tester la détection BL Sage selon les préférences utilisateur.

