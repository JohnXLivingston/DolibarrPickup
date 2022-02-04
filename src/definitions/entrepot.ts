import { StateDefinition } from '../lib/state/index'

export function pickEntrepot (goto: string): StateDefinition {
  return {
    type: 'pick',
    label: 'Entrepot',
    key: 'entrepot',
    goto,
    primaryKey: 'rowid',
    fields: [
      { name: 'ref', label: 'Entrepôt' }
    ]
  }
}
