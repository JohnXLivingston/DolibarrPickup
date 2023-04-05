<?php
/* Copyright (C) 2023		John Livingston		<license@john-livingston.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

dol_include_once('/product/stock/class/productlot.class.php');
dol_include_once('/product/stock/class/entrepot.class.php');
dol_include_once('/pickup/lib/printable_label.lib.php');

$langs->loadLangs(array("pickup@pickup", 'products', 'stocks', 'productbatch', "other"));

$action = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'scan';
$search = GETPOST('search', 'alphanohtml') ?? '';
$do_mass_movement = !empty(GETPOST('do_mass_movement'));
$confirm_mass_movement = !empty(GETPOST('confirm_mass_movement'));
$transfer_form_description = null;
$seen_transfer_form_line_suffix = [];
$known_warehouses = [];
$transfer_actions = null;

if ($action === 'list' && $do_mass_movement) {
  $action = 'transfer';
}

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service', 0, 'product&product');

if (($action === 'transfer' || $action === 'dotransfert') && empty($user->rights->stock->mouvement->creer)) {
  $action = 'list';
}

/*
 * Actions
 */

if ($action === 'transfer' || $action === 'dotransfer') {
  $transfer_form_description = get_transfer_form_description($search);
}

if ($action === 'dotransfer') {
  $transfer_actions = transfer_form_valid($transfer_form_description);
  if (false === $transfer_actions) {
    $action = 'transfer';
    $transfer_actions = null;
  } else {
    if (!empty($confirm_mass_movement)) {
      // doing the transfer...
      // This function makes a redirection on success.
      do_the_transfer($transfer_actions);
    }
    // display the form again.
    $action = 'transfer';
  }
}

/*
 * View
 */

$helpurl='';
$title = $langs->trans('PickupPrintableLabelSearchTitle');
llxHeader("", $title, $helpurl);

function _get_warehouse($fk_entrepot) {
  global $known_warehouses, $db;

  if (empty($fk_entrepot) || $fk_entrepot < 0) {
    return null;
  }

  if (!array_key_exists($fk_entrepot, $known_warehouses)) {
    $warehouse = new Entrepot($db);
    $warehouse->fetch($fk_entrepot);
    $known_warehouses[$fk_entrepot] = $warehouse;
  }
  return $known_warehouses[$fk_entrepot];
}

if ($action === 'scan') {
  print_scan_form($action, $search);
} else if ($action === 'list') {
  print_scan_form($action, $search);
  print_list($search);
} else if ($action === 'transfer') {
  print_scan_form($action, $search);
  print_transfer_form($transfer_form_description, $search, $transfer_actions);
}

function print_scan_form($action, $search) {
  global $user, $langs;

  print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="scan">'."\n";
  // if ($optioncss != '') {
  //   print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
  // }
  print '<input type="hidden" name="token" value="'.newToken().'">';
  print '<input type="hidden" name="action" value="list">';

  $text = $langs->trans('Product').' / '.$langs->trans('LotSerial');
  print_barre_liste($text, 0, $_SERVER["PHP_SELF"], '', '', '', '', null, '', 'product', 0, '', '', null, 0, 0, 1);

  if ($action === 'scan') {
    print '<div class="info">'.$langs->trans('PickupPrintableLabelSearchHelp').'</div>';
  }

  $style = $action !== 'scan' ? 'width:80%; max-height: 50x' : 'width:80%; min-height: 360px';
  print '<textarea name="search" style="'.$style.'">'.$search.'</textarea>';

  print '<br><input type="submit" class="button" value="'.$langs->trans("Search").'">';

  if (($action === 'list' || $action === 'scan') && !empty($user->rights->stock->mouvement->creer)) {
    print '<br><input type="submit" name="do_mass_movement" class="button" value="'.$langs->trans("MassMovement").'">';
  }

  print '</form>';
}

