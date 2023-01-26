+++
title="Saisie des transferts de stock"
menuTitle="Transferts de stocks"
weight=40
chapter=false
description="Saisie de transferts de stocks"
+++

Quand des produits doivent changer de stock (ceci peut arriver à différents
moments), on suivra la procédure décrite sur cette page.

Attention, en fonction du «type de suivi» du produit, la procédure est légèrement
différente.

## Suivi à la référence

Le type de suivi «à la référence» concerne les consommables, pièces détachées, ... :
tous les produits qui ne sont pas suivis individuellement.

Pour une même référence produit (par ex «Ampoule par 64 100W»), on aura un seul
code barre pour tous les produits.
Ce code barre pourra être collé sur certains produits, ou sur les caisses les
contenant, suivant les cas.

Le format du code barre est «R-AMPOULE_PAR_64_100W».
On y trouve le préfixe «R-» pour indiquer que le suivi se fait par référence,
suivi de la référence produit normalisée (on aura retiré les caractères accentués, espaces, ...).

Ce code barre est généré à la création de la fiche produit.
Si jamais la référence change, le code barre lui ne changera pas (pour ne pas
risquer de perdre la trace de toutes les pièces actuellement en stock).

Plusieurs écrans sont possibles pour saisir les transferts de ces produits.

### Depuis la fiche produit

Quand on a qu'un seul produit à déplacer, le plus simple est d'aller sur la fiche produit.

Si on a son code barre sous les yeux, on peut le scanner dans le champs de recherche associé
dans la liste des produits :

![Recherche de la fiche produit par code barre](./../images/dolibarr_barcode_search.png?classes=shadow,border)

Ensuite, dans l'onglet «Stock» de la fiche produit, on peut utiliser le bouton «Transférer Stock» :

| | |
|---|---|
| ![Transférer stock](./../images/dolibarr_product_transfert.png?classes=shadow,border) | ![Transférer stock](./../images/dolibarr_product_transfert2.png?classes=shadow,border)

### Transfert de stock en masse

Dolibarr propose un écran pour transférer plusieurs produits d'un coup :

![Transfert de stock en masse](./../images/dolibarr_product_massmove.png?classes=shadow,border&height=400)

## Suivi par numéro de série unique

Pour les produits dont on souhaite suivre les mouvements unité par unité,
on utilisera les capacités du module «Numéros de lot/série» standard de Dolibarr.
Sur ces fiches produits, le champs «Utiliser les numéros de lots/série» sera
positionné à «oui (numéro de série unique requis)».
Cela sera fait notamment quand la fiche produit a été créé par l'application mobile,
et que le champs «type de suivi» à été positionné à «numéro de série unique».

Les numéros de série générés par le module de collecte seront de la forme
«S-MARSHALL-JVM440-00001».

Le préfixe «S-» indique que c'est un numéro de **s**érie.
Ensuite, on a la référence produit normalisée.
Et pour finir, un compteur.

Ce numéro de série est imprimé sur une étiquette (en toutes lettres, et sous forme
d'un code barre) qui sera collée sur le produit.

Plusieurs écrans sont possibles pour saisir les transferts de ces produits.

### Depuis la fiche produit

Quand on a qu'un seul produit à déplacer, le plus simple est d'aller sur la fiche produit.

Pour cela, on pourra par exemple aller sur la page «Produits > Lots/Séries»,
et scanner avec une douchette le numéro de série dans le filtre adéquat :

![Recherche par numéro de série](./../images/dolibarr_product_search_serial.png?classes=shadow,border&height=400px)

Dans l'onglet «Stock» de la fiche produit, on pourra alors utiliser le bouton «Transférer le stock»
qui se trouve sur la ligne correspondant au numéro de série souhaité.

![Transfert de stock](./../images/dolibarr_product_transfert_serial.png?classes=shadow,border)

On peut aussi utiliser le même bouton «Transférer le stock» que dans le cas du
suivi par référence, et utiliser une douchette pour saisir le numéro de série.

{{% notice info %}}
Pour les produits avec un numéro de série unique, il faudra saisir les mouvements
produit par produit, la quantité devra toujours être positionnée à 1.
{{% /notice %}}

### Transfert de stock en masse

L'écran de transfert de stock en masse est capable de prendre en compte les numéros de série :

![Transfert de stock en masse](./../images/dolibarr_product_massmove_serial.png?classes=shadow,border&height=400)

{{% notice warning %}}
Cet écran n'est pas très pratique pour la saisie des transferts avec des numéros de série.
En effet, on est obligé de saisir le numéro de série, la référence produit, et
la quantité (qui ne peut être que 1).
De plus, il faut ajouter ligne par ligne.
On pourra étudier la possibilité de développer un écran plus pratique dans le module de Collecte par exemple,
où il suffirait de scanner des codes barres (que ce soit des code «par référence» ou «par numéro de série»).
{{% /notice %}}
