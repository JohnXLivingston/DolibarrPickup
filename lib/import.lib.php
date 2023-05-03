<?php
/* Copyright (C) 2023		John Livingston		<license@john-livingston.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    pickup/lib/correctdata_missing_batch_number.lib.php
 * \ingroup pickup
 * \brief   Library files with common functions
 */

// Following include will fail if we are not in Dolibarr context
dol_include_once('/core/lib/admin.lib.php');
dol_include_once('/categories/class/categorie.class.php');
dol_include_once('/pickup/class/mobilecat.class.php');
dol_include_once('/product/class/product.class.php');
dol_include_once('/pickup/lib/import/cat.tree.class.php');
dol_include_once('/product/stock/class/entrepot.class.php');
dol_include_once('/societe/class/societe.class.php');
dol_include_once('/pickup/class/pickup.class.php');
dol_include_once('/pickup/class/pickupline.class.php');

$langs->loadLangs(array('pickup@pickup', 'products', 'categories', 'stocks'));

function pickup_import(&$json, $simulate, $what) {
  $result = [
    'status' => 'ko',
    'actions' => [],
    'error' => null
  ];
  try {
    $data = json_decode($json);
    if ($data->version !== '1') {
      throw new Error('Data version incompatible');
    }

    // First entrepots (needed for pickup_conf->PICKUP_DEFAULT_STOCK)
    if (!empty($what['entrepot'])) {
      _pickup_import_entrepots($result, $data, $simulate);
    }
    // Then parameters...
    if (!empty($what['pickup_conf'])) {
      _pickup_import_conf($result, $data, $simulate);
    }
    // Then data... (order is important, some data depends on others)
    if (!empty($what['cat'])) {
      _pickup_import_cats($result, $data, $simulate);
    }
    if (!empty($what['societe'])) {
      _pickup_import_socs($result, $data, $simulate);
    }
    if (!empty($what['product'])) {
      _pickup_import_products($result, $data, $simulate);
    }
    if (!empty($what['pickup'])) {
      _pickup_import_pickups($result, $data, $simulate);
    }

    $result['status'] = 'ok';
  } catch (Throwable $e) {
    $result['error'] = $e->getMessage();
  }
  return $result;
}

function _pickup_import_entrepots(&$result, &$data, $simulate) {
  global $db, $langs, $user;
  $lines = empty($data->entrepots) ? [] : $data->entrepots;

  // Note: for now, we import entrepots «flatly» (no fk_parent)
  foreach ($lines as $line) {
    $label = $line->label;
    if (empty($label)) { continue; }

    $entrepot = new Entrepot($db);
    $fields_list = [];
    foreach (get_object_vars($line) as $field => $val) {
      if (substr($field, 0, 3) === 'fk_') {
        throw new Error('Seems the file to import contains foreign keys for entrepots, this is not supported');
      }
      if (property_exists($entrepot, $field)) {
        $fields_list[] = $field;
      }
    }
    if (!in_array('label', $fields_list)) {
      // In $entrepot->fields, 'label' is 'ref'...
      $fields_list[] = 'label';
    }

    if ($entrepot->fetch(null, $label) <= 0) {
      // New entrepot!
      $result['actions'][] = [
        'object_type' =>  $langs->transnoentities('Warehouse'),
        'object' => $label,
        'action' => 'CREATE',
        'message' => implode(', ', $fields_list)
      ];
      if (!$simulate) {
        $entrepot = new Entrepot($db);
        foreach ($fields_list as $field) {
          $entrepot->$field = $line->$field;
        }
        if ($entrepot->create($user) <= 0) {
          throw new Error('Failed to create entrepot.');
        }
      }
      continue;
    }

    // Update...
    $modified_fields = [];
    foreach ($fields_list as $field) {
      if ($field === 'label') { continue; }
      if ($entrepot->$field === $line->$field) { continue; }
      $modified_fields[] = $field;
    }
    if (count($modified_fields) === 0) {
      $result['actions'][] = [
        'object_type' =>  $langs->transnoentities('Warehouse'),
        'object' => $label,
        'action' => '-',
        'message' => ''
      ];
      continue;
    }
    $result['actions'][] = [
      'object_type' =>  $langs->transnoentities('Warehouse'),
      'object' => $label,
      'action' => 'UPDATE',
      'message' => implode(', ', $modified_fields)
    ];
    if (!$simulate) {
      foreach ($modified_fields as $field) {
        $entrepot->$field = $line->$field;
      }
      $entrepot->update($entrepot->id, $user);
    }
  }
}

