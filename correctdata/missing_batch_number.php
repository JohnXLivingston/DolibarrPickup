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

dol_include_once('/product/class/product.class.php');
dol_include_once('/product/stock/class/entrepot.class.php');
dol_include_once('/product/stock/class/productlot.class.php');
dol_include_once('/core/class/html.form.class.php');
// dol_include_once('/product/class/html.formproduct.class.php');
dol_include_once('/core/class/extrafields.class.php');
dol_include_once('/pickup/lib/correctdata_missing_batch_number.lib.php');

$langs->loadLangs(array("pickup@pickup", 'products', 'productbatch', 'stocks', "other"));

$action = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'list';
$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array');

$search_product_id = GETPOST('product_id', 'int');
$search_warehouse = GETPOST('search_warehouse', 'alpha');
$search_tobatch = GETPOST('search_tobatch', 'int');

$sortfield = 'p.ref';
$sortorder = 'ASC';

$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$param = '';
$nbtotalofrecords = '';
$picto = 'product';
$selectedfields = '';

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service', 0, 'product&product');
if (empty($conf->pickup->enabled)) {
  accessforbidden('Module not enabled');
}
if ($user->societe_id > 0) {	// Protection if external user
  accessforbidden();
}
if (!$user->rights->pickup->correctdata) {
  accessforbidden();
}

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$object = new Product($db);
$warehousetmp = new Entrepot($db);
$product_lot_static = new Productlot($db);
// $hookmanager->initHooks(array('productservicelist'));
$extrafields = new ExtraFields($db);
$form = new Form($db);
// $formproduct = new FormProduct($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
// $search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');


if (empty($action)) {
	$action = 'list';
}


// Definition of array of fields for columns
$arrayfields = array();

// Using some standard fields definition: copying form $object->fields
foreach (['rowid', 'ref', 'label'] as $key) {
  // must prefix with 'p.', cf sql query
  $akey = 'p.'.$key;
  $arrayfields[$akey] = $object->fields[$key];
  $arrayfields[$akey]['product_field_name'] = $key;

  $visible = $arrayfields[$akey]['visible'];
  if (!empty($visible)) {
    $visible = dol_eval($visible, 1, 1, '1');
    $arrayfields[$akey]['checked'] = (($visible < 0) ? 0 : 1);
  }
}

// Other product fields:
$arrayfields['p.tobatch'] = array(
  'label'=>"ManageLotSerial",
  'checked'=>1,
  'visible'=>1,
  'enabled'=>!empty($conf->productbatch->enabled),
  'position'=>60
);
$arrayfields['e.ref'] = array(
  'label'=>"Warehouse",
  'checked'=>1,
  'visible'=>1,
  'enabled'=>1,
  'position'=>70
);
// $arrayfields['batch'] = array(
//   'label'=>"Batch",
//   'checked'=>1,
//   'visible'=>1,
//   'enabled'=>1,
//   'position'=>80
// );
$arrayfields['stock_physique'] = array(
  'label'=>"PhysicalStock",
  'checked'=>1,
  'visible'=>1,
  'enabled'=>1,
  'position'=>90
);


$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

$search = array();
foreach ($arrayfields as $akey => $val) {
  $key = array_key_exists('product_field_name', $val) ? $val['product_field_name'] : $akey;
	if (GETPOST('search_'.$key, 'alpha') !== '') {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

/*
 * Actions
 */

 if (GETPOST('cancel', 'alpha')) {
	$action = 'list'; $massaction = '';
}

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
  foreach ($search as $key => $criteria) {
    unset($search[$key]);
  }
  $search_product_id = '';
  $search_warehouse = '';
  $search_tobatch = '';
  $toselect = array();
  // $search_array_options = array();
}
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
  || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
  $massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
}

if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
  if ($massaction !== 'generate_missing_batch_number_input') {
    $massaction = '';
  }
}

