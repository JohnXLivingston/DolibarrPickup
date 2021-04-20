import { State, StateDefinitionBase } from './state'
import { Stack, StackValue } from '../stack'
import { getData } from '../data'
import { RenderReason } from '../constants'
import { Veto } from '../veto'

interface PickField {
  name: string,
  label: string,
  applyFilter?: 'lowerCase' | 'upperCase' | 'localeLowerCase' | 'localeUpperCase'
}

type PickFields = PickField[]

interface PickOption {
  value: string,
  label: string,
  selected?: boolean
}

interface PickFormFieldInfo {
  disabled: boolean
  field: PickField,
  options: PickOption[] | undefined,
}
interface PickFormInfos {
  fieldsInfos: PickFormFieldInfo[],
  pickedItems: any[],
  creation: boolean
}

interface StatePickDefinition extends StateDefinitionBase {
  type: 'pick',
  key: string,
  fields: PickFields,
  primaryKey: string,
  goto: string, // default goto. Can be overriden by an item, if itemGotoField is defined
  itemGotoField?: string,
  creationGoto?: string
}

class StatePick extends State {
  private readonly key: string
  private readonly fields: PickFields
  private readonly goto: string
  readonly itemGotoField?: string
  private readonly creationGoto?: string
  readonly primaryKey: string

  constructor (definition: StatePickDefinition) {
    super('pick', definition)
    this.key = definition.key
    this.fields = definition.fields
    this.primaryKey = definition.primaryKey
    this.goto = definition.goto
    this.itemGotoField = definition.itemGotoField
    this.creationGoto = definition.creationGoto
  }

  renderVars (stack: Stack) {
    const h = super.renderVars(stack)
    h.data = getData(this.key, 'list')
    if (h.data.status === 'pending') {
      setTimeout(() => {
        const div = $('[pickupmobile-pick-pending]')
        if (div.length) {
          div.trigger('rerender-state')
        } else {
          console.log('The pending div is not in the dom anymore.')
        }
      }, 500)
    }

    if (h.data.status === 'resolved') {
      h.formInfos = this.getFormInfos(stack, h.data.data)
    }

    return h
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
    let goto = this.goto
    if (this.itemGotoField) {
      // FIXME: should be in common with the standard pick-pick event.
      if (vars.formInfos.pickedItems[0][this.itemGotoField]) {
        goto = vars.formInfos.pickedItems[0][this.itemGotoField]
      }
    }
    return {
      type: 'forward',
      goto: goto
    }
  }

  bindEvents (dom: JQuery, stack: Stack): void {
    dom.on('click.stateEvents', '[pickupmobile-pick-reload]', () => {
      getData(this.key, 'list', true) // force reload
      dom.trigger('rerender-state')
    })

    dom.on('change.stateEvents', '[pickupmobile-pick-select]', (ev) => {
      const select = $(ev.currentTarget)
      const option = select.find('option:selected:first')
      const value: string = option.attr('value') || ''
      const fieldName: string = select.attr('name') || ''
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
      const value = a.attr('pickupmobile-pick-pick') || ''
      const sv: StackValue = {
        label: this.key, // FIXME: something else
        name: this.key, // FIXME: i'm not sure this is good
        value: value,
        invisible: true // this value is not shown in interface
      }
      stack.changeOrAppendValues(sv) // dont loose silent values... they still be needed for display.
      let goto = this.goto
      if (this.itemGotoField) {
        const attrGoto = a.attr('pickupmobile-pick-pick-goto')
        if (attrGoto) {
          goto = attrGoto
        }
      }
      dom.trigger('goto-state', [goto])
    })

    dom.on('click.stateEvents', '[pickupmobile-pick-create]', () => {
      if (this.creationGoto) {
        stack.setValues(null) // Erasing...
        dom.trigger('goto-state', [this.creationGoto])
      }
    })
  }

  getFormInfos (stack: Stack, data: any[]): PickFormInfos {
    const r: PickFormInfos = {
      fieldsInfos: [],
      pickedItems: [],
      creation: !!this.creationGoto
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

      const already: {[key: string]: true} = {}
      let options: PickOption[] = []
      for (let i = 0; i < data.length; i++) {
        const d = data[i]
        let value: string = d[field.name] || ''
        switch (field.applyFilter) {
          case 'upperCase':
            value = value.toUpperCase()
            break
          case 'lowerCase':
            value = value.toLowerCase()
            break
          case 'localeUpperCase':
            value = value.toLocaleUpperCase()
            break
          case 'localeLowerCase':
            value = value.toLocaleLowerCase()
            break
        }
        const selected: boolean = currentValue === value
        if (selected) {
          nextData.push(d)
        }
        if (value in already) {
          continue
        }
        already[value] = true
        options.push({
          label: value === '' ? '-' : value,
          value,
          selected: selected
        })
      }
      options.push({
        label: '',
        value: '',
        selected: currentValue === undefined
      })
      options = options.sort((a, b) => a.label.localeCompare(b.label))

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

  async possibleGotos () {
    const r = []
    if (this.goto) r.push(this.goto)
    if (this.creationGoto) r.push(this.creationGoto)
    if (this.itemGotoField) {
      const d = getData(this.key, 'list')
      const p = Promise.all([d.promise])
      const data = (await p)[0]
      for (let i = 0; i < data.length; i++) {
        const item = data[i]
        const goto = item[this.itemGotoField]
        if (goto) {
          if (!r.find(s => s === goto)) {
            r.push(goto)
          }
        }
      }
    }
    return r
  }
}

export {
  StatePick,
  StatePickDefinition
}
