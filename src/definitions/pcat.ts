import { StateDefinition } from '../lib/state/index'

export function pickPCat (goto: string): StateDefinition {
  return {
    type: 'pick',
    label: 'Catégorie du produit',
    key: 'pcat',
    primaryKey: 'rowid',
    goto,
    creationGoto: undefined,
    fields: [
      { name: 'label', label: 'Catégorie' }
    ]
  }
}
