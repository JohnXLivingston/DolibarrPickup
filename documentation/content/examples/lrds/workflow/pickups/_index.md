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
| ![Nouvelle collecte](./../images/mobile_home.png?classes=shadow,border&height=200px) | ![Sélection du tiers](./../images/mobile_select_tiers.png?classes=shadow,border&height=200px) |
| ![Création du tiers](./../images/mobile_create_tiers.png?classes=shadow,border&height=200px) | ![Création de la collecte](./../images/mobile_create_pickup.png?classes=shadow,border&height=200px) |

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

On va ensuite lister les produits récupérés. Deux cas possibles: le produit
est déjà référencé dans Dolibarr, ou c'est un produit qui n'a jamais été référencé.

### Ajout d'un produit existant

On peut chercher si le produit est déjà référencé dans Dolibarr.
LRDS a activé un champs additionnel, fourni par le module de Collecte :
le champs «Marque».

![Recherche d'un produit existant](./../images/mobile_pick_product.png?classes=shadow,border&height=200px)

### Création d'une nouvelle fiche produit

Si le produit n'existe pas, on peut créer une fichier produit en utilisant
le bouton «Nouveau produit» en bas à gauche.

LRDS a activé l'utilisation des «Catégories de produits» dans l'application mobile.
La première information à remplir est donc de choisir une catégorie.

![Sélection de la catégorie](./../images/mobile_pick_tag.png?classes=shadow,border&height=200px)

LRDS a configuré les catégories de produits de sorte à ce qu'elles les
caractégorisent de manière très précise.

![Catégories produit](./../images/tags.png?classes=shadow,border&height=200px)

Ensuite, dans le module de Collecte, on indique quels tags utiliser dans
l'application mobile (en effet, certains tags pourraient ne pas concerner
le processus de collecte).

On peut également assigner à chaque tag les informations suivantes:

* forcer ou limiter le type de DEEE (voir [le contexte](../context/))
* ajouter des notes qui seront affichées dans l'application mobile, pour indiquer quelles informations saisir
* *forcer le type de suivi (voir plus loin)*. **NB: ceci n'est pas encore implémenté - voir si nécessaire**.

![Configuration des catégories pour l'application mobile](./../images/configure_tags.png?classes=shadow,border&height=200px)

Une fois la catégorie sélectionnée, on est invité à saisir les informations concernant le produit.

![Création d'un produit](./../images/mobile_create_product_wip.png?classes=shadow,border&height=200px)

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

![Saisi du poids unitaire](./../images/mobile_weight.png?classes=shadow,border&height=200px)

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

![Saisie de la quantité](./../images/mobile_qty.png?classes=shadow,border&height=200px)

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

![Génération des codes barres](./../images/mobile_bar_code.png?classes=shadow,border&height=200px)

{{% notice tip %}}
La fenêtre affichant les codes barres est un PDF affiché dans le navigateur.
Il faudra passer par le bouton «imprimer» affiché en haut de celui-ci.
Il faudra vérifier que les navigateurs web utilisés affiche cela correctement.
{{% /notice %}}

{{% notice tip %}}
Sous les code-barres, la valeur en toute lettre sera reprise, afin de pouvoir
l'interpréter «à l'œil nu».
{{% /notice %}}

## Fin de saisie

Une fois qu'on a fini d'ajouter les produits, on peut utiliser le bouton
«Fin de saisie» pour passer la collecte dans le statut «Traitement».

![Fin de saisie](./../images/mobile_pickup_validate.png?classes=shadow,border&height=200px)

À partir de ce moment, la collecte n'est plus modifiable depuis l'application mobile.

{{% notice tip %}}
Si la collecte a été saisie par un⋅e stagiaire, c'est ici que le processus s'arrête.
À partir de là, il faut avoir les droits des droits supplémentaires.
{{% /notice %}}

## Traitement de la collecte

À partir de maintenant, il faut retourner sur l'application Dolibarr classique.

![En cours de traitement](./../images/dolibarr_pickup_waiting.png?classes=shadow,border&height=200px)

En ouvrant la fiche de collecte, on peut voir que celle-ci est en statut «en cours de traitement».
C'est maintenant à la personne en charge des collectes de vérifier que tout a
été correctement saisi.

Si besoin de corriger des données, on peut retourner au statut «Brouillon» via
le bouton «Retour en Brouillon».

{{% notice tip %}}
Si le poids unitaire a été modifié sur la fiche produit, celui-ci s'affichera
en orange sur la fiche de collecte.
Si on retourne au statut «Brouillon», on pourra réappliquer le poids unitaire
depuis la fiche produit en cliquant sur le bouton «warning» affiché à droite.
![Correction du poids](./../images//dolibarr_pickup_correct_weight.png?classes=shadow,border)
{{% /notice %}}

À partir du statut «En cours de traitement», un pdf «bon de collecte» est généré.

![Bon de collecte](./../images/dolibarr_pickup_pdf.png?classes=shadow,border)

## Intégration au stock

Si tout est correct sur la fiche de collecte, on peut maintenant utiliser le
bouton «intégrer au stock» pour passer en statut «En Attente de Signature».

Attention, cette action n'est pas réversible.

En cliquant sur ce bouton, tous les produits listés vont être ajoutés dans le
stock, dans l'entrepôt «Collecte».

Pour les produits avec un «type de suivi» par «numéro de série unique», on
va également créer des fiches «Équipement».

Le module «Équipement» fait parti de la suite de modules «Gestion de location»
fourni par l'entreprise Altairis
(voir [la liste des modules utilisés](./../../plugins/) pour
plus d'informations).

Pour ces équipements, le numéro de série correspond à celui du code barre qu'on
aura pu imprimer lors de la création de la collecte.
