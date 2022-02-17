import type { StateDefinition } from './lib/state/index'
import { initLang } from './lib/translate'
import { initHistory } from './lib/history'
import { initNunjucks } from './lib/nunjucks'
import { Machine } from './lib/machine'
import * as definitions from './definitions/index'

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

  // version is a string that must be related to the Machine definition, and the backend configuration.
  // It is used to clear the stack on page load, if the configuration changed.
  let version = container.attr('data-modpickup-version') ?? '0'
  version += '_e' + (entrepotId ?? '0')
  version += '_c' + (usePCat ? '1' : '0')
  version += '_b' + (usePBrand ? '1' : '0')
  version += '_d' + (useDEEE ? '1' : '0')

  const definition: {[key: string]: StateDefinition} = {}

  definition.init = definitions.choosePickup('show_pickup', entrepotId !== undefined ? 'societe' : 'entrepot')

  definition.entrepot = definitions.pickEntrepot('societe')

  definition.societe = definitions.pickSociete('show_societe', 'create_societe')
  definition.create_societe = definitions.createSociete('save_societe')
  definition.save_societe = definitions.saveSociete('show_societe', 'create_societe')
  definition.show_societe = definitions.showSociete('create_pickup')

  definition.create_pickup = definitions.createPickup('save_pickup')
  definition.save_pickup = definitions.savePickup('show_pickup', entrepotId !== undefined ? 'societe' : 'entrepot')
  definition.show_pickup = definitions.showPickup(useDEEE, 'product')

  let saveUntilForProduct: string
  if (usePCat) {
    saveUntilForProduct = 'categorie'
    definition.product = definitions.pickProduct(usePBrand, 'show_product', 'categorie')
    definition.categorie = definitions.pickPCat('create_product') // Note: itemGotoField can override the goto
    definition.create_product = definitions.createProduct(usePCat, useDEEE, usePBrand, 'weight', '')
  } else {
    saveUntilForProduct = 'create_product'
    definition.product = definitions.pickProduct(usePBrand, 'show_product', 'create_product')
    definition.create_product = definitions.createProduct(usePCat, useDEEE, usePBrand, 'weight', '')
  }
  if (useDEEE) {
    definition.create_product_deee_off = definitions.createProduct(usePCat, useDEEE, usePBrand, 'weight', 'create_product_deee_off')
    definition.create_product_deee_gef = definitions.createProduct(usePCat, useDEEE, usePBrand, 'weight', 'create_product_deee_gef')
    definition.create_product_deee_ghf = definitions.createProduct(usePCat, useDEEE, usePBrand, 'weight', 'create_product_deee_ghf')
    definition.create_product_deee_pam = definitions.createProduct(usePCat, useDEEE, usePBrand, 'weight', 'create_product_deee_pam')
    definition.create_product_deee_pampro = definitions.createProduct(usePCat, useDEEE, usePBrand, 'weight', 'create_product_deee_pampro')
    definition.create_product_deee_ecr = definitions.createProduct(usePCat, useDEEE, usePBrand, 'weight', 'create_product_deee_ecr')
    definition.create_product_deee_ecrpro = definitions.createProduct(usePCat, useDEEE, usePBrand, 'weight', 'create_product_deee_ecrpro')
    definition.create_product_deee_pam_or_pampro = definitions.createProduct(usePCat, useDEEE, usePBrand, 'weight', 'create_product_deee_pam_or_pampro')
    definition.create_product_deee_ecr_or_ecrpro = definitions.createProduct(usePCat, useDEEE, usePBrand, 'weight', 'create_product_deee_ecr_or_ecrpro')
  }
  definition.weight = definitions.createProductWeight('save_product')
  definition.save_product = definitions.saveProduct('show_product', saveUntilForProduct)
  definition.show_product = definitions.showProduct(usePCat, useDEEE, usePBrand, 'qty')

  definition.qty = definitions.createPickupLine('save_pickupline')
  definition.save_pickupline = definitions.savePickupLine('show_pickup', 'init', 'show_pickup', true)

  const machine = new Machine(
    'myMachine',
    version,
    container.attr('data-user-id') ?? '',
    definition
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

  definition.init = {
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
        label: 'Show',
        value: 'show',
        goto: 'show'
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

  definition.pick = {
    type: 'pick',
    label: 'Pick test',
    key: 'demo',
    primaryKey: 'rowid',
    goto: 'unknown',
    creationGoto: 'unknown',
    creationLabel: 'New XXX',
    fields: [
      { name: 'field1', label: 'Demo 1', applyFilter: 'localeUpperCase' },
      { name: 'field2', label: 'Demo 2' }
    ]
  }

  definition.select = {
    type: 'select',
    label: 'Select test (not fully implemented)',
    options: [
      { label: 'Option 1', value: '1' },
      { label: 'Option 2', value: '2' }
    ]
  }

  const machine = new Machine(
    'Demo',
    version,
    container.attr('data-user-id') ?? '',
    definition
  )
  machine.init(container)

  window.pickupMobileMachine = machine
}
