// import type { StackValue } from '../lib/stack'
import { StateDefinition, FormField } from '../lib/state/index'
import { UseUnit } from '../lib/utils/units'
import { pushUnitFields } from './common'

export function createPickupLine (
  editMode: boolean,
  useEditUnits: boolean,
  useUnitWeight: UseUnit, useUnitLength: UseUnit, useUnitSurface: UseUnit, useUnitVolume: UseUnit,
  usePickuplineDescription: boolean, goto: string
): StateDefinition {
  const fields: FormField[] = [
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

  if (editMode && useEditUnits) {
    pushUnitFields(fields, 'line_', useUnitWeight, useUnitLength, useUnitSurface, useUnitVolume)
  }

  if (usePickuplineDescription) {
    fields.push({
      type: 'text',
      name: 'line_description',
      label: 'Remarques',
      mandatory: false
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
        if (usePickuplineDescription && key === 'line_description') {
          return {
            value: v,
            name: key
          }
        }
        if (useEditUnits && useUnitWeight && key === 'line_weight' && v !== null) {
          return {
            value: v,
            name: key
          }
        }
        if (useEditUnits && useUnitLength && key === 'line_length' && v !== null) {
          return {
            value: v,
            name: key
          }
        }
        if (useEditUnits && useUnitSurface && key === 'line_surface' && v !== null) {
          return {
            value: v,
            name: key
          }
        }
        if (useEditUnits && useUnitVolume && key === 'line_volume' && v !== null) {
          return {
            value: v,
            name: key
          }
        }
        if (key === 'qty') {
          return {
            value: v,
            name: key
          }
        }
        return false
      }
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
