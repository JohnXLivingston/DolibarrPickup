+++
title="Problèmes connus"
weight=90
chapter=false
+++

{{% notice note %}}
Cette page référence quelques problèmes ou incompatibilités connus.
{{% /notice %}}

## Multi-entreprise

Ce module n'a pas été testé avec le mode «multi-entreprise» de Dolibarr.
En théorie il devrait fonctionner, mais il pourrait y avoir des effets de bords non anticipés.

## Numéros de série unique

Depuis Dolibarr 14, il est possible d'utiliser des numéros de série qui doivent être uniques par produit.
Ce mode n'est pas encore supporté par le module.
Je n'ai pas encore trouvé la bonne façon de traiter cette option.
En l'état, cela va fonctionner si vous n'avez pas plus de 1 en quantité par ligne de produit. Mais si vous
saisissez une quantité supérieure, vous ne pourrez pas intégrer votre collecte au stock.

Si vous êtes intéressez par cette fonctionnalités, vous pouvez [me contacter](../support/) pour que nous
discutions ensemble de la façon de l'intégrer.

## Base de donnée

Bien qu'en théorie Dolibarr puisse être utilisé sur des bases de données Postgresql, le module n'a été testé
que sur MariaDB et Mysql.

## Documentation en ligne

La présente documentation en ligne est accessible soit par des adresses web publiques
(par exemple sur [Github](https://johnxlivingston.github.io/DolibarrPickup/)),
soit directement depuis votre Dolibarr.
Toutefois, dans ce dernier cas, elle ne fonctionnera correctement que si votre dolibarr est «à la racine» de votre hébergement web.
C'est à dire qu'il est accessible par une adresse du type «https://votre_domaine.tld/», et non pas
«https://votre_domaine.tld/dolibarr». Certaines fonctionnalités comme la recherche ne peuvent fonctionner dans cette situation.