function _pickup_import_cats(&$result, &$data, $simulate) {
  $lines = empty($data->categories) ? [] : $data->categories;

  // Building a tree, so we can create categories from top to bottom.
  $tree = new ImportCatTreeNode(null, '');
  foreach ($lines as $line) {
    $path = $line->path;
    if (count($path) == 0) { continue; }
    $node = $tree;
    foreach ($path as $label) {
      $node = $node->ensureChild($label);
    }
    // The last node has to get the data!
    $node->setData($line);
  }

  // Now we must walk through the tree
  $tree->doActions($result, $simulate);
}

function _pickup_import_conf(&$result, &$data, $simulate) {
  _pickup_import_conf_settings($result, $data, $simulate);
  _pickup_import_conf_pickup_types($result, $data, $simulate);
}

function _pickup_import_conf_settings(&$result, &$data, $simulate) {
  global $conf, $langs, $db;
  $lines = empty($data->pickup_conf) || empty($data->pickup_conf->settings) ? [] : $data->pickup_conf->settings;
  if (empty($lines)) { return; }

  dol_include_once('/custom/pickup/lib/settings.php');
  $settings = getPickupSettings();

  foreach ($settings as $name => $setting) {
    if (!$setting['enabled']) { continue; }
    if (!property_exists($lines, $name)) { continue; }

    $old_value = property_exists($conf->global, $name) ? $conf->global->$name : null;
    $new_value = $lines->$name;

    if ($name === 'PICKUP_DEFAULT_STOCK' && !empty($new_value)) {
      // Special case...
      $entrepot = new Entrepot($db);
      if ($entrepot->fetch(null, $new_value) > 0) {
        $new_value = $entrepot->id;
      } else {
        $result['actions'][] = [
          'object_type' =>  $langs->transnoentities('PickupSetup'),
          'object' => $name,
          'action' => 'FAILED',
          'message' => 'Can\'t find entrepot '.$new_value
        ];
        continue;
      }
    }

    if ($old_value === null || $old_value !== $new_value) {
      if ($new_value === null) {
        $result['actions'][] = [
          'object_type' =>  $langs->transnoentities('PickupSetup'),
          'object' => $name,
          'action' => 'DELETE',
          'message' => ''
        ];
        if (!$simulate) {
          dolibarr_del_const($db, 'PICKUP_DEFAULT_BATCH_PICKUP_REF', $conf->entity);
        }
      } else {
        if ($setting['type'] === 'boolean') {
          $setting_type = 'yesno';
        } else {
          $setting_type = 'chaine';
        }
        $result['actions'][] = [
          'object_type' =>  $langs->transnoentities('PickupSetup'),
          'object' => $name,
          'action' => 'UPDATE',
          'message' => ($old_value ?? '') . ' => ' . $new_value . ' (' . $setting_type . ')'
        ];
        if (!$simulate) {
          dolibarr_set_const($db, $name, $new_value, $setting_type, 0, '', $conf->entity);
        }
      }
    } else {
      $result['actions'][] = [
        'object_type' =>  $langs->transnoentities('PickupSetup'),
        'object' => $name,
        'action' => '-',
        'message' => ''
      ];
    }
  }
}