function print_list($search) {
  global $db, $langs;
  $values = preg_split('/\r\n|\r|\n/', $search);
  $result = search_printable_label($values);

  print '<div class="div-table-responsive">';
  print '<table class="tagtable nobottomiftotal liste">';
  print '<tr class="liste_titre">';
  print_liste_field_titre("Ref");
  print_liste_field_titre("Label");
  print_liste_field_titre("Batch");
  print_liste_field_titre("Entrepot");
  print_liste_field_titre("Stock");
  print '</tr>';

  foreach ($result as $line) {
    $product = $line['product'];
    $productlot = $line['productlot'];
    print '<tr>';

    // Ref
    print '<td class="nowrap">';
    print $product->getNomUrl(1, '', 16);
    print '</td>';

    // Label
    print '<td>'.htmlspecialchars($product->label).'</td>';

    // Batch
    print '<td>';
    if ($productlot) {
      print $productlot->getNomUrl(1);
    }
    print '</td>';
    print '<td></td>';
    print '<td></td>';

    print '</tr>';

    // Now we print lines describing the stock
    $product->load_stock('novirtual');
    foreach ($product->stock_warehouse as $fk_entrepot => $stock_info) {
      $qty = 0;
      if (!empty($productlot)) {
        // We requested a specific batch number, only showing lines for this one.
        $detail_batch = $stock_info->detail_batch;
        $detail_current_batch = array_filter(is_array($detail_batch) ? $detail_batch : [], function ($productbatch) use ($productlot) {
          return $productbatch->batch === $productlot->batch;
        });
        if (count($detail_current_batch) === 0) {
          continue;
        }
        foreach ($detail_current_batch as $productbatch) {
          $qty+= $productbatch->qty;
        }
      } else {
        $qty = $stock_info->real;
      }

      $warehouse = _get_warehouse($fk_entrepot);

      print '<tr>';
      print '<td></td>';
      print '<td></td>';
      // Batch numbers, if we did not scan it.
      print '<td>';
      if (empty($productlot) && is_array($stock_info->detail_batch)) {
        $batches = [];
        foreach ($stock_info->detail_batch as $productbatch) {
          if (empty($productbatch->batch)) { continue; }
          $batches[$productbatch->batch] = $productbatch->batch;
        }
        ksort($batches, SORT_STRING);
        foreach ($batches as $batch) {
          $pl = new Productlot($db);
          if ($pl->fetch(0, $product->id, $batch)>0) {
            print $pl->getNomUrl(1);
          } else {
            print htmlspecialchars($batch);
          }
          print '<br>';
        }
      }
      print '</td>';
      print '<td>'.$warehouse->getNomUrl(1).'</td>';
      print '<td>'.price2num($qty, 'MS').'</td>';
      print '</tr>';
    }
  }
  print '</table>';
}

