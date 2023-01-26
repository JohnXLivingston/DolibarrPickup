+++
title="Modules utilisés"
weight=20
chapter=false
description="Cette page résume les modules Dolibarr utilisés par LRDS dans le cadre de l'activité de ressourcerie."
+++

## Dolibarr

Version utilisée: 15.0.3 (ou supérieure).

## Modules standards

LRDS utilise les modules standards Dolibarr suivants:

| Module | Description
|--|--|
| Tiers | Permet de gérer les «tiers»: client⋅es, prospects, ... |
| Fournisseurs | Gestion des fournisseurs |
| Propositions commerciales | Permet de créer des devis |
| Commandes | Gestion des commandes client⋅es |
| Expéditions | Gestion des expéditions |
| Factures et Avoirs | Gestion des factures |
| Produits | Gestion des produits |
| Stock | Gestion du stock |
| Libellés/Catégorie | Gestion d'étiquettes sur les produits. Ces étiquettes sont utilisées pour catégoriser les différents matériels récupérés |
| Codes-barres | Gestion de code-barre ou QR-code |
| Numéros de Lot/Série | Gestion des numéros de série |
| Workflow inter-modules | Permet d'automatiser certaines étapes du processus métier: création automatique des commandes, ... |

## Modules non standards

LRDS utilise également les modules non standards suivants:

| Module | Activé | Description | Origine |
|--|--|--|--|
| Collecte | Oui | Il s'agit du module dont vous êtes entrain de lire la documentation. Ce module sert à saisir les données relatives à une «Collecte» de produits à revaloriser, puis à générer des rapports. | [John Livingston](https://johnxlivingston.github.io/DolibarrPickup/) |
| Gestion des locations / Dolirent | Oui |  C'est un module permettant de gérer les locations. | [Altairis](https://www.altairis.fr/boutique/dolirent/) |
| Sous-total | Oui |  Permet l'ajout de sous-totaux sur les devis, factures, ... | Est fourni avec le module [Dolirent d'Altairis](https://www.altairis.fr/boutique/dolirent/) |
| Dolitools | Oui |  Améliore l'expérience Dolibarr | Est fourni avec le module [Dolirent d'Altairis](https://www.altairis.fr/boutique/dolirent/) |
| Gestion des retours de produits | Oui |  Permet de gérer des retours de produits, après une location par exemple. | Est fourni avec le module [Dolirent d'Altairis](https://www.altairis.fr/boutique/dolirent/) |
| Modèles PDF améliorés | Oui |  Modèles PDF additionnels. | Est fourni avec le module [Dolirent d'Altairis](https://www.altairis.fr/boutique/dolirent/) |
| ~~Équipement~~ | **Non** |  Offre des fonctions avancées pour la gestion des numéros de série, etc. | Est fourni avec le module [Dolirent d'Altairis](https://www.altairis.fr/boutique/dolirent/) |

## Remarques

À noter que ces listes ne sont pas exhaustives. On a ici les modules qui sont liés
plus ou moins directement au processus de collecte propre aux ressourceries.
