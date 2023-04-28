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
dol_include_once('/categories/class/categorie.class.php');
dol_include_once('/product/stock/class/entrepot.class.php');
dol_include_once('/pickup/class/mobilecat.class.php');
dol_include_once('/product/class/product.class.php');


function print_pickup_export($what) {
  // to avoid memory limit hit, we will print JSON object by object.
  print '{';
  print '"version": "1"';
  if (!empty($what['cat'])) {
    print ',"categories":[';
    print_pickup_export_cats();
    print "]";
  }
  if (!empty($what['pickup_conf'])) {
    print ',"pickup_conf":';
    print_pickup_export_conf();
  }
  print '}';
}

function print_pickup_export_cats() {
  global $db;
  // We will export tags having active mobilecat

  $mobilecat = new PickupMobileCat($db);
  $mobilecats = $mobilecat->fetchAll('', '', 0, 0, ['customsql'=>'active = 1']);
  if ($mobilecats < 0) {
    throw new Error('Failed to fetch categories.');
  }

  $first = true;
  foreach ($mobilecats as $mobilecat) {
    $catid = $mobilecat->fk_category;
    $cat = new Categorie($db);
    if ($cat->fetch($catid) <= 0) {
      continue;
    }
    
    // Note: it seems that in reality, get_all_ways can only return one way.
    $all_ways = $cat->get_all_ways();
    foreach ($all_ways as $way) {
      $json = [
        'label' => $cat->label,
        'color' => $cat->color,
        'description' => $cat->description,
        'active' => $mobilecat->active,
        'batch_constraint' => $mobilecat->batch_constraint,
	      'deee_constraint' => $mobilecat->deee_constraint,
        'path' => []
      ];
      foreach ($way as $wcat) {
        $json['path'][] = $wcat->label;
      }
      if (!$first) {
        print ',';
      } else {
        $first = false;
      }
      print json_encode($json);
    }
  }
}

function print_pickup_export_conf() {
  global $conf, $db;
  dol_include_once('/custom/pickup/lib/settings.php');
  $settings = getPickupSettings();

  $data = [];
  foreach ($settings as $name => $setting) {
    if (!$setting['enabled']) { continue; }
    if ($name === 'PICKUP_DEFAULT_STOCK' && !empty($conf->global->$name)) {
      // Special case...
      $entrepot = new Entrepot($db);
      if ($entrepot->fetch($conf->global->$name) > 0) {
        $data[$name] = $entrepot->ref;
      }
    } else {
      $data[$name] = property_exists($conf->global, $name) ? $conf->global->$name : null;
    }
  }
  print json_encode([
    'settings' => $data
  ]);
}
