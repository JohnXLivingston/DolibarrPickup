import { StateDefinition, FormField, PickFields, ShowFields, StateDefinitionLoadData, FormFieldSelectFilterOptions } from '../lib/state/index'
import type { UnitsEditMode, UseUnit } from '../lib/utils/units'
import type { SpecificMode } from '../lib/utils/types'
import type { Stack } from '../lib/stack'
import type { StateRetrievedData } from '../lib/state/index'
import type { GetDataParams } from '../lib/data'
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

function getDeeeField (pcatStackKey: string, usePCat: boolean): FormField {
  let filterOptions: FormFieldSelectFilterOptions | undefined
  if (usePCat) {
    filterOptions = (field, stack, retrievedData) => {
      const pcatData = retrievedData.get('all_pcats')
      if (!pcatData || pcatData.status !== 'resolved') {
        console.log('product_deee_type.filterOptions: data are not loaded')
        return field.options
      }
      const pcatId = stack.searchValue(pcatStackKey)
      if (!pcatId) {
        console.error('product_deee_type.filterOptions: no pcat in stack')
        return field.options
      }

      const currentPcat = (pcatData.data as any[]).find(el => el.rowid === pcatId)
      if (!currentPcat) {
        console.error('product_deee_type.filterOptions: did not found the current pcat, with id=' + pcatId)
        return field.options
      }

      if (!currentPcat.deee_constraint || currentPcat.deee_constraint === '') {
        console.log('product_deee_type.filterOptions: the current pcat has not deee_constraint')
        return field.options
      }

      console.log('product_deee_type.filterOptions: deee_constraint is: ', currentPcat.deee_constraint)
      // console.log('product_deee_type.filterOptions: before filtering: ', field.options)
      const r = field.options.filter(option => {
        switch (currentPcat.deee_constraint) {
          case 'off':
            return option.value === ''
          case 'gef':
            return option.value === 'gef'
          case 'ghf':
            return option.value === 'ghf'
          case 'pam':
            return option.value === 'pam'
          case 'pampro':
            return option.value === 'pam_pro'
          case 'ecr':
            return option.value === 'ecr'
          case 'ecrpro':
            return option.value === 'ecr_pro'
          case 'pam_or_pampro':
            return option.value === 'pam' || option.value === 'pam_pro'
          case 'ecr_or_ecrpro':
            return option.value === 'ecr' || option.value === 'ecr_pro'
        }
        console.log('product_deee_type.filterOptions: invalid deee_constraint:', currentPcat.deee_constraint)
        return false
      })

      // console.log('product_deee_type.filterOptions: after filtering: ', r)
      return r
    }
  }

  return {
    type: 'select',
    name: 'product_deee_type',
    label: 'DEEE',
    // readonly: true, FIXME: should be true if only 1 possible value
    // default: value, FIXME: should be set if only 1 possible value
    mandatory: false,
    options: [],
    load: 'dict',
    loadParams: {
      what: 'deee_type'
    },
    dontAddEmptyOption: true,
    map: {
      value: 'value',
      label: 'label'
    },
    filterOptions,
    edit: {
      getDataFromSourceKey: 'deee_type'
    }
  }
}

function getHasBatchField (pcatStackKey: string, usePCat: boolean): FormField {
  let filterOptions: FormFieldSelectFilterOptions | undefined
  if (usePCat) {
    filterOptions = (field, stack, retrievedData) => {
      const pcatData = retrievedData.get('all_pcats')
      if (!pcatData || pcatData.status !== 'resolved') {
        console.log('product_hasbatch.filterOptions: data are not loaded')
        return field.options
      }
      const pcatId = stack.searchValue(pcatStackKey)
      if (!pcatId) {
        console.error('product_hasbatch.filterOptions: no pcat in stack')
        return field.options
      }

      const currentPcat = (pcatData.data as any[]).find(el => el.rowid === pcatId)
      if (!currentPcat) {
        console.error('product_hasbatch.filterOptions: did not found the current pcat, with id=' + pcatId)
        return field.options
      }

      if (!currentPcat.batch_constraint || currentPcat.batch_constraint === '') {
        console.log('product_hasbatch.filterOptions: the current pcat has not batch_constraint')
        return field.options
      }

      console.log('product_hasbatch.filterOptions: batch_constraint is: ', currentPcat.batch_constraint)
      return field.options.filter(option => {
        switch (currentPcat.batch_constraint) {
          case 'batch_status_0':
            return option.value === '0'
          case 'batch_status_1':
            return option.value === '1'
          case 'batch_status_2':
            return option.value === '2'
        }
        console.log('product_hasbatch.filterOptions: invalid batch_constraint:', currentPcat.batch_constraint)
        return false
      })
    }
  }

  return {
    type: 'select',
    name: 'product_hasbatch',
    label: 'Utiliser les numéros de lots/série',
    mandatory: false,
    options: [
      { value: '0', label: 'Non' },
      { value: '1', label: 'Lot/Série' },
      { value: '2', label: 'Numéro de série unique' }
    ],
    filterOptions,
    edit: {
      getDataFromSourceKey: 'hasbatch'
    }
  }
}

