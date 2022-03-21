type UseUnit = '0' | 'optional' | 'mandatory'

function readUseUnit (s: string | undefined): UseUnit {
  if (s === 'mandatory' || s === 'optional') { return s }
  return '0'
}

export {
  UseUnit,
  readUseUnit
}
