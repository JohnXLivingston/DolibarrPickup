import type { NunjucksVars } from '../nunjucks'
import { State, StateDefinitionBase } from './state'
import { Stack, StackValue } from '../stack'
import { ResultData, getData, GetDataParams } from '../data'
import { translate } from '../translate'
import { uniqAndSort, Filter } from '../utils/filters'

interface FormFieldNotesStatic {
  label: string
}
interface FormFieldNotesLoad {
  load: string // Source name
  basedOnValueOf: string // the value key to search in stack
  key: string // the field to use in the source data as key
  field: string // the field to display as a note
  label?: string // the computed notes will replace this value
}
/**
 * FormFieldNoteDesc describes how to load additional notes to display next to fields.
 */
type FormFieldNotes = FormFieldNotesStatic | FormFieldNotesLoad

interface FormFieldBase {
  type: string
  name: string
  label: string
  mandatory: boolean
  default?: string
  maxLength?: number
  notes?: FormFieldNotes
}

interface FormFieldVarchar extends FormFieldBase {
  type: 'varchar'
  suggestions?: string[]
  loadSuggestions?: {
    dataKey: string // name of the data list to load
    field: string // name of the field from the data list to use for the suggestions
    filter?: Filter
  }
}

interface FormFieldText extends FormFieldBase {
  type: 'text'
}

interface FormFieldSelectOption {
  value: string
  label: string
}
type FormFieldSelectLoadFilter = (option: FormFieldSelectOption) => boolean
interface FormFieldSelectSimple extends FormFieldBase {
  type: 'select'
  options: FormFieldSelectOption[]
}
interface FormFieldSelectDynamicLoadParamsFromStack {
  type: 'stack'
  key: string // the value to get from the stack
}
interface FormFieldSelectDynamicLoadParams {[key: string]: string | FormFieldSelectDynamicLoadParamsFromStack}
interface FormFieldSelectDynamic extends FormFieldBase {
  type: 'select'
  options: FormFieldSelectOption[]
  readonly?: boolean
  load: string
  loadParams?: FormFieldSelectDynamicLoadParams
  loadFilter?: FormFieldSelectLoadFilter // a function to filter values
  map: {value: string, label: string}
}
type FormFieldSelect = FormFieldSelectSimple | FormFieldSelectDynamic

interface FormFieldInteger extends FormFieldBase {
  type: 'integer'
  min: number
  max: number
}

interface FormFieldFloat extends FormFieldBase {
  type: 'float'
  min: number
  max: number
  step: number
}

interface FormFieldBoolean extends FormFieldBase {
  type: 'boolean'
}

interface FormFieldRadio extends FormFieldBase {
  type: 'radio'
  options: FormFieldRadioOption[]
}

interface FormFieldRadioOption {
  value: string
  label: string
}

interface FormFieldDate extends FormFieldBase {
  type: 'date'
  defaultToToday?: boolean
}

type FormField = FormFieldSelect | FormFieldVarchar | FormFieldText | FormFieldInteger | FormFieldFloat | FormFieldBoolean | FormFieldRadio | FormFieldDate

interface StateFormDefinition extends StateDefinitionBase {
  type: 'form'
  goto: string
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

