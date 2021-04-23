import { State, StateDefinitionBase } from './state'
import { Stack } from '../stack'
import { getData } from '../data'

interface ShowFieldBase {
  type: string,
  label: string,
  name: string
}

interface ShowFieldVarchar extends ShowFieldBase {
  type: 'varchar'
}
interface ShowFieldText extends ShowFieldBase {
  type: 'text'
}
interface ShowFieldInteger extends ShowFieldBase {
  type: 'integer'
}
interface ShowFieldLines extends ShowFieldBase {
  type: 'lines'
  lines: ShowField[]
}

type ShowField = ShowFieldVarchar | ShowFieldText | ShowFieldLines | ShowFieldInteger
interface StateShowDefinition extends StateDefinitionBase {
  type: 'show'
  key: string
  primaryKey: string,
  fields: ShowField[],
  addGoto?: string
}

class StateShow extends State {
  private readonly key: string
  private readonly primaryKey: string
  public readonly fields: ShowField[]
  public readonly addGoto?: string

  constructor (definition: StateShowDefinition) {
    super('show', definition)
    this.key = definition.key
    this.primaryKey = definition.primaryKey
    this.fields = definition.fields
    this.addGoto = definition.addGoto
  }

  private _getValue (stack: Stack): string | undefined {
    return stack.searchValue(this.primaryKey)
  }

  renderVars (stack: Stack) {
    const h = super.renderVars(stack)
    h.fields = this.fields
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
    dom.on('click.stateEvents', '[pickupmobile-show-add]', () => {
      if (this.addGoto) {
        dom.trigger('goto-state', [this.addGoto])
      }
    })
  }

  async possibleGotos () {
    const a: string[] = []
    if (this.addGoto) {
      a.push(this.addGoto)
    }
    return a
  }
}

export {
  StateShow,
  StateShowDefinition
}
