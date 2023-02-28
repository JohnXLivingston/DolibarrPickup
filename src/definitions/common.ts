import { FormField } from '../lib/state/form'
import { UseUnit } from '../lib/utils/units'

function pushUnitFields (fields: FormField[], fieldNamePrefix: string, fieldEditNamePrefix: string, useUnitWeight: UseUnit, useUnitLength: UseUnit, useUnitSurface: UseUnit, useUnitVolume: UseUnit): void {
  if (useUnitWeight !== '0') {
    fields.push({
      type: 'float',
      name: fieldNamePrefix + 'weight',
      label: 'Poids unitaire (kg)',
      mandatory: useUnitWeight === 'mandatory',
      min: 0,
      max: 1000,
      step: 0.1,
      edit: {
        getDataFromSourceKey: fieldEditNamePrefix + 'weight'
      }
    })
  }
  if (useUnitLength !== '0') {
    fields.push({
      type: 'float',
      name: fieldNamePrefix + 'length',
      label: 'Longueur unitaire (m)',
      mandatory: useUnitLength === 'mandatory',
      min: 0,
      max: 1000,
      step: 0.01,
      edit: {
        getDataFromSourceKey: fieldEditNamePrefix + 'length'
      }
    })
  }
  if (useUnitSurface !== '0') {
    fields.push({
      type: 'float',
      name: fieldNamePrefix + 'surface',
      label: 'Surface unitaire (m²)',
      mandatory: useUnitSurface === 'mandatory',
      min: 0,
      max: 1000,
      step: 0.0001,
      edit: {
        getDataFromSourceKey: fieldEditNamePrefix + 'surface'
      }
    })
  }
  if (useUnitVolume !== '0') {
    fields.push({
      type: 'float',
      name: fieldNamePrefix + 'volume',
      label: 'Volume unitaire (L)',
      mandatory: useUnitVolume === 'mandatory',
      min: 0,
      max: 100000,
      step: 0.01,
      edit: {
        getDataFromSourceKey: fieldEditNamePrefix + 'volume'
      }
    })
  }
}

export {
  pushUnitFields
}
