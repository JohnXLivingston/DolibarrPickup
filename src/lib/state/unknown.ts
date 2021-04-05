import { State } from './state'

class StateUnknown extends State {
  constructor () {
    super('unknown', {
      label: '???'
    })
  }

  bindEvents () {}

  async possibleGotos () {
    return []
  }
}

export {
  StateUnknown
}
