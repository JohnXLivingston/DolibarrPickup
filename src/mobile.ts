import type { StateDefinition } from './lib/state/index'
import { initLang } from './lib/translate'
import { initHistory } from './lib/history'
import { initNunjucks } from './lib/nunjucks'
import { Machine } from './lib/machine'
import * as definitions from './definitions/index'
import { readUnitsEditMode, readUseUnit } from './lib/utils/units'

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
  const usePBrand = container.attr('data-use-pbrand') === '1'
  const useDEEE = container.attr('data-use-deee') === '1'
  const askHasBatch = container.attr('data-ask-hasbatch') === '1'
  const unitsEditMode = readUnitsEditMode(container.attr('data-units-edit-mode'))
  const useUnitWeight = readUseUnit(container.attr('data-units-weight'))
  const useUnitLength = readUseUnit(container.attr('data-units-length'))
  const useUnitSurface = readUseUnit(container.attr('data-units-surface'))
  const useUnitVolume = readUseUnit(container.attr('data-units-volume'))
  const processingStatus = container.attr('data-processing-status') ?? null
  const usePickupType = container.attr('data-use-pickup-type') === '1'
  const usePickuplineDescription = container.attr('data-use-pickupline-description') === '1'
  const dolibarrUrl = container.attr('data-dolibarr-url') ?? undefined

  // version is a string that must be related to the Machine definition, and the backend configuration.
  // It is used to clear the stack on page load, if the configuration changed.
  let version = container.attr('data-modpickup-version') ?? '0'
  version += '_e' + (entrepotId ?? '0')
  version += '_c' + (usePCat ? '1' : '0')
  version += '_b' + (usePBrand ? '1' : '0')
  version += '_d' + (useDEEE ? '1' : '0')
  version += '_hb' + (askHasBatch ? '1' : '0')
  version += '_uw' + useUnitWeight[0]
  version += '_ul' + useUnitLength[0]
  version += '_us' + useUnitSurface[0]
  version += '_uv' + useUnitVolume[0]
  version += '_csps' + (processingStatus ?? 'x')
  version += '_pt' + (usePickupType ? '1' : '0')
  version += '_pld' + (usePickuplineDescription ? '1' : '0')
  version += '_eum' + unitsEditMode

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
    useUnitWeight, useUnitLength, useUnitSurface, useUnitVolume,
    'product', 'show_product_from_pickup', 'qty_edit',
    processingStatus ? { goto: 'save_pickup_status', processingStatus: processingStatus } : null,
    usePickupType
  )

  let saveUntilForProduct: string
  if (usePCat) {
    saveUntilForProduct = 'categorie'
    definition.product = definitions.pickProduct(usePBrand, 'show_product', 'categorie')
    definition.categorie = definitions.pickPCat('create_product') // Note: itemGotoField can override the goto
    definition.create_product = definitions.createProduct(usePCat, useDEEE, usePBrand, askHasBatch, 'product_specifications', '')
  } else {
    saveUntilForProduct = 'create_product'
    definition.product = definitions.pickProduct(usePBrand, 'show_product', 'create_product')
    definition.create_product = definitions.createProduct(usePCat, useDEEE, usePBrand, askHasBatch, 'product_specifications', '')
  }
  if (useDEEE) {
    definition.create_product_deee_off = definitions.createProduct(usePCat, useDEEE, usePBrand, askHasBatch, 'product_specifications', 'create_product_deee_off')
    definition.create_product_deee_gef = definitions.createProduct(usePCat, useDEEE, usePBrand, askHasBatch, 'product_specifications', 'create_product_deee_gef')
    definition.create_product_deee_ghf = definitions.createProduct(usePCat, useDEEE, usePBrand, askHasBatch, 'product_specifications', 'create_product_deee_ghf')
    definition.create_product_deee_pam = definitions.createProduct(usePCat, useDEEE, usePBrand, askHasBatch, 'product_specifications', 'create_product_deee_pam')
    definition.create_product_deee_pampro = definitions.createProduct(usePCat, useDEEE, usePBrand, askHasBatch, 'product_specifications', 'create_product_deee_pampro')
    definition.create_product_deee_ecr = definitions.createProduct(usePCat, useDEEE, usePBrand, askHasBatch, 'product_specifications', 'create_product_deee_ecr')
    definition.create_product_deee_ecrpro = definitions.createProduct(usePCat, useDEEE, usePBrand, askHasBatch, 'product_specifications', 'create_product_deee_ecrpro')
    definition.create_product_deee_pam_or_pampro = definitions.createProduct(usePCat, useDEEE, usePBrand, askHasBatch, 'product_specifications', 'create_product_deee_pam_or_pampro')
    definition.create_product_deee_ecr_or_ecrpro = definitions.createProduct(usePCat, useDEEE, usePBrand, askHasBatch, 'product_specifications', 'create_product_deee_ecr_or_ecrpro')
  }
  definition.product_specifications = definitions.createProductSpecifications(unitsEditMode, useUnitWeight, useUnitLength, useUnitSurface, useUnitVolume, 'save_product')
  definition.save_product = definitions.saveProduct('show_product', saveUntilForProduct)
  definition.show_product = definitions.showProduct(usePCat, useDEEE, usePBrand, unitsEditMode, useUnitWeight, useUnitLength, useUnitSurface, useUnitVolume, 'qty', undefined)

  definition.show_product_from_pickup = definitions.showProduct(usePCat, useDEEE, usePBrand, unitsEditMode, useUnitWeight, useUnitLength, useUnitSurface, useUnitVolume, undefined, 'edit_product')
  // FIXME: following line does not constrains DEEE types.
  // FIXME: save state.
  definition.edit_product = definitions.editProduct(usePCat, useDEEE, usePBrand, askHasBatch, unitsEditMode, useUnitWeight, useUnitLength, useUnitSurface, useUnitVolume, '', '')

  definition.qty = definitions.createPickupLine(false, unitsEditMode, useUnitWeight, useUnitLength, useUnitSurface, useUnitVolume, usePickuplineDescription, 'save_pickupline')
  definition.qty_edit = definitions.createPickupLine(true, unitsEditMode, useUnitWeight, useUnitLength, useUnitSurface, useUnitVolume, usePickuplineDescription, 'save_pickupline')
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
