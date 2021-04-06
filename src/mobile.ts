import { initLang } from './lib/translate'
import { initHistory } from './lib/history'
import { initNunjucks } from './lib/nunjucks'
import { Machine } from './lib/machine'

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
  const machine = new Machine(
    'myMachine',
    1, // this is the version number. Change it if there is no retro compatibility for existing stacks
    container.attr('data-user-id') ?? '',
    {
      init: {
        type: 'choice',
        label: 'Accueil',
        choices: [
          {
            label: 'Nouvelle saisie',
            value: 'new',
            goto: 'pickup'
          }
        ]
      },
      pickup: {
        type: 'pick',
        label: 'Mes collectes en attente de validation',
        key: 'pickup',
        goto: 'what',
        creationGoto: 'entrepot',
        primaryKey: 'rowid',
        fields: [
          { name: 'display', label: 'Collecte' }
        ]
      },
      entrepot: {
        type: 'pick',
        label: 'Entrepot',
        key: 'entrepot',
        goto: 'societe',
        primaryKey: 'rowid',
        fields: [
          { name: 'ref', label: 'Entrepôt' }
        ]
      },
      societe: {
        type: 'pick',
        label: 'Sélection du donneur',
        key: 'soc',
        goto: 'create_pickup',
        creationGoto: 'create_societe',
        primaryKey: 'rowid',
        fields: [
          { name: 'nom', label: 'Nom du donneur' }
        ]
      },
      create_societe: {
        type: 'form',
        label: 'Fiche du donneur',
        goto: 'save_societe',
        fields: [
          {
            name: 'name_alias',
            type: 'varchar',
            label: 'Nom de la structure',
            mandatory: true,
            maxLength: 128
          },
          {
            name: 'name', // TODO: which field?
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
      },
      save_societe: {
        type: 'save',
        label: 'Sauvegarde du donneur',
        key: 'soc',
        primaryKey: 'rowid',
        labelKey: 'name',
        saveUntil: 'create_societe',
        goto: 'create_pickup'
      },
      create_pickup: {
        type: 'form',
        label: 'Nouvelle collecte',
        goto: 'save_pickup', // FIXME
        fields: [
          {
            name: 'date_pickup',
            type: 'date',
            label: 'Date de la collecte',
            mandatory: true,
            defaultToToday: true
          },
          {
            type: 'text',
            name: 'description',
            label: 'Remarques',
            mandatory: false
          }
        ]
      },
      save_pickup: {
        type: 'save',
        label: 'Création de la collecte',
        saveUntil: 'entrepot', // FIXME: might not be that...
        key: 'pickup',
        primaryKey: 'rowid', // FIXME: to check.
        labelKey: 'Collecte',
        goto: 'what'
      },
      what: {
        type: 'choice',
        label: 'Type de produit',
        choices: [
          {
            label: 'Matériel',
            value: 'materiel',
            goto: 'product'
          },
          {
            label: 'Matériaux',
            value: 'materiaux',
            goto: '???'
          }
        ]
      },
      product: {
        type: 'pick',
        label: 'Recherche d\'un produit connu',
        key: 'product',
        primaryKey: 'rowid',
        goto: 'qty',
        creationGoto: 'categorie',
        fields: [
          { name: 'options_marque', label: 'Marque', applyFilter: 'localeUpperCase' },
          { name: 'ref', label: 'Ref' }
        ]
      },
      categorie: {
        type: 'pick',
        label: 'Catégorie du produit',
        key: 'pcat',
        primaryKey: 'rowid',
        goto: 'create_product',
        creationGoto: undefined,
        itemGotoField: 'form',
        fields: [
          { name: 'label', label: 'Catégorie' }
        ]
      },
      create_product: {
        type: 'form',
        label: 'Produit',
        goto: 'weight',
        fields: [
          {
            type: 'varchar',
            name: 'product_marque',
            label: 'Marque',
            mandatory: true,
            maxLength: 25
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
            type: 'text',
            name: 'desc',
            label: 'Description du produit',
            mandatory: false,
            notes: {
              load: 'pcat',
              key: 'rowid',
              basedOnValueOf: 'pcat',
              field: 'notes'
            }
          }
        ]
      },
      weight: {
        type: 'form',
        label: 'Poids unitaire',
        goto: 'qty',
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
      },
      qty: {
        type: 'form',
        label: 'Quantité',
        goto: 'remarque',
        fields: [
          {
            type: 'integer',
            name: 'qty', // FIXME: is this correct?
            label: 'Quantité',
            mandatory: true,
            default: '1',
            min: 1,
            max: 1000
          }
        ]
      },
      remarque: {
        type: 'form',
        label: 'Remarques sur la ligne de collecte',
        goto: 'save_pickupline',
        fields: [
          {
            type: 'text',
            name: 'description',
            label: 'Remarques',
            mandatory: false
          }
        ]
      },
      save_pickupline: {
        type: 'save',
        label: 'Sauvegarde du produit',
        saveUntil: 'pickup',
        key: 'pickupline',
        primaryKey: 'rowid', // FIXME: to check.
        labelKey: 'Produit',
        goto: 'pickup'
      }
    }
  )
  machine.init(container)

  window.pickupMobileMachine = machine
})

