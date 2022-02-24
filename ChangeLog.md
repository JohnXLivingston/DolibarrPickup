# DolibarrPickup

## 1.1.0 (Unreleased yet)

### Fonctionnalités

* Prise en compte des produits avec numéro de lot/série:
  * Si le module «lot/série» est activé, on peut choisir si les produits depuis l'application mobiles ont l'option activée ou non.
  * Pour les produits avec n° lot/série, il faut renseigner celui-ci avant de pouvoir intégrer la collecte au stock.
  * On peut choisir d'appliquer par défaut un numéro de lot égal à la réf de la collecte.
* Fiche produit: affichage d'un onglet Collecte.

### Corrections et changements mineurs

* Traduction des libellés des permissions d'accès.

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