$generate_missing_batch_number_result = null;
if ($massaction === 'generate_missing_batch_number_input') {
  // No extra permissions needed, having access to this screen is enough.

  // Resetting previous results.
  unset($_SESSION['dol_pickup_missing_batch_number_result']);
  unset($_SESSION['dol_pickup_missing_batch_number_was_for_product']);
  if ($action === 'do_generate_missing_batch_number') {
    $generate_missing_batch_number_actions = read_generate_missing_batch_number_actions(is_array($toselect) ? $toselect : array());
    $generate_missing_batch_number_result = do_generate_missing_batch_number_actions($generate_missing_batch_number_actions);
    if (!empty($generate_missing_batch_number_result)) {
      // To prevent submitting twice, when reloading the page,
      // we will store the result text in session, then redirect, then display text.
      $result_text = '';

      $codemove = $generate_missing_batch_number_result['codemove'];
      $labelmovement = $generate_missing_batch_number_result['labelmovement'];
      $result_text.= '<table class="valid centpercent">';
      $result_text.= '<tr class="valid">';
      $result_text.= '<th class="valid" colspan="6">';
      $result_text.= '<a target="_blank" href="'.htmlspecialchars(DOL_URL_ROOT.'/product/stock/movement_list.php?search_inventorycode='.urlencode('^'.$codemove.'$')).'">';
      $result_text.= htmlspecialchars($labelmovement);
      $result_text.= '</a>';
      if (!empty($conf->global->PICKUP_USE_PRINTABLE_LABEL)) {
        $plids = [];
        foreach ($generate_missing_batch_number_result['lines'] as $gmbnr) {
          $pl = $gmbnr['productlot'];
          if (!empty($pl)) {
            $plids[] = "'".$pl->id."'";
          }
        }
        $plids = implode(', ', $plids);
        $printbutton = '<a class="button buttongen"';
        $printbutton.= ' onclick="window.dolibarrPickup.printProductLotLabel(this, ['.htmlspecialchars($plids).']);"';
        $printbutton.= ' title="'.$langs->trans('PickupPrintLabel').'"';
        $printbutton.= ' style="min-width: 34px;"';
        $printbutton.= '>';
        $printbutton.= '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="16" fill="currentColor" viewBox="0 0 32 16">';
        $printbutton.= '<g id="bars" fill="currentColor" stroke="none">';
        $printbutton.= '	<rect x="0" y="0" width="4" height="30"></rect>';
        $printbutton.= '	<rect x="6" y="0" width="2" height="30"></rect>';
        $printbutton.= '	<rect x="12" y="0" width="2" height="30"></rect>';
        $printbutton.= '	<rect x="22" y="0" width="4" height="30"></rect>';
        $printbutton.= '	<rect x="28" y="0" width="6" height="30"></rect>';
        $printbutton.= '</g>';
        $printbutton.= '</svg>';
        $printbutton.= '</a>';
        $result_text.= $printbutton;
      }
      $result_text.= '</th>';
      $result_text.= '</tr>';

      $result_text.= '<tr class="valid">';
      $result_text.= '<th class="valid">'.$langs->trans('Ref').'</th>';
      $result_text.= '<th class="valid">'.$langs->trans('Label').'</th>';
      $result_text.= '<th class="valid">'.$langs->trans('ManageLotSerial').'</th>';
      $result_text.= '<th class="valid">'.$langs->trans('Warehouse').'</th>';
      $result_text.= '<th class="valid">'.$langs->trans('Qty').'</th>';
      $result_text.= '<th class="valid">'.$langs->trans('Batch').'</th>';
      $result_text.= '</tr>';

      foreach ($generate_missing_batch_number_result['lines'] as $gmbnr) {
        $p = $gmbnr['product'];
        $e = $gmbnr['entrepot'];
        $pl = $gmbnr['productlot'];
        $result_text.= '<tr class="valid">';
        $result_text.= '<td class="valid">';
        $result_text.= $p->getNomUrl(1, 'stock');
        $result_text.= '</td>';
        $result_text.= '<td class="valid">';
        $result_text.= $p->showOutputField($p->fields['label'], 'label', $p->label, '');
        $result_text.= '</td>';
        $result_text.= '<td class="valid">';
        switch ($p->status_batch) {
          case 0:
            $result_text.= $langs->trans("ProductStatusNotOnBatch");
            break;
          case 1:
            $result_text.= $langs->trans("ProductStatusOnBatch");
            break;
          case 2:
            $result_text.= $langs->trans("ProductStatusOnSerial");
            break;
        }
        $result_text.= '</td>';
        $result_text.= '<td class="valid">';
        $result_text.= $e->getNomUrl(1);
        $result_text.= '</td>';
        $result_text.= '<td class="valid">';
        $result_text.= price2num($gmbnr['qty'], 'MS');
        $result_text.= '</td>';
        $result_text.= '<td class="valid">';
        if (empty($pl)) {
          $result_text.= htmlspecialchars($gmbnr['batch_number']);
        } else {
          $result_text.= $pl->getNomUrl(1);
          if (!empty($conf->global->PICKUP_USE_PRINTABLE_LABEL)) {
            $result_text.= '<a class="button buttongen"';
            $result_text.= ' onclick="window.dolibarrPickup.printProductLotLabel(this, \''.htmlspecialchars($pl->id).'\');"';
            $result_text.= ' title="'.$langs->trans('PickupPrintLabel').'"';
            $result_text.= ' style="min-width: 34px;"';
            $result_text.= '>';
            $result_text.= '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="16" fill="currentColor" viewBox="0 0 32 16">';
            $result_text.= '<g id="bars" fill="currentColor" stroke="none">';
            $result_text.= '	<rect x="0" y="0" width="4" height="30"></rect>';
            $result_text.= '	<rect x="6" y="0" width="2" height="30"></rect>';
            $result_text.= '	<rect x="12" y="0" width="2" height="30"></rect>';
            $result_text.= '	<rect x="22" y="0" width="4" height="30"></rect>';
            $result_text.= '	<rect x="28" y="0" width="6" height="30"></rect>';
            $result_text.= '</g>';
            $result_text.= '</svg>';
            $result_text.= '</a>';
          }
        }
        $result_text.= '</td>';
        $result_text.= '</tr>';
      }

      $result_text.= '</table>';
      $result_text.= '<br><br>';

      $_SESSION['dol_pickup_missing_batch_number_result'] = $result_text;
      $_SESSION['dol_pickup_missing_batch_number_was_for_product'] = $search_product_id;
      header('Location: '.$_SERVER["PHP_SELF"]);
      exit;
    }
    $action = 'list';
    $massaction = '';
    $toselect = array();
  } // else, only display the form.
}