// Bellow are some machine states that were removed.
// sous_categorie: {
//   type: 'choice',
//   label: 'Sous catégorie',
//   // FIXME name?
//   choices: [
//     { label: 'Son', value: 'son', goto: 'sc_son' },
//     { label: 'Backline', value: 'backline', goto: 'sc_backline' },
//     { label: 'Lumière', value: 'lumiere', goto: 'sc_lumiere' },
//     { label: 'Électricité', value: 'electricite', goto: 'sc_electricite' },
//     { label: 'Vidéo', value: 'video', goto: 'sc_video' },
//     { label: 'Informatique ou bureautique', value: 'bureautique', goto: 'sc_bureautique' },
//     { label: 'Rangement ou transport', value: 'transport', goto: 'sc_transport' },
//     { label: 'Structure de scène', value: 'structure', goto: 'sc_structure' },
//     { label: 'Câbles', value: 'cable', goto: 'sc_cable' }
//   ]
// },
// sc_son: {
//   type: 'choice',
//   label: 'Famille',
//   // FIXME name?
//   choices: [
//     { label: 'Amplificateur', value: 'amplificateur', goto: 'son_amplificateur' },
//     { label: 'Enceinte', value: 'enceinte', goto: 'son_enceinte' },
//     { label: 'Micro', value: 'micro', goto: 'son_micro' },
//     { label: 'Casque', value: 'casque', goto: 'son_casque' },
//     { label: 'Console', value: 'console', goto: 'son_console' },
//     { label: 'Traitement audio', value: 'traitement_audio', goto: 'son_traitement_audio' },
//     { label: 'Pied ou support', value: 'support', goto: 'son_support' },
//     { label: 'Autre chose', value: 'autre', goto: 'son_autre' }
//   ]
// },
// sc_backline: {
//   type: 'choice',
//   label: 'Famille',
//   // FIXME name?
//   choices: [
//     { label: 'Amplis', value: 'amplificateur', goto: 'backline_amplificateur' },
//     { label: 'Instrument', value: 'instrument', goto: 'backline_instrument' },
//     { label: 'Pied support', value: 'support', goto: 'backline_support' }
//   ]
// },
// sc_lumiere: {
//   type: 'choice',
//   label: 'Famille',
//   // FIXME name?
//   choices: [
//     { label: 'Gradateur', value: 'gradateur', goto: 'lumiere_gradateur' },
//     { label: 'Projecteur', value: 'projecteur', goto: 'lumiere_projecteur' },
//     { label: 'Guirlande', value: 'guirlande', goto: 'lumiere_guirlande' },
//     { label: 'Pupitre', value: 'pupitre', goto: 'lumiere_pupitre' },
//     { label: 'Pied support', value: 'pied_support', goto: 'lumiere_pied_support' },
//     { label: 'Autre', value: 'autre', goto: 'lumiere_autre' }
//   ]
// },
// sc_electricite: {
//   type: 'choice',
//   label: 'Famille',
//   // FIXME name?
//   choices: [
//     { label: 'Tableau', value: 'tableau', goto: 'electricite_tableau' },
//     { label: 'Câblage', value: 'cablage', goto: 'electricite_cablage' },
//     { label: 'Transformateurs', value: 'transformateurs', goto: 'electricite_transformateurs' },
//     { label: 'Accessoires', value: 'accessoires', goto: 'electricite_accessoires' }
//   ]
// },
// sc_video: {
//   type: 'choice',
//   label: 'Famille',
//   // FIXME name?
//   choices: [
//     { label: 'Écran vidéo', value: 'ecran', goto: 'video_ecran' },
//     { label: 'Toile d\'écran', value: 'toile', goto: 'video_toile' },
//     { label: 'Lecteur vidéo', value: 'lecteur', goto: 'video_lecteur' },
//     { label: 'Vidéo projecteur', value: 'projecteur', goto: 'video_projecteur' },
//     { label: 'Traitement vidéo', value: 'traitement', goto: 'video_traitement' },
//     { label: 'Caméra', value: 'camera', goto: 'video_camera' },
//     { label: 'Pied support', value: 'pied', goto: 'video_pied' }
//   ]
// },
// sc_bureautique: {
//   type: 'choice',
//   label: 'Famille',
//   // FIXME name?
//   choices: [
//     { label: 'Tour d\'ordinateur', value: 'tour', goto: 'bureautique_tour' },
//     { label: 'Écran', value: 'ecran', goto: 'bureautique_ecran' },
//     { label: 'Accessoire', value: 'accessoire', goto: 'bureautique_accessoire' },
//     { label: 'Imprimante', value: 'imprimante', goto: 'bureautique_imprimante' },
//     { label: 'Photocopieur', value: 'photocopieur', goto: 'bureautique_photocopieur' },
//     { label: 'Scanner', value: 'scanner', goto: 'bureautique_scanner' }
//   ]
// },
// sc_transport: {
//   type: 'choice',
//   label: 'Famille',
//   // FIXME name?
//   choices: [
//     { label: 'Caisse de transport', value: 'caisse', goto: 'transport_caisse' },
//     { label: 'Rack', value: 'rack', goto: 'transport_rack' },
//     { label: 'Malette', value: 'malette', goto: 'transport_malette' }
//   ]
// },
// sc_structure: {
//   type: 'choice',
//   label: 'Famille',
//   // FIXME name?
//   choices: [
//     { label: 'Praticable et estrade', value: 'estrade', goto: 'structure_estrade' },
//     { label: 'Structure alu', value: 'alu', goto: 'structure_alu' },
//     { label: 'Pied support', value: 'support', goto: 'structure_support' }
//   ]
// },
// sc_cable: {
//   type: 'choice',
//   label: 'Famille',
//   // FIXME name?
//   choices: [
//     { label: 'Son', value: 'son', goto: 'cable_son' },
//     { label: 'Lumière', value: 'lumiere', goto: 'cable_lumiere' },
//     { label: 'Vidéo', value: 'video', goto: 'cable_video' },
//     { label: 'Électricité', value: 'electricite', goto: 'cable_electricite' },
//     { label: 'Autre', value: 'autre', goto: 'cable_autre' }
//   ]
// },
// /////////////////////// 2021-03-03: Remove temporarily to simplify the process
// son_amplificateur: {
//   type: 'form',
//   label: 'Amplificateur',
//   goto: 'son_amplificateur_puissance',
//   fields: [
//     {
//       type: 'integer',
//       name: 'input_xlr_nb',
//       label: 'Nombre d\'entrées XLR',
//       mandatory: true,
//       min: 0,
//       max: 100,
//       default: '0'
//     },
//     {
//       type: 'integer',
//       name: 'input_jack_nb',
//       label: 'Nombre d\'entrées jack',
//       mandatory: true,
//       min: 0,
//       max: 100,
//       default: '0'
//     },
//     {
//       type: 'integer',
//       name: 'input_rca_nb',
//       label: 'Nombre d\'entrées RCA',
//       mandatory: true,
//       min: 0,
//       max: 100,
//       default: '0'
//     },
//     {
//       type: 'integer',
//       name: 'input_other_nb', // FIXME
//       label: 'Nombre d\'entrées autres',
//       mandatory: true,
//       min: 0,
//       max: 100,
//       default: '0'
//     },
//     {
//       type: 'varchar',
//       name: 'input_other_type',
//       label: 'Type d\'entrées autres',
//       mandatory: false
//     },
//     {
//       type: 'integer',
//       name: 'output_dmx_nb',
//       label: 'Nombre de sorties DMX',
//       mandatory: true,
//       min: 0,
//       max: 100,
//       default: '0'
//     },
//     {
//       type: 'integer',
//       name: 'output_jack_nb',
//       label: 'Nombre de sorties jack',
//       mandatory: true,
//       min: 0,
//       max: 100,
//       default: '0'
//     },
//     {
//       type: 'integer',
//       name: 'output_bornier_nb',
//       label: 'Nombre de sorties bornier',
//       mandatory: true,
//       min: 0,
//       max: 100,
//       default: '0'
//     },
//     {
//       type: 'integer',
//       name: 'output_other_nb', // FIXME
//       label: 'Nombre de sorties autres',
//       mandatory: true,
//       min: 0,
//       max: 100,
//       default: '0'
//     },
//     {
//       type: 'varchar',
//       name: 'output_other_type',
//       label: 'Type de sorties autres',
//       mandatory: false
//     }
//   ]
// },
// son_amplificateur_puissance: {
//   type: 'form',
//   label: 'Amplificateur',
//   goto: 'son_amplificateur_compute',
//   fields: [
//     // FIXME: what if multiple power configuration?
//     {
//       type: 'integer',
//       name: 'power1',
//       label: 'Puissance en Watt (1)',
//       mandatory: false,
//       min: 0,
//       max: 5000
//     },
//     {
//       type: 'integer',
//       name: 'impedance1',
//       label: 'Impédance en ohms (1)',
//       mandatory: false,
//       min: 0,
//       max: 1000
//     },
//     {
//       type: 'integer',
//       name: 'power2',
//       label: 'Puissance en Watt (2)',
//       mandatory: false,
//       min: 0,
//       max: 5000
//     },
//     {
//       type: 'integer',
//       name: 'impedance2',
//       label: 'Impédance en ohms (2)',
//       mandatory: false,
//       min: 0,
//       max: 1000
//     },
//     {
//       type: 'boolean',
//       name: 'parallel',
//       label: 'L\'ampli peut se mettre en parallel ou mono',
//       mandatory: false,
//       default: '0'
//     },
//     {
//       type: 'boolean',
//       name: 'bridge',
//       label: 'L\'ampli peut se mettre en bridge',
//       mandatory: false,
//       default: '0'
//     },
//     {
//       type: 'integer',
//       name: 'bridge_power',
//       label: 'Puissance en Watt (en bridge)',
//       mandatory: false,
//       min: 0,
//       max: 5000
//     },
//     {
//       type: 'integer',
//       name: 'bridge_impedance',
//       label: 'Impédance en ohms (en bridge)',
//       mandatory: false,
//       min: 0,
//       max: 1000
//     }
//   ]
// },
// son_amplificateur_compute: {
//   type: 'compute',
//   goto: 'weight',
//   label: 'Description',
//   computeUntil: 'product',
//   nunjucks: {
//     name: 'desc',
//     label: 'Description',
//     format: 'TODO : this is only a test. Bridge impedance is {{ bridge_impedance }}.' // TODO
//   }
// },
// son_enceinte: {
//   type: 'form',
//   label: 'Enceinte',
//   goto: 'weight',
//   fields: [
//     {
//       type: 'radio',
//       name: 'type_enceinte',
//       label: 'Enceinte de',
//       mandatory: true,
//       options: [
//         { label: 'Façade', value: 'facade' },
//         { label: 'Retour', value: 'retour' }
//       ]
//     },
//     {
//       type: 'radio', // FIXME: ce champs ne doit etre la que pour Facade
//       name: 'enceinte_facade_type',
//       label: 'Qui est',
//       mandatory: false,
//       options: [
//         { label: 'Tête', value: 'tete' },
//         { label: 'Sub', value: 'sub' }
//       ]
//     }
//   ]
// },
// /////////////////////// End 2021-03-03 removal
