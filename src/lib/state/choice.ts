import { State, StateDefinitionBase } from './state'
import { Stack, StackValue } from '../stack'

interface Choice {
  label: string,
  value: string,
  goto: string,
  name?: string // if provided, this choice value will be save in field "name". It overrides StateChoice.name.
}

type Choices = Choice[]

interface StateChoiceDefinition extends StateDefinitionBase {
  type: 'choice',
  choices: Choices
}

class StateChoice extends State {
  readonly choices: Choices
  readonly name?: string // if provided, the choice value will be saved in field "name". Can be overriden in choice.name.

  constructor (definition: StateChoiceDefinition) {
    super('choice', definition)
    this.choices = definition.choices
  }

  bindEvents (dom: JQuery, stack: Stack): void {
    dom.on('click.stateEvents', '[pickupmobile-button]', ev => {
      console.log('Clicking on button...')
      const value = $(ev.currentTarget).attr('pickupmobile-button')
      const choice = this.choices.find((c) => c.value === value)
      if (!choice) {
        throw new Error('Cant find this choice: ' + value)
      }
      const sv: StackValue = {
        label: this.label,
        name: this.name || choice.name || '', // FIXME '' value.
        value: value || '',
        silent: !(this.name || choice.name),
        display: choice.label
      }
      stack.setValues(sv)
      dom.trigger('goto-state', [choice.goto])
    })
  }

  async possibleGotos () {
    const r: {[key: string]: true} = {}
    for (let i = 0; i < this.choices.length; i++) {
      const goto = this.choices[i].goto
      if (goto) r[goto] = true
    }
    return Object.keys(r)
  }
}

export {
  StateChoice,
  StateChoiceDefinition
}
