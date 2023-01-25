+++
title="La collecte"
weight=20
chapter=false
description="Cette page décrit le processus de collecte, c'est à dire comment sont gérées les entrées en stock pour les récupérations."
+++

Quand un tiers apporte du matériel, ou quand LRDS va chercher du matériel chez
un tiers, on va alors saisir une «Collecte» dans le module du même nom.

## Initialisation de la fiche de collecte

Cette saisie se fait via [l'application mobile](./../../../user/mobileapp/).

|  |  |
| ------ | ----------- |
| ![Nouvelle collecte](./../images/mobile_home.png?classes=shadow,border) | ![Sélection du tiers](./../images/mobile_select_tiers.png?classes=shadow,border) |
| ![Création du tiers](./../images/mobile_create_tiers.png?classes=shadow,border) | ![Création de la collecte](./../images/mobile_create_pickup.png?classes=shadow,border) |

Le champs «type de collecte» a été configuré avec les deux valeurs suivantes :

* Apport sur site d'activité
* Enlèvement chez le tiers

{{% notice tip %}}
Les collectes peuvent être saisies par des stagiaires. Il est possible de
configurer les droits dans Dolibarr pour que les stagiaires n'aient accès
qu'à l'application mobile, et ne puisse donc pas voir ou éditer d'autres
informations.
{{% /notice %}}

## Ajout de produits sur la fiche de collecte

On va ensuite lister les produits récupérés.

### Ajout d'un produit existant

On peut chercher si le produit est déjà référencé dans Dolibarr.
LRDS a activé un champs additionnel, fourni par le module de Collecte :
le champs «Marque».

![Recherche d'un produit existant](./../images/mobile_pick_product.png?classes=shadow,border)

### Création d'une nouvelle fiche produit

Si le produit n'existe pas, on peut créer une fichier produit en utilisant
le bouton «Nouveau produit» en bas à gauche.

LRDS a activé l'utilisation des «Catégories de produits» dans l'application mobile.
La première information à remplir est donc de choisir une catégorie.

![Sélection de la catégorie](./../images/mobile_pick_tag.png?classes=shadow,border)

LRDS a configuré les catégories de produits de sorte à ce qu'elles les
caractégorisent de manière très précise.

![Catégories produit](./../images/tags.png?classes=shadow,border)

Ensuite, dans le module de Collecte, on indique quels tags utiliser dans
l'application mobile (en effet, certains tags pourraient ne pas concerner
le processus de collecte).

On peut également assigner à chaque tag les informations suivantes:

* forcer ou limiter le type de DEEE (voir [le contexte](../context/))
* ajouter des notes qui seront affichées dans l'application mobile, pour indiquer quelles informations saisir
* *forcer le type de suivi (voir plus loin)*. **NB: ceci n'est pas encore implémenté - voir si nécessaire**.

![Configuration des catégories pour l'application mobile](./../images/configure_tags.png?classes=shadow,border)

Une fois la catégorie sélectionnée, on est invité à saisir les informations concernant le produit.

![Création d'un produit](./../images/mobile_create_product_wip.png?classes=shadow,border)

On retrouve le champs «marque», qui est un champs ajouté par le module de collecte
(l'ajout de ce champs est optionnel, et se configure via les options du module).
Ce champs propose une autocomplétion, basée sur les marques déjà connues.

On peut noter dans la capture d'écran ci-dessus que le champs «DEEE» a été
forcé à «PAM Pro». Ceci car nous avons choisi une catégorie «Backline >> Amplis»
qui force ce type.
D'autres catégories pourront limiter le choix entre «PAM» et «PAM Pro» par exemple,
ou encore laisser le choix entre toutes les possibilités.

{{% notice warning %}}
NB : le «type de suivi» n'est pas encore implémenté, il s'agit d'une évolution à l'étude.
{{% /notice %}}

Le champs «type de suivi» permet d'indiquer si ce produit devra être suivi à
l'aide de numéro de série unique, ou si c'est plutôt un produit de type «pièce détachée»
ou «consommable». Dans ce deuxième cas, on n'utilisera que la référence produit
quand on effectura des transferts de stock, des ventes, etc.

Le champs «type de suivi» est ici contraint par la catégorie produit choisie.

Une fois cet écran rempli, on arrive sur l'écran de saisie du poids unitaire.

![Saisi du poids unitaire](./../images/mobile_weight.png?classes=shadow,border)

LDRS a choisi de n'utiliser que les poids comme unité, et que cette saisie soit
obligatoire.

{{% notice tip %}}
Il est possible d'activer/désactiver les unités à utiliser dans l'application
mobile, ainsi que de choisir si elles sont obligatoires ou non.
Pour les unités possibles, il y a : le poids, le volume, la surface et la longueur.
{{% /notice %}}

{{% notice tip %}}
Il est possible de configurer la saisie de ces unités soient sur les fiches
produits, soit sur les bons de collecte.
Chez LRDS on a choisi de le faire sur les fiches produits, le poids étant ensuite
automatiquement calculé sur les bons de collecte.
{{% /notice %}}

La saisie du poids unitaire achève la création de la fiche produit.

### Saisie de la quantité

Que l'on ai sélectionné un produit existant, ou qu'on ai créé une nouvelle
fiche produit, on se retrouve ensuite sur l'écran de saisie des quantités.

![Saisie de la quantité](./../images/mobile_qty.png?classes=shadow,border)

### Impression d'une étiquette

{{% notice warning %}}
NB : l'impression des étiquettes n'est pas encore implémenté, il s'agit d'une évolution à l'étude.
{{% /notice %}}

Après la saisi de la quantité, on aura l'occasion d'imprimer une étiquette
avec un code barre pouvant identifer le produit.

L'écran dépendra du «type de suivi» du produit.

Si c'est un suivi par numéro de série unique, on aura une page qui proposera l'impression
d'autant d'étiquettes que la quantité ajoutée.
Chaque étiquette aura un numéro de série unique, généré automatiquement.

{{% notice warning %}}
Le format de ce numéro de série n'a pas encore été défini.
On pourrait partir sur une codification du type «E-REFERENCE_PRODUIT-00012».
Le préfixe «E-» indiquant qu'il s'agira d'un «Equipement» (cf plus loin).
{{% /notice %}}

Si c'est un suivi par référence, on aura une page avec un seul code barre,
correspondant à la référence produit. Si on a besoin d'en imprimer plusieurs,
on choisira le nombre dans la fenêtre d'impression.

{{% notice warning %}}
Le format du code barre correspondant à la référence n'a pas encore été défini.
On pourrait partir sur une codification du type «R-REFERENCE_PRODUIT»
directement dérivé de la ref produit.
Le préfixe «R-» indiquant qu'il s'agit d'une référence produit.
{{% /notice %}}

![Génération des codes barres](./../images/mobile_bar_code.png?classes=shadow,border)

{{% notice tip %}}
La fenêtre affichant les codes barres est un PDF affiché dans le navigateur.
Il faudra passer par le bouton «imprimer» affiché en haut de celui-ci.
Il faudra vérifier que les navigateurs web utilisés affiche cela correctement.
{{% /notice %}}

{{% notice tip %}}
Sous les code-barres, la valeur en toute lettre sera reprise, afin de pouvoir
l'interpréter «à l'œil nu».
{{% /notice %}}
