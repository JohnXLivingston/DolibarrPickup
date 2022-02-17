import type { Stack } from '../stack'
import { State, StateDefinitionBase } from './state'

interface Option {
  value: string
  label: string
}

type Options = Option[]

interface StateSelectDefinition extends StateDefinitionBase {
  type: 'select'
  options: Options
}

class StateSelect extends State {
  readonly options: Options

  constructor (definition: StateSelectDefinition) {
    super('select', definition)
    this.options = definition.options
  }

  bindEvents (_dom: JQuery, _stack: Stack): void {
    console.error('not implemented yet')
  }

  async possibleGotos (): Promise<string[]> {
    throw new Error('Not implemented yet')
  }
}

export {
  StateSelect,
  StateSelectDefinition
}