function print_transfer_form(&$transfer_form_description, $search, $actions_to_confirm) {
  global $langs, $db;

  $now = dol_now();
  $codemove = (GETPOSTISSET("codemove") ? GETPOST("codemove", 'alpha') : dol_print_date($now, '%Y%m%d%H%M%S'));
  $labelmovement = GETPOST("label") ? GETPOST('label') : $langs->trans("MassStockTransferShort").' '.dol_print_date($now, '%Y-%m-%d %H:%M');

  dol_include_once('/product/class/html.formproduct.class.php');
  $formproduct = new FormProduct($db);

  print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
  // if ($optioncss != '') {
  //   print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
  // }
  print '<input type="hidden" name="token" value="'.newToken().'">';
  print '<input type="hidden" name="action" value="dotransfer">';
  print '<input type="hidden" name="search" value="'.htmlspecialchars($search).'">';

  if (!empty($actions_to_confirm)) {
    // We must confirm the current actions.
    print '<table class="valid centpercent">';
    print '<tr class="validtitre">';
    print '<th class="validtitre" colspan="5">';
    print $langs->trans('PrintableSearchTransferConfirm');
    print '</th>';
    print '</tr>';

    print '<tr class="valid">';
    print '<th class="valid">'.$langs->trans('WarehouseSource').'</th>';
    print '<th class="valid">'.$langs->trans('WarehouseTarget').'</th>';
    print '<th class="valid">'.$langs->trans('Ref').'</th>';
    print '<th class="valid">'.$langs->trans('Batch').'</th>';
    print '<th class="valid">'.$langs->trans('Qty').'</th>';
    print '</tr>';

    foreach ($actions_to_confirm as $action_to_confirm) {
      print '<tr class="valid">';
      print '<td class="valid">';
      if (!empty($action_to_confirm['from_warehouse_object'])) {
        print $action_to_confirm['from_warehouse_object']->getNomUrl(1);
      }
      print '</td>';
      print '<td class="valid">';
      if (!empty($action_to_confirm['to_warehouse_object'])) {
        print $action_to_confirm['to_warehouse_object']->getNomUrl(1);
      }
      print '</td>';
      print '<td class="valid">';
      if (!empty($action_to_confirm['product'])) {
        print $action_to_confirm['product']->getNomUrl(1);
      }
      print '</td>';
      print '<td class="valid">';
      if (!empty($action_to_confirm['productlot'])) {
        print $action_to_confirm['productlot']->getNomUrl(1);
      }
      print '</td>';
      print '<td class="valid right">';
      print price2num($action_to_confirm['qty']);
      print '</td>';
      print '</tr>';
    }

    print '<tr class="valid">';
    print '<td class="valid center" colspan="5">';
    print '<a class="butActionDelete" ';
    print ' onclick="$(this).closest(\'table\').remove();" ';
    print '>'.$langs->trans('Cancel').'</a>';
    // Note: type="button" (+ hidden field) to prevent this button to be trigger on enter in a field from the form under it.
    print '<input type="button" class="button" value="'.$langs->trans("Validate").'" ';
    print ' onclick="$(this).closest(\'form\').find(\'input[name=confirm_mass_movement]\').val(1); $(this).closest(\'form\').submit();" ';
    print '>';
    print '<input type="hidden" name="confirm_mass_movement" class="button" value="">';
    print '</td>';
    print '</tr>';
    print '</table>';
    print '<br><br>';
  }

  print '<div class="div-table-responsive-no-min">';
  print '<table class="liste centpercent">';
  print '<tr class="liste_titre">';
  print getTitleFieldOfList($langs->trans('WarehouseSource'));
  print getTitleFieldOfList($langs->trans('Product'));
  print getTitleFieldOfList($langs->trans('Batch'));
  print getTitleFieldOfList($langs->trans('Qty'));
  foreach ($transfer_form_description['lines'] as &$form_line) {
    $product = $form_line['product'];
    $productlot = $form_line['productlot'] ?? null;

    print '<tr>';

    print '<td class="nowraponall">';
    print img_picto($langs->trans("WarehouseSource"), 'stock', 'class="paddingright"');
    print $formproduct->selectWarehouses(
      $form_line['from_warehouse_field']['current_value'],
      $form_line['from_warehouse_field']['name'],
      'warehouseopen,warehouseinternal',
      1, // can be empty
      0, // disabled
      $form_line['from_warehouse_field']['show_stock'] ? $product->id : 0, // Add quantity of stock in label for product
      '', // Empty label if needed (only if $empty=1)
      $form_line['from_warehouse_field']['show_stock'] ? 1 : 0, // show stock count
      0, // force combo
      array(), // events to add to select2
      'minwidth200imp maxwidth200'.($form_line['from_warehouse_field']['is_error'] ? ' error' : '') // morecss
    );
    print '</td>';

    print '<td class="nowraponall">';
    print $product->getNomUrl(1);
    print '</td>';

    print '<td class="nowraponall">';
    if (!empty($productlot)) {
      print $productlot->getNomUrl(1);
    }
    print '</td>';

    print '<td class="right">';
    print '<input type="number" min="0" step="1" ';
    if ($product->status_batch == 2) {
      print 'max="1" ';
    }
    print 'class="flat maxwidth50 right ';
    if ($form_line['qty_field']['is_error']) {
      print 'error ';
    }
    print '"';
    print 'name="'.$form_line['qty_field']['name'].'" ';
    print 'value="'.price2num((int) $form_line['qty_field']['current_value'], 'MS').'" ';
    print '>';
    print '</td>';

    print '</tr>';
  }
  print '</table>';
  print '</div>';

  print '<div class="center">';
  print '<div>';
  print '<span class="fieldrequired">'.$langs->trans("WarehouseTarget").':</span> ';
  print $formproduct->selectWarehouses(
    $transfer_form_description['to_warehouse_field']['current_value'],
    $transfer_form_description['to_warehouse_field']['name'],
    'warehouseopen,warehouseinternal',
    1, // can be empty
    0, // disabled
    0, // Add quantity of stock in label for product
    '', // Empty label if needed (only if $empty=1)
    0, // show stock count
    0, // force combo
    array(), // events to add to select2
    'minwidth200imp maxwidth200'.($transfer_form_description['to_warehouse_field']['is_error'] ? 'error' : '')  // morecss
  );
  print '</div>';
  print '<span class="fieldrequired">'.$langs->trans("InventoryCode").':</span> ';
  print '<input type="text" name="codemove" class="maxwidth300" value="'.dol_escape_htmltag($codemove).'"> &nbsp; ';
  print '<span class="clearbothonsmartphone"></span>';
  print $langs->trans("MovementLabel").': ';
  print '<input type="text" name="label" class="minwidth300" value="'.dol_escape_htmltag($labelmovement).'"><br>';
  print '<br>';
  print '</div>';

  print '<input type="submit" class="button" value="'.$langs->trans("RecordMovement").'">';
  print '</form>';
}

