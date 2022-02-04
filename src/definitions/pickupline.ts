import { StateDefinition } from '../lib/state/index'

export function createPickupLine (goto: string): StateDefinition {
  return {
    type: 'form',
    label: 'Quantité',
    goto,
    fields: [
      {
        type: 'integer',
        name: 'qty', // FIXME: is this correct?
        label: 'Quantité',
        mandatory: true,
        default: '1',
        min: 1,
        max: 1000
      }
    ]
  }
}

export function savePickupLine (goto: string, saveUntil: string, removeUntil: string, removeFromStack: boolean): StateDefinition {
  return {
    type: 'save',
    label: 'Ajout du produit sur la collecte',
    key: 'pickupline',
    primaryKey: 'rowid', // FIXME: to check.
    labelKey: 'Produit',
    dependingCacheKey: 'pickup',
    saveUntil,
    removeUntil,
    removeFromStack,
    goto
  }
}
