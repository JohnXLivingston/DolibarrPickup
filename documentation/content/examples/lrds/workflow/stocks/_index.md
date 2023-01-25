+++
title="Pôles d'utilisation / Entrepôts Dolibarr"
weight=10
chapter=false
description="Cette page décrit comment sont organisés les «Entrepôts» Dolibarr, et à quoi ils correspondent «physiquement»."
+++

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
