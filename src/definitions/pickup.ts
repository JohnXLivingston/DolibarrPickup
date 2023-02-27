import type { StateDefinition, ShowFields, FormField } from '../lib/state/index'
import { UseUnit } from '../lib/utils/units'

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

export function createPickup (goto: string, usePickupType: boolean): StateDefinition {
  const fields: FormField[] = [
    {
      name: 'date_pickup',
      type: 'date',
      label: 'Date de la collecte',
      mandatory: true,
      defaultToToday: true,
      maxToToday: true
    }
  ]

  if (usePickupType) {
    fields.push({
      name: 'pickup_type',
      type: 'select',
      label: 'Type de collecte',
      options: [],
      load: 'dict',
      loadParams: {
        what: 'pickup_type'
      },
      map: {
        value: 'id',
        label: 'label'
      },
      mandatory: false
    })
  }

  fields.push({
    type: 'text',
    name: 'description',
    label: 'Remarques',
    mandatory: false
  })
  return {
    type: 'form',
    label: 'Nouvelle collecte',
    goto,
    fields
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

export function showPickup (
  useDEEE: boolean,
  usePBrand: boolean,
  useUnitWeight: UseUnit, useUnitLength: UseUnit, useUnitSurface: UseUnit, useUnitVolume: UseUnit,
  addGoto: string,
  lineProductGoto: string | undefined,
  editLineGoto: string,
  setProcessingStatusGoto: null | {processingStatus: string, goto: string},
  usePickupType: boolean
): StateDefinition {
  const fields: ShowFields = [
    {
      type: 'varchar',
      name: 'display',
      label: 'Collecte'
    },
    {
      type: 'varchar',
      name: 'date',
      label: 'Date'
    }
  ]
  if (usePickupType) {
    fields.push({
      type: 'varchar',
      name: 'pickup_type_label',
      label: 'Type de collecte'
    })
  }
  fields.push(
    {
      type: 'text',
      name: 'description',
      label: 'Description'
    }
  )

  const productShowFields: ShowFields = []
  if (usePBrand) {
    productShowFields.push({
      type: 'varchar',
      name: 'pbrand',
      label: 'Marque'
    })
  }
  productShowFields.push({
    type: 'varchar',
    name: 'name',
    label: 'Ref',
    goto: lineProductGoto,
    pushToStack: [
      {
        fromDataKey: 'product_rowid',
        pushOnStackKey: 'product',
        stackLabel: 'Produit',
        silent: false,
        invisible: true
      }
    ]
  })
  productShowFields.push({
    type: 'varchar',
    name: 'label',
    label: 'Label'
  })

  const lineCols: ShowFields = []
  lineCols.push({
    type: 'concatenate',
    name: 'name',
    label: 'Produit',
    separatorHTML: '<br>',
    ignoreEmpty: true,
    fields: productShowFields
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

  if (useUnitWeight || useUnitLength || useUnitSurface || useUnitVolume) {
    lineCols.push({
      type: 'text',
      name: 'line_unitary_html',
      label: 'Valeurs unitaires'
    })
  }

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

  fields.push({
    type: 'lines',
    name: 'lines',
    label: 'Produits',
    lines: lineCols
  })

  if (setProcessingStatusGoto !== null) {
    fields.push({
      type: 'edit',
      name: 'pickup',
      disabledFunc: (data: any) => { return !(data?.lines?.length > 0) },
      label: 'Fin de la saisie',
      pushToStack: [
        {
          // L'id de la collecte à sauvegarder
          fromDataKey: 'rowid',
          pushOnStackKey: 'pickup_id',
          stackLabel: 'ID Collecte',
          silent: false,
          invisible: true
        },
        {
          // De quoi afficher la collecte concernée sur l'écran de sauvegarde
          fromDataKey: 'display',
          pushOnStackKey: 'pickup_display',
          stackLabel: 'Collecte',
          silent: true,
          invisible: false
        },
        {
          // Le nouveau statut (valeur pour le backend)
          value: setProcessingStatusGoto.processingStatus,
          pushOnStackKey: 'pickup_status',
          stackLabel: 'Statut',
          silent: false,
          invisible: true
        },
        {
          // Le nouveau statut (libellé pour le frontend)
          value: 'En cours de traitement',
          pushOnStackKey: 'pickup_status_label',
          stackLabel: 'Statut',
          silent: true,
          invisible: false
        }
      ],
      goto: setProcessingStatusGoto.goto
    })
  }

  return {
    type: 'show',
    label: 'Collecte',
    key: 'pickup',
    primaryKey: 'pickup', // FIXME: should be less ambigous
    addGoto,
    addLabel: 'Ajouter un produit',
    fields
  }
}

export function savePickupStatus (goto: string, saveUntil: string): StateDefinition {
  return {
    type: 'save',
    label: 'Fin de la saisie',
    key: 'pickup',
    primaryKey: 'rowid', // FIXME: to check.
    labelKey: 'Collecte',
    saveUntil,
    goto
  }
}
