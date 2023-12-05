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
 * \file    pickup/lib/scan_labels_action.lib.php
 * \ingroup pickup
 * \brief   Some common functions for the scan labels action added to some forms.
 */


 /**
  * Check if the user can use the scan label functionnality
  */
function scan_labels_rights_ok($object, $currentcontext) {
  global $user, $conf;
  if (
    !$conf->pickup->enabled
    || empty($conf->global->PICKUP_USE_PRINTABLE_LABEL)
  ) {
    return false;
  }

  $table_element = null;
  $context = null;
  foreach ([
    'propalcard' => 'propal',
    'ordercard' => 'commande',
    'invoicecard' => 'facture'
  ] as $tmp_context => $tmp_table_element) {
    if (
      empty($currentcontext)
      || $object->table_element !== $tmp_table_element
      || $currentcontext !== $tmp_context
    ) {
      continue;
    }
    $table_element = $tmp_table_element;
    $context = $tmp_context;
    break;
  }

  if (!$table_element) {
    return false;
  }

  switch ($object->table_element) {
    case 'propal':
      dol_include_once('/comm/propal/class/propal.class.php');
      if ($object->status != Propal::STATUS_DRAFT) {
        return false;
      }
      if (!$user->hasRight('propal', 'creer')) {
        return false;
      }
      break;
    case 'commande':
      dol_include_once('/commande/class/commande.class.php');
      if ($object->status != Commande::STATUS_DRAFT) { // attention, status=string, STATUS_DRAFT=integer
        return false;
      }
      if (!$user->hasRight('commande', 'creer')) {
        return false;
      }
      break;
    case 'facture':
      dol_include_once('/compta/facture/facture.class.php');
      if ($object->status != Facture::STATUS_DRAFT) {
        return false;
      }
      if (!$user->hasRight('facture', 'creer')) {
        return false;
      }
      break;
    // case 'expedition':
    //   dol_include_once('/expedition/expedition.class.php');
    //   if ($object->status != Expedition::STATUS_DRAFT) {
    //     return false;
    //   }
    //   if (!$user->hasRight('expedition', 'creer')) {
    //     return false;
    //   }
    //   break;
    default:
      return false;
  }

  return true;
}

/**
 * Prints the scan labels forms.
 * Must be used in a formAddObjectLine hook.
 */
function print_scan_labels_add_object_line($object) {
  global $user, $langs;
  $langs->loadLangs(array("pickup@pickup"));

  $search = GETPOST('search', 'alphanohtml') ?? '';

  print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="pickupscanlabels">'."\n";
  print '<input type="hidden" name="token" value="'.newToken().'">';
  print '<input type="hidden" name="action" value="pickupscanlabels_exec">';

  $text = $langs->trans('Product').' / '.$langs->trans('LotSerial');
  print_barre_liste($text, 0, $_SERVER["PHP_SELF"], '', '', '', '', null, '', 'product', 0, '', '', null, 0, 0, 1);

  print '<textarea autofocus name="search" style="width:80%; min-height: 360px">'.$search.'</textarea>';

  print '<br>';
  print '<input type="submit" class="button" value="'.$langs->trans("Add").'" ';
  print ' onclick="const b = this; setTimeout(function() {b.disabled = true;}, 0);" ';
  print '>';
  print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';

  print '</form>';
}

/**
 * Execute the scan label action: adds lines to the object.
 */
