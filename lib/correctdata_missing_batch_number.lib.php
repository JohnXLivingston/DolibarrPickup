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

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('/product/class/product.class.php');
dol_include_once('/product/stock/class/entrepot.class.php');
dol_include_once('/product/stock/class/productlot.class.php');
dol_include_once('/pickup/class/pbatch.class.php');

function read_generate_missing_batch_number_actions($arrayofselected) {
  global $db, $conf;
  dol_syslog('Calling read_generate_missing_batch_number_actions', LOG_DEBUG);

  $result = [];
  foreach ($arrayofselected as $key => $pbid) {
    if (!preg_match('/^(\d+)$/', $pbid)) {
      dol_syslog('Invalid value: '.$pbid, LOG_WARNING);
      continue;
    }

    $sql = 'SELECT pb.qty, ps.fk_product, ps.fk_entrepot, pb.batch ';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'product_batch as pb';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as ps on pb.fk_product_stock = ps.rowid '; // Detail for each lot on each warehouse
    $sql.= " WHERE pb.rowid = '".$db->escape($pbid)."' ";

    $resql = $db->query($sql);
    if (!$resql) {
      dol_syslog('Error retrieving product_batch: '.$db->lasterror(), LOG_ERR);
      continue;
    }
    $product_batch_data = $db->fetch_object($resql);
    $db->free($resql);
    
    if (empty($product_batch_data)) {
      dol_syslog('Cant find product_batch: '.$pbid, LOG_ERR);
      continue;
    }

    if (!preg_match('/^0+$/', $product_batch_data->batch)) {
      dol_syslog('product_batch was not 000000 for: '.$pbid, LOG_ERR);
      continue;
    }

    $product_id = $product_batch_data->fk_product;
    $entrepot_id = $product_batch_data->fk_entrepot;

    $product = new Product($db);
    if ($product->fetch($product_id) <= 0) {
      dol_syslog('Product not found: '.$product_id, LOG_WARNING);
      continue;
    }
    $entrepot = new Entrepot($db);
    if ($entrepot->fetch($entrepot_id) <= 0) {
      dol_syslog('Entrepot not found: '.$entrepot_id, LOG_WARNING);
      continue;
    }
    
    $qty = GETPOST('generate_missing_batch_number_'.$pbid, 'int');
    if (empty($qty) || $qty == 0) {
      dol_syslog('Qty is null for: '.$pbid, LOG_DEBUG);
      continue;
    }

    if (!(
      ($product->status_batch == 2 && $conf->global->PICKUP_DEFAULT_UNIQUE_BATCH === 'generate')
      || (
        $product->status_batch == 1
        && (
          $conf->global->PICKUP_DEFAULT_BATCH === 'generate'
          || $conf->global->PICKUP_DEFAULT_BATCH === 'generate_per_product'
        )
      )
    )) {
      dol_syslog('Cant generate batch number for status_batch: '.$product->status_batch, LOG_WARNING);
      continue;
    }

    if ($qty > $product_batch_data->qty) {
      dol_syslog('Qty is too high, using the current qty as value', LOG_WARNING);
      $qty = $product_batch_data->qty;
    }

    $result[] = [
      'product' => $product,
      'entrepot' => $entrepot,
      'qty' => $qty,
      'null_batch_number' => $product_batch_data->batch
    ];
  }

  return $result;
}

