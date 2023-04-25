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
dol_include_once('/core/class/html.form.class.php');
// dol_include_once('/product/class/html.formproduct.class.php');
dol_include_once('/core/class/extrafields.class.php');
dol_include_once('/categories/class/categorie.class.php');

$langs->loadLangs(array("pickup@pickup", 'products', 'productbatch', "other"));

$action = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'list';
$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array');

$sortfield = 'ref';
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

// categorie fields:
$arrayfields['cat'] = array(
  'label'=>'PickupCorrectDataResponsibleCat',
  'checked'=>1,
  'visible'=>1,
  'enabled'=>1,
  'position'=>80
);
$arrayfields['all_cats'] = array(
  'label'=>'Categories',
  'checked'=>1,
  'visible'=>1,
  'enabled'=>1,
  'position'=>85
);
// $categorie = new Categorie($db);
// foreach (['label'] as $key) {
//   // must prefix with 'cat.', cf sql query
//   $akey = 'cat.'.$key;
//   $arrayfields[$akey] = $object->fields[$key];

//   $visible = $arrayfields[$akey]['visible'];
//   if (!empty($visible)) {
//     $visible = dol_eval($visible, 1, 1, '1');
//     $arrayfields[$akey]['checked'] = (($visible < 0) ? 0 : 1);
//   }
// }

$arrayfields['wanted_status_batch'] = array(
  'label'=>'PickupCorrectDataWantedStatusBatch',
  'checked'=>1,
  'visible'=>1,
  'enabled'=>1,
  'position'=>70
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
  $toselect = array();
  // $search_array_options = array();
}
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
  || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
  $massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
}

if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

if ($massaction === 'correct_status_batch') {
  // No extra permissions needed, having access to this screen is enough.
  $product = new Product($db);
  foreach ($toselect as $toselectid) {
    $result = $product->fetch($toselectid);
    if ($result > 0 && $product->id > 0) {
      $correct_status_batch = GETPOST('correct_status_batch_'.$product->id, 'alpha') ?? '';
      if (preg_match('/^batch_status_(\d+)$/', $correct_status_batch, $matches)) {
        $product->status_batch = intval($matches[1]);
        if ($product->update($product->id, $user) < 0) {
          setEventMessages($product->error, $product->errors, 'errors');
        }
      }
    }
  }
}

/*
 * View
 */

$title =  $langs->trans("PickupMenuCorrectData") .' / '. $langs->trans("PickupMenuCorrectDataProductStatusBatch");

$sql = ''; // we will set the SELECT later on, as we will need to make a count request
$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
$sql.= ' RIGHT JOIN '.MAIN_DB_PREFIX.'categorie_'.Categorie::TYPE_PRODUCT.' as ct ON ct.fk_product = p.rowid ';
$sql.= ' RIGHT JOIN '.MAIN_DB_PREFIX.'categorie as c ON ct.fk_categorie = c.rowid ';
$sql.= ' RIGHT JOIN '.MAIN_DB_PREFIX.'pickup_mobilecat as mc ON fk_category = c.rowid ';
$sql.= ' WHERE p.entity IN ('.getEntity('product').') ';
$sql.= ' AND p.fk_product_type = 0 '; // only products
$sql.= ' AND mc.active = 1 ';
$sql.= ' AND mc.batch_constraint IS NOT NULL ';
$sql.= ' AND (
  (mc.batch_constraint = "batch_status_0" AND p.tobatch != 0)
  OR
  (mc.batch_constraint = "batch_status_1" AND p.tobatch != 1)
  OR
  (mc.batch_constraint = "batch_status_2" AND p.tobatch != 2)
)';

foreach ($search as $key => $val) {
  if ($key === 'label' || $key === 'ref') {
    $sql.= natural_search("p.".$db->escape($key), $search[$key], 0);
  }
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
$sql = 'SELECT p.rowid, c.rowid as cat_rowid, mc.batch_constraint as batch_constraint '.$sql; // product will be fetched one by one later on.
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
$arrayofmassactions = array(
	'correct_status_batch'=>img_picto('', 'edit', 'class="pictofixedwidth"').$langs->trans('PickupCorrectDataCorrectStatusBatch'),
);
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

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

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $picto, 0, '', '', $limit, 0, 0, 1);

$selectedfields = '';
// $selectedfields.= $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '')); // This also change content of $arrayfields
if ($massactionbutton) {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
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
  if ($key !== 'label' && $key !== 'ref') {
    print '<td></td>';
    continue;
  }
  print '<td>';
  print $object->showInputField($val, $key, (isset($search[$key]) ? $search[$key] : ''), '', '', 'search_', $cssforfield.' maxwidth250', 1);
  print '</td>';
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

  $categorie = new Categorie($db);
  $categorie->fetch($obj->cat_rowid);

  $batch_constraint = $obj->batch_constraint;

  print '<tr class="oddeven">';

  // Action column
  if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
    print '<td class="nowrap center">';
    if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
      $selected = 0;
      if (in_array($obj->rowid, $arrayofselected)) {
        $selected = 1;
      }
      print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
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
    } else if ($key === 'cat') {
      if ($categorie->id > 0) {
        $allways = $categorie->get_all_ways();
        foreach ($allways as $way) {
          $label = array();
          foreach ($way as $parent_cat) {
            $label[] = $parent_cat->label;
          }
          $label = implode(' >> ', $label);
          print $label.' ';
        }
      }
    } else if ($key === 'all_cats') {
      print $form->showCategories($product->id, Categorie::TYPE_PRODUCT, 1);
    } else if ($key === 'wanted_status_batch') {
      print '<span class="warning">';
      switch ($batch_constraint) {
        case 'batch_status_0':
          print $langs->trans("ProductStatusNotOnBatch");
          break;
        case 'batch_status_1':
          print $langs->trans("ProductStatusOnBatch");
          break;
        case 'batch_status_2':
          print $langs->trans("ProductStatusOnSerial");
          break;
      }
      print '</span>';

      // also adding an hidden field for the correct_status_batch mass action.
      print '<input type="hidden" name="correct_status_batch_'.htmlspecialchars($product->id).'" value="'.htmlspecialchars($batch_constraint).'">';
    } else if (preg_match('/^p\.(\w+)$/', $key, $matches)) {
      $product_field = $matches[1];
      print $product->showOutputField($val, $product_field, $product->$product_field, '');
      // print htmlspecialchars($product->$product_field);
    }
    print '</td>';
  }

  // Action column
  if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
    print '<td class="nowrap center">';
    if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
      $selected = 0;
      if (in_array($obj->rowid, $arrayofselected)) {
        $selected = 1;
      }
      print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
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
