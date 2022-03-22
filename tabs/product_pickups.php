<?php

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

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pickup/class/pickup.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pickup/class/pickupline.class.php';

$langs->loadLangs(array("pickup@pickup", "products", "other", "stocks"));

$id=GETPOST('id', 'int');
$ref=GETPOST('ref','alpha');
$object = new Product($db) ;
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
}

if ($object->id > 0) {
	if ($object->type == $object::TYPE_PRODUCT) {
		restrictedArea($user, 'produit', $object->id, 'product&product', '', '');
	} else {
    accessforbidden();
  }
} else {
  accessforbidden();
}

if (!$user->rights->pickup->read) {
  accessforbidden();
}

/*
 *	Actions
 */

/*
 * View
 */

llxHeader("", "", $langs->trans("Pickups"));


$head = product_prepare_head($object, $user);
$titre = $langs->trans("CardProduct".$product->type);
$picto = ($product->type == Product::TYPE_SERVICE ? 'service' : 'product');
dol_fiche_head($head, 'pickups', $titre, -1, $picto);

$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $product, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if ($reshook < 0) {
  setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
$shownav = 1;
if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) {
  $shownav = 0;
}
dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

function print_pickup_title($object, $key) {
  $val = $object->fields[$key];
  $cssforfield=(empty($val['css'])?'':$val['css']);
  if ($key == 'status') $cssforfield.=($cssforfield?' ':'').'center';
  elseif (in_array($val['type'], array('date','datetime','timestamp'))) $cssforfield.=($cssforfield?' ':'').'center';
  elseif (in_array($val['type'], array('timestamp'))) $cssforfield.=($cssforfield?' ':'').'nowrap';
  elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price'))) $cssforfield.=($cssforfield?' ':'').'right';
  print getTitleFieldOfList($val['label'], 0, '', '', '', '', ($cssforfield?'class="'.$cssforfield.'"':''), '', '', ($cssforfield?$cssforfield.' ':''), 1)."\n";
}
function print_pickup_line($object, $key) {
  global $db;

  $val = $object->fields[$key];
  $cssforfield=(empty($val['css'])?'':$val['css']);
  if (in_array($val['type'], array('date','datetime','timestamp'))) $cssforfield.=($cssforfield?' ':'').'center';
  elseif ($key == 'status') $cssforfield.=($cssforfield?' ':'').'center';

  if (in_array($val['type'], array('timestamp'))) $cssforfield.=($cssforfield?' ':'').'nowrap';
  elseif ($key == 'ref') $cssforfield.=($cssforfield?' ':'').'nowrap';

  if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $key != 'status') $cssforfield.=($cssforfield?' ':'').'right';

  print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
  if ($key == 'status') print $object->getLibStatut(5);
  elseif (in_array($val['type'], array('date','datetime','timestamp'))) print $object->showOutputField($val, $key, $db->jdate($object->$key), '');
  else print $object->showOutputField($val, $key, $object->$key, '');
  print '</td>';
  // if (! $i) $totalarray['nbfield']++;
  // if (! empty($val['isameasure'])) {
  //   if (! $i) $totalarray['pos'][$totalarray['nbfield']]='t.'.$key;
  //   $totalarray['val']['t.'.$key] += $object->$key;
  // }
}

$pickup = new Pickup($db);
$pickupline = new PickupLine($db);

