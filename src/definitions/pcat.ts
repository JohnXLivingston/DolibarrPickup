import { StateDefinition } from '../lib/state/index'

export function pickPCat (goto: string, itemGotoField: string | undefined): StateDefinition {
  return {
    type: 'pick',
    label: 'Catégorie du produit',
    key: 'pcat',
    primaryKey: 'rowid',
    goto,
    creationGoto: undefined,
    itemGotoField,
    fields: [
      { name: 'label', label: 'Catégorie' }
    ]
  }
}