export function createProduct (
  usePCat: boolean,
  useDEEE: boolean,
  productRefAuto: boolean,
  usePBrand: boolean,
  askHasBatch: boolean,
  goto: string,
  pcatStackName: string,
  specificMode: SpecificMode
): StateDefinition {
  const fields: FormField[] = []

  let mustLoadPCat = false

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

  if (!productRefAuto) {
    fields.push({
      type: 'varchar',
      name: 'product_ref',
      label: 'Référence',
      defaultFunc: specificMode !== 'ressourcerie_cinema'
        ? undefined
        : (stack: Stack, retrievedData: StateRetrievedData) => {
            const tag = stack.searchValue('label') ?? ''
            if (!tag) { return '' }
            const m = tag.match(/>>\s*(\d\d\d\d+)/)
            if (!m) { return '' }
            const nomenclature = m[1]

            const pickupLabel = stack.searchValue('display') ?? '' // FIXME: rename this field?
            const mPL = pickupLabel.match(/P2\d(\d{2})\d{2}-(\d+)/) // P202308-0001 => get 23 and 0001
            if (!mPL) { return '' }
            const year = mPL[1]
            let pickupNumber = mPL[2]
            while (pickupNumber.length > 3 && pickupNumber.startsWith('0')) {
              pickupNumber = pickupNumber.substring(1)
            }

            const pickup = retrievedData.get('pickup')
            if (!pickup) { return '' }
            if (pickup.status !== 'resolved') { return '' }
            const lines = pickup.data.lines
            if (!lines) { return '' }

            const middlePart = '-' + year + pickupNumber + '-'
            let cpt = Math.max(1, lines.length)
            // We will also check if there is already a ref with a higher number
            for (const line of lines) {
              if (!line.name.toString().includes(middlePart)) { continue }
              const c = parseInt(line.name.split(/-/g).pop())
              if (c >= cpt) {
                cpt = c + 1
              }
            }
            let sCpt = cpt.toString()
            while (sCpt.length < 4) { sCpt = '0' + sCpt }

            // On doit générer un numéro sous la forme:
            // NNNN-CCCCC-DDDD avec:
            // NNNN: nomenclature, les 4 chiffres du tag
            // CCCCC: 2 chiffres pour l'année, puis un compteur de collecte (on va le déduire du numéro de collecte)
            // DDDD: un compteur sur 4 chiffre, du nombre de produit sur la collecte.
            return nomenclature + middlePart + sCpt
          },
      mandatory: true,
      maxLength: 128
    })
  }

  fields.push({
    type: 'varchar',
    name: 'product_label',
    label: 'Libellé',
    mandatory: productRefAuto,
    maxLength: 255,
    defaultFunc: specificMode !== 'ressourcerie_cinema'
      ? undefined
      : (stack: Stack) => {
          const tag = stack.searchValue('label') ?? ''
          if (!tag) { return '' }
          const m = tag.match(/^\s*\d+.*>>\s*\d\d\d\d+\s*(.*)$/)
          if (!m) { return '' }
          return m[1]
        }
  })

  if (askHasBatch) {
    mustLoadPCat = true
    fields.push(getHasBatchField(pcatStackName, usePCat))
  }

  if (useDEEE) {
    mustLoadPCat = true
    const deeeField: FormField = getDeeeField(pcatStackName, usePCat)
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
    label: 'Description de la fiche produit',
    mandatory: false,
    notes: descriptionNotes
  })

  const loadData: StateDefinitionLoadData[] = []
  if (mustLoadPCat && usePCat && pcatStackName) {
    loadData.push({
      dataKey: 'pcat',
      retrievedDataKey: 'all_pcats',
      requestType: 'list'
    })
  }
  if (specificMode === 'ressourcerie_cinema') {
    loadData.push({
      dataKey: 'pickup',
      requestType: 'get',
      requestParamsFunc: (stack: Stack): GetDataParams => {
        return {
          id: stack.searchValue('pickup') ?? ''
        }
      }
    })
  }

  return {
    type: 'form',
    label: 'Remplir la fiche produit',
    goto,
    fields,
    loadData
  }
}

