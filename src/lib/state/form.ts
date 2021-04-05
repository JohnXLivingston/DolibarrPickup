import { State, StateDefinitionBase } from './state'
import { Stack, StackValue } from '../stack'
import { ResultData, getData } from '../data'

interface FormFieldNotesStatic {
  label: string
}
interface FormFieldNotesLoad {
  load: string, // Source name
  basedOnValueOf: string, // the value key to search in stack
  key: string, // the field to use in the source data as key
  field: string, // the field to display as a note
  label?: string // the computed notes will replace this value
}
/**
 * FormFieldNoteDesc describes how to load additional notes to display next to fields.
 */
type FormFieldNotes = FormFieldNotesStatic | FormFieldNotesLoad

interface FormFieldBase {
  type: string,
  name: string,
  label: string,
  mandatory: boolean,
  default?: string,
  maxLength?: number,
  notes?: FormFieldNotes
}

interface FormFieldVarchar extends FormFieldBase {
  type: 'varchar'
}

interface FormFieldText extends FormFieldBase {
  type: 'text'
}

interface FormFieldSelectSimple extends FormFieldBase {
  type: 'select',
  options: {value: string, label: string}[]
}
interface FormFieldSelectDynamic extends FormFieldBase {
  type: 'select',
  options: {value: string, label: string}[],
  load: string,
  map: {value: string, label: string}
}
type FormFieldSelect = FormFieldSelectSimple | FormFieldSelectDynamic

interface FormFieldInteger extends FormFieldBase {
  type: 'integer',
  min: number,
  max: number
}

interface FormFieldFloat extends FormFieldBase {
  type: 'float',
  min: number,
  max: number
  step: number
}

interface FormFieldBoolean extends FormFieldBase {
  type: 'boolean'
}

interface FormFieldRadio extends FormFieldBase {
  type: 'radio',
  options: FormFieldRadioOption[]
}

interface FormFieldRadioOption {
  value: string,
  label: string
}

interface FormFieldDate extends FormFieldBase {
  type: 'date',
  defaultToToday?: boolean
}

type FormField = FormFieldSelect | FormFieldVarchar | FormFieldText | FormFieldInteger | FormFieldFloat | FormFieldBoolean | FormFieldRadio | FormFieldDate

interface StateFormDefinition extends StateDefinitionBase {
  type: 'form',
  goto: string,
  fields: FormField[]
}

class StateForm extends State {
  private readonly goto: string
  readonly fields: FormField[]

  constructor (definition: StateFormDefinition) {
    super('form', definition)
    this.goto = definition.goto
    this.fields = definition.fields
  }

  renderVars (stack: Stack) {
    const h = super.renderVars(stack)

    // Is there any field that needs data?
    const dataPerKey = this.asyncFieldsData(stack, false)
    h.dataPerKey = dataPerKey
    h.dataStatus = 'resolved'
    for (const fieldName in dataPerKey) {
      const data = dataPerKey[fieldName]
      if (data.status === 'pending') {
        // at least one field pending... waiting...
        h.dataStatus = 'pending'
        setTimeout(() => {
          const div = $('[pickupmobile-form-pending]')
          if (div.length) {
            div.trigger('rerender-state')
          } else {
            console.log('The pending div is not in the dom anymore.')
          }
        }, 500)

        // no need to go further
        break
      } else if (data.status === 'rejected') {
        h.dataStatus = 'rejected'
      }
    }

    this.initDateDefaults()
    h.useDefaultValues = !stack.isAnyValue()
    return h
  }

  bindEvents (dom: JQuery, stack: Stack): void {
    dom.on('click.stateEvents', '[pickupmobile-form-reload]', () => {
      this.asyncFieldsData(stack, true) // force reload
      dom.trigger('rerender-state')
    })

    dom.on('submit.stateEvents', 'form', (ev) => {
      const form = $(ev.currentTarget)
      const dataArray = form.serializeArray()

      const sva: StackValue[] = this.checkForm(dataArray)
      if (sva.find(sv => sv.invalid)) {
        // In order to init fields, we are going to save data.
        // Notice that invalid values are flagged invalid, so they should not be sent accidently.
        stack.setValues(sva)
        dom.trigger('rerender-state', [])
        return
      }

      stack.setValues(sva)
      dom.trigger('goto-state', [this.goto])
    })
  }

