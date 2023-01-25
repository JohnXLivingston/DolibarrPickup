+++
title="Saisie des transferts de stock"
menuTitle="Transferts de stocks"
weight=40
chapter=false
description="Saisie de transferts de stocks"
+++

Quand des produits doivent changer de stock (ceci peut arriver à différents
moments), on suivra la procédure décrite sur cette page.

Attention, en fonction du «type de suivi» du produit, la procédure est différente.

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

{{% notice warning %}}
Cet écran ne sait pas gérer les «Équipements» pour l'instant.
J'ai posé la question à Altairis: est-il envisageable d'ajouter le support
des équipements dans cet écran. J'attends la réponse.
{{% /notice %}}

Dolibarr propose un écran pour transférer plusieurs produits d'un coup :

![Transfert de stock en masse](./../images/dolibarr_product_massmove.png?classes=shadow,border&height=400)

## Suivi par numéro de série unique

Pour les produits dont on souhaite suivre les mouvements unités ou unités,
on passera par le module «Equipement».

Les équipements auront des numéros de série générés par le module de collecte,
de la forme «E-MARSHALL-JVM440-00001».

Le préfixe «E-» indique que c'est un équipement.
Ensuite, on a la référence produit normalisée.
Et pour finir, un compteur.

Ce numéro de série est imprimé sur une étiquette (en toutes lettres, et sous forme
d'un code barre) qui sera collée sur le produit.

Plusieurs écrans sont possibles pour saisir les transferts de ces produits.

### Depuis la fiche produit / Equipement

Quand on a qu'un seul produit à déplacer, le plus simple est d'aller sur la fiche produit ou la fiche équipement.

On peut alors passer par le même écran que pour les produits suivis à la référence,
à la différence près qu'on devra saisir les numéros d'équipements dans le champs dédié.
