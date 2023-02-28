import type { NunjucksVars } from '../nunjucks'
import { State, StateDefinitionBase, StateRetrievedData } from './state'
import { Stack, StackValue } from '../stack'
import { getData } from '../data'
import { RenderReason } from '../constants'
import { Veto } from '../veto'
import { uniqAndSort, Filter } from '../utils/filters'

interface PickField {
  name: string
  label: string
  applyFilter?: Filter
}

type PickFields = PickField[]

interface PickOption {
  value: string
  label: string
  selected?: boolean
}

interface PickFormFieldInfo {
  disabled: boolean
  field: PickField
  options: PickOption[] | undefined
}
interface PickFormInfos {
  fieldsInfos: PickFormFieldInfo[]
  pickedItems: any[]
  creation: boolean
  creationLabel?: string
}

interface StatePickDefinition extends StateDefinitionBase {
  type: 'pick'
  key: string
  fields: PickFields
  primaryKey: string
  goto: string
  creationGoto?: string
  creationLabel?: string
}

class StatePick extends State {
  private readonly key: string
  private readonly fields: PickFields
  private readonly goto: string
  private readonly creationGoto?: string
  private readonly creationLabel?: string
  readonly primaryKey: string

  constructor (definition: StatePickDefinition) {
    super('pick', definition)
    this.key = definition.key
    this.fields = definition.fields
    this.primaryKey = definition.primaryKey
    this.goto = definition.goto
    this.creationGoto = definition.creationGoto
    this.creationLabel = definition.creationLabel
  }

  retrieveData (stack: Stack, force: boolean): StateRetrievedData {
    const r = super.retrieveData(stack, force)
    r.set('data', getData(this.key, 'list', force))
    return r
  }

  _renderVars (stack: Stack, retrievedData: StateRetrievedData, h: NunjucksVars): void {
    const dataResult = retrievedData.get('data')
    if (dataResult && dataResult.status === 'resolved' && dataResult?.data) {
      h.formInfos = this.getFormInfos(stack, dataResult.data)
    }
  }

  renderVeto2 (reason: RenderReason, stack: Stack, vars: any): Veto | undefined {
    const parentVeto = super.renderVeto2(reason, stack, vars)
    if (parentVeto) return parentVeto

    if (reason !== RenderReason.REFRESHING) {
      return undefined
    }
    if (!vars || !vars.formInfos) {
      return undefined
    }
    if (!vars.formInfos.pickedItems) {
      return undefined
    }
    if (vars.formInfos.pickedItems.length !== 1) {
      return undefined
    }
    // FIXME: should be in common with the standard pick-pick event.
    const sv: StackValue = {
      label: this.key, // FIXME: something else
      name: this.key, // FIXME: i'm not sure this is good
      value: vars.formInfos.pickedItems[0][this.primaryKey],
      invisible: true // this value is not shown in interface
    }
    stack.changeOrAppendValues(sv) // dont loose silent values... they still be needed for display.
    return {
      type: 'forward',
      goto: this.goto
    }
  }

  bindEvents (dom: JQuery, stack: Stack): void {
    dom.on('change.stateEvents', '[pickupmobile-pick-select]', (ev) => {
      const select = $(ev.currentTarget)
      const option = select.find('option:selected:first')
      const value: string = option.attr('value') ?? ''
      const fieldName: string = select.attr('name') ?? ''
      const field = this.fields.find(f => f.name === fieldName)
      if (!field) {
        throw new Error('Cant find field ' + fieldName)
      }

      if (value === '' && /^\s*$/.test(option.text())) {
        // This is different from value==='' && text === '-'
        stack.removeValue(field.name)
      } else {
        stack.changeOrAppendValues({
          label: field.label,
          name: field.name,
          value: value,
          display: option.text(),
          silent: true // this value is not meant to be sent to backend
        })
      }
      option.trigger('rerender-state')
    })

    dom.on('click.stateEvents', '[pickupmobile-pick-pick]', ev => {
      const a = $(ev.currentTarget)
      const value = a.attr('pickupmobile-pick-pick') ?? ''
      const sv: StackValue = {
        label: this.key, // FIXME: something else
        name: this.key, // FIXME: i'm not sure this is good
        value: value,
        invisible: true // this value is not shown in interface
      }
      stack.changeOrAppendValues(sv) // dont loose silent values... they still be needed for display.
      dom.trigger('goto-state', [this.goto])
    })

    dom.on('click.stateEvents', '[pickupmobile-pick-create]', () => {
      if (this.creationGoto) {
        stack.setValues(null) // Erasing...
        dom.trigger('goto-state', [this.creationGoto])
      }
    })

    dom.on('click.stateEvents', '[pickupmobile-pick-empty]', () => {
      stack.setValues(null) // Erasing...
      dom.trigger('rerender-state')
    })
  }

  // Not working. Keep this for later.
  // postRenderAndBind (dom: JQuery, stack: Stack, bind: boolean, vars: any): void {
  //   super.postRenderAndBind(dom, stack, bind, vars)

  //   const el = dom.find('select[pickupmobile-pick-select]:not(disabled)').last()
  //   if (el.length) {
  //     el.select2('open')
  //   }
  // }

  getFormInfos (stack: Stack, data: any[]): PickFormInfos {
    const r: PickFormInfos = {
      fieldsInfos: [],
      pickedItems: [],
      creation: !!this.creationGoto,
      creationLabel: this.creationLabel
    }

    let isPreviousSet: boolean = true
    for (let i = 0; i < this.fields.length; i++) {
      const field = this.fields[i]
      const nextData: any[] = []
      if (!isPreviousSet) {
        r.fieldsInfos.push({
          disabled: true,
          field: field,
          options: []
        })
        data = nextData
        continue
      }

      const currentValue: string | undefined = stack.getValue(field.name)

      const options: PickOption[] = [{
        label: '',
        value: '',
        selected: currentValue === undefined
      }]

      const uniqandSorted = uniqAndSort(data, field.name, field.applyFilter)
      for (const label of uniqandSorted.values) {
        const selected: boolean = currentValue === label
        options.push({
          label: label === '' ? '-' : label,
          value: label,
          selected: selected
        })
      }

      if (currentValue !== undefined && uniqandSorted.matchingLines.has(currentValue)) {
        const lines = uniqandSorted.matchingLines.get(currentValue) ?? []
        for (const line of lines) {
          nextData.push(line)
        }
      }

      r.fieldsInfos.push({
        disabled: false,
        field,
        options
      })
      data = nextData
      if (currentValue === undefined) isPreviousSet = false
    }

    if (isPreviousSet) {
      r.pickedItems = data
    }

    return r
  }

  async possibleGotos (): Promise<string[]> {
    const r = []
    if (this.goto) r.push(this.goto)
    if (this.creationGoto) r.push(this.creationGoto)
    return r
  }
}

export {
  PickField,
  PickFields,
  StatePick,
  StatePickDefinition
}
