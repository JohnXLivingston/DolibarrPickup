import type { NunjucksVars } from '../nunjucks'
import { State, StateDefinitionBase, StateRetrievedData } from './state'
import { Stack } from '../stack'
import { getData } from '../data'

interface ShowFieldBase {
  type: string
  label: string
  name: string
}

interface ShowFieldVarchar extends ShowFieldBase {
  type: 'varchar'
}
interface ShowFieldText extends ShowFieldBase {
  type: 'text'
}
interface ShowFieldBoolean extends ShowFieldBase {
  type: 'boolean'
  total?: boolean
  totalQtyFieldName?: string // Optional field name, to apply a quantity on true values
}
interface ShowFieldInteger extends ShowFieldBase {
  type: 'integer'
  total?: boolean
}
interface ShowFieldLines extends ShowFieldBase {
  type: 'lines'
  lines: ShowFields
}

type ShowField = ShowFieldVarchar | ShowFieldText | ShowFieldBoolean | ShowFieldLines | ShowFieldInteger
type ShowFields = ShowField[]
interface StateShowDefinition extends StateDefinitionBase {
  type: 'show'
  key: string
  primaryKey: string
  fields: ShowFields
  addGoto?: string
  addLabel?: string
  okGoto?: string
}

class StateShow extends State {
  private readonly key: string
  private readonly primaryKey: string
  public readonly fields: ShowFields
  public readonly addGoto?: string
  public readonly addLabel?: string
  public readonly okGoto?: string

  constructor (definition: StateShowDefinition) {
    super('show', definition)
    this.key = definition.key
    this.primaryKey = definition.primaryKey
    this.fields = definition.fields
    this.addGoto = definition.addGoto
    this.addLabel = definition.addLabel
    this.okGoto = definition.okGoto
  }

  private _getValue (stack: Stack): string | undefined {
    return stack.searchValue(this.primaryKey)
  }

  retrieveData (stack: Stack, force: boolean): StateRetrievedData {
    const r = super.retrieveData(stack, force)

    const value = this._getValue(stack)
    if (value === undefined) {
      r.set('data', false)
      return r
    }
    r.set('data', getData(this.key, 'get', force, { id: value }))
    return r
  }

  _renderVars (stack: Stack, retrievedData: StateRetrievedData, h: NunjucksVars): void {
    h.fields = this.fields
    h.data = retrievedData.get('data')
  }

  bindEvents (dom: JQuery, _stack: Stack): void {
    dom.on('click.stateEvents', '[pickupmobile-show-add]', () => {
      if (this.addGoto) {
        dom.trigger('goto-state', [this.addGoto])
      }
    })
    dom.on('click.stateEvents', '[pickupmobile-show-ok]', () => {
      if (this.okGoto) {
        dom.trigger('goto-state', [this.okGoto])
      }
    })
  }

  async possibleGotos (): Promise<string[]> {
    const a: string[] = []
    if (this.addGoto) {
      a.push(this.addGoto)
    }
    if (this.okGoto) {
      a.push(this.okGoto)
    }
    return a
  }
}

export {
  ShowField,
  ShowFields,
  StateShow,
  StateShowDefinition
}