  renderVars (stack: Stack): NunjucksVars {
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

  postRenderAndBind (dom: JQuery, stack: Stack, bind: boolean, vars: any): void {
    super.postRenderAndBind(dom, stack, bind, vars)

    // Focus the first visible field.
    if (vars?.useDefaultValues) {
      // useDefaultValues seems a good test: only add focus if this is an empty form, not if it is already filled (error or nav back)
      const el = dom.find('input:visible:not(disabled), checkbox:visible:not(disabled), textarea:visible:not(disabled)').first()
      if (el.length) {
        el.focus()
      }
    } else {
      // focus to the first invalid field
      const el = dom.find('input.is-invalid:visible:not(disabled), checkbox.is-invalid:visible:not(disabled), textarea.is-invalid:visible:not(disabled)').first()
      if (el.length) {
        el.focus()
      }
    }
  }

  /**
   * Returns getData promises for each field that need async data.
   */
  private asyncFieldsData (stack: Stack, force: boolean): {[key: string]: ResultData} {
    const r: {[key: string]: ResultData} = {}
    for (let i = 0; i < this.fields.length; i++) {
      const field = this.fields[i]
      if (field.notes && 'load' in field.notes) {
        const notes: FormFieldNotesLoad = field.notes
        const data = getData(notes.load, 'list', force)
        r['__notes_' + field.name] = data
        if (data.status === 'resolved') {
          const value = stack.searchValue(notes.basedOnValueOf)
          if (value !== undefined) {
            const note = (data.data as any[]).find(
              el => notes.key && el[notes.key] === value
            )
            if (note) {
              notes.label = note[notes.field]
            }
          }
        }
      }

      if (field.type === 'varchar' && 'loadSuggestions' in field && field.loadSuggestions) {
        const data = getData(field.loadSuggestions.dataKey, 'list', force)
        r[field.name + '.suggestions'] = data
        if (data.status === 'resolved') {
          field.suggestions = uniqAndSort(data.data, field.loadSuggestions.field, field.loadSuggestions.filter, true).values
        }
      }

      if (field.type === 'select' && 'load' in field) {
        let loadParams: GetDataParams | undefined
        if (field.loadParams) {
          loadParams = {}
          for (const key in field.loadParams) {
            const loadParam = field.loadParams[key]
            if (typeof loadParam === 'string') {
              loadParams[key] = loadParam
            } else if ((typeof loadParam === 'object') && loadParam.type === 'stack') {
              const value = stack.searchValue(loadParam.key)
              if (value !== undefined) {
                loadParams[key] = value
              }
            } else {
              throw new Error('Wrong definition')
            }
          }
        }
        const data = getData(field.load, 'list', force, loadParams)
        r[field.name] = data
        if (data.status === 'resolved') {
          field.options = data.data.map((d: any) => {
            return { value: d[field.map.value], label: d[field.map.label] }
          })
          // Adding empty value if not present in data source.
          if (!field.options.find(option => option.value === '' || option.value === '0')) {
            field.options.unshift({ value: '', label: '-' })
          }
          if (field.loadFilter) {
            field.options = field.options.filter(field.loadFilter)
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
        const y: string = today.getFullYear().toString()
        let m: string = (today.getMonth() + 1).toString()
        if (m.length < 2) m = '0' + m
        let d: string = today.getDate().toString()
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
          sv.invalid = translate('This field is mandatory')
          continue
        }
        if (field.type === 'select' && sv.value === '0') {
          // For select fields, '0' is considered as an empty value.
          sv.invalid = translate('This field is mandatory')
          continue
        }
      }
      if (sv.value && field.maxLength) {
        if (sv.value.length > field.maxLength) {
          sv.invalid = translate('Max length is {length}', { length: field.maxLength.toString() })
          continue
        }
      }
      if (field.type === 'integer') {
        if (!/^\d*$/.test(sv.value)) {
          sv.invalid = translate('Invalid number')
          continue
        }
      }
      if (field.type === 'float') {
        if (!/^$|^\d+(\.\d*)?$/.test(sv.value)) {
          sv.invalid = translate('Invalid number')
          continue
        }
      }
      if (field.type === 'integer' || field.type === 'float') {
        if (Number(sv.value) < field.min) {
          sv.invalid = translate('Minimum is {min}', { min: field.min.toString() })
        }
        if (Number(sv.value) > field.max) {
          sv.invalid = translate('Maximum is {max}', { max: field.max.toString() })
        }
      }
      if (field.type === 'date') {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
          sv.invalid = translate('Invalid date')
        }
      }
    }
    return sva
  }

  async possibleGotos (): Promise<string[]> {
    return [this.goto]
  }
}

export {
  StateForm,
  StateFormDefinition,
  FormField,
  FormFieldSelectLoadFilter
}
