+++
title="Expédition"
weight=30
chapter=false
description="Création d'un bon d'expédition."
+++

Depuis la fiche de Commande, vous pouvez :

* soit aller sur l'onglet «Expéditions»
* soit utiliser le bouton «créer expédition», qui redirige vers l'onglet «Expéditions»

Ensuite vous devez sélectionner un entrepôt.
Si tous les produits ne viennent pas du même entrepôt, ce n'est pas grave,
vous pourrez changer ligne par ligne par la suite.

![Création d'une expédition](./../../images/dolibarr_expedition.png?classes=shadow,border&height=400px)

Vous pouvez ensuite saisir les quantités à envoyer.

Pour les produits suivants par numéro de série, il faudra choisir lequels envoyer.
C'est à la personne qui prépare la commande de choisir.
Si le client souhaite un produit particulier, et que ce n'est pas la même personne
qui gère la commande et l'expédition, on pourra ajouter des annotations pour le préciser.

![Saisie d'une expédition](./../../images/dolibarr_expedition_2.png?classes=shadow,border&height=400px)

Il ne reste plus qu'à valider le bon d'expédition.
Cela décrémentera les stocks.

{{% notice tip %}}
LRDS utilise le module Dolibarr standard «Workflow» qui va automatiquement
classer la commande comme «livrée».
{{% /notice %}}
