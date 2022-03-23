// import type { StackValue } from '../lib/stack'
import { StateDefinition } from '../lib/state/index'

export function createPickupLine (goto: string): StateDefinition {
  return {
    type: 'form',
    label: 'Quantité',
    goto,
    edit: {
      stackKey: 'pickup_line_id',
      getDataKey: 'pickupline',
      convertData: (key: string, v: any) => {
        // if (key === 'name') {
        //   const r: StackValue = {
        //     label: 'Produit',
        //     name: 'dummy_product_name',
        //     value: v,
        //     silent: true,
        //     invisible: false
        //   }
        //   return r
        // }
        if (key === 'qty') {
          return {
            value: v,
            name: key
          }
        }
        return false
      }
    },
    fields: [
      {
        type: 'integer',
        name: 'qty', // FIXME: is this correct?
        label: 'Quantité',
        mandatory: true,
        default: '1',
        min: 0,
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