function _pickup_import_conf_pickup_types(&$result, &$data, $simulate) {
  global $conf, $langs, $db;
  $lines = empty($data->pickup_conf) || empty($data->pickup_conf->pickup_types) ? [] : $data->pickup_conf->pickup_types;
  if (count($lines) === 0) { return; }

  foreach ($lines as $line) {
    $label = $line->label;
    $sql = "SELECT label, active ";
    $sql .= " FROM ".MAIN_DB_PREFIX.'c_pickup_type ';
    $sql .= " WHERE entity = '".$db->escape($conf->entity)."'";
    $sql .= " AND label='".$db->escape($label)."'";
    $resql = $db->query($sql);
    if (!$resql) {
      throw new Error('Failed to get pickup types');
    }
    $obj = $db->fetch_object($resql);
    if ($obj) {
      if ($obj->active === $line->active) {
        $result['actions'][] = [
          'object_type' =>  $langs->transnoentities('PickupType'),
          'object' => $label,
          'action' => '-',
          'message' => ''
        ];
      } else {
        $result['actions'][] = [
          'object_type' =>  $langs->transnoentities('PickupType'),
          'object' => $label,
          'action' => 'UPDATE',
          'message' => 'active'
        ];
        if (!$simulate) {
          $sql = "UPDATE ".MAIN_DB_PREFIX.'c_pickup_type ';
          $sql .= ' SET active = '.$db->escape((int) $line->active);
          $sql .= " WHERE entity = '".$db->escape($conf->entity)."'";
          $sql .= " AND label='".$db->escape($label)."'";
          $db->query($sql);
        }
      }
    } else {
      $result['actions'][] = [
        'object_type' =>  $langs->transnoentities('PickupType'),
        'object' => $label,
        'action' => 'CREATE',
        'message' => ''
      ];
      if (!$simulate) {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.'c_pickup_type ';
        $sql .= ' (entity, label, active) VALUES ';
        $sql .= " ('".$db->escape($conf->entity)."', '".$db->escape($line->label)."', '".$db->escape($line->active)."')";
        $db->query($sql);
      }
    }
    $db->free($resql);
  }
}

