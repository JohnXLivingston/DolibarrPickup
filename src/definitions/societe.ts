import { StateDefinition } from '../lib/state/index'

export function pickSociete (goto: string, creationGoto: string): StateDefinition {
  return {
    type: 'pick',
    label: 'Sélection du donneur',
    key: 'soc',
    goto,
    creationGoto,
    creationLabel: 'Nouveau donneur',
    primaryKey: 'rowid',
    fields: [
      { name: 'nom', label: 'Nom du donneur' }
    ]
  }
}

export function createSociete (goto: string): StateDefinition {
  return {
    type: 'form',
    label: 'Remplir la fiche du donneur',
    goto,
    fields: [
      {
        name: 'name',
        type: 'varchar',
        label: 'Nom de la structure',
        mandatory: true,
        maxLength: 128
      },
      {
        name: 'name_alias',
        type: 'varchar',
        label: 'Nom de la personne référente',
        mandatory: false,
        maxLength: 128
      },
      {
        name: 'address',
        type: 'text',
        label: 'Adresse',
        mandatory: false
      },
      {
        name: 'zip',
        type: 'varchar',
        label: 'Code postal',
        mandatory: false
      },
      {
        name: 'town',
        type: 'varchar',
        label: 'Ville',
        mandatory: false
      },
      {
        name: 'email',
        type: 'varchar',
        label: 'Email',
        mandatory: false,
        maxLength: 32 // FIXME: check this limit.
      },
      {
        name: 'phone',
        type: 'varchar',
        label: 'Téléphone',
        mandatory: false
      },
      {
        name: 'typent_id',
        type: 'select',
        label: 'Type du tiers',
        options: [],
        load: 'dict',
        loadParams: {
          what: 'typent_id' // FIXME: country=FR
        },
        map: {
          value: 'id',
          label: 'libelle'
        },
        mandatory: true
      },
      {
        name: 'forme_juridique_code',
        type: 'select',
        label: 'Type de structure',
        options: [],
        load: 'dict',
        loadParams: {
          what: 'forme_juridique_code',
          country: 'fr'
        },
        map: {
          value: 'code',
          label: 'label'
        },
        mandatory: false
      }
    ]
  }
}

export function saveSociete (goto: string, saveUntil: string): StateDefinition {
  return {
    type: 'save',
    label: 'Sauvegarde du donneur',
    key: 'soc',
    primaryKey: 'rowid',
    labelKey: 'name',
    saveUntil,
    goto
  }
}

export function showSociete (okGoto: string): StateDefinition {
  return {
    type: 'show',
    label: 'Fiche du donneur',
    key: 'soc',
    primaryKey: 'soc', // FIXME: should be less ambigous
    okGoto,
    fields: [
      {
        type: 'varchar',
        name: 'name',
        label: 'Nom'
      },
      {
        type: 'varchar',
        name: 'name_alias',
        label: 'Nom de la personne référente'
      },
      {
        type: 'varchar',
        name: 'complete_address',
        label: 'Adresse'
      },
      {
        type: 'varchar',
        name: 'email',
        label: 'Email'
      },
      {
        type: 'varchar',
        name: 'phone',
        label: 'Téléphone'
      },
      {
        type: 'varchar',
        name: 'typent_libelle',
        label: 'Type du tiers'
      },
      {
        type: 'varchar',
        name: 'forme_juridique',
        label: 'Type de structure'
      }
    ]
  }
}
