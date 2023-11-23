# DolibarrPickup

## 2.3.0 (Not Released Yet)

### Nouvelles fonctionnalités

* Ajout de bouton «scanner les étiquettes» sur certaines objets (#90):
  * propositions commerciales
  * commandes

### Corrections et changements mineurs

* Fix: la suppression d'une collecte doit supprimer les "pbatch" liés aux lignes (Fix #101).

## 2.2.2

* Fix dolibarr >= 17: sélection des produits pour les collectes et les pbatch.

## 2.2.1

* Fix création de produits depuis l'application mobile.

## 2.2.0

### Nouvelles fonctionnalités

* Options pour les produits créés par l'application mobile :
  * automatiquement marqués «en vente»
  * utilisent le modèle de numérotation des produits si configuré
* Nouvelles fonctionnalités d'import/export:
  * pour les tags (ne prend en compte que les tags activés dans le module de collecte)
  * on peut activer les imports/exports d'autres données (surtout utile pour les imports initiaux ou les installations de test):
    * les paramètres du module de collecte (dictionnaire du type de collecte inclu)
    * la liste des entrepots. Notes: ne prend pas en compte leur hiérarchie, les imports «à plat». N'importe pas les pays ou départements.
    * les fiches produits
    * les tiers clients
    * les collectes (import uniquement)
    * Attention: ces imports/exports sont partiels, et ne sont pas à considérer comme fiables pour un environnement de production.

### Corrections et changements mineurs

* Paramètres du plugin: on sépare certains paramètres liés à la fiche produit.
* Fix: l'action de suppression en masse de collectes ne fonctionnait pas.

## 2.1.0

**Important** : si vous n'aviez pas installé la v2.0.0, merci de lire les remarques concernant cette version.

### Nouvelles fonctionnalités

* Écran de génération des numéros de lot/série manquants.

### Corrections et changements mineurs

* Ajustement pour que l'impression des étiquettes fonctionne sur les imprimantes Zebra utilisées par LRDS.
* Écran de correction des produits: ajout de la pagination.
* Écran de correction des produits: ajout d'une recherche par label et libellé.
* Écran de correction: fix titres de colonnes qui étaient clickable alors que le tri n'est pas implémenté.
* Correction droits pour le bouton «générer numéro de lot» dans l'onglet stock.
* Fix numéro de lot parfois mal associé aux collectes.

## 2.0.0

**Important** : Cette version contient de nombreuses nouveautés. Certaines fonctionnalités sont
dépréciées, et d'autres demandent de vérifier les paramètres pour que le module fonctionne comme
avant. Tout est indiqué dans le présent fichier, mais étant conscient que cela peut être
compliqué, n'hésitez pas à [me contacter](https://johnxlivingston.github.io/DolibarrPickup/support/)
pour vous faire accompagner (gratuitement) lors de la mise à jour.

**Important** : Cette mise à jour contient des modifications dans la gestion des hooks Dolibarr
et dans les définitions de base de donnée.
Pour les appliquer, il faut désactiver puis réactiver le module.

**Important** : Cette version rend le champs «marque» déprécié. Si vous l'utilisez
et souhaitez continuer à le faire, merci de contacter l'auteur du plugin.

Si vous l'utilisiez, vous pouvez aller le désactiver dans les paramètres du module.
Un bouton pour migrer les données vous sera proposé. Ce bouton ajoutera la marque
en préfixe des références produits (si elle n'est pas déjà en préfixe).

**Important** : La version Dolibarr minimum est désormais la version 14.

**Important** : Si vous utilisez le champs «description» sur les lignes de collecte,
celui-ci n'est plus automatiquement ajouté dans les PDF générés. Il faut aller activer l'option adéquate.

### Changements non rétro-compatibles

* Champs «marque» déprécié.
* Version minimum Dolibarr: 14.

### Nouvelles fonctionnalités

* Impression d'étiquettes: le module permet maintenant d'imprimer des étiquettes :
  * Celles-ci sont générées par TcPDF, quelque soit la configuration du module Code-Barre.
  * Les étiquettes peuvent contenir:
    * un code-barre 2D pointant sur la fiche produit, le cas échéant
    * des infos sur le produit (ref, ...)
    * un code-barre avec le numéro de lot/série le cas échéant
  * Un nouvel écran de recherche de produit «par étiquette» est disponible, qui permet :
    * de scanner en masse des code-barre 1D ou 2D pour afficher les produits/lots correspondants
* Gestion des numéros de série uniques.
* Application mobile: ajout du choix «numéro de série unique». Ce choix peut être contraint en fonction du tag.
* Settings: on peut forcer les produits créés depuis l'appli mobile à utiliser des numéros de série uniques.
* Options pour la génération automatique de numéros de lot/série (uniques ou non) depuis l'application mobile.
* Ajout de boutons pour créer des numéros de série depuis les écrans de correction de stock.
* Amélioration de l'ergonomie Dolibarr: quand on créé des mouvements de stocks sur des produits avec numéro unique, on initialise la quantité à 1.
* Écrans de «correction des données», pour traiter les incohérences :
  * lister les produits dont le type de numéro de lot/série ne correspond pas au tag
  * à venir plus tard: génération des numéros de lot/série manquant

### Corrections et changements mineurs

* Paramètres du module: l'édition se fait «bloc par bloc» pour plus de clarté, et minimiser le risque d'erreur.
* Champs «description» des lignes de collecte :
  * devient un type «text», et non plus «html»
  * peut être copié sur les fiches de lots sous forme d'un attribut supplémentaire

## 1.5.0

### Nouvelles fonctionnalités

* #75: dans l'application mobile, on peut maintenant ouvrir une fiche produit, et éditer ce dernier.

### Corrections et changements mineurs

* Fix #68: le formulaire de collecte ne permettait pas d'enregistrer un type de collecte vide.
* Fix #74: affichage des retours à la ligne dans l'application mobile.
* Fix #76 #77 #79: cohérence des libellés.
* Fix de quelques warnings php sans incidence.
* Compatibilité Dolibarr 16: remplacement des boutons d'action par des appels à dolGetButtonAction avec des tokens anti-CSRF.
* Appli mobile: meilleure interface quand on revient en arrière sur une page de type «pick».
* Appli mobile: simplification du mode édition.
* Appli mobile: suppression du mécanisme «itemGotoField», remplacé par un mécanisme plus robuste.

## 1.4.0

### Nouvelles fonctionnalités

* Refonte de l'écran de gestion des tags pour l'application mobile (#62).
* Suppression de l'onglet «Collecte» sur les tags (#62).

### Corrections et changements mineurs

* Mise à jour de sécurité des dépendances NPM.
* Désactivation de la protection anti-CSRF sur la page d'API mobile (#72).

## 1.3.6

* Définition du paramètre url_last_version, qui permet d'être prévenu des nouvelles version du module.
* La page «editor_url» pointe désormais vers la documentation.

## 1.3.5

### Corrections et changements mineurs

* Paramètres du module: on ajoute le bouton «modifier» entre les tableaux, pour plus de visibilité.
* Fix #53: Le bouton «envoyer email» doit être masqué si la fonctionnalité est désactivée.
* Fix #56: Ajout d'un bouton pour retourner sous Dolibarr depuis l'application mobile.

## 1.3.4

* Fix #14: affichage du libellé et de la marque dans l'application mobile et dans les collectes.
* Fix #61: un improbable conflit de nom de variable faisait échouer l'activation de catégories pour l'application mobile, quand le module «marquepage» est activé.
* NPM dependencies: install security fix.

## 1.3.3

* Fix: les rapports DEEE affichaient tous 0.
* Rapports: ajout d'une ligne de total

## 1.3.2

* Compatibilité Dolibarr v15: fix ajout de pièce jointes sur les collecte.

## 1.3.1

### Procédure de mise à jour

**Important** : cette mise à jour contient des modifications dans la gestion des hooks Dolibarr.
Pour les appliquer, il faut désactiver puis réactiver le module.

### Fonctionnalités

* Possibilité de créer des modèles d'e-mails spécifiques pour les Collectes.
* Application mobile: affichage des poids/longueurs/... unitaires sur l'écran de collecte.
* Pour les poids/longueurs/..., on propose 2 modes de saisies distincts dans les paramètres du module.
  * Mode «sur la fiche produit». Ce mode correspond à l'usage de LRDS. Les valeurs sont saisies sur la fiche produit (application mobile) et reprises automatiquement à l'ajout sur la collecte.
  * Mode «sur le bon de collecte». Ce mode correspond à l'usage de La Matière. Dans l'appli mobile, on ne saisie pas les valeurs à la création du produit, mais au moment de l'ajout sur la fiche collecte. Dans l'application Dolibarr, on saisi les valeurs dès l'ajout.

## 1.3.0

### Procédure de mise à jour

**Important** : cette mise à jour contient des modifications de base de données.
Pour les appliquer, il faut désactiver puis réactiver le module.

### Fonctionnalités

* Possibilité de passer directement du statut «Brouillon» à l'intégration dans le stock.
* Possibilité de désactiver le statut «signé». Dans ce cas:
  * Le statut «en attente signature» est renommé en «en stock».
  * Ce statut «en stock» est considéré final.
* Ajout de nouveaux types d'unités à prendre en compte (m, m², m³, l, ...). On peut paramétrer les unités à utiliser.
* Application mobile: possibilité d'éditer une ligne de collecte (pour corriger la quantité, ...)
* Possibilité de passer la collecte «en cours de traitement» depuis l'application mobile. Note: l'utilisateur⋅rice doit avoir le droit associé.
* Envoi par e-mail des bons de collecte.
  * Note: à cause d'une contrainte dans Dolibarr, cette fonctionnalité est optionnelle. Le fait de l'activer change le format des noms de PDF (car ceux-ci doivent contenir la référence)
* Ajout du champ «Type de collecte» (optionnel). Basé sur un dictionnaire à paramétrer.
* Ajout du champs «description» (optionnel) sur les lignes de collecte.

### Corrections et changements mineurs

* Application mobile: on peut mettre une quantité à 0. Permet de corriger une ligne ajoutée par erreur. À noter que j'ai fait le choix de ne pas permettre de la supprimer, pour qu'une personne qualifiée puisse supprimer le produit si nécessaire.
* Suppression des utilisations de la constante DOL_DOCUMENT_ROOT bannies par le Dolistore.
* Optimisation de l'utilisation du local storage.

## 1.2.1

### Changements mineurs

* Retrait de la documentation en ligne du module, et remplacement par un lien vers la documentation sur github pages. Le fichier généré était trop volumineux, et ne respectait pas les standards du Dolistore (doit pouvoir marcher depuis le dossier htdocs).

## 1.2.0

### Procédure de mise à jour

**Important** : cette mise à jour contient des modifications de base de données.
Pour les appliquer, il faut désactiver puis réactiver le module.

### Fonctionnalités

* Documentation en ligne.
* Mouvements de stock: on ajoute comme «origine» la collecte.
* Passage en license AGPL.

## 1.1.2

### Correction et changements mineurs

* Fix #41: Fiche produit, onglet Collectes: le lien vers les collectes ne fonctionne pas.

## 1.1.1

### Corrections et changements mineurs

* Éditions de tags, onglet collecte: affichage des boutons «précédent» et «suivant».
* Possibilité de modifier les références des collectes.
* Ajout d'un index UNIQUE sur la référence collecte.
* Impossibilité de saisir une collecte avec une date dans le futur.
* Possibilité d'autoriser les dates de collectes dans le futur (mais pas dans l'application mobile).
* Fix régression: le choix du stock par défaut avait disparu des paramètres.
* Les numéros de collectes repartent à 1 pour chaque nouvelle année fiscale.

## 1.1.0

### Fonctionnalités

* Prise en compte des produits avec numéro de lot/série:
  * Si le module «lot/série» est activé, on peut choisir si les produits depuis l'application mobiles ont l'option activée ou non.
  * Pour les produits avec n° lot/série, il faut renseigner celui-ci avant de pouvoir intégrer la collecte au stock.
  * On peut choisir d'appliquer par défaut un numéro de lot égal à la réf de la collecte.
* Fiche produit: affichage d'un onglet Collecte.

### Corrections et changements mineurs

* Traduction des libellés des permissions d'accès.
* Application mobile : message d'erreur spécifique pour les produits déjà existants.

## 1.0.0

### Fonctionnalités

* Compatibilité avec d'autres Dolibarr que celui de LRDS:
  * Ajout des dépendances aux autres modules.
  * Les champs customs DEEE, type DEEE et marque deviennent optionnels. Les champs sont créé à l'activation depuis les paramètres du module.
  * Script de migration de ces champs customs pour LRDS.
  * On peut désactiver l'utilisation des tags/catégories de produit.
* Application mobile: libellés des boutons de création et d'ajout personnalisables.
* Application mobile: boutons de validation toujours en bas de l'écran, pour une meilleure ergonomie.

### Corrections et changements mineurs

* Application mobile: affichage du libellé pour le «type du tiers», plutôt que le code.
* Intégration des collectes au stock: en cas d'erreur, rollback des modifications.
* Application mobile: on donne le focus aux premiers champs, pour éviter de devoir cliquer dedans pour commencer la saisie.
* Application mobile: fiche Collecte, erreur sur le compteur DEEE.
* Application mobile: nettoyage du localStorage pour retirer les versions obsolètes.
* Application mobile: Demo mode.
* Application mobile: fix styling for choice states.
* Application mobile: les states «compute» peuvent calculer via JS.
* Application mobile: nettoyage du code, pour mettre en commun la gestion de la remontées des données.

## 0.17.1

* Correctif: il n'était plus possible de créer une collecte.

## 0.17.0

### Fonctionnalités

* Automatisation de la saisie DEEE dans l'application mobile :
  * Au niveau de chaque tag, on peut forcer DEEE à OFF, ou à un type particulier.
  * On peut également proposer le choix entre les DEEE ECR/ECR Pro et PAM/PAM Pro.
  * L'application mobile adapte le formulaire de création de produit en fonction.

### Corrections et changements mineurs

* Déclaration des états mobiles : rangement et refactoring.

## 0.16.0

### Fonctionnalités

* Appli mobile : Fix d'un bug bloquant (impossibilité de sélectionner un produit existant).
* Appli mobile : affichage du champs DEEE sur la fiche collecte.
* Appli mobile : affichage du total des produits sur la fiche collecte.

### Corrections mineures

* Passage au `semantic versioning` pour la numérotation des versions.

## 0.15

### Fonctionnalités

* Appli mobile : le champs «marque» suggère les valeurs déjà existantes.
* Colonne «entrepôt» dans la liste des collectes: cachée par défaut.

### Corrections

* Appli mobile : ne s'appuie plus sur les libs JS de Dolibarr.
* Appli mobile : la barre de bouton du haut reste en place.
* Appli mobile : on n'importe que les styles nécessaires de bootstrap.
* Suppression de l'option de compilation de debug, ajout des source-map en fichier séparé.

## 0.14

### Fonctionnalités

* Appli mobile : refonte graphique complète (utilisation de Bootstap).

### Corrections mineures

* Clean du code.

## 0.13

### Fonctionnalités

* Appli mobile : si on a le droit «Create pickups», on liste toutes les collectes en cours.
* Appli mobile : l'écran d'accueil devient le choix de la collecte.
* Liste des collectes : affichage du nombre de produits (DEEE vs non DEEE).

### Corrections mineures

* Mise à jour des dépendances npm
* Appli mobile : traductions des erreurs de ssaisie
* Appli mobile : Changements de certains libellés pour LRDS.
* Tags / onglet Collecte : correctif de l'écran. Certains libellés n'étaient pas affichés, ou étaient incorrect.
* Nettoyage du code (diverses modifications).
* Ajout de StateVirtual.
* Appli mobile : le champs «Notes» devient «Description» (et n'est plus stocké dans les notes privées).
* Modification de libellé sur la fiche collecte : «Date» => «Date de la collecte»
* Formulaire de création de collecte : on retire le statut.

## 0.12

Initial version. Released for LaRessourcerieDuSpectacle.
