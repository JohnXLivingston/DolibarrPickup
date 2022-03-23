import type { NunjucksVars } from '../nunjucks'
import { State, StateDefinitionBase, StateRetrievedData } from './state'
import { Stack, StackValue } from '../stack'
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

interface ShowFieldEditPushToStackBase {
  pushOnStackKey: string // name to use when pushing value in stack
  stackLabel?: string
  silent?: boolean // if true, the value will not be sent to backend
  invisible?: boolean // if true, the value will not be shown by displayStackValue
}
interface ShowFieldEditPushToStackFromData extends ShowFieldEditPushToStackBase {
  fromDataKey: string // get the value from the current data using this key
}
interface ShowFieldEditPushToStackFixed extends ShowFieldEditPushToStackBase {
  value: string
}

type ShowFieldEditPushToStack = ShowFieldEditPushToStackFromData | ShowFieldEditPushToStackFixed

interface ShowFieldEdit extends ShowFieldBase {
  type: 'edit'
  goto: string
  disabledFunc?: (data: any) => boolean
  pushToStack: ShowFieldEditPushToStack[]
}

type ShowField = ShowFieldVarchar | ShowFieldText | ShowFieldBoolean | ShowFieldLines | ShowFieldInteger | ShowFieldEdit
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

    // Erasing current stack values.
    // Indeed, Show states can only have values when using an edit button.
    // So we clean values (in case of history back, or return after save).
    stack.setValues(null)

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

  bindEvents (dom: JQuery, stack: Stack): void {
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
    dom.on('click.StateEvents', '[pickupmobile-show-edit-goto]', ev => {
      const a = $(ev.currentTarget)
      const goto = a.attr('pickupmobile-show-edit-goto')
      const data: any = JSON.parse(a.attr('pickupmobile-show-edit-data') ?? '{}')
      const pushToStacks: ShowFieldEditPushToStack[] = JSON.parse(a.attr('pickupmobile-show-edit-push-to-stack') ?? '[]')

      const svs: StackValue[] = []
      for (const pushToStack of pushToStacks) {
        let value
        if ('fromDataKey' in pushToStack) {
          value = data[pushToStack.fromDataKey]
        } else if ('value' in pushToStack) {
          value = pushToStack.value
        } else {
          continue
        }
        svs.push({
          label: pushToStack.stackLabel ?? pushToStack.pushOnStackKey,
          name: pushToStack.pushOnStackKey,
          value,
          invisible: pushToStack.invisible,
          silent: pushToStack.silent
        })
      }
      stack.setValues(svs)
      dom.trigger('goto-state', [goto])
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
    function searchFieldGoto (fields: ShowFields): void {
      fields.forEach(field => {
        if (field.type === 'edit' && field.goto) {
          a.push(field.goto)
        } else if (field.type === 'lines') {
          searchFieldGoto(field.lines)
        }
      })
    }
    searchFieldGoto(this.fields)
    return a
  }
}

export {
  ShowField,
  ShowFields,
  StateShow,
  StateShowDefinition
}
