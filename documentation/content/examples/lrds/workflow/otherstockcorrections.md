+++
title="Intégration dans le stocks d'achats (et autres)"
menuTitle="Achats et autres origines"
weight=50
chapter=false
description="Intégration dans le stock de produits achetés, ou d'origine autre que des collectes."
+++

Il peut arriver que des produits soient à intégréer dans le stock, sans être
passé par des collectes.
Par exemple si on achète des pièces détachées, ou des consommables.

Dans ce cas là, on ira incrémenter les stocks directement sur les fiches
produits, via le bouton «Corriger le stock».

Ou encore en créant une «commande fournisseur», et passant par la fonction
de «Réceptions» :

![Réception de commande fournisseur](./../images/dolibarr_order_reception.png?classes=shadow,border&height=400px)

Si le produit est géré à la référence, rien de particulier à faire.

Si le produit est géré par numéro de série unique, il faudra le saisir.

{{% notice warning %}}
En l'état, il manquerait un bouton pour générer un numéro de série, ainsi que pour l'imprimer.
Il faudra voir si on peut ajouter les boutons nécessaires via le module de Collecte.
{{% /notice %}}
