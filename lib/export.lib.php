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
  $data = new stdClass();
  $data->version = '1';

  if (!empty($what['cat'])) {
    $data->categories = pickup_export_cats();
  }
  if (!empty($what['pickup_conf'])) {
    $data->pickup_conf = pickup_export_conf();
  }
  if (!empty($what['entrepot'])) {
    $data->entrepots = pickup_export_entrepots();
  }
  if (!empty($what['product'])) {
    $data->products = pickup_export_products();
  }

  header('Content-disposition: attachment; filename=export.json');
  header('Content-type: application/json');
  print json_encode($data);
}

function pickup_export_cats() {
  global $db;
  // We will export tags having active mobilecat

  $mobilecat = new PickupMobileCat($db);
  $mobilecats = $mobilecat->fetchAll('', '', 0, 0, ['customsql'=>'active = 1']);
  if ($mobilecats < 0) {
    throw new Error('Failed to fetch categories.');
  }

  $result = [];

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
      $result[] = $json;
    }
  }

  return $result;
}

function pickup_export_conf() {
  global $conf, $db;
  dol_include_once('/custom/pickup/lib/settings.php');
  $settings = getPickupSettings();

  $settings_data = new stdClass();
  foreach ($settings as $name => $setting) {
    if (!$setting['enabled']) { continue; }
    if ($name === 'PICKUP_DEFAULT_STOCK' && !empty($conf->global->$name)) {
      // Special case...
      $entrepot = new Entrepot($db);
      if ($entrepot->fetch($conf->global->$name) > 0) {
        $settings_data->$name = $entrepot->ref;
      }
    } else {
      $settings_data->$name = property_exists($conf->global, $name) ? $conf->global->$name : null;
    }
  }

  $result = new stdClass();
  $result->settings = $settings_data;

  if (!empty($conf->global->PICKUP_USE_PICKUP_TYPE)) {
    $result->pickup_types = [];
    $sql = "SELECT label, active ";
    $sql .= " FROM ".MAIN_DB_PREFIX.'c_pickup_type ';
    $sql .= " WHERE entity = '".$db->escape($conf->entity)."'";
    $resql = $db->query($sql);
    if (!$resql) {
      throw new Error('Failed to get pickup types');
    }
    while ($obj = $db->fetch_object($resql)) {
      $result->pickup_types[] = [
        'label' => $obj->label,
        'active' => $obj->active
      ];
    }
    $db->free($resql);
  }

  return $result;
}

function pickup_export_entrepots() {
  global $db;
  $entrepot = new Entrepot($db);
  $entrepots = array_merge($entrepot->list_array(1), $entrepot->list_array(0));
  $result = [];
  foreach ($entrepots as $k => $label) {
    $entrepot = new Entrepot($db);
    if ($entrepot->fetch(null, $label) <= 0) { continue; }
    $data = new stdClass();
    foreach (['label', 'description', 'lieu', 'address', 'zip', 'town', 'phone', 'fax', 'statut'] as $field) {
      if (property_exists($entrepot, $field)) {
        $data->$field = $entrepot->$field;
      }
    }
    $result[] = $data;
  }
  return $result;
}

function pickup_export_products() {
  global $db;
  $result = [];

  $categorie_class = new Categorie($db);

  $sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'product as p';
  $sql.= ' WHERE p.entity IN ('.getEntity('product').')';
  $sql.= ' AND fk_product_type = 0'; // product only, not services
  $sql.= $db->order('ref', 'ASC');
  $resql = $db->query($sql);
  if (!$resql) {
    throw new Error('Failed getting product list');
  }

  while ($obj = $db->fetch_object($resql)) {
    $id = $obj->rowid;
    $product = new Product($db);
    if ($product->fetch($id) <= 0) { continue; } // product deleted during the export?
    $data = new stdClass();
    foreach ([
      'ref', 'label', 'barcode', 'note', 'note_public',
      'description',
      // 'price', 'price_ttc', 'price_min', 'price_min_ttc', 'price_base_type',
      'net_measure', 'net_measure_units',
      'status', 'status_buy', 'status_batch',
      'weight', 'weight_units',
      'length', 'length_units',
      'width', 'width_units',
      'height', 'height_units',
      'surface', 'surface_units',
      'volume', 'volume_units',
      'url', 'cost_price'
    ] as $field) {
      if (property_exists($product, $field)) {
        $data->$field = $product->$field;
      }
    }

    $data->categories = [];
    $categories = $categorie_class->getListForItem($product->id, 'product');
    if ($categories) {
      foreach ($categories as $cat) {
        $categorie = new Categorie($db);
        if ($categorie->fetch($cat['id']) <=0) { continue; }
        $all_ways = $categorie->get_all_ways();
        foreach ($all_ways as $way) {
          $cat_line = new stdClass();
          $cat_line->label = $categorie->label;
          $cat_line->path = [];
          foreach ($way as $wcat) {
            $cat_line->path[] = $wcat->label;
          }
          $data->categories[] = $cat_line;
        }
      }
    }

    $result[] = $data;
  }
  $db->free($resql);

  return $result;
}