export function editProduct (
  usePCat: boolean, useDEEE: boolean, usePBrand: boolean, askHasBatch: boolean,
  unitsEditMode: UnitsEditMode,
  useUnitWeight: UseUnit, useUnitLength: UseUnit, useUnitSurface: UseUnit, useUnitVolume: UseUnit,
  goto: string,
  pcatStackName: string,
  specificMode: SpecificMode
): StateDefinition {
  const fields: FormField[] = []

  let mustLoadPCat = false

  if (askHasBatch) {
    mustLoadPCat = true
    fields.push(getHasBatchField(pcatStackName, usePCat))
  }

  if (useDEEE) {
    const deeeField: FormField = getDeeeField(pcatStackName, usePCat)
    fields.push(deeeField)
  }

  if (unitsEditMode === 'product') {
    pushUnitFields(fields, '', '', useUnitWeight, useUnitLength, useUnitSurface, useUnitVolume)
  }

  let descriptionNotes
  if (usePCat) {
    descriptionNotes = {
      load: 'pcat',
      key: 'rowid',
      basedOnValueOf: 'reference_pcat_id',
      field: 'notes'
    }
  }
  fields.push({
    type: 'text',
    name: 'product_description',
    label: 'Description de la fiche produit',
    mandatory: false,
    notes: descriptionNotes,
    edit: {
      getDataFromSourceKey: 'description'
    }
  })

  if (specificMode === 'ressourcerie_cinema') {
    pushLRDCFields(fields)
  }

  const loadData: StateDefinitionLoadData[] = []
  if (mustLoadPCat && usePCat && pcatStackName) {
    loadData.push({
      dataKey: 'pcat',
      retrievedDataKey: 'all_pcats',
      requestType: 'list'
    })
  }

  return {
    type: 'form',
    label: 'Corriger la fiche produit',
    edit: {
      stackKey: 'product',
      getDataKey: 'product'
    },
    goto,
    fields,
    loadData
  }
}

