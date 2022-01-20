import { State, StateDefinitionBase } from './state'
import { Stack } from '../stack'
import { RenderReason } from '../constants'
import { Veto } from '../veto'

interface StateVirtualDefinition extends StateDefinitionBase {
  type: 'virtual'
  goto: string
}

class StateVirtual extends State {
  private readonly goto: string

  constructor (definition: StateVirtualDefinition) {
    super('virtual', definition)
    this.goto = definition.goto
  }

  renderVeto1 (reason: RenderReason, stack: Stack): Veto | undefined {
    const parentVeto = super.renderVeto1(reason, stack)
    if (parentVeto) {
      console.log('Im on a virtual state, but my parent class as already a veto.')
      return parentVeto
    }

    if (reason === RenderReason.GOING_BACKWARD) {
      console.log('Im on a virtual state, and we are going backward...')
      return { type: 'backward' }
    }

    console.log('Im on a virtual state, and we are not going backward. Go forward...')

    return {
      type: 'forward',
      goto: this.goto
    }
  }

  bindEvents (): void {
    throw new Error('Should not bind events for a virtual state.')
  }

  async possibleGotos (): Promise<string[]> {
    return [this.goto]
  }
}

export {
  StateVirtual,
  StateVirtualDefinition
}