function _pickup_import_products(&$result, &$data, $simulate) {
  global $db, $langs, $user;
  $lines = empty($data->products) ? [] : $data->products;

  $categorie_class = new Categorie($db);

  foreach ($lines as $line) {
    $ref = $line->ref;
    if (empty($ref)) { continue; }

    $product = new Product($db);
    $fields_list = [];
    foreach (get_object_vars($line) as $field => $val) {
      if (substr($field, 0, 3) === 'fk_') {
        throw new Error('Seems the file to import contains foreign keys for products, this is not supported');
      }
      if ($field === 'categories') { continue; } // will be done later.
      if (property_exists($product, $field)) {
        $fields_list[] = $field;
      }
    }

    $product_id = null;
    if ($product->fetch(null, $ref) <= 0) {
      // New product!
      $result['actions'][] = [
        'object_type' =>  $langs->transnoentities('Product'),
        'object' => $ref,
        'action' => 'CREATE',
        'message' => implode(', ', $fields_list)
      ];
      if (!$simulate) {
        $product = new Product($db);
        foreach ($fields_list as $field) {
          $product->$field = $line->$field;
        }
        if ($product->create($user) <= 0) {
          throw new Error('Failed to create product. '.implode('.', $product->errors ?? [ $product->error ]));
        }
        $product_id = $product->id;
      }
    } else {
      // Update...
      $product_id = $product->id;
      $modified_fields = [];
      foreach ($fields_list as $field) {
        if ($field === 'ref') { continue; }
        if ($product->$field === $line->$field) { continue; }
        $modified_fields[] = $field;
      }
      if (count($modified_fields) === 0) {
        $result['actions'][] = [
          'object_type' =>  $langs->transnoentities('Product'),
          'object' => $ref,
          'action' => '-',
          'message' => ''
        ];
      } else {
        $result['actions'][] = [
          'object_type' =>  $langs->transnoentities('Product'),
          'object' => $ref,
          'action' => 'UPDATE',
          'message' => implode(', ', $modified_fields)
        ];
        if (!$simulate) {
          foreach ($modified_fields as $field) {
            $product->$field = $line->$field;
          }
          $product->update($product->id, $user);
        }
      }
    }

    // And now, product tags!
    if (!property_exists($line, 'categories') || count($line->categories) === 0) { continue; }
    $current_tags = empty($product_id) ? [] : $categorie_class->getListForItem($product_id, 'product');
    $current_tags_ids = [];
    if (is_array($current_tags)) {
      foreach ($current_tags as $current_tag) {
        $current_tags_ids[$current_tag['id']] = true;
      }
    }
    foreach ($line->categories as $cat) {
      $categorie = _fetch_categorie_from_path($cat->path);
      if (!$categorie) {
        $result['actions'][] = [
          'object_type' =>  $langs->transnoentities('Product') .'/'. $langs->transnoentities('ProductsCategory'),
          'object' => $ref,
          'action' => 'FAILED',
          'message' => 'Missing category '.implode('>', $cat->path),
        ];
      } else {
        if (array_key_exists($categorie->id, $current_tags_ids)) {
          $result['actions'][] = [
            'object_type' =>  $langs->transnoentities('Product') .'/'. $langs->transnoentities('ProductsCategory'),
            'object' => $ref,
            'action' => '-',
            'message' => implode('>', $cat->path),
          ];
        } else {
          $result['actions'][] = [
            'object_type' =>  $langs->transnoentities('Product') .'/'. $langs->transnoentities('ProductsCategory'),
            'object' => $ref,
            'action' => 'ADD',
            'message' => implode('>', $cat->path)
          ];
          if (!$simulate) {
            $categorie->add_type($product, 'product');
          }
        }
      }
    }

  }
}

function _fetch_categorie_from_path($path) {
  global $db;

  $categorie_id = 0;

  while ($cur_label = array_shift($path)) {
    $sql = "SELECT c.rowid";
    $sql.= " FROM ".MAIN_DB_PREFIX."categorie as c ";
    $sql.= " WHERE c.entity IN (".getEntity('category').")";
    $sql.= " AND c.type = 0"; // product
    $sql.= " AND c.fk_parent = ".((int) $categorie_id);
    $sql.= " AND c.label = '".$db->escape($cur_label)."'";

    $resql = $db->query($sql);
    if (!$resql || $db->num_rows($resql) <= 0) {
      return null;
    }
    $obj = $db->fetch_object($resql);
    $categorie_id = $obj->rowid;
  }

  if (empty($categorie_id)) { return null; }
  $categorie = new Categorie($db);
  if ($categorie->fetch($categorie_id) <= 0) {
    return null;
  }

  return $categorie;
}

/**
 * Import generic objects.
 * Note: $classname must have a fetch method that accept a 2nd arg corresponding to the unique key to use.
 * Moreover, create and update methods must accept respectively 1 and 2 parameters.
 */
