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

require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';

$langs->loadLangs(array("pickup@pickup", "products", "other"));

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

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border tableforfield" width="100%">';


$sql = 'SELECT pl.rowid as plid, p.rowid as pid ';
$sql.= ' FROM '.MAIN_DB_PREFIX.'pickup_pickup as p ';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'pickup_pickupline as pl ON pl.fk_pickup = p.rowid ';
$sql.= ' WHERE ';
$sql.= " pl.fk_product = '".$db->escape($object->id)."' ";
$sql.= $db->order('date_pickup,plid', 'DESC,DESC');
$result = $db->query($sql);

if ($result > 0) {
  
} else {
  dol_print_error($db);
}
$db->free($result);

print "</table>";

print '</div>';
print '<div style="clear:both"></div>';

dol_fiche_end();


// End of page
llxFooter();
$db->close();
