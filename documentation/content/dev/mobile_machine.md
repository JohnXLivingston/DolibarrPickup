+++
title = "API de l'application Mobile"
date = 2022-02-25T16:54:00+01:00
weight = 5
chapter = false
+++

# Application mobile : Automate

L'application mobile utilise un automate à état fini pour son fonctionnement.

L'automate est décrit dans le fichier [mobile.ts](/src/mobile.ts).

À l'exécution, on aura une pile (stack) qui retiendra tous les choix successifs, et toutes les valeurs des différents formulaires rencontrés.

## Définition de l'automate

La définition de l'automate est un object javascript.

Chaque clé de cet objet représente un écran.

L'écran initial doit avoir pour clé «init».

Chaque état est ensuite décrit par un object javascript. La clé `type` donne le type d'écran.

## États

Le présent chapitre liste les différents états et leurs différentes options.

### State

La classe [State](/src/lib/state/state.ts) est la classe de base.
Les options qu'auront tous les autres états :

#### type

Le type de l'état. Le fichier [index.ts](/src/lib/state/index.ts) va se baser sur cette valeur pour instancier la bonne classe.

#### label

Le nom de l'écran. Sera affiché en haut.

### StateChoice

La classe [StateChoice](/src/lib/state/choice.ts) représente un écran qui proposera plusieurs choix, sous forme de boutons.

#### choices

L'option `choices` est un tableau d'objets de la forme :

```javascript
{
  label: 'Le libellé du choix',
  value: 'La valeur associée', // c'est la valeur qui sera mise dans la stack
  goto: 'stateX', // vers quel state aller quand on fait ce choix
  name: 'xxx' // Optionnel. voir plus loin
}
```

Le champ `name` est optionnel. Il permet de changer le nom du champ qui sera stocké dans la stack (par défaut c'est le nom de l'état courant).

### StateForm

La classe [StateForm](/src/lib/state/form.ts) représente un formulaire.

#### goto

Vers quel état aller quand le formulaire est validé.

#### fields

Un tableau de définition de champs.

Ce code étant encore amené à beaucoup bouger, la documentation de ce qu'est un `field` est volontairement incomplète. On ne retrouve ici que les attributs de base, communs à tous les types de champs.

##### type

Le type du champ (`varchar`, `text`, `integer`, `select`, ...).

##### name

Le nom technique du champs. Ce nom sera la clé utilisée pour la sauvegarde des données.

##### label

Le libellé du champs.

##### mandatory

Si le champ est obligatoire ou non (booléen).

##### default (optionnel)

Valeur par défaut du champ.

##### maxlength (optionnel)

Longueur max de la valeur.

##### notes (optionnel)

Permet d'ajouter des annotations à coté d'un champs, pour guider l'usage.

Plusieurs formes possibles :

```javascript
{
  label: 'Ceci est une note...'
}
```

On peut aussi se baser sur des choix précédemment fait (et donc dans la stack) pour personnaliser les notes à partir d'une liste remontée du backend.

Par exemple, le backend a une liste de tags, pour chaque tag une note optionnelle à afficher. On va donc charger une liste de tags depuis le backend, chercher le tag qui est actuellement appliqué au produit, et afficher la note correspondante.

```typescript
interface {
  load: string, // Le nom de la source backend à utiliser (exemple : les pcat)
  basedOnValueOf: string, // nom de champs qu'on va chercher dans la stack. On utilisera la valeur de ce champs pour filtrer 
  key: string, // le champs clé dans la source de donnée, dans lequel il vaut chercher la valeur de la stack (ex: rowid)
  field: string, // le nom du champs de la source de donnée qui contient les notes à afficher (ex: notes)
}
```

#### Mode edition

Un formulaire peut être en mode «édition» pour modifier des données existantes.
La définition sera alors du type suivant :

```typescript
interface StateFormDefinition extends StateDefinitionBase {
  type: 'form'
  goto: string
  edit?: StateFormEditDefinition
  fields: FormField[]
}
interface StateFormEditDefinition {
  stackKey: string
  getDataKey: string
  convertData: (key: string, v: any) => JQuery.NameValuePair | false // | StackValue
}
```

`stackKey` est le nom de la clé primaire se trouvant dans la stack.

