import type { StateDefinition } from './lib/state/index'
import type { SpecificMode } from './lib/utils/types'
import { initLang } from './lib/translate'
import { initHistory } from './lib/history'
import { initNunjucks } from './lib/nunjucks'
import { Machine } from './lib/machine'
import * as definitions from './definitions/index'
import { readUnitsEditMode, readUseUnit, UnitsOptions } from './lib/utils/units'
import { setPrintLabelUrl } from './shared/printlabel'

// FIXME: only pick needed files.
import '../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js'

declare global {
  interface Window {
    pickupMobileMachine: Machine
  }
}

$(function () {
  initLang()
  initNunjucks()
  initHistory()

  const container = $('[pickupmobileapp-container]')
  if (container.attr('data-demo') === '1') {
    demoMode(container)
    return
  }

  let entrepotId = container.attr('data-entrepot-id')
  if (entrepotId === '') { entrepotId = undefined }
  const usePCat = container.attr('data-use-pcat') === '1'
  const productRefAuto = container.attr('data-product-ref-auto') === '1'
  const usePBrand = container.attr('data-use-pbrand') === '1'
  const useSellPrice = container.attr('data-use-sellprice') === '1'
  const useRentalPrice = container.attr('data-use-rentalprice') === '1'
  const useDEEE = container.attr('data-use-deee') === '1'
  const useBatch = container.attr('data-use-batch') === '1'
  const askHasBatch = container.attr('data-ask-hasbatch') === '1'
  const unitsEditMode = readUnitsEditMode(container.attr('data-units-edit-mode'))
  const useUnitWeight = readUseUnit(container.attr('data-units-weight'))
  const useUnitLength = readUseUnit(container.attr('data-units-length'))
  const useUnitWidth = unitsEditMode !== 'product' ? '0' : readUseUnit(container.attr('data-units-width'))
  const useUnitHeight = unitsEditMode !== 'product' ? '0' : readUseUnit(container.attr('data-units-height'))
  const useUnitSurface = readUseUnit(container.attr('data-units-surface'))
  const useUnitVolume = readUseUnit(container.attr('data-units-volume'))

  const weightUnit = container.attr('data-weight-unit') as string
  const weightUnitLabel = container.attr('data-weight-unit-label') as string
  const sizeUnit = container.attr('data-size-unit') as string
  const sizeUnitLabel = container.attr('data-size-unit-label') as string
  const surfaceUnit = container.attr('data-surface-unit') as string
  const surfaceUnitLabel = container.attr('data-surface-unit-label') as string
  const volumeUnit = container.attr('data-volume-unit') as string
  const volumeUnitLabel = container.attr('data-volume-unit-label') as string

  const processingStatus = container.attr('data-processing-status') ?? null
  const usePickupType = container.attr('data-use-pickup-type') === '1'
  const usePickuplineDescription = container.attr('data-use-pickupline-description') === '1'
  const printableLabelUrl = container.attr('data-printable-label-url')
  const usePrintableLabel = !!printableLabelUrl
  const dolibarrUrl = container.attr('data-dolibarr-url') ?? undefined
  const specificMode: SpecificMode = (container.attr('data-specific-mode') ?? '') as SpecificMode

  if (printableLabelUrl) { setPrintLabelUrl(printableLabelUrl) }

  // version is a string that must be related to the Machine definition, and the backend configuration.
  // It is used to clear the stack on page load, if the configuration changed.
  let version = container.attr('data-modpickup-version') ?? '0'
  version += '_e' + (entrepotId ?? '0')
  version += '_c' + (usePCat ? '1' : '0')
  version += '_pra' + (productRefAuto ? '1' : '0')
  version += '_b' + (usePBrand ? '1' : '0')
  version += '_sp' + (useSellPrice ? '1' : '0')
  version += '_rp' + (useRentalPrice ? '1' : '0')
  version += '_pl' + (usePrintableLabel ? '1' : '0')
  version += '_d' + (useDEEE ? '1' : '0')
  version += '_ba' + (useBatch ? '1' : '0')
  version += '_hb' + (askHasBatch ? '1' : '0')
  version += '_uw' + useUnitWeight[0]
  version += '_ul' + useUnitLength[0]
  version += '_uw' + useUnitWidth[0]
  version += '_uh' + useUnitHeight[0]
  version += '_us' + useUnitSurface[0]
  version += '_uv' + useUnitVolume[0]
  version += '_csps' + (processingStatus ?? 'x')
  version += '_pt' + (usePickupType ? '1' : '0')
  version += '_pld' + (usePickuplineDescription ? '1' : '0')
  version += '_eum' + unitsEditMode
  version += '_wg' + weightUnit
  version += '_su' + sizeUnit
  version += '_su' + surfaceUnit
  version += '_vu' + volumeUnit
  if (specificMode) { version += '_sm' } // no need to add the specific value, should never change

  const unitsOptions: UnitsOptions = {
    useUnitWeight,
    useUnitLength,
    useUnitWidth,
    useUnitHeight,
    useUnitSurface,
    useUnitVolume,
    weightUnit,
    weightUnitLabel,
    sizeUnit,
    sizeUnitLabel,
    surfaceUnit,
    surfaceUnitLabel,
    volumeUnit,
    volumeUnitLabel,
    editMode: unitsEditMode
  }

  const definition: {[key: string]: StateDefinition} = {}

  definition.init = definitions.choosePickup('show_pickup', entrepotId !== undefined ? 'societe' : 'entrepot')

  definition.entrepot = definitions.pickEntrepot('societe')

  definition.societe = definitions.pickSociete('show_societe', 'create_societe')
  definition.create_societe = definitions.createSociete('save_societe')
  definition.save_societe = definitions.saveSociete('show_societe', 'create_societe')
  definition.show_societe = definitions.showSociete('create_pickup')

  definition.create_pickup = definitions.createPickup('save_pickup', usePickupType)
  definition.save_pickup = definitions.savePickup('show_pickup', entrepotId !== undefined ? 'societe' : 'entrepot')
  definition.show_pickup = definitions.showPickup(
    useDEEE,
    usePBrand,
    usePrintableLabel,
    unitsOptions,
    'product', 'show_product_from_pickup', 'qty_edit',
    processingStatus ? { goto: 'save_pickup_status', processingStatus: processingStatus } : null,
    usePickupType
  )

  let saveUntilForProduct: string
  if (usePCat) {
    saveUntilForProduct = 'categorie'
    definition.product = definitions.pickProduct(usePBrand, 'show_product', 'categorie')
    definition.categorie = definitions.pickPCat('create_product')
    definition.create_product = definitions.createProduct(usePCat, useDEEE, productRefAuto, usePBrand, askHasBatch, 'product_specifications', 'pcat', specificMode)
  } else {
    saveUntilForProduct = 'create_product'
    definition.product = definitions.pickProduct(usePBrand, 'show_product', 'create_product')
    definition.create_product = definitions.createProduct(usePCat, useDEEE, productRefAuto, usePBrand, askHasBatch, 'product_specifications', 'pcat', specificMode)
  }
  definition.product_specifications = definitions.createProductSpecifications(useSellPrice, useRentalPrice, unitsOptions, 'save_product', specificMode)
  definition.save_product = definitions.saveProduct('show_product', saveUntilForProduct)
  definition.show_product = definitions.showProduct(usePCat, useSellPrice, useRentalPrice, useDEEE, usePBrand, useBatch, unitsOptions, 'qty', undefined, undefined, specificMode)

  definition.show_product_from_pickup = definitions.showProduct(usePCat, useSellPrice, useRentalPrice, useDEEE, usePBrand, useBatch, unitsOptions, undefined, 'edit_product', 'edit_product_cat', specificMode)
  definition.edit_product = definitions.editProduct(usePCat, useSellPrice, useRentalPrice, useDEEE, usePBrand, askHasBatch, unitsOptions, 'save_edit_product', 'reference_pcat_id', specificMode)
  definition.save_edit_product = definitions.saveEditProduct('show_pickup', 'init', 'show_pickup', true)

  definition.edit_product_cat = definitions.pickPCat('save_edit_product_cat')
  definition.save_edit_product_cat = definitions.saveEditProduct('show_product_from_pickup', 'init', 'show_product_from_pickup', true)

  definition.qty = definitions.createPickupLine(false, unitsOptions, usePickuplineDescription, 'save_pickupline')
  definition.qty_edit = definitions.createPickupLine(true, unitsOptions, usePickuplineDescription, 'save_pickupline')
  definition.save_pickupline = definitions.savePickupLine('show_pickup', 'init', 'show_pickup', true)

  if (processingStatus) {
    definition.save_pickup_status = definitions.savePickupStatus('init', 'show_pickup')
  }

  const machine = new Machine(
    'myMachine',
    version,
    container.attr('data-user-id') ?? '',
    true,
    definition,
    dolibarrUrl
  )
  machine.init(container)

  window.pickupMobileMachine = machine
})

/**
 * This instanciate a false Machine, where we can easily test all states types.
 * Use only for dev purpose.
 * @param container
 */
function demoMode (container: JQuery): void {
  const version = container.attr('data-modpickup-version') ?? '0'
  const definition: {[key: string]: StateDefinition} = {}

  definition.init = definitions.demoInit()
  definition.pick = definitions.demoPick('show', 'form')
  definition.select = definitions.demoSelect()
  definition.form = definitions.demoForm('save')
  definition.save = definitions.demoSave('show', 'form')
  definition.compute = definitions.demoCompute('init', 'show')
  definition.show = definitions.demoShow()

  const machine = new Machine(
    'Demo',
    version,
    container.attr('data-user-id') ?? '',
    true,
    definition
  )
  machine.init(container)

  window.pickupMobileMachine = machine
}