function get_transfer_form_line_suffix($product, $productlot = null, $fk_entrepot = null) {
  $suffix = '_'.strval($product->id);
  if (!empty($productlot)) {
    $suffix.= '_'.strval($productlot->id);
  }
  if (!empty($fk_entrepot)) {
    $suffix.= '__'.strval($fk_entrepot);
  }
  return $suffix;
}

function get_unique_transfer_form_line_suffix($product, $productlot = null, $fk_entrepot = null) {
  global $seen_transfer_form_line_suffix;

  $suffix = get_transfer_form_line_suffix($product, $productlot);
  if (array_key_exists($suffix, $seen_transfer_form_line_suffix)) {
    return null;
  }
  $seen_transfer_form_line_suffix[$suffix] = true;
  return $suffix;
}

function get_transfer_form_description($search) {
  global $db;

  $values = preg_split('/\r\n|\r|\n/', $search);
  $search_infos = search_printable_label($values);

  $line_description = [];
  $seen_suffixes = [];

  foreach ($search_infos as $search_infos_line) {
    $product = $search_infos_line['product'];
    $productlot = $search_infos_line['productlot'];
    $scan_count = $search_infos_line['scan_count'];

    $product->load_stock('novirtual');

    if (!empty($productlot)) {
      // productlot specified, displaying 1 line for this batch
      $number_to_move = $product->status_batch == 2 ? 1 : $scan_count;
      $suffix = get_unique_transfer_form_line_suffix($product, $productlot);
      if (empty($suffix)) { continue; }

      // computing default from_warehouse...
      $default_from_warehouse = null;
      foreach ($product->stock_warehouse as $fk_entrepot => $stock_info) {
        $detail_batch = $stock_info->detail_batch;
        $detail_current_batch = array_filter(is_array($detail_batch) ? $detail_batch : [], function ($productbatch) use ($productlot) {
          return ($productbatch->batch === $productlot->batch) && ($productbatch->qty > 0);
        });
        if (count($detail_current_batch) > 0) {
          $default_from_warehouse = $fk_entrepot;
          break;
        }
      }

      $qty_field_name = 'qty'.$suffix;
      $from_field_name = 'from_wh'.$suffix;
      $line_description[] = [
        'qty_field' => [
          'name' => $qty_field_name,
          'default_value' => $number_to_move,
          'current_value' => GETPOSTISSET($qty_field_name) ? GETPOST($qty_field_name, 'int') : $number_to_move,
          'is_error' => false
        ],
        'from_warehouse_field' => [
          'name' => $from_field_name,
          'default_value' => $default_from_warehouse,
          'current_value' => GETPOSTISSET($from_field_name) ? GETPOST($from_field_name, 'int') : $default_from_warehouse,
          'show_stock' => false,
          'is_error' => false
        ],
        'product' => $product,
        'productlot' => $productlot
      ];
      continue;
    }

    // productlot undefined. If !hasbatch, just display one line.
    if (!$product->hasbatch()) {
      $number_to_move = $scan_count;
      $suffix = get_unique_transfer_form_line_suffix($product, $productlot);
      if (empty($suffix)) { continue; }

      // TODO: computing default from_warehouse...
      $default_from_warehouse = null;

      $qty_field_name = 'qty'.$suffix;
      $from_field_name = 'from_wh'.$suffix;
      $line_description[] = [
        'qty_field' => [
          'name' => $qty_field_name,
          'default_value' => $number_to_move,
          'current_value' => GETPOSTISSET($qty_field_name) ? GETPOST($qty_field_name, 'int') : $number_to_move,
          'is_error' => false
        ],
        'from_warehouse_field' => [
          'name' => $from_field_name,
          'default_value' => $default_from_warehouse,
          'current_value' => GETPOSTISSET($from_field_name) ? GETPOST($from_field_name, 'int') : $default_from_warehouse,
          'show_stock' => false,
          'is_error' => false
        ],
        'product' => $product,
        'productlot' => $productlot
      ];
      continue;
    }

    // Last case: display one line per product batch! (with stock > 0)
    // Note: product->load_stock fills stock_warehouse[fk_entrepot]->detail_batch
    //  with batch that are in stock. So it is the perfect data to rely on.
    foreach ($product->stock_warehouse as $fk_entrepot => $detail) {
      if (empty($detail->detail_batch) || !is_array($detail->detail_batch)) { continue; }
      $detail_batch = $detail->detail_batch;
      foreach ($detail_batch as $product_batch) {
        if (empty($product_batch->qty)) { continue; } // should not happen, but just in case.

        $pl = null;
        if (!empty($product_batch->batch)) {
          $pl = new Productlot($db);
          $pl->fetch(0, $product->id, $product_batch->batch);
        }
        if (empty($pl->id)) {
          // Note: when product was in stock before enabling status_batch, ProductLot can be unexisting.
          // In such case, we add a line, but without productlot.
          $pl = null;
        }

        // In this loop, we initialize qty to 0, as we don't know if there is other lines...
        $number_to_move = 0;
        $suffix = get_unique_transfer_form_line_suffix($product, $pl, $fk_entrepot);
        if (empty($suffix)) {
          continue;
        }

        $default_from_warehouse = $fk_entrepot;

        $qty_field_name = 'qty'.$suffix;
        $from_field_name = 'from_wh'.$suffix;
        $line_description[] = [
          'qty_field' => [
            'name' => $qty_field_name,
            'default_value' => $number_to_move,
            'current_value' => GETPOSTISSET($qty_field_name) ? GETPOST($qty_field_name, 'int') : $number_to_move,
            'is_error' => false
          ],
          'from_warehouse_field' => [
            'name' => $from_field_name,
            'default_value' => $default_from_warehouse,
            'current_value' => GETPOSTISSET($from_field_name) ? GETPOST($from_field_name, 'int') : $default_from_warehouse,
            'show_stock' => true, // Here we want to show the current stock
            'is_error' => false
          ],
          'product' => $product,
          'productlot' => $pl
        ];
      }
    }
  }

  $to_field_name = 'to_wh';
  $transfer_form_description = [
    'lines' => $line_description,
    'to_warehouse_field' => [
      'name' => $to_field_name,
      'default_value' => null,
      'current_value' => GETPOSTISSET($to_field_name) ? GETPOST($to_field_name, 'int') : null,
      'is_error' => false
    ],
  ];
  return $transfer_form_description;
}