$columns = array(
  array('type' => 'pickup', 'col' => 'ref'),
  array('type' => 'pickup', 'col' => 'label'),
  array('type' => 'pickup', 'col' => 'fk_soc'),
  array('type' => 'pickup', 'col' => 'date_pickup'),
  array('type' => 'pickupline', 'col' => 'batch', 'hide' => !$object->hasbatch()),
  array('type' => 'pickupline', 'col' => 'fk_stock_movement'),
  array('type' => 'pickupline', 'col' => 'qty'),
  array(
    'type' => 'pickupline',
    'label' => 'Weight',
    'css' => 'right nowrap',
    'func' => function ($pickuplinestatic) {
      return ($pickuplinestatic->weight * $pickuplinestatic->qty) . ' ' . measuringUnitString(0, "weight", $pickuplinestatic->weight_units);
    },
    'hide' => empty($conf->global->PICKUP_UNITS_WEIGHT)
  ),
  array(
    'type' => 'pickupline',
    'label' => 'Length',
    'css' => 'right nowrap',
    'func' => function ($pickuplinestatic) {
      return ($pickuplinestatic->length * $pickuplinestatic->qty) . ' ' . measuringUnitString(0, "size", $pickuplinestatic->length_units);
    },
    'hide' => empty($conf->global->PICKUP_UNITS_LENGTH)
  ),
  array(
    'type' => 'pickupline',
    'label' => 'Surface',
    'css' => 'right nowrap',
    'func' => function ($pickuplinestatic) {
      return ($pickuplinestatic->surface * $pickuplinestatic->qty) . ' ' . measuringUnitString(0, "surface", $pickuplinestatic->surface_units);
    },
    'hide' => empty($conf->global->PICKUP_UNITS_SURFACE)
  ),
  array(
    'type' => 'pickupline',
    'label' => 'Volume',
    'css' => 'right nowrap',
    'func' => function ($pickuplinestatic) {
      return ($pickuplinestatic->volume * $pickuplinestatic->qty) . ' ' . measuringUnitString(0, "volume", $pickuplinestatic->volume_units);
    },
    'hide' => empty($conf->global->PICKUP_UNITS_VOLUME)
  ),
  array('type' => 'pickup', 'col' => 'status'),
);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
foreach ($columns as $def) {
  if ($def['hide']) { continue; }
  if (empty($def['col'])) {
    $cssforfield = $def['css'];
    print getTitleFieldOfList($def['label'], 0, '', '', '', '', ($cssforfield?'class="'.$cssforfield.'"':''), '', '', ($cssforfield?$cssforfield.' ':''), 1)."\n";
    continue;
  }
  print_pickup_title($def['type'] === 'pickupline' ? $pickupline : $pickup, $def['col']);
}
print '</tr>';

$sql = 'SELECT p.rowid as p_rowid, pl.rowid as pl_rowid, ';
foreach($pickup->fields as $key => $val) {
	$sql.='p.'.$key.' as p_'.$key.', ';
}
foreach($pickupline->fields as $key => $val) {
	$sql.='pl.'.$key.' as pl_'.$key.', ';
}
$sql =preg_replace('/,\s*$/', '', $sql);
$sql.= ' FROM '.MAIN_DB_PREFIX.'pickup_pickup as p ';
$sql.= ' JOIN '.MAIN_DB_PREFIX.'pickup_pickupline as pl ON pl.fk_pickup = p.rowid ';
$sql.= ' WHERE ';
$sql.= " pl.fk_product = '".$db->escape($object->id)."' ";
$sql.= $db->order('date_pickup,pl.position,pl.rowid', 'DESC,DESC,DESC');
$result = $db->query($sql);

if ($result > 0) {
  while ($data = $db->fetch_object($result)) {
    $pickupstatic = new Pickup($db);
    $pickupstatic->id = $data->p_rowid;
    foreach($pickupstatic->fields as $key => $val) {
      $sql_key = 'p_'.$key;
      if (property_exists($data, $sql_key)) $pickupstatic->$key = $data->$sql_key;
    }
    $pickuplinestatic = new PickupLine($db);
    $pickuplinestatic->id = $data->p_rowid;
    foreach($pickuplinestatic->fields as $key => $val) {
      $sql_key = 'pl_'.$key;
      if (property_exists($data, $sql_key)) $pickuplinestatic->$key = $data->$sql_key;
    }

    print '<tr class="oddeven">';
    foreach ($columns as $def) {
      if ($def['hide']) { continue; }
      $line_obj = $def['type'] === 'pickupline' ? $pickuplinestatic : $pickupstatic;
      if (empty($def['col'])) {
        $cssforfield = $def['css'];
        $content = $def['func']($line_obj);
        print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
        print $content;
        print '</td>';
        continue;
      }
      print_pickup_line($line_obj, $def['col']);
    }
    print '</tr>';
  }
} else {
  dol_print_error($db);
}
$db->free($result);

print "</table>";

print '</div>';
print '</div>';
print '<div style="clear:both"></div>';

dol_fiche_end(-1);


// End of page
llxFooter();
$db->close();