function do_generate_missing_batch_number_actions($generate_missing_batch_number_actions) {
  global $db, $conf, $user, $langs;
  $db->begin();
  $error = 0;

  dol_syslog('Calling do_generate_missing_batch_number_actions', LOG_DEBUG);
  $now = dol_now();
  $labelmovement = $langs->transnoentities('PickupCorrectDataGenerateMissingBatchMovLabel').' '.dol_print_date($now, '%Y-%m-%d %H:%M');
  $codemove = dol_print_date($now, '%Y%m%d%H%M%S');

  $result = [];
  foreach ($generate_missing_batch_number_actions as $gmbna) {
    $product = $gmbna['product'];
    $entrepot = $gmbna['entrepot'];
    $qty = $gmbna['qty'];
    $status_batch = $product->status_batch;

    $product->load_stock('novirtual'); // Load array product->stock_warehouse
    $old_productlot = new Productlot($db);
    $old_productlot->fetch(0, $product->id, $gmbna['null_batch_number']);
    $dlc = $productlot->eatby;
    $dluo = $productlot->sellby;

    // Define value of products moved
    $pricesrc = 0;
    if (!empty($product->pmp)) {
      $pricesrc = $product->pmp;
    }
    $pricedest = $pricesrc;


    if ($status_batch == 2) {
      if ($conf->global->PICKUP_DEFAULT_UNIQUE_BATCH !== 'generate') {
        dol_sylog('Cant generate for product '.$product->id.' because PICKUP_DEFAULT_UNIQUE_BATCH disabled', LOG_ERR);
        continue;
      }
      for ($i = 0; $i < $qty; $i++) {
        $batch_number = PBatch::getNextPBatchNumber();

        $r_minus = $product->correct_stock_batch(
          $user,
          $entrepot->id,
          1,
          1, // remove
          $labelmovement,
          $pricesrc,
          $dlc,
          $dluo,
          $gmbna['null_batch_number'],
          $codemove
        );
        if ($r_minus < 0) {
          $error++;
          setEventMessages($product->error, $product->errors, 'errors');
        }

        $r_add = $product->correct_stock_batch(
          $user,
          $entrepot->id,
          1,
          0, // add
          $labelmovement,
          $pricedest,
          $dlc,
          $dluo,
          $batch_number,
          $codemove
        );
        if ($r_add < 0) {
          $error++;
          setEventMessages($product->error, $product->errors, 'errors');
        }

        $result[] = [
          'product' => $product,
          'entrepot' => $entrepot,
          'qty' => 1,
          'batch_number' => $batch_number
        ];
      }
    } else if ($status_batch == 1) {
      if ($conf->global->PICKUP_DEFAULT_BATCH === 'generate') {
        $batch_number = PBatch::getNextPBatchNumber();
      } else if ($conf->global->PICKUP_DEFAULT_BATCH === 'generate_per_product') {
        $batch_number = PBatch::getPBatchPerProduct($product->id, $user);
      } else {
        dol_sylog('Dont know what to do with product '.$product->id, LOG_ERR);
        continue;
      }

      $r_minus = $product->correct_stock_batch(
        $user,
        $entrepot->id,
        $qty,
        1, // remove
        $labelmovement,
        $pricesrc,
        $dlc,
        $dluo,
        $gmbna['null_batch_number'],
        $codemove
      );
      if ($r_minus < 0) {
        $error++;
        setEventMessages($product->error, $product->errors, 'errors');
      }

      $r_add = $product->correct_stock_batch(
        $user,
        $entrepot->id,
        $qty,
        0, // add
        $labelmovement,
        $pricedest,
        $dlc,
        $dluo,
        $batch_number,
        $codemove
      );
      if ($r_add < 0) {
        $error++;
        setEventMessages($product->error, $product->errors, 'errors');
      }

      $result[] = [
        'product' => $product,
        'entrepot' => $entrepot,
        'qty' => $qty,
        'batch_number' => $batch_number
      ];
    } else {
      dol_sylog('Product '.$product->id.' has no status_batch', LOG_ERR);
      continue;
    }
  }

  foreach ($result as $k => $r) {
    $product = $r['product'];
    $batch_number = $r['batch_number'];
    $productlot = new Productlot($db);
    if ($productlot->fetch(0, $product->id, $batch_number) > 0) {
      $result[$k]['productlot'] = $productlot;
    }
  }

  if ($error) {
    $db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
    return false;
  }
  $db->commit();
  return [
    'lines' => $result,
    'labelmovement' => $labelmovement,
    'codemove' => $codemove
  ];
}
