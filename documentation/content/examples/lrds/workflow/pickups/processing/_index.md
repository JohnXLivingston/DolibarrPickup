+++
title="Traitement de la collecte"
weight=40
chapter=false
description="Validation de la saisie."
+++

À partir de maintenant, il faut retourner sur l'application Dolibarr classique.

![En cours de traitement](./../../images/dolibarr_pickup_waiting.png?classes=shadow,border&height=200px)

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
![Correction du poids](./../../images//dolibarr_pickup_correct_weight.png?classes=shadow,border)
{{% /notice %}}

À partir du statut «En cours de traitement», un pdf «bon de collecte» est généré.
Celui-ci sera à faire signer par le donneur (le tiers qui a fourni le matériel).

![Bon de collecte](./../../images/dolibarr_pickup_pdf.png?classes=shadow,border)
