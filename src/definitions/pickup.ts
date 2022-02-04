import { StateDefinition } from '../lib/state/index'

export function choosePickup (goto: string): StateDefinition {
  return {
    type: 'pick',
    label: 'Accueil',
    key: 'pickup',
    goto: 'show_pickup',
    creationGoto: goto,
    primaryKey: 'rowid',
    fields: [
      { name: 'display', label: 'Collecte' }
    ]
  }
}

export function createPickup (goto: string): StateDefinition {
  return {
    type: 'form',
    label: 'Nouvelle collecte',
    goto,
    fields: [
      {
        name: 'date_pickup',
        type: 'date',
        label: 'Date de la collecte',
        mandatory: true,
        defaultToToday: true
      },
      {
        type: 'text',
        name: 'description',
        label: 'Remarques',
        mandatory: false
      }
    ]
  }
}

export function savePickup (goto: string, saveUntil: string): StateDefinition {
  return {
    type: 'save',
    label: 'Création de la collecte',
    key: 'pickup',
    primaryKey: 'rowid', // FIXME: to check.
    labelKey: 'Collecte',
    saveUntil,
    goto
  }
}

export function showPickup (addGoto: string): StateDefinition {
  return {
    type: 'show',
    label: 'Collecte',
    key: 'pickup',
    primaryKey: 'pickup', // FIXME: should be less ambigous
    addGoto,
    fields: [
      {
        type: 'varchar',
        name: 'display',
        label: 'Collecte'
      },
      {
        type: 'varchar',
        name: 'date',
        label: 'Date'
      },
      {
        type: 'text',
        name: 'description',
        label: 'Description'
      },
      {
        type: 'lines',
        name: 'lines',
        label: 'Produits',
        lines: [
          {
            type: 'varchar',
            name: 'name',
            label: 'Produit'
          },
          {
            type: 'boolean',
            name: 'deee',
            label: 'DEEE',
            total: true
          },
          {
            type: 'integer',
            name: 'qty',
            label: 'Quantité',
            total: true
          }
        ]
      }
    ]
  }
}
