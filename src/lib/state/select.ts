import { State, StateDefinitionBase } from './state'

interface Option {
  value: string,
  label: string
}

type Options = Option[]

interface StateSelectDefinition extends StateDefinitionBase {
  type: 'select',
  options: Options
}

class StateSelect extends State {
  readonly options: Options

  constructor (definition: StateSelectDefinition) {
    super('select', definition)
    this.options = definition.options
  }

  bindEvents (dom: JQuery): void {
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