/*
 * View
 */

$title =  $langs->trans("PickupMenuCorrectData") .' / '. $langs->trans("PickupMenuCorrectDataBatchNumber");
$title_html = $title;
if (!empty($search_product_id)) {
  $producttmp = new Product($db);
  if ($producttmp->fetch($search_product_id) > 0) {
    $title_html.= ' / '.$producttmp->getNomUrl(1, 'stock');
  }
}

$sql = ''; // we will set the SELECT later on, as we will need to make a count request
$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as ps on p.rowid = ps.fk_product'; // Detail for each warehouse
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'entrepot as e on ps.fk_entrepot = e.rowid'; // Link on unique key
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_batch as pb on pb.fk_product_stock = ps.rowid'; // Detail for each lot on each warehouse
// $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_lot as pl on pl.fk_product = p.rowid AND pl.batch = pb.batch'; // Link on unique key
$sql.= " WHERE p.entity IN (".getEntity('product').") AND e.entity IN (".getEntity('stock').")";
$sql.= " AND p.tobatch != 0 AND pb.batch = '000000' ";
$sql.= " AND pb.qty > 0 ";
// $sql.= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,";
// $sql.= " p.fk_product_type, p.tms,";
// $sql.= " p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock, p.stock, p.tosell, p.tobuy, p.tobatch,";
// $sql.= " ps.fk_entrepot, ps.reel,";
// $sql.= " e.ref, e.lieu, e.fk_parent,";
// $sql.= " pb.batch, pb.eatby, pb.sellby,";
// $sql.= " pl.rowid, pl.eatby, pl.sellby";

foreach ($search as $key => $val) {
  if ($key === 'label' || $key === 'ref') {
    $sql.= natural_search("p.".$db->escape($key), $search[$key], 0);
  }
}
if (!empty($search_warehouse)) {
  $sql.= natural_search("e.ref", $search_warehouse, 0);
}
if (!empty($search_tobatch)) {
 $sql.= " AND p.tobatch = '".$db->escape($search_tobatch)."'";
}
if (!empty($search_product_id)) {
  $sql.= " AND p.rowid = '".$db->escape($search_product_id)."'";
}

// Now we can count the number of results
$sqlforcount = 'SELECT COUNT(*) as nbtotalofrecords '.$sql;
$resql = $db->query($sqlforcount);
if ($resql) {
  $objforcount = $db->fetch_object($resql);
  $nbtotalofrecords = $objforcount->nbtotalofrecords;
} else {
  dol_print_error($db);
}
if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
  $page = 0;
  $offset = 0;
}
$db->free($resql);