`getDataKey` est le nom de la source de donnée à utiliser.

`convertData` est une méthode qui va transformer les données de la source, pour
les faire correspondre aux champs du formulaire.
En effet, les noms des champs ne correspondent pas forcément, et pour certaines
valeurs il faut potentiellement les transformer.

### StatePick

La classe [StatePick](/src/lib/state/pick.ts) proposera de sélectionner une valeur dans une ou plusieurs liste(s) (en utilisant des widgets de type «autocomplete»).

#### key

La clé qui sera utilisée pour charger la liste depuis le backend.

#### fields

On peut donc sélectionner une ligne avec un ou plusieurs champs successifs. Exemple : d'abord la marque, puis la référence.
La liste des références sera filtrée en fonction de la marque préalablement choisie.

La définition d'un field :

```typescript
interface PickField {
  name: string, // le nom du champs tel qu'il sera stocké dans la stack
  label: string, // le libellé du champs
  applyFilter?: 'lowerCase' | 'upperCase' | 'localeLowerCase' | 'localeUpperCase' // un filtre à appliquer aux valeurs pour les homogénéiser. 
}
```

#### primaryKey

Nom du champs dans la source de donnée qui contient la valeur à stocker dans la stack (la clé primaire).

#### goto et itemGotoField(optionnel)

L'état où aller après avoir sélectionné une valeur.

Chaque item peut surcharger cette valeur.
Si itemGotoField est fourni, on cherchera un champs de ce nom dans l'item sélectionnée. Si la source a fourni une valeur, ce sera le nom du state vers lequel aller pour cette valeur spécifique.

Ainsi, le backend peut piloter le comportement de l'automate. Par exemple, afficher un formulaire différent en fonction de la catégorie de produit.

#### creationGoto / creationLabel (optionnel)

Si fourni, un bouton «créer nouveau» sera affiché. La valeur est le state dans lequel aller.

creationLabel permet de personnaliser le libellé du bouton.

### StateSelect

La classe [StateSelect](/src/lib/state/select.ts) est un état qui affichera un `select`.

#### options

```typescript
interface Option {
  value: string,
  label: string
}
```

### StateShow

La classe [StateShow](/src/lib/state/show.ts) représente une page va afficher un objet remonté du backend.
Par exemple une fiche produit.

#### key

Le type d'objet (clé pour la source backend). Par exemple `product`.

#### primaryKey

Nom de la clé à chercher dans la stock, pour avoir l'id à chercher niveau backend.
Par exemple `product` si dans ma stack j'ai un champ `product` dont la valeur est l'id produit.

#### fields

Les champs à afficher.
Voir le code pour la doc (le code bouge encore, ce sera à documenter plus tard).

#### okGoto (optionnel)

Affiche un bouton «ok» qui ira sur le state correspondant.

#### addGoto / addLabel (optionnel)

Affiche un bouton «ajouter» qui ira sur le state correspondant.

addLabel permet de personnaliser le libellé du bouton.

### StateVirtual

La classe [StateVirtual](/src/lib/state/virtual.ts) est un état virtuel qui avance automatiquement.

### StateCompute

La classe [StateCompute](/src/lib/state/compute.ts) est un état virtuel qui permet d'effectuer des modifications sur les valeurs en stack.

Par exemple d'injecter les valeurs de la stack dans un template, et de de s'en serveur pour générer une description.

Cette classe n'est pas documentée pour l'instant, car non utilisée et sujette à changements.

### StateSave

La classe [StateSave](/src/lib/state/save.ts) permet de faire un récapitulatif des données dans la stack, et de proposer de les enregistrer.

Documentation à faire.

## Notes

Pour débuguer, on peut accéder à la machine dans la console JS du navigateur via `window.pickupMobileMachine`.
La stack est dans `window.pickupMobileMachine.stack`. On peut en avoir une version «lisible à l'oeil humain» via `window.pickupMobileMachine.stack.dumpForHuman()`.

On peut également débuguer le cache des données remontées du backend via `window.pickupMobileDebugDataCache()`.

Il y a une fonction qui essaie de trouver les problèmes de définition de l'automate (status manquants, template manquant, etc...) : `window.pickupMobileMachine.findProblems().then(console.log)`.
