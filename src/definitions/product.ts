import { StateDefinition } from '../lib/state/index'

export function pickProduct (goto: string, creationGoto: string): StateDefinition {
  return {
    type: 'pick',
    label: 'Recherche d\'un produit connu',
    key: 'product',
    primaryKey: 'rowid',
    goto,
    creationGoto,
    fields: [
      { name: 'options_marque', label: 'Marque', applyFilter: 'localeUpperCase' },
      { name: 'ref', label: 'Ref' }
    ]
  }
}

export function createProduct (goto: string): StateDefinition {
  return {
    type: 'form',
    label: 'Remplir la fiche produit',
    goto,
    fields: [
      {
        type: 'varchar',
        name: 'product_marque',
        label: 'Marque',
        mandatory: true,
        maxLength: 25,
        loadSuggestions: {
          dataKey: 'product',
          field: 'options_marque',
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
      {
        type: 'select',
        name: 'product_deee_type',
        label: 'DEEE',
        mandatory: false,
        options: [],
        load: 'dict',
        loadParams: {
          what: 'deee_type'
        },
        map: {
          value: 'value',
          label: 'label'
        }
      },
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
        name: 'marque',
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
