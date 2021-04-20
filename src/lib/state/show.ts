import { State, StateDefinitionBase } from './state'
import { Stack } from '../stack'
import { getData } from '../data'

interface StateShowDefinition extends StateDefinitionBase {
  type: 'show'
  key: string
  primaryKey: string
}

class StateShow extends State {
  private readonly key: string
  private readonly primaryKey: string

  constructor (definition: StateShowDefinition) {
    super('show', definition)
    this.key = definition.key
    this.primaryKey = definition.primaryKey
  }

  private _getValue (stack: Stack): string | undefined {
    return stack.searchValue(this.primaryKey)
  }

  renderVars (stack: Stack) {
    const h = super.renderVars(stack)
    const value = this._getValue(stack)
    if (value === undefined) {
      h.missingKey = true
      return h
    }
    h.data = getData(this.key, 'get', false, { id: value })
    if (h.data.status === 'pending') {
      setTimeout(() => {
        const div = $('[pickupmobile-show-pending]')
        if (div.length) {
          div.trigger('rerender-state')
        } else {
          console.log('The pending div is not in the dom anymore.')
        }
      }, 500)
    }
    return h
  }

  bindEvents (dom: JQuery, stack: Stack): void {
    dom.on('click.stateEvents', '[pickupmobile-show-reload]', () => {
      const value = this._getValue(stack)
      if (value !== undefined) {
        getData(this.key, 'get', true, { id: value }) // force reload
        dom.trigger('rerender-state')
      }
    })
  }

  async possibleGotos () {
    return []
  }
}

export {
  StateShow,
  StateShowDefinition
}
