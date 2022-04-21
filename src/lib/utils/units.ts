type UnitsEditMode = 'product' | 'pickupline'
type UseUnit = '0' | 'optional' | 'mandatory'

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
  UseUnit,
  readUnitsEditMode,
  readUseUnit
}
