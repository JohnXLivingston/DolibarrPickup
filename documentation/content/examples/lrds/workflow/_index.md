+++
title="Processus métiers et utilisation de Dolibarr"
weight=30
chapter=false
+++

Nous allons détailler ici les pratiques métiers de LRDS en lien avec leur activité
de ressoucerie, et comment Dolibarr est utilisé dans ce contexte.

{{% notice warning %}}
Les processus décrit dans le présent document sont encore en cours d'élaboration.
Certains de ces processus ne sont pas encore réellement en place.
Ce document est un document de travail visant à valider les processus métiers
à mettre en place pour l'année 2023.
Certaines fonctionnalités du module de Collecte décrites dans ce document ne
sont pas encore disponibles (par exemple celles liées aux numéros de série).
{{% /notice %}}

## Pôles d'utilisation / Entrepôts Dolibarr

Concernant les matériels que LRDS revalorise, ils suivent en général un processus du type :

* matériels collectés chez un tiers, ou apportés par celui-ci
* diagnostic des matériels récupérés
* puis :
  * soit démantellement pour revalorisation des pièces détachées ou matériaux
  * soit mise en location
  * soit mise en vente
  * soit mise au rebus

Il a été choisi de représenter ces différents postes par des «Entrepôts» Dolibarr :
le module standard «Stock» ajoute cette notion, et l'on peut ensuite suivre les
mouvements des produits de stocks en stocks. Cette catégorisation par entrepôts
correspond également à la réalité sur le terrain : les lieux de stockages sont
physiquements différents.

La présence dans tel ou tel stock permet ensuite de savoir ce qu'on pourra en faire.
Par exemple, un ampli guitare qui serait dans le stock «En location» pourra être loué
à un⋅e client⋅e.

À noter que LRDS a fait le choix de séparer complètement la location de la vente :
un produit dans le stock de location ne sera pas proposé à la vente (on pourra 
néanmoins le transférer d'un stock à l'autre si on souhaite le vendre).

Le schéma ci-dessous reprend les différents entrepots, et les flux les plus courants :

{{<mermaid>}}
graph TD;
  .(( )) --> Collecte[Collecte]
  Diagnostique[Diagnostique]
  Reparation[À réparer / En maintenance]
  Vente[En vente]
  Location[Prestations et Locations]
  Dechet[Déchet]

  Collecte --> Diagnostique
  Diagnostique --> Reparation
  Diagnostique --> Location
  Diagnostique --> Vente
  Diagnostique --> Dechet

  Reparation --> Location
  Reparation --> Vente
  Reparation --> Dechet
{{< /mermaid >}}

On pourra bien sûr avoir d'autres flux (de la location vers la vente par exemple).

## La collecte

Quand un tiers apporte du matériel, ou quand LRDS va chercher du matériel chez
un tiers, on va alors saisir une «Collecte» dans le module du même nom.

Cette saisie se fait via [l'application mobile](./../../../user/mobileapp/).

|  |  |
| ------ | ----------- |
| ![Nouvelle collecte](./images/mobile_home.png?classes=shadow,border) | ![Sélection du tiers](./images/mobile_select_tiers.png?classes=shadow,border) |
| ![Création du tiers](./images/mobile_create_tiers.png?classes=shadow,border) | ![Création de la collecte](./images/mobile_create_pickup.png?classes=shadow,border) |

Le champs «type de collecte» a été configuré avec les deux valeurs suivantes :

* Apport sur site d'activité
* Enlèvement chez le tiers

{{% notice info %}}
Les collectes peuvent être saisies par des stagiaires. Il est possible de
configurer les droits dans Dolibarr pour que les stagiaires n'aient accès
qu'à l'application mobile, et ne puisse donc pas voir ou éditer d'autres
informations.
{{% /notice %}}

On va ensuite lister les produits récupérés.

On peut chercher si le produit est déjà référencé dans Dolibarr.
LRDS a activé un champs additionnel, fourni par le module de Collecte :
le champs «Marque».

![Recherche d'un produit existant](./images/mobile_pick_product.png?classes=shadow,border)

Si le produit n'existe pas, on peut créer une fichier produit en utilisant
le bouton «Nouveau produit» en bas à gauche.

LRDS a activé l'utilisation des «Catégories de produits» dans l'application mobile.
La première information à remplir est donc de choisir une catégorie.

![Sélection de la catégorie](./images/mobile_pick_tag.png?classes=shadow,border)

LRDS a configuré les catégories de produits de sorte à ce qu'elles les
caractégorisent de manière très précise.

![Catégories produit](./images/tags.png?classes=shadow,border)