function print_scan_labels_exec_action(&$object) {
  global $db, $user, $langs, $mysoc;
  $langs->loadLangs(array("pickup@pickup"));

  dol_include_once('/pickup/lib/printable_label.lib.php');
  $search = GETPOST('search', 'alphanohtml') ?? '';
  $values = preg_split('/\r\n|\r|\n/', $search);
  $search_infos = search_printable_label($values, true);

  if (
    property_exists($object, 'thirdparty')
    && empty($object->thirdparty)
    && method_exists($object, 'fetch_thirdparty')
  ) {
    $object->fetch_thirdparty();
  }

  $errors = [];

  $db->begin();

  foreach ($search_infos as $search_infos_line) {
    $product = $search_infos_line['product'];
    // $productlot = $search_infos_line['productlot'];
    $scan_count = $search_infos_line['scan_count'];

    $qty = $scan_count;
    // // Qty: if product has unique batch number => just one. Else scan_count.
    // $qty = $product->hasbatch() && $product->status_batch == 2 ? 1 : $scan_count;
    // line label: product label + batch number, if batch_status=unique!
    $line_label = ''; // default case: dolibarr will handle
    // if ($productlot && $product->hasbatch() && $product->status_batch == 2) {
    //   $line_label = $product->label . ' ' . $productlot->batch;
    // }

    switch ($object->table_element) {
      case 'propal':
        // FIXME: use rental price when the object is a rental.
        $price = $product->getSellPrice($mysoc, $object->thirdparty);

        $object->addline(
          '', // desc
          $price['pu_ht'],
          $qty,
          $price['tva_tx'],
          null, // $txlocaltax1 Local tax 1 rate (deprecated, use instead txtva with code inside)
          null, // $txlocaltax2
          $product->id,
          0, // remise_percent
          $price['price_base_type'], // $price_base_type	HT or TTC
          $price['pu_ttc'],
          0, // $info_bits
          0, // type (product = 0)
          -1, // rang (position of line)
          0, // special_code
          0, // fk_parent_line
          0, // $fk_fournprice = 0
          0, // $pa_ht
          $line_label, // $label
          '', // start date
          '', // end date
          0, // array_options		extrafields array
          $object->fk_unit // fk_unit 			Code of the unit to use. Null to use the default one
          // ... $origin = '', $origin_id = 0, $pu_ht_devise = 0, $fk_remise_except = 0, $noupdateafterinsertline = 0
        );
        break;
      case 'commande':
        // FIXME: use rental price when the object is a rental.
        $price = $product->getSellPrice($mysoc, $object->thirdparty);

        $object->addline(
          '', // desc
          $price['pu_ht'],
          $qty,
          $price['tva_tx'],
          null, // $txlocaltax1 Local tax 1 rate (deprecated, use instead txtva with code inside)
          null, // $txlocaltax2
          $product->id,
          0, // remise_percent
          0, // $info_bits
          0, // fk_remise_except
          $price['price_base_type'], // $price_base_type	HT or TTC
          $price['pu_ttc'],
          '', // start date
          '', // end date
          0, // type (product = 0)
          -1, // rang (position of line)
          0, // special_code
          0, // fk_parent_line
          null, // $fk_fournprice = null
          0, // $pa_ht
          $line_label, // $label
          0, // array_options		extrafields array
          $object->fk_unit // fk_unit 			Code of the unit to use. Null to use the default one
          // ... $origin_id = 0, $pu_ht_devise = 0, $ref_ext = '', $noupdateafterinsertline = 0)
        );
        break;
      case 'facture':
        // FIXME: use rental price when the object is a rental.
        $price = $product->getSellPrice($mysoc, $object->thirdparty);
        // FIXME: 'label' parameter is deprecated...

        $object->addline(
          '', // desc
          $price['pu_ht'],
          $qty,
          $price['tva_tx'],
          0, // $txlocaltax1 Local tax 1 rate (deprecated, use instead txtva with code inside)
          0, // $txlocaltax2
          $product->id,
          0, // remise_percent
          '', // start date
          '', // end date
          0, // $ventil Code of dispatching into accountancy
          0, // $info_bits
          0, // fk_remise_except
          $price['price_base_type'], // $price_base_type	HT or TTC
          $price['pu_ttc'],
          0, // type (product = 0)
          -1, // rang (position of line)
          0, // special_code
          '', // $origin = '',
          0, // $origin_id = 0,
          0, // $fk_parent_line = 0,
          null, // $fk_fournprice = null,
          0, // $pa_ht = 0,
          $line_label, // label
          // .. $array_options = 0, $situation_percent = 100, $fk_prev_id = 0, $fk_unit = null, $pu_ht_devise = 0, $ref_ext = '', $noupdateafterinsertline = 0
        );
        break;
      default:
        // This does not change from 1 line to another, so just return.
        return 'Invalid object type "'.$object->table_element.'".';
    }
  }

  if (count($errors)) {
    $db->rollback();
    // Errors will be displayed by the calling hook manager.
		// setEventMessages($langs->trans("Error"), null, 'errors');
    return $errors;
  }

  $db->commit();
  setEventMessages($langs->trans("PickupScanLabelsLineAdded"), null, 'mesgs');
  return true;
}

function front_end_scan_labels_rights_ok($object, $currentcontext, $action) {
  global $user, $conf;
  if (
    !$conf->pickup->enabled
    || empty($conf->global->PICKUP_USE_PRINTABLE_LABEL)
  ) {
    return false;
  }

  if ($currentcontext !== 'expeditioncard') {
    return false;
  }

  if ($action !== 'create') {
    return false;
  }

  if (!$user->hasRight('expedition', 'creer')) {
    return false;
  }

  return true;
}