function _pickup_import_generic(&$result, &$datalist, $simulate, $classname, $keyfield, $object_label, $create_only, $fields, $fk_fetch_methods = [], $lines_import = null) {
  global $db, $langs, $user;
  $lines = empty($datalist) ? [] : $datalist;

  $ensure_value = function ($field, $val) use ($fk_fetch_methods) {
    if (!array_key_exists($field, $fk_fetch_methods)) {
      return $val;
    }
    if (empty($val)) { return null; }
    $new_val = $fk_fetch_methods[$field]($val);
    if (empty($new_val)) {
      throw new Error('Cant find '.$field.' for '.$val);
    }
    return $new_val;
  };

  foreach ($lines as $line) {
    $ref = $line->$keyfield;
    if (!$create_only && empty($ref)) { continue; }

    $object = new $classname($db);
    $effective_fields_list = [];
    foreach (get_object_vars($line) as $field => $val) {
      if (!in_array($field, $fields)) { continue; }
      if (substr($field, 0, 3) === 'fk_') {
        if (empty($fk_fetch_methods) || empty($fk_fetch_methods[$field])) {
          throw new Error('Seems the file to import contains foreign keys for entrepots, this is not supported');
        }
      }
      if (property_exists($object, $field)) {
        $effective_fields_list[] = $field;
      }
    }
    if (!in_array($keyfield, $effective_fields_list)) {
      $effective_fields_list[] = $keyfield;
    }

    if ($create_only || $object->fetch(null, $ref) <= 0) {
      // New object!
      $result['actions'][] = [
        'object_type' =>  $object_label,
        'object' => $ref,
        'action' => 'CREATE',
        'message' => implode(', ', $effective_fields_list)
      ];
      if (!$simulate) {
        $object = new $classname($db);
        foreach ($effective_fields_list as $field) {
          $object->$field = $ensure_value($field, $line->$field);
        }
        if ($object->create($user) <= 0) {
          throw new Error('Failed to create '.$classname.'.');
        }
      // } else {
      //   // just test $ensure_value to be sure everything is ok
      //   foreach ($effective_fields_list as $field) {
      //     $ensure_value($field, $line->$field);
      //   }
      }
      if (!empty($lines_import) && property_exists($line, 'lines')) {
        _pickup_import_generic_lines(
          $result,
          $line->lines,
          $simulate,
          $object,
          $lines_import->classname,
          $lines_import->parent_fk_field,
          $lines_import->object_label,
          $create_only,
          $lines_import->fields,
          $lines_import->fk_fetch_methods
        );
      }
      continue;
    }

    if (!empty($lines_import)) {
      throw new Error('Line import is only supported for create_only mode');
    }

    // Update...
    $modified_fields = [];
    foreach ($effective_fields_list as $field) {
      if ($field === $keyfield) { continue; } // never change the primary key
      if ($ensure_value($field, $object->$field) == $ensure_value($field, $line->$field)) { continue; }
      $modified_fields[] = $field;
    }
    if (count($modified_fields) === 0) {
      $result['actions'][] = [
        'object_type' =>  $object_label,
        'object' => $ref,
        'action' => '-',
        'message' => ''
      ];
      continue;
    }
    $result['actions'][] = [
      'object_type' =>  $object_label,
      'object' => $ref,
      'action' => 'UPDATE',
      'message' => implode(', ', $modified_fields)
    ];
    if (!$simulate) {
      foreach ($modified_fields as $field) {
        $object->$field = $ensure_value($field, $line->$field);
      }
      $object->update($object->id, $user);
    // } else {
      // // just test $ensure_value to be sure everything is ok
      // foreach ($modified_fields as $field) {
      //   $ensure_value($field, $line->$field);
      // }
    }
  }
}

