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
$transfer_form_description = null;
$seen_transfer_form_line_suffix = [];

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
  // ...
  $action = 'transfer';
}

/*
 * View
 */

$helpurl='';
$title = $langs->trans('PickupPrintableLabelSearchTitle');
llxHeader("", $title, $helpurl);

if ($action === 'scan') {
  print_scan_form($action, $search);
} else if ($action === 'list') {
  print_scan_form($action, $search);
  print_list($search);
} else if ($action === 'transfer') {
  print_scan_form($action, $search);
  print_transfer_form($transfer_form_description, $search);
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

  $warehouses = [];

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

      if (array_key_exists($fk_entrepot, $warehouses)) {
        $warehouse = $warehouses[$fk_entrepot];
      } else {
        $warehouse = new Entrepot($db);
        $warehouse->fetch($fk_entrepot);
        $warehouses[$fk_entrepot] = $warehouse;
      }

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

function print_transfer_form($transfer_form_description, $search) {
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

  print '<div class="div-table-responsive-no-min">';
  print '<table class="liste centpercent">';
  print '<tr class="liste_titre">';
  print getTitleFieldOfList($langs->trans('WarehouseSource'));
  print getTitleFieldOfList($langs->trans('Product'));
  print getTitleFieldOfList($langs->trans('Batch'));
  print getTitleFieldOfList($langs->trans('Qty'));
  foreach ($transfer_form_description['lines'] as $form_line) {
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
      'minwidth200imp maxwidth200' // morecss
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

    print '<td class="rigth">';
    print '<input type="text" class="flat maxwidth50 right" ';
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
    'minwidth200imp maxwidth200' // morecss
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

function get_transfer_form_line_suffix($product, $productlot = null) {
  $suffix = '_'.strval($product->id);
  if (!empty($productlot)) {
    $suffix.= '_'.strval($productlot->id);
  }
  return $suffix;
}

function get_unique_transfer_form_line_suffix($product, $productlot = null) {
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
          'current_value' => GETPOSTISSET($qty_field_name) ? GETPOST($qty_field_name, 'int') : $number_to_move
        ],
        'from_warehouse_field' => [
          'name' => $from_field_name,
          'default_value' => $default_from_warehouse,
          'current_value' => GETPOSTISSET($from_field_name) ? GETPOST($from_field_name, 'int') : $default_from_warehouse,
          'show_stock' => false
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
          'current_value' => GETPOSTISSET($qty_field_name) ? GETPOST($qty_field_name, 'int') : $number_to_move
        ],
        'from_warehouse_field' => [
          'name' => $from_field_name,
          'default_value' => $default_from_warehouse,
          'current_value' => GETPOSTISSET($from_field_name) ? GETPOST($from_field_name, 'int') : $default_from_warehouse,
          'show_stock' => true // Here we want to show the current stock
        ],
        'product' => $product,
        'productlot' => $productlot
      ];
      continue;
    }

    // TODO: display one line per product batch!
  }

  $to_field_name = 'to_wh';
  $transfer_form_description = [
    'lines' => $line_description,
    'to_warehouse_field' => [
      'name' => $to_field_name,
      'default_value' => null,
      'current_value' => GETPOSTISSET($to_field_name) ? GETPOST($to_field_name, 'int') : null,
    ],
  ];
  return $transfer_form_description;
}

// End of page
llxFooter();
$db->close();
