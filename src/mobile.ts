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
  let entrepotId = container.attr('data-entrepot-id')
  if (entrepotId === '') { entrepotId = undefined }

  const definition: {[key: string]: StateDefinition} = {}

  definition.init = definitions.choosePickup(entrepotId !== undefined ? 'societe' : 'entrepot')

  definition.entrepot = definitions.pickEntrepot('societe')

  definition.societe = definitions.pickSociete('show_societe', 'create_societe')
  definition.create_societe = definitions.createSociete('save_societe')
  definition.save_societe = definitions.saveSociete('show_societe', 'create_societe')
  definition.show_societe = definitions.showSociete('create_pickup')

  definition.create_pickup = definitions.createPickup('save_pickup')
  definition.save_pickup = definitions.savePickup(entrepotId !== undefined ? 'societe' : 'entrepot', 'show_pickup')
  definition.show_pickup = definitions.showPickup('product')

  definition.product = definitions.pickProduct('show_product', 'categorie')
  definition.categorie = definitions.pickPCat('create_product') // Note: itemGotoField can override the goto
  definition.create_product = definitions.createProduct('weight')
  definition.weight = definitions.createProductWeight('save_product')
  definition.save_product = definitions.saveProduct('show_product', 'categorie')
  definition.show_product = definitions.showProduct('qty')

  definition.qty = definitions.createPickupLine('save_pickupline')
  definition.save_pickupline = definitions.savePickupLine('show_pickup', 'init', 'show_pickup', true)

  const machine = new Machine(
    'myMachine',
    2022020401, // this is the version number. Change it if there is no retro compatibility for existing stacks
    container.attr('data-user-id') ?? '',
    definition
  )
  machine.init(container)

  window.pickupMobileMachine = machine
})
