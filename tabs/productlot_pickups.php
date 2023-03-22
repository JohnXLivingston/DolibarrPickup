<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021-2023		John Livingston		<license@john-livingston.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       productlot_pickups.php
 *  \ingroup    productlot
 *  \brief      Tab for pickups on productlot
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
dol_include_once('/pickup/lib/pickups_tab.lib.php');

// Load translation files required by the page
$langs->loadLangs(array('pickup@pickup', 'stocks', 'other', 'products'));

// Get parameters
$id = GETPOST('id', 'int');
// $ref        = GETPOST('ref', 'alpha');
// $action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object = new Productlot($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array('productlotpickups')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
$object = new ProductLot($db) ;
if ($id > 0) {
	$object->fetch($id);
} else {
  accessforbidden();
}

if ($object->id <= 0) {
  accessforbidden();
}

$permissiontoread = $user->rights->produit->lire && $user->rights->pickup->read;

// Security check (enable the most restrictive one)
if (empty($conf->productbatch->enabled)) {
	accessforbidden('Module not enabled');
}
$socid = 0;
if ($user->socid > 0) { // Protection if external user
	//$socid = $user->socid;
	accessforbidden();
}
//$result = restrictedArea($user, 'productbatch');
if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$reshook = $hookmanager->executeHooks('doActions', array(), $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once
}


/*
 * View
 */

$form = new Form($db);

$help_url = '';
llxHeader('', $langs->trans('productlot'), $help_url);

$object->fetch_thirdparty();

$head = productlot_prepare_head($object);

print dol_get_fiche_head($head, 'pickups', $langs->trans('Pickups'), -1, $object->picto);

// Object card
// ------------------------------------------------------------
$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/productlot_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'batch');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

$pickups_tab_content = new PickupsTabContent($db, $conf);
$pickups_tab_content->printContent(
  "pbatches.batch_number = '".$db->escape($object->batch)."'",
	'JOIN '.MAIN_DB_PREFIX.'pickup_batch as pbatches ON pbatches.fk_pickupline = pl.rowid'
);

print '</div>';


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
