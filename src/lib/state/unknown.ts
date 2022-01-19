import { State } from './state'

class StateUnknown extends State {
  constructor () {
    super('unknown', {
      label: '???'
    })
  }

  bindEvents (): void {}

  async possibleGotos (): Promise<string[]> {
    return []
  }
}

export {
  StateUnknown
}
