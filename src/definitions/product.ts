import type { StateDefinition, FormField, FormFieldSelectLoadFilter } from '../lib/state/index'

export function pickProduct (goto: string, creationGoto: string): StateDefinition {
  return {
    type: 'pick',
    label: 'Recherche d\'un produit connu',
    key: 'product',
    primaryKey: 'rowid',
    goto,
    creationGoto,
    creationLabel: 'Nouveau produit',
    fields: [
      { name: 'pbrand', label: 'Marque', applyFilter: 'localeUpperCase' },
      { name: 'ref', label: 'Ref' }
    ]
  }
}

function getDeeeField (deeeForm: string): FormField {
  switch (deeeForm) {
    case 'create_product_deee_off':
      return getDeeeFieldForce('')
    case 'create_product_deee_gef':
      return getDeeeFieldForce('gef')
    case 'create_product_deee_ghf':
      return getDeeeFieldForce('ghf')
    case 'create_product_deee_pam':
      return getDeeeFieldForce('pam')
    case 'create_product_deee_pampro':
      return getDeeeFieldForce('pam_pro')
    case 'create_product_deee_ecr':
      return getDeeeFieldForce('ecr')
    case 'create_product_deee_ecrpro':
      return getDeeeFieldForce('ecr_pro')
    case 'create_product_deee_pam_or_pampro':
    case 'create_product_deee_ecr_or_ecrpro':
    default:
      return getDeeeFieldMultiple(deeeForm)
  }
}
function getDeeeFieldForce (value: string): FormField {
  return {
    type: 'select',
    name: 'product_deee_type',
    label: 'DEEE',
    readonly: true,
    mandatory: false,
    default: value,
    options: [],
    load: 'dict',
    loadParams: {
      what: 'deee_type'
    },
    loadFilter: (option) => option.value === value,
    map: {
      value: 'value',
      label: 'label'
    }
  }
}
function getDeeeFieldMultiple (deeeForm: string): FormField {
  let loadFilter: FormFieldSelectLoadFilter | undefined
  if (deeeForm === 'create_product_deee_pam_or_pampro') {
    loadFilter = (option) => { return option.value === 'pam' || option.value === 'pam_pro' }
  } else if (deeeForm === 'create_product_deee_ecr_or_ecrpro') {
    loadFilter = (option) => { return option.value === 'ecr' || option.value === 'ecr_pro' }
  }
  return {
    type: 'select',
    name: 'product_deee_type',
    label: 'DEEE',
    mandatory: false,
    options: [],
    load: 'dict',
    loadParams: {
      what: 'deee_type'
    },
    loadFilter,
    map: {
      value: 'value',
      label: 'label'
    }
  }
}

export function createProduct (goto: string, deeeForm: string): StateDefinition {
  const deeeField: FormField = getDeeeField(deeeForm)

  return {
    type: 'form',
    label: 'Remplir la fiche produit',
    goto,
    fields: [
      {
        type: 'varchar',
        name: 'product_pbrand',
        label: 'Marque',
        mandatory: true,
        maxLength: 25,
        loadSuggestions: {
          dataKey: 'product',
          field: 'pbrand',
          filter: 'localeUpperCase'
        }
      },
      {
        type: 'varchar',
        name: 'product_ref',
        label: 'Référence',
        mandatory: true,
        maxLength: 128
      },
      {
        type: 'varchar',
        name: 'product_label',
        label: 'Libellé',
        mandatory: false,
        maxLength: 255
      },
      deeeField,
      {
        type: 'text',
        name: 'product_description',
        label: 'Notes',
        mandatory: false,
        notes: {
          load: 'pcat',
          key: 'rowid',
          basedOnValueOf: 'pcat',
          field: 'notes'
        }
      }
    ]
  }
}

export function createProductWeight (goto: string): StateDefinition {
  return {
    type: 'form',
    label: 'Poids unitaire',
    goto,
    fields: [
      {
        type: 'float',
        name: 'weight',
        label: 'Poids unitaire (kg)',
        mandatory: true,
        min: 0,
        max: 1000,
        step: 0.1
      }
    ]
  }
}

export function saveProduct (goto: string, saveUntil: string): StateDefinition {
  return {
    type: 'save',
    label: 'Sauvegarde du produit',
    key: 'product',
    primaryKey: 'rowid', // FIXME: to check.
    labelKey: 'Produit',
    saveUntil,
    goto
  }
}

export function showProduct (okGoto: string): StateDefinition {
  return {
    type: 'show',
    label: 'Produit',
    key: 'product',
    primaryKey: 'product',
    okGoto,
    fields: [
      {
        type: 'varchar',
        name: 'pcats',
        label: 'Catégorie'
      },
      {
        type: 'varchar',
        name: 'pbrand',
        label: 'Marque'
      },
      {
        type: 'varchar',
        name: 'ref',
        label: 'Référence'
      },
      {
        type: 'varchar',
        name: 'label',
        label: 'Libellé'
      },
      {
        type: 'text',
        name: 'description',
        label: 'Description'
      },
      {
        type: 'varchar',
        name: 'deee_type',
        label: 'DEEE'
      },
      {
        type: 'varchar',
        name: 'weight_txt',
        label: 'Poids'
      }
    ]
  }
}
