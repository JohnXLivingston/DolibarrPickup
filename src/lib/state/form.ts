import type { NunjucksVars } from '../nunjucks'
import { State, StateDefinitionBase, StateRetrievedData } from './state'
import { Stack, StackValue } from '../stack'
import { getData, GetDataParams } from '../data'
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

/**
 * FormFieldEditInfo: when in edit mode, how to map stack with field
 */
interface FormFieldEditInfo {
  getDataFromSourceKey: string // the key in the data source for this field
  convertData?: (v: any) => string // if needed, we can transform the value in the stack
}

interface FormFieldBase {
  type: string
  name: string
  label: string
  mandatory: boolean
  default?: string
  defaultFunc?: (stack: Stack, retrievedData: StateRetrievedData) => string
  maxLength?: number
  notes?: FormFieldNotes
  edit?: FormFieldEditInfo
}

interface FormFieldHidden extends FormFieldBase {
  type: 'hidden'
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
type FormFieldSelectFilterOptions = (field: FormFieldSelect, stack: Stack, retrievedData: StateRetrievedData) => FormFieldSelectOption[]
interface FormFieldSelectSimple extends FormFieldBase {
  type: 'select'
  options: FormFieldSelectOption[]
  filterOptions?: FormFieldSelectFilterOptions // a function to filter values, just before the rendering (ie: all data are available)
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
  dontAddEmptyOption?: boolean
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
  maxToToday?: boolean
  min?: string
  max?: string
}

type FormField = FormFieldSelect | FormFieldHidden | FormFieldVarchar | FormFieldText | FormFieldInteger | FormFieldFloat | FormFieldBoolean | FormFieldRadio | FormFieldDate

interface StateFormEditDefinition {
  stackKey: string
  getDataKey: string
}

interface StateFormDefinition extends StateDefinitionBase {
  type: 'form'
  goto: string
  edit?: StateFormEditDefinition
  fields: FormField[]
}

function todayString (): string {
  const today = new Date(Date.now())
  const y: string = today.getFullYear().toString()
  let m: string = (today.getMonth() + 1).toString()
  if (m.length < 2) m = '0' + m
  let d: string = today.getDate().toString()
  if (d.length < 2) d = '0' + d
  return y + '-' + m + '-' + d
}

class StateForm extends State {
  private readonly goto: string
  private readonly edit?: StateFormEditDefinition
  readonly fields: FormField[]

  constructor (definition: StateFormDefinition) {
    super('form', definition)
    this.goto = definition.goto
    this.edit = definition.edit
    this.fields = definition.fields
  }

  _renderVars (stack: Stack, retrievedData: StateRetrievedData, h: NunjucksVars): void {
    this.initDateFields()
    this.initFieldsDefaults(stack, retrievedData)
    h.useDefaultValues = !stack.isAnyValue()
    h.filteredOptions = {}
    for (const field of this.fields) {
      if ('options' in field) {
        h.filteredOptions[field.name] = field.options
      }
      if (('filterOptions' in field) && field.filterOptions) {
        console.log('form._renderVars: field ' + field.name + ' use filterOptions, calling it.')
        h.filteredOptions[field.name] = field.filterOptions(field, stack, retrievedData)
      }
    }
  }

  bindEvents (dom: JQuery, stack: Stack): void {
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

  retrieveData (stack: Stack, force: boolean): StateRetrievedData {
    const r = super.retrieveData(stack, force)

    if (this.edit?.stackKey) {
      const value = stack.searchValue(this.edit.stackKey)
      if (value) {
        const data = getData(this.edit.getDataKey, 'get', force, { id: value })
        r.set('__edit', data)
        const sva1: StackValue[] = []
        if (!stack.isAnyValue() && data.status === 'resolved') {
          const dataArray: JQuery.NameValuePair[] = []
          console.debug('Mapping fields edit value from stack...')
          console.debug('  Data are:', data.data)
          for (let i = 0; i < this.fields.length; i++) {
            const field = this.fields[i]
            if (!field.edit?.getDataFromSourceKey) { continue }
            console.debug('  Field ' + field.name + ' must be mapped from:', field.edit.getDataFromSourceKey)
            let v = data.data[field.edit.getDataFromSourceKey]
            if (field.edit.convertData) {
              console.debug('    and the value must be converted.')
              v = field.edit.convertData(v)
            }
            console.debug('  Field ' + field.name + ' retained value:', v)
            dataArray.push({
              name: field.name,
              value: v
            })
          }
          const sva2: StackValue[] = this.checkForm(dataArray)
          stack.setValues(sva1.concat(sva2))
        }
      }
    }

    for (let i = 0; i < this.fields.length; i++) {
      const field = this.fields[i]
      if (field.notes && 'load' in field.notes) {
        const notes: FormFieldNotesLoad = field.notes
        const data = getData(notes.load, 'list', force)
        r.set('__notes_' + field.name, data)
        if (data.status === 'resolved') {
          console.debug('Loading notes for field ' + field.name + ', based on value of ' + notes.basedOnValueOf)
          const value = stack.searchValue(notes.basedOnValueOf)
          console.debug('  value in stack:', value)
          console.debug('  looking for key in data:', notes.key)
          console.debug('  note field:', notes.field)
          console.debug('  data: ', data.data)
          if (value !== undefined) {
            const note = (data.data as any[]).find(
              el => notes.key && el[notes.key] === value
            )
            if (note) {
              console.debug('Found data for note:', note)
              notes.label = note[notes.field]
            }
          }
        }
      }

      if (field.type === 'varchar' && 'loadSuggestions' in field && field.loadSuggestions) {
        const data = getData(field.loadSuggestions.dataKey, 'list', force)
        r.set(field.name + '.suggestions', data)
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
        r.set(field.name, data)
        if (data.status === 'resolved') {
          field.options = data.data.map((d: any) => {
            return { value: d[field.map.value], label: d[field.map.label] }
          })
          // Adding empty value if not present in data source.
          if (!field.dontAddEmptyOption) {
            if (!field.options.find(option => option.value === '' || option.value === '0')) {
              field.options.unshift({ value: '', label: '-' })
            }
          }
        }
      }
    }
    return r
  }

  private initDateFields (): void {
    for (let i = 0; i < this.fields.length; i++) {
      const field = this.fields[i]
      if (field.type !== 'date') {
        continue
      }
      if (field.defaultToToday) {
        field.default = todayString()
      }
      if (field.maxToToday) {
        field.max = todayString()
      }
    }
  }

  private initFieldsDefaults (stack: Stack, retrievedData: StateRetrievedData): void {
    for (let i = 0; i < this.fields.length; i++) {
      const field = this.fields[i]
      if (field.type === 'date' && field.defaultToToday) {
        field.default = todayString()
        continue
      }
      if (!field.defaultFunc) { continue }
      field.default = field.defaultFunc(stack, retrievedData)
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
      } else if (field.type === 'select') {
        // FIXME: if the field has a filterOptions attribute, it is not taken into account here.
        const option = field.options.find(o => o.value === sv.value)
        if (option) {
          sv.display = option.label
        }
      } else if (field.type === 'radio') {
        const option = field.options.find(o => o.value === sv.value)
        if (option) {
          sv.display = option.label
        }
      } else if (field.type === 'hidden') {
        sv.invisible = true
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
  FormFieldSelectFilterOptions
}