function transfer_form_valid(&$transfer_form_description) {
  global $langs;
  $langs->load("errors");

  $form_lines = &$transfer_form_description['lines'];
  
  $error = 0;
  $at_least_one_line = false;
  $transfer_actions = [];
  $wharehouse_by_id = [];

  $to_warehouse = $transfer_form_description['to_warehouse_field']['current_value'];
  if ($to_warehouse < 0) { $to_warehouse = 0; }
  if (empty($to_warehouse)) {
    $error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseTarget")), null, 'errors');
    $transfer_form_description['to_warehouse_field']['is_error'] = true;
  }

  $codemove = GETPOST("codemove", 'alpha');
  if (empty($codemove)) {
    $error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("InventoryCode")), null, 'errors');
  }
  $label = GETPOST("label");
  if (empty($label)) {
    $error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MovementLabel")), null, 'errors');
  }

  foreach ($form_lines as &$form_line) {
    $qty = price2num($form_line['qty_field']['current_value']);
    $from_warehouse = $form_line['from_warehouse_field']['current_value'];
    if ($from_warehouse < 0) { $from_warehouse = 0; }

    if (empty($qty) || !is_numeric($qty)) {
      continue;
    }
    $at_least_one_line = true;

    // Now, we know qty>0, so the from_warehouse is mandatory
    // TODO: allow correct stock from here, when from_warehouse is empty?
    if (empty($from_warehouse)) {
      $error++;
      setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WarehouseSource")), null, 'errors');
      $form_line['from_warehouse_field']['is_error'] = true;
      continue;
    }

    if ($from_warehouse > 0 && $from_warehouse == $to_warehouse) {
      $error++;
      setEventMessages($langs->trans("ErrorWarehouseMustDiffers"), null, 'errors');
      $form_line['from_warehouse_field']['is_error'] = true;
      continue;
    }

    if ($qty > 1 && $form_line['product']->status_batch == 2) {
      $error++;
      $product_desc = !empty($form_line['productlot']) ? $form_line['productlot']->batch : $form_line['product']->ref;
      setEventMessages($langs->trans("PrintableSearchTransferQtyMustBeOne", $product_desc), null, 'errors');
      $form_line['qty']['is_error'] = true;
      continue;
    }

    $transfer_actions[] = [
      'qty' => $qty,
      'from_warehouse' => $from_warehouse,
      'from_warehouse_object' => _get_warehouse($from_warehouse),
      'to_warehouse' => $to_warehouse,
      'to_warehouse_object' => _get_warehouse($to_warehouse),
      'product' => $form_line['product'],
      'productlot' => $form_line['productlot']
    ];
  }

  if ($error) {
    return false;
  }
  if (!$at_least_one_line) {
    setEventMessages($langs->trans("PrintableSearchTransferMissingLines"), null, 'errors');
    return false;
  }
  return $transfer_actions;
}