  /**
   * Returns getData promises for each field that need async data.
   */
  private asyncFieldsData (stack: Stack, force: boolean) :{[key: string]: ResultData} {
    const r:{[key: string]: ResultData} = {}
    for (let i = 0; i < this.fields.length; i++) {
      const field = this.fields[i]
      if (field.notes && 'load' in field.notes) {
        const notes: FormFieldNotesLoad = field.notes
        const data = getData(notes.load, force)
        r['__notes_' + field.name] = data
        if (data.status === 'resolved') {
          const value = stack.searchValue(notes.basedOnValueOf)
          if (value !== undefined) {
            const note = (data.data as Array<any>).find(
              el => notes.key && el[notes.key] === value
            )
            if (note) {
              notes.label = note[notes.field]
            }
          }
        }
      }

      if (field.type === 'select' && 'load' in field) {
        const data = getData(field.load, force)
        r[field.name] = data
        if (data.status === 'resolved') {
          field.options = data.data.map((d: any) => {
            return { value: d[field.map.value], label: d[field.map.label] }
          })
          // Adding empty value if not present in data source.
          if (!field.options.find(option => option.value === '' || option.value === '0')) {
            field.options.unshift({ value: '', label: '-' })
          }
        }
      }
    }
    return r
  }

  private initDateDefaults (): void {
    for (let i = 0; i < this.fields.length; i++) {
      const field = this.fields[i]
      if (field.type !== 'date') {
        continue
      }
      if (field.defaultToToday) {
        const today = new Date(Date.now())
        const y: string = '' + today.getFullYear()
        let m: string = '' + (today.getMonth() + 1)
        if (m.length < 2) m = '0' + m
        let d:string = '' + today.getDate()
        if (d.length < 2) d = '0' + d
        field.default = y + '-' + m + '-' + d
      }
    }
  }

  getField (fieldName: string): FormField | undefined {
    return this.fields.find(f => f.name === fieldName)
  }

  private checkForm (dataArray: JQuery.NameValuePair[]): StackValue[] {
    const sva = []
    for (let i = 0; i < this.fields.length; i++) {
      const field: FormField = this.fields[i]

      const l = dataArray.filter(nvp => nvp.name === field.name)
      if (l.length > 1) {
        throw new Error('Did not expert to have multiple values for field ' + field.name)
      }
      const value = l.length ? l[0].value : ''

      const sv: StackValue = {
        label: field.label,
        name: field.name,
        value: value
      }
      if (field.type === 'boolean') {
        sv.display = value === '1' ? 'Yes' : 'No'
      }
      if (field.type === 'select') {
        const option = field.options.find(o => o.value === sv.value)
        if (option) {
          sv.display = option.label
        }
      }
      if (field.type === 'radio') {
        const option = field.options.find(o => o.value === sv.value)
        if (option) {
          sv.display = option.label
        }
      }
      sva.push(sv)

      // Checking constraints...
      if (field.mandatory) {
        if (!sv.value) {
          sv.invalid = 'This field is mandatory.'
          continue
        }
        if (field.type === 'select' && sv.value === '0') {
          // For select fields, '0' is considered as an empty value.
          sv.invalid = 'This field is mandatory.'
          continue
        }
      }
      if (sv.value && field.maxLength) {
        if (sv.value.length > field.maxLength) {
          sv.invalid = 'Max length is ' + field.maxLength + '.'
          continue
        }
      }
      if (field.type === 'integer') {
        if (!/^\d*$/.test(sv.value)) {
          sv.invalid = 'Invalid number.'
          continue
        }
      }
      if (field.type === 'float') {
        if (!/^$|^\d+(\.\d*)?$/.test(sv.value)) {
          sv.invalid = 'Invalid number.'
          continue
        }
      }
      if (field.type === 'integer' || field.type === 'float') {
        if (Number(sv.value) < field.min) {
          sv.invalid = 'Minimum is ' + field.min
        }
        if (Number(sv.value) > field.max) {
          sv.invalid = 'Maximum is ' + field.max
        }
      }
      if (field.type === 'date') {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
          sv.invalid = 'Invalid date'
        }
      }
    }
    return sva
  }

  async possibleGotos () {
    return [this.goto]
  }
}

export {
  StateForm,
  StateFormDefinition
}
