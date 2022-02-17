import type { StateDefinition } from '../lib/state/index'

export function demoInit (): StateDefinition {
  return {
    type: 'choice',
    label: 'Accueil',
    choices: [
      {
        label: 'Pick',
        value: 'pick',
        goto: 'pick'
      },
      {
        label: 'Form',
        value: 'form',
        goto: 'form'
      },
      {
        label: 'Compute+Show',
        value: 'show',
        goto: 'compute'
      },
      {
        label: 'Select',
        value: 'select',
        goto: 'select'
      },
      {
        label: 'Unknown',
        value: 'unknown',
        goto: 'dontexist'
      }
    ]
  }
}

export function demoCompute (computeUntil: string, goto: string): StateDefinition {
  return {
    type: 'compute',
    goto,
    label: 'compute',
    computeUntil,
    func: (_stack, _vars) => {
      return {
        label: 'Compute',
        name: 'rowid',
        value: '1'
      }
    }
  }
}

export function demoPick (goto: string, creationGoto: string): StateDefinition {
  return {
    type: 'pick',
    label: 'Pick test',
    key: 'demo',
    primaryKey: 'rowid',
    goto,
    creationGoto,
    creationLabel: 'New XXX',
    fields: [
      { name: 'field1', label: 'Demo 1', applyFilter: 'localeUpperCase' },
      { name: 'field2', label: 'Demo 2' }
    ]
  }
}

export function demoSelect (): StateDefinition {
  return {
    type: 'select',
    label: 'Select test (not fully implemented)',
    options: [
      { label: 'Option 1', value: '1' },
      { label: 'Option 2', value: '2' }
    ]
  }
}

export function demoForm (goto: string): StateDefinition {
  return {
    type: 'form',
    label: 'Form',
    goto,
    fields: [
      {
        type: 'varchar',
        name: 'field1',
        label: 'Field 1',
        mandatory: true,
        maxLength: 25,
        loadSuggestions: {
          dataKey: 'demo',
          field: 'field1',
          filter: 'localeUpperCase'
        }
      },
      {
        type: 'varchar',
        name: 'field2',
        label: 'Field 2',
        mandatory: true,
        maxLength: 25
      },
      {
        type: 'boolean',
        name: 'fieldbool',
        label: 'Boolean',
        mandatory: false
      },
      {
        type: 'date',
        name: 'fielddate',
        label: 'Date',
        mandatory: false,
        defaultToToday: true
      },
      {
        type: 'integer',
        name: 'fieldinteger',
        label: 'Integer',
        mandatory: false,
        min: 0,
        max: 100
      },
      {
        type: 'float',
        name: 'fieldfloat',
        label: 'Float',
        mandatory: false,
        min: 0,
        max: 100,
        step: 0.2
      },
      {
        type: 'radio',
        name: 'fieldradio',
        label: 'Radio',
        mandatory: false,
        options: [
          { label: 'Option 1', value: 'o1' },
          { label: 'Option 2', value: 'o2' }
        ]
      },
      {
        type: 'select',
        name: 'fieldselect',
        label: 'Select',
        mandatory: false,
        options: [
          { label: 'Option 1', value: 'o1' },
          { label: 'Option 2', value: 'o2' }
        ]
      },
      {
        type: 'text',
        name: 'fieldtext',
        mandatory: false,
        label: 'Text'
      }
    ]
  }
}

export function demoSave (goto: string, saveUntil: string): StateDefinition {
  return {
    type: 'save',
    label: 'Save',
    key: 'demo',
    primaryKey: 'rowid',
    labelKey: 'Demo',
    saveUntil,
    goto
  }
}

export function demoShow (): StateDefinition {
  return {
    type: 'show',
    label: 'Show',
    key: 'demo',
    primaryKey: 'rowid',
    fields: [
      {
        type: 'varchar',
        label: 'Field 1',
        name: 'field1'
      },
      {
        type: 'varchar',
        label: 'Field 2',
        name: 'field2'
      }
    ]
  }
}
