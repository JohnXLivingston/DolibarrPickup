import { State, StateDefinitionBase, PossibleNunjucks } from './state'
import { Stack, StackValue } from '../stack'
import { RenderReason } from '../constants'
import { Veto } from '../veto'
import { nunjucks } from '../nunjucks'

interface ComputeNunjucks {
  name: string
  label: string
  format: string
}

type ComputeFunction = (stack: Stack, vars: any) => undefined | StackValue | StackValue[]

interface StateComputeDefinition extends StateDefinitionBase {
  type: 'compute'
  goto: string
  computeUntil: string
  nunjucks?: ComputeNunjucks
  func?: ComputeFunction
}

class StateCompute extends State {
  private readonly goto: string
  private readonly computeUntil: string
  private readonly nunjucks?: ComputeNunjucks
  private readonly func?: ComputeFunction

  constructor (definition: StateComputeDefinition) {
    super('compute', definition)
    this.goto = definition.goto
    this.nunjucks = definition.nunjucks
    this.func = definition.func
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
    const valuesToPush: StackValue[] = []
    if (this.nunjucks) {
      console.log('I have to render a nunjucks string')
      const r = this.renderNunjucks(stack)
      if (r) {
        const sv: StackValue = {
          label: this.nunjucks.label,
          name: this.nunjucks.name,
          value: r
        }
        valuesToPush.push(sv)
      }
    }

    if (this.func) {
      console.log('I have to call a compute function')
      const vars: any = {}
      const sva = stack.getStackValuesUntil(this.computeUntil) ?? []
      for (let i = 0; i < sva.length; i++) {
        const va: StackValue = sva[i]
        vars[va.name] = va.display ?? va.value
      }
      const r = this.func(stack, vars)
      if (r) {
        if (Array.isArray(r)) {
          valuesToPush.push(...r)
        } else {
          valuesToPush.push(r)
        }
      }
    }
    if (valuesToPush.length > 0) {
      stack.setValues(valuesToPush)
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
    const sva = stack.getStackValuesUntil(this.computeUntil) ?? []
    for (let i = 0; i < sva.length; i++) {
      const va: StackValue = sva[i]
      vars[va.name] = va.display ?? va.value
    }
    return nunjucks.renderString(this.nunjucks.format, vars)
  }

  async possibleGotos (): Promise<string[]> {
    return [this.goto]
  }

  async possibleNunjucks (): Promise<PossibleNunjucks[]> {
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