function do_the_transfer(&$transfer_actions) {
  global $user, $db, $langs, $conf;

  $error = 0;
  $db->begin();

  $codemove = GETPOST("codemove", 'alpha');
  $labelmovement = GETPOST("label");

  foreach ($transfer_actions as &$transfer_action) {
    if ($error) {
      break; // if previous iteration had error, stop.
    }

    $productlot = $transfer_action['productlot'];
    $from_warehouse = $transfer_action['from_warehouse'];
    $to_warehouse = $transfer_action['to_warehouse'];
    $qty = $transfer_action['qty'];

    // Following code is an adaptation of the official massstockmove.php file.

    // to avoid any issue when a same product has multiple lines, we will fetch it again.
    $product = new Product($db);
    $product->fetch($transfer_action['product']->id);
    $product->load_stock('novirtual'); // Load array product->stock_warehouse

    // Define value of products moved
    $pricesrc = 0;
    if (!empty($product->pmp)) {
      $pricesrc = $product->pmp;
    }
    $pricedest = $pricesrc;

    // Note: the code that describe the form ensure that we have productlot when needed.
    //  We don't have to check anything. The presence of productlot is enougha as criteria
    //  to use correct_stock_batch instead of correct_stock.
    if (empty($productlot)) {
      // Remove stock if source warehouse defined
      if ($from_warehouse > 0) {
        $result1 = $product->correct_stock(
          $user,
          $from_warehouse,
          $qty,
          1, // remove
          $labelmovement,
          $pricesrc,
          $codemove
        );
        if ($result1 < 0) {
          $error++;
          setEventMessages($product->error, $product->errors, 'errors');
        }
      }

      // Add stock
      $result2 = $product->correct_stock(
        $user,
        $to_warehouse,
        $qty,
        0, // add
        $labelmovement,
        $pricedest,
        $codemove
      );
      if ($result2 < 0) {
        $error++;
        setEventMessages($product->error, $product->errors, 'errors');
      }
    } else {
      $batch = $productlot->batch;
      $dlc = $productlot->eatby;
      $dluo = $productlot->sellby;

      // Remove stock
      if ($from_warehouse > 0) {
        $result1 = $product->correct_stock_batch(
          $user,
          $from_warehouse,
          $qty,
          1, // remove
          $labelmovement,
          $pricesrc,
          $dlc,
          $dluo,
          $batch,
          $codemove
        );
        if ($result1 < 0) {
          $error++;
          setEventMessages($product->error, $product->errors, 'errors');
        }
      }

      // Add stock
      $result2 = $product->correct_stock_batch(
        $user,
        $to_warehouse,
        $qty,
        0, // add
        $labelmovement,
        $pricedest,
        $dlc,
        $dluo,
        $batch,
        $codemove
      );
      if ($result2 < 0) {
        $error++;
        setEventMessages($product->error, $product->errors, 'errors');
      }
    }
  }

  if ($error) {
    $db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
    return;
  }

  $db->commit();
  setEventMessages($langs->trans("StockMovementRecorded"), null, 'mesgs');
  // Redirecting on the movement_list page:
  header("Location: ".DOL_URL_ROOT.'/product/stock/movement_list.php?search_inventorycode='.urlencode('^'.$codemove.'$'));
  exit;
}

// End of page
llxFooter();
$db->close();
