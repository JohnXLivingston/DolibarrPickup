import type { StateDefinition, FormField, FormFieldSelectLoadFilter, PickFields, ShowFields } from '../lib/state/index'
import type { UseUnit } from '../lib/utils/units'
import { pushUnitFields } from './common'

export function pickProduct (usePBrand: boolean, goto: string, creationGoto: string): StateDefinition {
  const fields: PickFields = []
  if (usePBrand) {
    fields.push({ name: 'pbrand', label: 'Marque', applyFilter: 'localeUpperCase' })
  }
  fields.push({ name: 'ref', label: 'Ref' })
  return {
    type: 'pick',
    label: 'Recherche d\'un produit connu',
    key: 'product',
    primaryKey: 'rowid',
    goto,
    creationGoto,
    creationLabel: 'Nouveau produit',
    fields
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

export function createProduct (usePCat: boolean, useDEEE: boolean, usePBrand: boolean, askHasBatch: boolean, goto: string, deeeForm: string): StateDefinition {
  const fields: FormField[] = []

  if (usePBrand) {
    fields.push({
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
    })
  }

  fields.push({
    type: 'varchar',
    name: 'product_ref',
    label: 'Référence',
    mandatory: true,
    maxLength: 128
  }, {
    type: 'varchar',
    name: 'product_label',
    label: 'Libellé',
    mandatory: false,
    maxLength: 255
  })

  if (askHasBatch) {
    fields.push({
      type: 'boolean',
      name: 'product_hasbatch',
      label: 'Utiliser les numéros de lots/série',
      mandatory: false
    })
  }

  if (useDEEE) {
    const deeeField: FormField = getDeeeField(deeeForm)
    fields.push(deeeField)
  }

  let descriptionNotes
  if (usePCat) {
    descriptionNotes = {
      load: 'pcat',
      key: 'rowid',
      basedOnValueOf: 'pcat',
      field: 'notes'
    }
  }
  fields.push({
    type: 'text',
    name: 'product_description',
    label: 'Notes',
    mandatory: false,
    notes: descriptionNotes
  })

  return {
    type: 'form',
    label: 'Remplir la fiche produit',
    goto,
    fields
  }
}

export function createProductSpecifications (
  useUnitWeight: UseUnit, useUnitLength: UseUnit, useUnitSurface: UseUnit, useUnitVolume: UseUnit,
  goto: string
): StateDefinition {
  const r: StateDefinition = {
    type: 'form',
    label: 'Caractéristiques',
    goto,
    fields: []
  }

  pushUnitFields(r.fields, '', useUnitWeight, useUnitLength, useUnitSurface, useUnitVolume)

  if (r.fields.length === 0) {
    return {
      type: 'virtual',
      goto,
      label: 'Caractéristiques'
    }
  }
  return r
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

export function showProduct (
  usePCat: boolean, useDEEE: boolean, usePBrand: boolean,
  useUnitWeight: UseUnit, useUnitLength: UseUnit, useUnitSurface: UseUnit, useUnitVolume: UseUnit,
  okGoto: string
): StateDefinition {
  const fields: ShowFields = []

  if (usePCat) {
    fields.push({
      type: 'varchar',
      name: 'pcats',
      label: 'Catégorie'
    })
  }

  if (usePBrand) {
    fields.push({
      type: 'varchar',
      name: 'pbrand',
      label: 'Marque'
    })
  }

  fields.push({
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
  })

  if (useDEEE) {
    fields.push({
      type: 'varchar',
      name: 'deee_type',
      label: 'DEEE'
    })
  }

  if (useUnitWeight !== '0') {
    fields.push({
      type: 'varchar',
      name: 'weight_txt',
      label: 'Poids'
    })
  }

  if (useUnitLength !== '0') {
    fields.push({
      type: 'varchar',
      name: 'length_txt',
      label: 'Longueur'
    })
  }

  if (useUnitSurface !== '0') {
    fields.push({
      type: 'varchar',
      name: 'surface_txt',
      label: 'Surface'
    })
  }

  if (useUnitVolume !== '0') {
    fields.push({
      type: 'varchar',
      name: 'volume_txt',
      label: 'Volume'
    })
  }

  return {
    type: 'show',
    label: 'Produit',
    key: 'product',
    primaryKey: 'product',
    okGoto,
    fields
  }
}
