+++
title = "Statuts"
weight = 40
chapter = false
+++

La fiche de collecte passe par plusieurs statuts.

{{% notice tip %}}
Chacun de ces statuts est accompagné de droits associés, permettant ainsi de définir avec précision quels utilisateur⋅rice⋅s ou groupes d'utilisateur⋅rice⋅s peuvent intervernir, et à quelles étapes.
{{% /notice %}}

### Brouillon

À ce stade on peut ajouter des produits, modifier leurs quantités, poids, ...

### En cours de traitement

Ici il n'est plus possible de modifier le contenu de la collecte, uniquement ses méta-données.
Il est possible de revenir au statut Brouillon si nécessaire.

### En stock

À partir du moment où l'on passe dans cet état, les produits récoltés sont intégrés au module Stock de Dolibarr.
Les mouvements de stocks associés sont créés.

|  |  |
| ------ | ----------- |
| ![Stocks](./images/stocks.png?classes=shadow,border) | ![Mouvements](./images/mouvements.png?classes=shadow,border)

{{% notice warning %}}
Une fois la fiche de collecte entrée en stock, il n'est plus possible de revenir en arrière.
{{% /notice %}}

Un bon de collecte au format PDF est généré.

![Bon de collecte](./images/bon_collecte.png?classes=shadow,border)

### Signée

Le tiers a signé le bon de collecte, le cycle de celle-ci est donc terminé.

{{% notice tip %}}
Il est possible de désactiver ce statut, auquel cas le statut «En stock» est le statut final.
{{% /notice %}}