// Complete request and execute it with limit
$sql.= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}


// $sql = 'SELECT '.implode(', ', array_keys($arrayfields)).' '.$sql;
$sql = 'SELECT p.rowid, fk_entrepot, e.ref as warehouse_ref, pb.rowid as pbid, pb.batch, pb.qty as stock_physique '.$sql; // product will be fetched one by one later on.
// I removed: pl.rowid as lotid, pl.eatby, pl.sellby
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

$arrayofselected = is_array($toselect) ? $toselect : array();

$helpurl = '';
llxHeader('', $title, $helpurl, '', 0, 0, array(), array(), '', '');

// List of mass actions available
$arrayofmassactions = array();
if (
  $conf->global->PICKUP_DEFAULT_UNIQUE_BATCH === 'generate'
  || $conf->global->PICKUP_DEFAULT_BATCH === 'generate'
  || $conf->global->PICKUP_DEFAULT_BATCH === 'generate_per_product'
) {
	$arrayofmassactions['generate_missing_batch_number_input'] = img_picto('', 'edit', 'class="pictofixedwidth"').$langs->trans('PickupCorrectDataGenerateMissingBatch');
};
$massactionbutton = $form->selectMassAction($massaction === 'generate_missing_batch_number_input' ? $massaction : '', $arrayofmassactions);

$param = '';
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
foreach ($search as $key => $val) {
	if (is_array($search[$key])) {
		foreach ($search[$key] as $skey) {
			if ($skey != '') {
				$param .= '&search_'.$key.'[]='.urlencode($skey);
			}
		}
	} elseif (preg_match('/(_dtstart|_dtend)$/', $key) && !empty($val)) {
		$param .= '&search_'.$key.'month='.((int) GETPOST('search_'.$key.'month', 'int'));
		$param .= '&search_'.$key.'day='.((int) GETPOST('search_'.$key.'day', 'int'));
		$param .= '&search_'.$key.'year='.((int) GETPOST('search_'.$key.'year', 'int'));
	} elseif ($search[$key] != '') {
		$param .= '&search_'.$key.'='.urlencode($search[$key]);
	}
}
if (!empty($search_product_id)) {
  $param .= '&product_id='.urlencode($search_product_id);
}
if (!empty($search_warehouse)) {
  $param .= "&search_warehouse=".urlencode($search_warehouse);
}
if (!empty($search_tobatch)) {
  $param .= "&search_tobatch=".urlencode($search_tobatch);
}

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
// if ($optioncss != '') {
// 	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
// }
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';

print_barre_liste($title_html, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $picto, 0, '', '', $limit, 0, 0, 1);

