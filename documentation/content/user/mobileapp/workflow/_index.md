+++
title="Flux de travail"
weight=40
chapter=false
+++

{{% notice note %}}
L'application mobile est entièrement paramétrable.
Cette page décrit de manière générique l'enchaînement des différents écrans,
mais cela peut varier grandement d'une installation à l'autre.
De manière générale, les élements entourés en pointillés peuvent être désactivés.
{{% /notice %}}

{{<mermaid>}}
graph TD;
  PickCollecte[Sélection d'une collecte]
  subgraph Création d'une fiche collecte
    PickEntrepot[Sélection de l'entrepot]
    PickSociete[Sélection du donneur]
    subgraph Création du donneur
      FormSociete[Formulaire donneur]
      SaveSociete[Sauvegarde donneur]
    end
    ShowSociete[Fiche donneur]
    FormCollecte[Formulaire collecte]
    SaveCollecte[Sauvegarde collecte]
  end
  subgraph Ajout de produits sur la fiche
    ShowCollecte[Fiche collecte]
    PickProduit[Sélection de fiche produit]
    subgraph Création de fiche produit
      PickCategorie[Sélection de la catégorie]
      FormProduit[Formulaire produit]
      FormProduitPoids[Saisie du poids]
      SaveProduit[Sauvegarde produit]
    end
    ShowProduit[Fiche produit]
    FormQuantite[Saisie de la quantité]
    SaveLigne[Sauvegarde de la ligne]
  end

  PickCollecte -->|Nouvelle collecte| PickEntrepot
  PickEntrepot --> PickSociete
  PickSociete --> ShowSociete
  PickSociete -->|Nouveau donneur| FormSociete
  FormSociete --> SaveSociete
  SaveSociete --> ShowSociete
  ShowSociete --> FormCollecte
  FormCollecte --> SaveCollecte
  PickCollecte --> ShowCollecte
  SaveCollecte --> ShowCollecte

  ShowCollecte --> PickProduit

  PickProduit --> ShowProduit
  PickProduit -->|Nouveau produit| PickCategorie
  PickCategorie --> FormProduit
  FormProduit --> FormProduitPoids
  FormProduitPoids --> SaveProduit
  SaveProduit --> ShowProduit

  ShowProduit --> FormQuantite
  FormQuantite --> SaveLigne
  SaveLigne --> ShowCollecte

  style PickEntrepot stroke-dasharray: 5 5
  style PickCategorie stroke-dasharray: 5 5
{{< /mermaid >}}

{{% notice tip %}}
L'écran de sélection de collecte ne montre que les collectes que vous avez créé
et qui sont encore en statut «Brouillon».
Il est possible, suivant vos droits d'accès, que vous puissiez aussi voir les
collectes créées par les autres utilisateur⋅rice⋅s, mais dans tous les cas seules
celles en statut «Brouillon» seront listées.
{{% /notice %}}
