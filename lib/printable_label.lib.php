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
 * \file    pickup/lib/pickups_tab.lib.php
 * \ingroup pickup
 * \brief   Library files with common functions for Pickup
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('/product/class/product.class.php');
dol_include_once('/product/stock/class/productlot.class.php');
dol_include_once('/pickup/class/pbatch.class.php');

function search_printable_label($values) {
  global $db;

  dol_syslog('Calling search_printable_label', LOG_DEBUG);

  if (empty($values)) {
    return [];
  }
  if (!is_array($values)) {
    $values = [ $values ];
  }

  $result = [];
  foreach ($values as $value) {
    $value = trim($value);
    if (empty($value)) { continue; }
    $line = [
      'product' => null,
      'productlot' => null
    ];

    // if it is an url
    if (
      preg_match(
        '/^https?:\/\/.*product\/card\.php\?(?:.*&)?id=(\w+)(?:&|$)/',
        $value,
        $matches
      )
    ) {
      $product_id = $matches[1];
      dol_syslog('search_printable_label: value is a product card url, with id='.$product_id, LOG_DEBUG);
      $product = new Product($db);
      if ($product->fetch($product_id) <= 0) {
        dol_syslog('search_printable_label: product not found, with id='.$product_id, LOG_DEBUG);
        continue;
      }
      $line['product'] = $product;
      $result[] = $line;
      continue;
    }

    // searching a productlot.
    // Note: a productlot.batch is only unique when associated to a product_id.
    //    But we don't have the product id here.
    //    So we return all results.
    //    Products created by the Pickup plugin should not have cross-batch_number.
    dol_syslog('search_printable_label: searching a batch number='.$value, LOG_DEBUG);

    $sql = "SELECT rowid ";
    $sql.= " FROM ".(property_exists($db, 'prefix') ? $db->prefix : MAIN_DB_PREFIX)."product_lot ";
    $sql.= " WHERE batch = '".$db->sanitize($db->escape($value))."'";
    $resql = $db->query($sql);
    if (!$resql) {
      dol_syslog(' query failed', LOG_DEBUG);
      continue;
    }

    for ($i = 0; $i < $db->num_rows($resql); $i++) {
      $obj = $db->fetch_object($resql);
      $productlot = new Productlot($db);
      if ($productlot->fetch($obj->rowid) <= 0) {
        dol_syslog('search_printable_label: failed to fetch productlot id='.$obj->rowid, LOG_DEBUG);
        continue;
      }
      
      $product = new Product($db);
      if ($product->fetch($productlot->fk_product) <= 0) {
        dol_syslog('search_printable_label: failed to fetch product id='.$productlot->fk_product, LOG_DEBUG);
        continue;
      }
      
      $line['productlot'] = $productlot;
      $line['product'] = $product;
      $result[] = $line;
    }

    $db->free($resql);
  }

  return $result;
}