export function createProductSpecifications (
  unitsEditMode: UnitsEditMode,
  useUnitWeight: UseUnit, useUnitLength: UseUnit, useUnitSurface: UseUnit, useUnitVolume: UseUnit,
  goto: string,
  specificMode: SpecificMode
): StateDefinition {
  const r: StateDefinition = {
    type: 'form',
    label: 'Caractéristiques',
    goto,
    fields: []
  }

  if (unitsEditMode === 'product') {
    pushUnitFields(r.fields, '', '', useUnitWeight, useUnitLength, useUnitSurface, useUnitVolume)
  }

  if (specificMode === 'ressourcerie_cinema') {
    // Champs spécifiques pour La Ressourcerie Du Cinéma.
    pushLRDCFields(r.fields)
  }

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

export function saveEditProduct (goto: string, saveUntil: string, removeUntil: string, removeFromStack: boolean): StateDefinition {
  return {
    type: 'save',
    label: 'Sauvegarde du produit',
    key: 'product',
    primaryKey: 'rowid', // FIXME: to check.
    labelKey: 'Produit',
    dependingCacheKey: 'pickup',
    saveUntil,
    removeUntil,
    removeFromStack,
    goto
  }
}

export function showProduct (
  usePCat: boolean, useDEEE: boolean, usePBrand: boolean,
  useBatch: boolean,
  _unitsEditMode: UnitsEditMode,
  useUnitWeight: UseUnit, useUnitLength: UseUnit, useUnitSurface: UseUnit, useUnitVolume: UseUnit,
  okGoto: string | undefined,
  editGoto: string | undefined,
  editCatGoto: string | undefined,
  specificMode: SpecificMode
): StateDefinition {
  const fields: ShowFields = []

  if (usePCat) {
    fields.push({
      type: 'varchar',
      name: 'reference_pcat_label',
      label: 'Catégorie de référence',
      goto: editCatGoto,
      pushToStack: [
        // Note: no need to add 'product' on the stack, it is already here from the previous state.
        {
          // we take the product ref, just to show it on save screen
          fromDataKey: 'ref',
          pushOnStackKey: 'ref',
          silent: true,
          invisible: false,
          stackLabel: 'Référence produit'
        },
        {
          // here we specify a «subaction» for the backend API.
          value: 'edit_product_cat_from_pickup',
          pushOnStackKey: 'subaction',
          silent: false,
          invisible: true
        }
      ]
    })
    fields.push({
      type: 'varchar',
      name: 'pcats',
      label: 'Toutes les catégories'
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
  })

  if (editGoto) {
    fields.push({
      type: 'edit',
      name: 'product',
      label: 'Éditer',
      pushToStack: [
        // Note: no need to add 'product' on the stack, it is already here from the previous state.
        // {
        //   fromDataKey: 'rowid',
        //   pushOnStackKey: 'product',
        //   silent: false,
        //   invisible: true,
        //   stackLabel: 'ID produit'
        // },
        {
          // we take the product ref, just to show it on save screen
          fromDataKey: 'ref',
          pushOnStackKey: 'ref',
          silent: true,
          invisible: false,
          stackLabel: 'Référence produit'
        },
        {
          // we have to push the current reference tag, that is needed for the form
          fromDataKey: 'reference_pcat_id',
          pushOnStackKey: 'reference_pcat_id',
          silent: true,
          invisible: true
        },
        {
          // here we specify a «subaction» for the backend API.
          value: 'edit_product_attrs_from_pickup',
          pushOnStackKey: 'subaction',
          silent: false,
          invisible: true
        }
      ],
      goto: editGoto
    })
  }

  if (useBatch) {
    fields.push({
      type: 'varchar',
      name: 'hasbatch_txt',
      label: 'Numéro de lot/série'
    })
  }

  if (useDEEE) {
    fields.push({
      type: 'varchar',
      name: 'deee_type_txt',
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

  fields.push({
    type: 'text',
    name: 'description',
    label: 'Description de la fiche produit'
  })

  if (specificMode === 'ressourcerie_cinema') {
    pushLRDCFields(fields)
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

/**
 * Ajout de champs spécifiques La Ressourcerie Du Cinéma sur les formulaires.
 */
function pushLRDCFields (fields: FormField[] | ShowFields): void {
  fields.push({
    type: 'float',
    label: 'Diamètre',
    name: 'lrdc_diametre',
    mandatory: false,
    min: 0,
    max: 1000,
    step: 0.001,
    edit: {
      getDataFromSourceKey: 'lrdc_diametre'
    }
  })
  fields.push({
    type: 'float',
    label: 'Épaisseur',
    name: 'lrdc_epaisseur',
    mandatory: false,
    min: 0,
    max: 1000,
    step: 0.001,
    edit: {
      getDataFromSourceKey: 'lrdc_epaisseur'
    }
  })
  fields.push({
    type: 'varchar',
    label: 'Matière produit',
    name: 'lrdc_matiereproduit',
    mandatory: false,
    edit: {
      getDataFromSourceKey: 'lrdc_matiereproduit'
    }
  })
  fields.push({
    type: 'varchar',
    label: 'Prix commerce',
    name: 'lrdc_pxcommerce',
    mandatory: false,
    edit: {
      getDataFromSourceKey: 'lrdc_pxcommerce'
    }
  })
  fields.push({
    type: 'varchar',
    label: 'Couleur',
    name: 'lrdc_couleur',
    mandatory: false,
    edit: {
      getDataFromSourceKey: 'lrdc_couleur'
    }
  })
  fields.push({
    type: 'varchar',
    label: 'Style - genre',
    name: 'lrdc_style',
    mandatory: false,
    edit: {
      getDataFromSourceKey: 'lrdc_style'
    }
  })
}
