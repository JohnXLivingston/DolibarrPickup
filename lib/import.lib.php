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

    // TODO First entrepots (needed for pickup_conf->PICKUP_DEFAULT_STOCK)

    // Then parameters...
    if (!empty($what['pickup_conf'])) {
      _pickup_import_conf($result, $data, $simulate);
    }
    // Then data... (order is important, some data depends on others)
    if (!empty($what['cat'])) {
      _pickup_import_cats($result, $data, $simulate);
    }

    $result['status'] = 'ok';
  } catch (Throwable $e) {
    $result['error'] = $e->getMessage();
  }
  return $result;
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
  global $conf, $langs, $db;

  $lines = empty($data->pickup_conf) || empty($data->pickup_conf->settings) ? [] : $data->pickup_conf->settings;

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
    
    if (empty($old_value) || $old_value !== $new_value) {
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
