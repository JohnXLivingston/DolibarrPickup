import { State, StateDefinitionBase } from './state'
import { Stack, StackValue } from '../stack'
import { RenderReason } from '../constants'
import { Veto } from '../veto'
import { nunjucks } from '../nunjucks'

interface ComputeNunjucks {
  name: string,
  label: string,
  format: string
}

interface StateComputeDefinition extends StateDefinitionBase {
  type: 'compute',
  goto: string,
  computeUntil: string,
  nunjucks?: ComputeNunjucks
}

class StateCompute extends State {
  private goto: string
  private computeUntil: string
  private nunjucks?: ComputeNunjucks

  constructor (definition: StateComputeDefinition) {
    super('compute', definition)
    this.goto = definition.goto
    this.nunjucks = definition.nunjucks
    this.computeUntil = definition.computeUntil
  }

  renderVeto1 (reason: RenderReason, stack: Stack): Veto | undefined {
    const parentVeto = super.renderVeto1(reason, stack)
    if (parentVeto) {
      console.log('Im on a compute state, but my parent class as already a veto.')
      return parentVeto
    }

    if (reason === RenderReason.GOING_BACKWARD) {
      console.log('Im on a compute state, and we are going backward...')
      return { type: 'backward' }
    }

    console.log('Im on a compute state, and we are not going backward. Computing...')
    if (this.nunjucks) {
      console.log('I have to render a nunjucks string')
      const r = this.renderNunjucks(stack)
      if (r) {
        const sv: StackValue = {
          label: this.nunjucks.label,
          name: this.nunjucks.name,
          value: r
        }
        stack.setValues(sv)
      }
    }

    return {
      type: 'forward',
      goto: this.goto
    }
  }

  bindEvents (): void {
    throw new Error('Should not bind events for a compute state.')
  }

  renderNunjucks (stack: Stack): string | undefined {
    if (!this.nunjucks) {
      return undefined
    }
    const vars: any = {}
    const sva = stack.getStackValuesUntil(this.computeUntil) || []
    for (let i = 0; i < sva.length; i++) {
      const va: StackValue = sva[i]
      vars[va.name] = va.display || va.value
    }
    return nunjucks.renderString(this.nunjucks.format, vars)
  }

  async possibleGotos () {
    return [this.goto]
  }

  async possibleNunjucks () {
    if (!this.nunjucks) return []
    return [{
      format: this.nunjucks.format
    }]
  }
}

export {
  StateCompute,
  StateComputeDefinition
}
