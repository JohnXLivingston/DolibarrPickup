import { FormField } from '../lib/state/form'
import { UnitsOptions } from '../lib/utils/units'

function pushUnitFields (
  fields: FormField[],
  fieldNamePrefix: string,
  fieldEditNamePrefix: string,
  unitsOptions: UnitsOptions
): void {
  const {
    useUnitWeight,
    useUnitLength,
    useUnitWidth,
    useUnitHeight,
    useUnitSurface,
    useUnitVolume
  } = unitsOptions

  if (useUnitWeight !== '0') {
    fields.push({
      type: 'float',
      name: fieldNamePrefix + 'weight',
      label: 'Poids unitaire (' + unitsOptions.weightUnitLabel + ')',
      mandatory: useUnitWeight === 'mandatory',
      min: 0,
      max: 1000000,
      step: 0.001,
      edit: {
        getDataFromSourceKey: fieldEditNamePrefix + 'weight'
      }
    })

    fields.push({
      type: 'hidden',
      name: fieldNamePrefix + 'weight_unit',
      label: 'Unité de poids',
      mandatory: false,
      default: unitsOptions.weightUnit
    })
  }
  if (useUnitLength !== '0') {
    fields.push({
      type: 'float',
      name: fieldNamePrefix + 'length',
      label: 'Longueur unitaire (' + unitsOptions.sizeUnitLabel + ')',
      mandatory: useUnitLength === 'mandatory',
      min: 0,
      max: 1000000,
      step: 0.01,
      edit: {
        getDataFromSourceKey: fieldEditNamePrefix + 'length'
      }
    })

    fields.push({
      type: 'hidden',
      name: fieldNamePrefix + 'length_unit',
      label: 'Unité de longueur',
      mandatory: false,
      default: unitsOptions.sizeUnit
    })
  }
  if (useUnitWidth !== '0') {
    fields.push({
      type: 'float',
      name: fieldNamePrefix + 'width',
      label: 'Largeur unitaire (' + unitsOptions.sizeUnitLabel + ')',
      mandatory: useUnitWidth === 'mandatory',
      min: 0,
      max: 1000000,
      step: 0.01,
      edit: {
        getDataFromSourceKey: fieldEditNamePrefix + 'width'
      }
    })

    fields.push({
      type: 'hidden',
      name: fieldNamePrefix + 'width_unit',
      label: 'Unité de largeur',
      mandatory: false,
      default: unitsOptions.sizeUnit
    })
  }
  if (useUnitHeight !== '0') {
    fields.push({
      type: 'float',
      name: fieldNamePrefix + 'height',
      label: 'Hauteur unitaire (' + unitsOptions.sizeUnitLabel + ')',
      mandatory: useUnitHeight === 'mandatory',
      min: 0,
      max: 1000000,
      step: 0.01,
      edit: {
        getDataFromSourceKey: fieldEditNamePrefix + 'height'
      }
    })

    fields.push({
      type: 'hidden',
      name: fieldNamePrefix + 'height_unit',
      label: 'Unité de hauteur',
      mandatory: false,
      default: unitsOptions.sizeUnit
    })
  }
  if (useUnitSurface !== '0') {
    fields.push({
      type: 'float',
      name: fieldNamePrefix + 'surface',
      label: 'Surface unitaire (' + unitsOptions.surfaceUnitLabel + ')',
      mandatory: useUnitSurface === 'mandatory',
      min: 0,
      max: 1000000,
      step: 0.0001,
      edit: {
        getDataFromSourceKey: fieldEditNamePrefix + 'surface'
      }
    })

    fields.push({
      type: 'hidden',
      name: fieldNamePrefix + 'surface_unit',
      label: 'Unité de surface',
      mandatory: false,
      default: unitsOptions.surfaceUnit
    })
  }
  if (useUnitVolume !== '0') {
    fields.push({
      type: 'float',
      name: fieldNamePrefix + 'volume',
      label: 'Volume unitaire (' + unitsOptions.volumeUnitLabel + ')',
      mandatory: useUnitVolume === 'mandatory',
      min: 0,
      max: 100000000,
      step: 0.01,
      edit: {
        getDataFromSourceKey: fieldEditNamePrefix + 'volume'
      }
    })

    fields.push({
      type: 'hidden',
      name: fieldNamePrefix + 'volume_unit',
      label: 'Unité de volume',
      mandatory: false,
      default: unitsOptions.volumeUnit
    })
  }
}

export {
  pushUnitFields
}
