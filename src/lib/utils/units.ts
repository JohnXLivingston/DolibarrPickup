type UnitsEditMode = 'product' | 'pickupline'
type UseUnit = '0' | 'optional' | 'mandatory'

interface UnitsOptions {
  useUnitWeight: UseUnit
  useUnitLength: UseUnit
  useUnitWidth: UseUnit
  useUnitHeight: UseUnit
  useUnitSurface: UseUnit
  useUnitVolume: UseUnit
  weightUnit: string
  weightUnitLabel: string
  sizeUnit: string
  sizeUnitLabel: string
  surfaceUnit: string
  surfaceUnitLabel: string
  volumeUnit: string
  volumeUnitLabel: string
  editMode: UnitsEditMode
}

function readUnitsEditMode (s: string | undefined): UnitsEditMode {
  if (s === 'pickupline') {
    return s
  }
  return 'product'
}

function readUseUnit (s: string | undefined): UseUnit {
  if (s === 'mandatory' || s === 'optional') { return s }
  return '0'
}

export {
  UnitsEditMode,
  UnitsOptions,
  UseUnit,
  readUnitsEditMode,
  readUseUnit
}