$selectedfields = '';
// $selectedfields.= $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '')); // This also change content of $arrayfields
if ($massactionbutton) {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

if ($massaction === 'generate_missing_batch_number_input') {
  $generate_missing_batch_number_actions = read_generate_missing_batch_number_actions($arrayofselected);
  if ($action === 'confirm_generate_missing_batch_number') {
    // Confirm action to generate missing batch numbers.
    print '<table class="valid centpercent">';
    print '<tr class="validtitre">';
    print '<th class="validtitre" colspan="5">';
    print $langs->trans('PickupCorrectDataGenerateMissingBatchConfirm');
    print '</th>';
    print '</tr>';

    print '<tr class="valid">';
    print '<th class="valid">'.$langs->trans('Ref').'</th>';
    print '<th class="valid">'.$langs->trans('Label').'</th>';
    print '<th class="valid">'.$langs->trans('ManageLotSerial').'</th>';
    print '<th class="valid">'.$langs->trans('Warehouse').'</th>';
    print '<th class="valid">'.$langs->trans('Qty').'</th>';
    print '</tr>';

    foreach ($generate_missing_batch_number_actions as $gmbna) {
      $p = $gmbna['product'];
      $e = $gmbna['entrepot'];
      print '<tr class="valid">';
      print '<td class="valid">';
      print $p->getNomUrl(1, 'stock');
      print '</td>';
      print '<td class="valid">';
      print $p->showOutputField($p->fields['label'], 'label', $p->label, '');
      print '</td>';
      print '<td class="valid">';
      switch ($p->status_batch) {
        case 0:
          print $langs->trans("ProductStatusNotOnBatch");
          break;
        case 1:
          print $langs->trans("ProductStatusOnBatch");
          break;
        case 2:
          print $langs->trans("ProductStatusOnSerial");
          break;
      }
      print '</td>';
      print '<td class="valid">';
      print $e->getNomUrl(1);
      print '</td>';
      print '<td class="valid">';
      print price2num($gmbna['qty'], 'MS');
      print '</td>';
      print '</tr>';
    }

    print '<tr class="valid">';
    print '<td class="valid center" colspan="5">';
    print '<a class="butActionDelete" ';
    print ' onclick="$(this).closest(\'form\').find(\'[name=action]\').val(\'list\'); $(this).closest(\'form\').submit();" ';
    print '>'.$langs->trans('Cancel').'</a>';
    // Note: type="button" to prevent this button to be trigger on enter in a field from the form under it.
    print '<input type="button" class="button" value="'.$langs->trans("Validate").'" ';
    print ' onclick="$(this).closest(\'form\').find(\'input[name=action]\').val(\'do_generate_missing_batch_number\'); $(this).closest(\'form\').submit();" ';
    print '>';
    print '</td>';
    print '</tr>';
    print '</table>';
    print '<br><br>';
  } else {
    print '<table class="valid centpercent">';
    print '<tr class="validtitre">';
    print '<th class="validtitre">';
    print $langs->trans('PickupCorrectDataGenerateMissingBatchConfirm');
    print '</th>';
    print '</tr>';
    print '<tr class="valid">';
    print '<td class="valid">';
    print '<a class="butActionDelete" ';
    print ' onclick="$(this).closest(\'form\').find(\'[name=action]\').val(\'list\'); $(this).closest(\'form\').find(\'[name=massaction]\').val(\'\'); $(this).closest(\'form\').submit();" ';
    print '>'.$langs->trans('Cancel').'</a>';
    // Note: type="button" to prevent this button to be trigger on enter in a field from the form under it.
    print '<input type="button" class="button" value="'.$langs->trans("Validate").'" ';
    print ' onclick="$(this).closest(\'form\').find(\'input[name=action]\').val(\'confirm_generate_missing_batch_number\'); $(this).closest(\'form\').submit();" ';
    print '>';
    print '</td>';
    print '</tr>';
    print '</table>';
    print '<br><br>';
  }
}

if (!empty($_SESSION['dol_pickup_missing_batch_number_result'])) {
  if ($_SESSION['dol_pickup_missing_batch_number_was_for_product'] != $search_product_id) {
    unset($_SESSION['dol_pickup_missing_batch_number_result']);
    unset($_SESSION['dol_pickup_missing_batch_number_was_for_product']);
  } else {
    print $_SESSION['dol_pickup_missing_batch_number_result'];
    // NB: no need to unset, so the user can see it event if he left the page accidently.
    // We will unset on next submit.
  }
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
  print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
foreach ($arrayfields as $akey => $val) {
  $key = array_key_exists('product_field_name', $val) ? $val['product_field_name'] : $akey;
  $cssforfield = '';
  if (empty($arrayfields[$akey]['checked'])) {
    continue;
  }
  print '<td>';
  if ($key === 'label' || $key === 'ref') {
    print $object->showInputField($val, $key, (isset($search[$key]) ? $search[$key] : ''), '', '', 'search_', $cssforfield.' maxwidth250', 1);
  } else if ($key === 'e.ref') {
    print '<input class="flat" type="text" name="search_warehouse" size="6" value="'.htmlspecialchars($search_warehouse).'">';
  } else if ($key === 'p.tobatch') {
    $statutarray = array('' => '', '1' => $langs->trans("ProductStatusOnBatch"), '2' => $langs->trans("ProductStatusOnSerial"));
    print $form->selectarray('search_tobatch', $statutarray, $search_tobatch);
  }
  print '</td>';
}
if ($massaction === 'generate_missing_batch_number_input') {
  print '<td></td>';
}
if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
  print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>';

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	print_liste_field_titre($selectedfields, '', "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
foreach ($arrayfields as $key => $val) {
  if (!empty($arrayfields[$key]['checked'])) {
    print_liste_field_titre($val['label'], '', "", "", $param, "", $sortfield, $sortorder);
  }
}
if ($massaction === 'generate_missing_batch_number_input') {
  print '<td></td>';
}
if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	print_liste_field_titre($selectedfields, '', "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
print "</tr>\n";

$i = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
  $product = new Product($db);
  $product->fetch($obj->rowid);

  $toselect_val = $obj->pbid;
  $is_line_selected = in_array($toselect_val, $arrayofselected);

  // $categorie = new Categorie($db);
  // $categorie->fetch($obj->cat_rowid);

  // $product_lot_static->batch = $obj->batch;
	// $product_lot_static->fk_product = $obj->rowid;
	// $product_lot_static->id = $obj->lotid;
	// $product_lot_static->eatby = $obj->eatby;
	// $product_lot_static->sellby = $obj->sellby;

  print '<tr class="oddeven">';

  // Action column
  if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
    print '<td class="nowrap center">';
    if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
      print '<input id="cb'.$toselect_val.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$toselect_val.'"'.($is_line_selected ? ' checked="checked"' : '').'>';
    }
    print '</td>';
  }

  foreach ($arrayfields as $key => $val) {
    if (empty($val['checked'])) { continue; }
    print '<td>';
    if ($key === 'p.tobatch') {
      switch ($product->status_batch) {
        case 0:
          print $langs->trans("ProductStatusNotOnBatch");
          break;
        case 1:
          print $langs->trans("ProductStatusOnBatch");
          break;
        case 2:
          print $langs->trans("ProductStatusOnSerial");
          break;
      }
    } else if ($key === 'e.ref') {
      if ($obj->fk_entrepot > 0) {
        $warehousetmp->id = $obj->fk_entrepot;
        $warehousetmp->ref = $obj->warehouse_ref;
        $warehousetmp->label = $obj->warehouse_ref;
        $warehousetmp->fk_parent = $obj->warehouse_parent;
        print $warehousetmp->getNomUrl(1);
      }
    // } else if ($key === 'batch') {
    //   if ($product_lot_static->batch) {
    //     print $product_lot_static->getNomUrl(1);
    //   }
    } else if ($key === 'stock_physique') {
      if (!empty($obj->stock_physique)) {
        if ($obj->stock_physique < 0) { print '<span class="warning">'; }
        print price2num($obj->stock_physique, 'MS');
        if ($obj->stock_physique < 0) { print '</span>'; }
      }
    } else if ($key === 'p.ref') {
      print $product->getNomUrl(1, 'stock');
    } else if (preg_match('/^p\.(\w+)$/', $key, $matches)) {
      $product_field = $matches[1];
      print $product->showOutputField($val, $product_field, $product->$product_field, '');
      // print htmlspecialchars($product->$product_field);
    }
    print '</td>';
  }

  if ($massaction === 'generate_missing_batch_number_input') {
    print '<td class="nowrap">';
    if ($is_line_selected) {
      if (
        ($product->status_batch == 2 && $conf->global->PICKUP_DEFAULT_UNIQUE_BATCH === 'generate')
        || (
          $product->status_batch == 1
          && (
            $conf->global->PICKUP_DEFAULT_BATCH === 'generate'
            || $conf->global->PICKUP_DEFAULT_BATCH === 'generate_per_product'
          )
        )
      ) {
        $gmbni_field_name = 'generate_missing_batch_number_'.$toselect_val;
        $autofill_onclick = "$('[name=".$gmbni_field_name."]').val('".$obj->stock_physique."')";
        print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmount' onclick=\"".$autofill_onclick.'"');
        print '<input type="number" min="0" ';
        $cur_val = GETPOST($gmbni_field_name, 'int');
        if ($cur_val == '0' || !empty($cur_val)) {
          print ' value="'.htmlspecialchars($cur_val).'" ';
        } else if ($product->status_batch == 2) {
          print ' value="1" ';
        } else {
          print ' value="'.$obj->stock_physique.'" ';
        }
        print ' max="'.$obj->stock_physique.'" ';
        print ' name="'.$gmbni_field_name.'" ';
        print ' id="'.$gmbni_field_name.'" ';
        print '>';
      }
    }
    print '</td>';
  }

  // Action column
  if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
    print '<td class="nowrap center">';
    if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
      print '<input id="cb'.$toselect_val.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$toselect_val.'"'.($is_line_selected ? ' checked="checked"' : '').'>';
    }
    print '</td>';
  }
  print '</tr>';
	$i++;
}

$db->free($resql);

// If no record found
if ($num == 0) {
	$colspan = 1; // set to 1 because of massaction (select column).
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

print "</table>";
print "</div>";
print '</form>';

// End of page
llxFooter();
$db->close();
