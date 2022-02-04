# DolibarrPickup

## 0.16.0 (Not released yet)

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