function _pickup_import_generic_lines(&$result, &$datalist, $simulate, &$parent_object, $classname, $parent_fk_field, $object_label, $create_only, $fields, $fk_fetch_methods = []) {
  if (!$create_only) {
    throw new Error('Line import is only supported for create_only mode');
  }
  global $db, $langs, $user;
  $lines = empty($datalist) ? [] : $datalist;

  $ensure_value = function ($field, $val) use ($fk_fetch_methods) {
    if (!array_key_exists($field, $fk_fetch_methods)) {
      return $val;
    }
    if (empty($val)) { return null; }
    $new_val = $fk_fetch_methods[$field]($val);
    if (empty($new_val)) {
      throw new Error('Cant find '.$field.' for '.$val);
    }
    return $new_val;
  };

  foreach ($lines as $line) {
    $object = new $classname($db);
    $effective_fields_list = [];
    foreach (get_object_vars($line) as $field => $val) {
      if (!in_array($field, $fields)) { continue; }
      if (substr($field, 0, 3) === 'fk_') {
        if (empty($fk_fetch_methods) || empty($fk_fetch_methods[$field])) {
          throw new Error('Seems the file to import contains foreign keys for entrepots, this is not supported');
        }
      }
      if (property_exists($object, $field)) {
        $effective_fields_list[] = $field;
      }
    }

    // New object!
    $result['actions'][] = [
      'object_type' =>  $object_label,
      'object' => $ref,
      'action' => 'CREATE',
      'message' => implode(', ', $effective_fields_list)
    ];
    if (!$simulate) {
      $object = new $classname($db);
      $object->$parent_fk_field = $parent_object->id;
      foreach ($effective_fields_list as $field) {
        $object->$field = $ensure_value($field, $line->$field);
      }
      if ($object->create($user) <= 0) {
        throw new Error('Failed to create '.$classname.'.');
      }
    }
    // } else {
    //   // just test $ensure_value to be sure everything is ok
    //   foreach ($effective_fields_list as $field) {
    //     $ensure_value($field, $line->$field);
    //   }
  }
}

function _pickup_import_socs(&$result, &$data, $simulate) {
  global $langs;
  return _pickup_import_generic(
    $result, $data->societes, $simulate,
    'Societe', 'name',
    $langs->transnoentities('Client'),
    false,
    [
      'name',
      'name_alias',
      'country_id',
      'address',
      'zip',
      'town',
      'email',
      'phone',
      'client',
      'code_client',
      'forme_juridique_code',
      'typent_id',
      'fournisseur',
    ]
  );
}

function _pickup_import_pickups(&$result, &$data, $simulate) {
  global $langs, $db;
  $import_pickupline_infos = new stdClass();
  $import_pickupline_infos->classname = 'PickupLine';
  $import_pickupline_infos->parent_fk_field = 'fk_pickup';
  $import_pickupline_infos->object_label = $langs->transnoentities('Pickup').' (line)';
  $import_pickupline_infos->fields = [
    'fk_product',
		'description',
		'qty',
		'weight',
		'weight_units',
		'length',
		'length_units',
		'surface',
		'surface_units',
		'volume',
		'volume_units',
		'deee',
		'deee_type',
  ];
  $import_pickupline_infos->fk_fetch_methods = [
    'fk_product' => function ($ref) {
      global $db;
      $product = new Product($db);
      if ($product->fetch(null, $ref) <= 0) { return null; }
      return $product->id;
    }
  ];

  return _pickup_import_generic(
    $result, $data->pickups, $simulate,
    'Pickup', 'ref',
    $langs->transnoentities('Pickup'),
    true,
    [
      'ref',
      'label',
      'fk_soc',
      'date_pickup',
      'fk_pickup_type',
      'description',
      'date_creation',
      'status',
      'fk_entrepot',
      'note_public',
      'note_private'
    ],
    [
      'fk_soc' => function ($ref) {
        global $db;
        $soc = new Societe($db);
        if ($soc->fetch(null, $ref) <= 0) { return null; }
        return $soc->id;
      },
      'fk_entrepot' => function ($ref) {
        global $db;
        $entrepot = new Entrepot($db);
        if ($entrepot->fetch(null, $ref) <= 0) { return null; }
        return $entrepot->id;
      },
      'fk_pickup_type' => function ($label) {
        global $db, $conf;
        $sql = "SELECT rowid ";
        $sql .= " FROM ".MAIN_DB_PREFIX.'c_pickup_type ';
        $sql .= " WHERE entity = '".$db->escape($conf->entity)."'";
        $sql .= " AND label='".$db->escape($label)."'";
        $resql = $db->query($sql);
        if (!$resql) {
          throw new Error('Failed to get pickup types');
        }
        $obj = $db->fetch_object($resql);
        if (!$obj) { return null; }
        return $obj->rowid;
      }
    ],
    $import_pickupline_infos
  );
}
