// import type { StackValue } from '../lib/stack'
import { StateDefinition, FormField } from '../lib/state/index'
import { UnitsOptions } from '../lib/utils/units'
import { pushUnitFields } from './common'

export function createPickupLine (
  editMode: boolean,
  unitsOptions: UnitsOptions,
  usePickuplineDescription: boolean,
  goto: string
): StateDefinition {
  const fields: FormField[] = [
    {
      type: 'integer',
      name: 'qty', // FIXME: is this correct?
      label: 'Quantité',
      mandatory: true,
      default: '1',
      min: 0,
      max: 1000,
      edit: {
        getDataFromSourceKey: 'qty'
      }
    }
  ]

  if (unitsOptions.editMode === 'pickupline') {
    pushUnitFields(fields, 'line_', 'line_', unitsOptions)
  }

  if (usePickuplineDescription) {
    fields.push({
      type: 'text',
      name: 'line_description',
      label: 'Remarques',
      mandatory: false,
      edit: {
        getDataFromSourceKey: 'line_description'
      }
    })
  }

  const result: StateDefinition = {
    type: 'form',
    label: 'Quantité',
    goto,
    fields
  }

  if (editMode) {
    result.edit = {
      stackKey: 'pickup_line_id',
      getDataKey: 'pickupline'
    }
  }

  return result
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
