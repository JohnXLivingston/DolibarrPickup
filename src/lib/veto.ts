interface VetoForward {
  type: 'forward',
  goto: string
}

interface VetoBackward {
  type: 'backward'
}

type Veto = VetoForward | VetoBackward

export {
  Veto,
  VetoBackward,
  VetoForward
}
