import type { StateDefinition, ShowFields } from '../lib/state/index'

export function choosePickup (goto: string, creationGoto: string): StateDefinition {
  return {
    type: 'pick',
    label: 'Accueil',
    key: 'pickup',
    goto,
    creationGoto,
    creationLabel: 'Nouvelle collecte',
    primaryKey: 'rowid',
    fields: [
      { name: 'display', label: 'Collecte' }
    ]
  }
}

export function createPickup (goto: string): StateDefinition {
  return {
    type: 'form',
    label: 'Nouvelle collecte',
    goto,
    fields: [
      {
        name: 'date_pickup',
        type: 'date',
        label: 'Date de la collecte',
        mandatory: true,
        defaultToToday: true,
        maxToToday: true
      },
      {
        type: 'text',
        name: 'description',
        label: 'Remarques',
        mandatory: false
      }
    ]
  }
}

export function savePickup (goto: string, saveUntil: string): StateDefinition {
  return {
    type: 'save',
    label: 'Création de la collecte',
    key: 'pickup',
    primaryKey: 'rowid', // FIXME: to check.
    labelKey: 'Collecte',
    saveUntil,
    goto
  }
}

export function showPickup (useDEEE: boolean, addGoto: string, editLineGoto: string): StateDefinition {
  const lineCols: ShowFields = []
  lineCols.push({
    type: 'varchar',
    name: 'name',
    label: 'Produit'
  })
  if (useDEEE) {
    lineCols.push({
      type: 'boolean',
      name: 'deee',
      label: 'DEEE',
      total: true,
      totalQtyFieldName: 'qty'
    })
  }
  lineCols.push({
    type: 'integer',
    name: 'qty',
    label: 'Quantité',
    total: true
  })
  lineCols.push({
    type: 'edit',
    name: 'pickupline',
    label: 'Modifier',
    pushToStack: [
      {
        fromDataKey: 'rowid', // when on showCollecte page, rowid is the pickup_line id.
        pushOnStackKey: 'pickup_line_id', // pushing the id in this key
        silent: false,
        invisible: true
      },
      {
        fromDataKey: 'name', // this is the product ref. Pushing it as silent, so we can see it in the save page.
        pushOnStackKey: 'pickup_line_product_ref',
        stackLabel: 'Produit',
        silent: true,
        invisible: false
      }
    ],
    goto: editLineGoto
  })

  return {
    type: 'show',
    label: 'Collecte',
    key: 'pickup',
    primaryKey: 'pickup', // FIXME: should be less ambigous
    addGoto,
    addLabel: 'Ajouter un produit',
    fields: [
      {
        type: 'varchar',
        name: 'display',
        label: 'Collecte'
      },
      {
        type: 'varchar',
        name: 'date',
        label: 'Date'
      },
      {
        type: 'text',
        name: 'description',
        label: 'Description'
      },
      {
        type: 'lines',
        name: 'lines',
        label: 'Produits',
        lines: lineCols
      }
    ]
  }
}
