<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021-2022		John Livingston		<license@john-livingston.fr>
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

/**
 *   	\file       mobilecat_list.php
 *		\ingroup    pickup
 *		\brief      List page for mobilecat
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB','1');					// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER','1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC','1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN','1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION','1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION','1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK','1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL','1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK','1');				// Do not check style html tag into posted data
//if (! defined('NOIPCHECK'))                define('NOIPCHECK','1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU','1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML','1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX','1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN",'1');						// If this page is public (can be called outside logged session)
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT','auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE','aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN',1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("XFRAMEOPTIONS_ALLOWALL"))   define('XFRAMEOPTIONS_ALLOWALL',1);		// Do not add the HTTP header 'X-Frame-Options: SAMEORIGIN' but 'X-Frame-Options: ALLOWALL'

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

// require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
// require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
// require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// load pickup libraries
require_once __DIR__ . '/class/mobilecat.class.php';
dol_include_once('/categories/class/categorie.class.php');

// Load translation files required by the page
$langs->loadLangs(array("pickup@pickup","other"));

$action     = GETPOST('action', 'aZ09')?GETPOST('action', 'aZ09'):'view';				// The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha');											// The bulk action (combo box choice into lists)
// $show_files = GETPOST('show_files', 'int');												// Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha');												// Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha');												// We click on a Cancel button
$toselect   = GETPOST('toselect', 'array');												// Array of ids of elements selected into a list
$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'pickuplist';   // To manage different context of search
// $backtopage = GETPOST('backtopage', 'alpha');											// Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ');												// Option for the css output (always '' except when 'print')

$id = GETPOST('id', 'int'); // the line to edit (when action === 'editline')

// --------------------------------------------------------------------
// Note: in the following code, logic is regrouped in functions.
// They all takes explicit arguments, to make code clearer.
// Except for following variables, that are considered globals (that is the case for Dolibarr, so...):
// - $db
// - $user
// - $conf
// - $langs
// - $hookmanager

list ($page, $limit) = mobilecat_list_pagination();
list ($sortfield, $sortorder) = mobilecat_list_sort();

$object = new PickupMobileCat($db);
$extrafields = new ExtraFields($db);
$categstatic = new Categorie($db);

$hookmanager->initHooks(array('mobilecatlist'));     // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('mobilecat');	// Load $extrafields->attributes['mobilecat']

// Security check
list ($permissionedit) = mobilecat_list_security_check();

// Load $mobileforms: available values for mobilecat->form.
dol_include_once('/pickup/lib/mobile_forms.php');
$mobileforms = mobileListProductForms();

// Definition of fields for list
$arrayfields = mobilecat_list_array_fields($object, $extrafields);

// Sorting fields by position
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

/*
 * Actions
 */
mobilecat_list_check_actions($object, $cancel, $action, $massaction, $permissionedit);
mobilecat_list_handle_actions($object, $arrayfields, $action, $massaction, $permissionedit);

/*
* View
*/

list ($fulltree) = mobilecat_list_load_data($categstatic);
list($ids_to_edit, $is_edit_mode, $is_edit_multiple) = mobilecat_list_edit_vars($action, $fulltree, $id);

$help_url='';
$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("MobileCats"));

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
// if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
// foreach($search as $key => $val)
// {
//     if (is_array($search[$key]) && count($search[$key])) foreach($search[$key] as $skey) $param.='&search_'.$key.'[]='.urlencode($skey);
//     else $param.= '&search_'.$key.'='.urlencode($search[$key]);
// }
if ($optioncss != '')     $param.='&optioncss='.urlencode($optioncss);
// // Add $param from extra fields
// include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

mobilecat_list_print_barre_list(
	$title,
	count($fulltree), count($fulltree), $page, $limit,
	$sortfield, $sortorder,
	$param
);

mobilecat_list_print_open_form_and_button($is_edit_mode, $is_edit_multiple, $permissionedit);

mobilecat_list_print_open_table();
mobilecat_list_print_table_head($object, $arrayfields, $param, $sortfield, $sortorder);
mobilecat_list_print_table_content(
	$object, $arrayfields, $fulltree,
	$mobileforms,
	$ids_to_edit, $is_edit_mode, $is_edit_multiple, $permissionedit
);
mobilecat_list_print_close_table();

mobilecat_list_print_close_form_and_button($is_edit_mode, $is_edit_multiple, $permissionedit);

// End of page
llxFooter();

// Utilitary functions
// --------------------------------------------------------------------

/**
 * Returns pagination vars.
 */
function mobilecat_list_pagination() {
	// Note: no pagination on this page, always display all results.
	$page = 0;
	$limit = 0;
	return array($page, $limit);
}

/**
 * Returns sort variables.
 */
function mobilecat_list_sort() {
	// No search on this page.
	// Important note: if adding search or sort feature, you risk to break the edit mode (because of nested forms).
	$sortfield = '';
	$sortorder = '';
	return array($sortfield, $sortorder);
}

/**
 * Perform security checks
 */
function mobilecat_list_security_check() {
	global $conf, $user;

	$permissionedit = 0;
	if (empty($conf->pickup->enabled)) {
		accessforbidden('Module not enabled');
	}
	if ($user->societe_id > 0) {	// Protection if external user
		accessforbidden();
	}
	if (!$user->rights->pickup->configure) {
		accessforbidden();
	} else {
		$permissionedit = 1;
	}
	return array($permissionedit);
}

/**
 * Return fields definition for columns.
 */
function mobilecat_list_array_fields(&$object, &$extrafields) {
	$arrayfields=array();
	foreach($object->fields as $key => $val) {
		// If $val['visible']==0, then we never show the field
		if (empty($val['visible'])) {
			continue;
		}

		$arrayfields['t.'.$key]=array(
			'label'=>$val['label'],
			'checked'=>(($val['visible']<0)?0:1),
			'enabled'=>($val['enabled'] && ($val['visible'] != 3)),
			'position'=>$val['position'],
			'help'=>$val['help']
		);

	}

	// // Extra fields
	// if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0) {
	// 	foreach($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	// 	{
	// 		if (! empty($extrafields->attributes[$object->table_element]['list'][$key]))
	// 			$arrayfields["ef.".$key]=array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key]<0)?0:1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key])!=3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
	// 	}
	// }
	return $arrayfields;
}

/**
 * Get current action.
 * Check if ok.
 * Update variables if needed:
 * - if the user has not the right, the action is replaced by the default one
 * - if action was not confirmed, or cancelled, .... 
 * After this function, $action and $massaction will have correct values.
 */
function mobilecat_list_check_actions(&$object, &$cancel, &$action, &$massaction, $permissionedit) {
	global $hookmanager;

	if ($cancel) {
		$action='list'; $massaction='';
	}
	if (! GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
		$massaction='';
	}

	if (($action === 'editline' || $action === 'editall' || $action === 'update') && !$permissionedit) {
		$action = 'list';
		$massaction = '';
	}

	$parameters=array();
	$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);
	// Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	if (empty($reshook)) {
		// // Selection of new fields
		// Note: this template needs $action, $db, $conf, $object
		// include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

		// // Purge search criteria
		// if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') ||GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
		// {
		// 	foreach($object->fields as $key => $val)
		// 	{
		// 		$search[$key]='';
		// 	}
		// 	$toselect='';
		// 	$search_array_options=array();
		// }
		// if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		// 	|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))
		// {
		// 	$massaction='';     // Protection to avoid mass action if we force a new search during a mass action confirmation
		// }

		// // Mass actions
		// $objectclass='PickupMobileCat';
		// $objectlabel='PickupMobileCat';
		// $permtoread = $user->rights->pickup->read;
		// $permtodelete = $user->rights->pickup->delete;
		// $uploaddir = $conf->pickup->dir_output;
		// include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
	}
}

/**
 * Handle current action.
 */
function mobilecat_list_handle_actions(&$object, &$arrayfields, &$action, &$massaction, $permissionedit) {
	if (empty($action) || $action === 'list') {
		return;
	}
	if ($action === 'update') {
		mobilecat_list_handle_action_update($object, $arrayfields, $action, $massaction, $permissionedit);
	}
}

function mobilecat_list_handle_action_update(&$object, &$arrayfields, &$action, &$massaction, $permissionedit) {
	global $db;

	if (empty($permissionedit)) {
		// should not happen, mobilecat_list_check_actions should have checked.
		// But just in case...
		return;
	}

	// The active field is mandatory. Otherwise we will do messy things with db.
	// So we just check that there is nothing that disabled this field.
	$active_def = $arrayfields['t.active'];
	if (empty($active_def) || empty($active_def['checked']) || empty($active_def['enabled'])) {
		dol_print_error($db, 'It seems the «active» field is not enabled. Should not happen.');
		exit;
	}

	$line_edit_prefixes = GETPOST('edit_line', 'array',	2); // 2 = only POST.
	foreach ($line_edit_prefixes as $line_edit_prefix) {
		if (!preg_match('/^line_(\d+)_$/', $line_edit_prefix, $matches)) {
			continue;
		}
		$cat_id = $matches[1];
		mobilecat_list_handle_action_update_one_line($object, $arrayfields, $line_edit_prefix, $cat_id);
	}
}

function mobilecat_list_handle_action_update_one_line(&$object, &$arrayfields, $line_edit_prefix, $cat_id) {
	global $db, $user;

	$cat = new Categorie($db);
	if ($cat->fetch($cat_id) <= 0) {
		// Error and not found => return
		dol_syslog(__FUNCTION__." Error: cant find the category ".$cat_id.". ".$db->lasterror(), LOG_ERR);
		return;
	}
	$mobilecat = new PickupMobileCat($db);
	if ($mobilecat->fetchByCategory($cat->id) < 0) {
		// Here it is ok if there is no mobilecat.
		dol_syslog(__FUNCTION__." Error: error when fetching the mobilecat for category ".$cat_id.". ".$db->lasterror(), LOG_ERR);
		return;
	}

	$active = GETPOST($line_edit_prefix.'active', 'int') ? 1 : 0;
	if (!$active && !$mobilecat->id) {
		// disabled, and mobilecat does not exist for now => nothing to do
		return;
	}
	
	$mobilecat->active = $active;
	if (empty($mobilecat->fk_category)) {
		$mobilecat->fk_category = $cat_id;
	}
	foreach($object->fields as $key => $val) {
		if (empty($arrayfields['t.'.$key]['checked'])) {
			// The field is hidden. Ignore it.
			continue;
		}
		if ($key === 'fk_category') {
			continue;
		}
		if (!empty($val['noteditable'])) {
			continue;
		}

		if (in_array($key, array('notes'))) {
			$notes = GETPOST($line_edit_prefix.'notes', 'nohtml');
			$mobilecat->notes = empty($notes) || $notes === '' ? null : $notes;
			continue;
		}
		if (in_array($key, array('form'))) {
			$form_value = GETPOST($line_edit_prefix.'form', 'alpha');
			if (empty($form_value) || array_key_exists($form_value, $val['arrayofkeyval'])) {
				$mobilecat->form = $form_value;
			}
			continue;
		}
	}

	if (!$mobilecat->id) {
		if ($mobilecat->create($user) < 0) {
			dol_print_error($db);
			exit;
		}
	} else {
		if ($mobilecat->update($user) < 0) {
			dol_print_error($db);
			exit;
		}
	}
}

/**
 * Load needed data.
 */
function mobilecat_list_load_data($categstatic) {
	$fulltree = $categstatic->get_full_arbo('product');
	return array($fulltree);
}

/**
 * Returns variables that describe the current edit mode (if relevant).
 */
function mobilecat_list_edit_vars($action, $fulltree, $id) {
	$ids_to_edit = [];
	$is_edit_multiple = false;
	$is_edit_mode = false;
	if ($action === 'editline' && !empty($id)) {
		$ids_to_edit[intval($id)] = true;
		$is_edit_mode = true;
	} elseif ($action === 'editall') {
		$is_edit_multiple = true;
		foreach ($fulltree as $key => $val) {
			$ids_to_edit[intval($val['id'])] = true;
			$is_edit_mode = true; // at list one id => true
		}
	}
	return array($ids_to_edit, $is_edit_mode, $is_edit_multiple);
}

/**
 * Print the title bar
 */
function mobilecat_list_print_barre_list(
	$title,
	$num, $nbtotalofrecords, $page, $limit,
	$sortfield, $sortorder,
	$param
) {
	print_barre_liste(
		$title,
		$page,
		$_SERVER["PHP_SELF"],
		$param,
		$sortfield,
		$sortorder,
		'',
		$num,
		$nbtotalofrecords,
		'category',
		0,
		'',
		'',
		$limit
	);
}

/**
 * Print the <form> tag if needed.
 * Print also the top buttons.
 */
function mobilecat_list_print_open_form_and_button($is_edit_mode, $is_edit_multiple, $permissionedit) {
	global $langs;

	if ($is_edit_mode) {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="update">';
		if ($is_edit_multiple) {
			print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		}
	} elseif ($permissionedit) {
		print '<div class="right">';
		print '<a class="button" href="'.$_SERVER["PHP_SELF"].'?action=editall">';
		print $langs->trans('PickupModifyAllCat');
		print '</a>';
		print '</div>';
	}
}

/**
 * Close the <form> tag if needed.
 * Print also the bottom buttons.
 */
function mobilecat_list_print_close_form_and_button($is_edit_mode, $is_edit_multiple, $permissionedit) {
	global $langs;

	if ($is_edit_mode) {
		if ($is_edit_multiple) {
			print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		}
		print '</form>';
	}
}

/**
 * Print the beginning of the table where content will be.
 */
function mobilecat_list_print_open_table() {
	print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="tagtable liste">'."\n";
}

function mobilecat_list_print_close_table() {
	print '</table>';
	print '</div>';
}

/**
 * Print the table header line
 */
function mobilecat_list_print_table_head(
	&$object, &$arrayfields,
	$param, $sortfield, $sortorder
) {
	global $langs;

	print '<tr class="liste_titre">';
	foreach($object->fields as $key => $val) {
		$cssforfield=(empty($val['css'])?'':$val['css']);

		if ($key == 'status') {
			$cssforfield.= ($cssforfield?' ':'').'center';
		} elseif (in_array($val['type'], array('date','datetime','timestamp'))) {
			$cssforfield.=($cssforfield?' ':'').'center';
		}	elseif (in_array($val['type'], array('timestamp'))) {
			$cssforfield.=($cssforfield?' ':'').'nowrap';
		} elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price'))) {
			$cssforfield.=($cssforfield?' ':'').'right';
		}

		if (! empty($arrayfields['t.'.$key]['checked'])) {
			print getTitleFieldOfList(
				$arrayfields['t.'.$key]['label'], // name
				0, // thead 0=To use with standard table format
				$_SERVER['PHP_SELF'], // Url used when we click on sort picto
				'', // 't.'.$key, // Field to use for new sorting. Empty if this field is not sortable. Example "t.abc" or "t.abc,t.def"
				'', // $begin
				$param, // Add more parameters on sort url links ("" by default)
				($cssforfield?'class="'.$cssforfield.'"':''), // Add more attributes on th
				$sortfield, // Current field used to sort (Ex: 'd.datep,d.id')
				$sortorder, // Current sort order (Ex: 'asc,desc')
				($cssforfield?$cssforfield.' ':''), // Prefix for css.
				1, // 1=Disable sort link
				$arrayfields['t.'.$key]['help'] // Tooltip
			)."\n";
		}
	}
	// // Extra fields
	// include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// // Hook fields
	// $parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	// $reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
	// print $hookmanager->resPrint;
	// Action column
	print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";

	print '</tr>'."\n";
}

/**
 * Print the table content
 */
function mobilecat_list_print_table_content (
	&$object, &$arrayfields, &$fulltree,
	&$mobileforms,
	$ids_to_edit, $is_edit_mode, $is_edit_multiple, $permissionedit
) {
	global $langs, $db;

	foreach ($fulltree as $key => $val) {
		$cat = new Categorie($db);
		$cat->fetch($val['id']);
		$allways = $cat->get_all_ways();

		$mobilecat = new PickupMobileCat($db);
		if ($mobilecat->fetchByCategory($cat->id) < 0) {
			dol_print_error($db);
			exit;
		}
		if (empty($mobilecat->id)) {
			// Normal case: tags that were never activated for mobile usage dont have any mobilecat
			$mobilecat = null; // this makes following code clearer.
		}

		foreach ($allways as $way) {
			$label = array();
			foreach ($way as $parent_cat) {
				$label[] = $parent_cat->label;
			}
			$label = implode(' &gt;&gt; ', $label);

			mobilecat_list_print_table_content_line(
				$object, $arrayfields, $fulltree,
				$mobileforms,
				$ids_to_edit, $is_edit_mode, $is_edit_multiple, $permissionedit,
				$cat, $mobilecat, $label
			);
		}
	}
}

/**
 * Print one line of the table content
 */
function mobilecat_list_print_table_content_line (
	&$object, &$arrayfields, &$fulltree,
	&$mobileforms,
	$ids_to_edit, $is_edit_mode, $is_edit_multiple, $permissionedit,
	&$cat, &$mobilecat, $label
) {
	global $db, $langs;

	$line_edit_prefix = 'line_'.$cat->id.'_'; // must be line_NUMBER_. Otherwise mobilecat_list_handle_action_update will not accept.
	$is_line_edited = $permissionedit && array_key_exists(intval($cat->id), $ids_to_edit) && $ids_to_edit[intval($cat->id)] === true;

	print '<tr class="oddeven">';

	// Two main case:
	// - mobilecat was found: display informations
	// - mobilecat was not found: only display the fk_category field ($label)

	foreach($object->fields as $key => $val) {
		mobilecat_list_print_table_content_line_field(
			$object, $arrayfields, $fulltree,
			$mobileforms,
			$ids_to_edit, $is_edit_mode, $is_edit_multiple, $permissionedit,
			$cat, $mobilecat, $label,
			$line_edit_prefix, $is_line_edited,
			$key, $val
		);
	}
	// // Extra fields
	// include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
	// // Fields from hook
	// $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
	// $reshook=$hookmanager->executeHooks('printFieldListValue', $parameters, $object);    // Note that $action and $object may have been modified by hook
	// print $hookmanager->resPrint;
	// if (!empty($conf->global->PICKUP_USE_DEEE)) {
	// 	// DEEE qty column
	// 	print '<td class="nowrap right">';
	// 	$qtyTotals = $object->computeQtyTotals();
	// 	print price($qtyTotals['deee_qty'], 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
	// 	print  ' / ';
	// 	print price($qtyTotals['qty'], 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
	// 	print '</td>';
	// }
	// Action column
	print '<td class="nowrap center">';
	// if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	// {
	// 	$selected=0;
	// 	if (in_array($obj->rowid, $arrayofselected)) $selected=1;
	// 	print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
	// }
	?><div id="line_<?php echo $cat->id; ?>"></div><?php
	if ($permissionedit) {
		if ($is_line_edited) {
			// line is in edit mode. Displaying save/cancel button (unless it is a multiple edit)
			if (!$is_edit_multiple) {
				?>
				<input type="submit" class="button buttongen marginbottomonly" id="savelinebutton marginbottomonly" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
				<input type="submit" class="button buttongen marginbottomonly" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
				<?php
			}
		} else {
			?><a class="editfielda reposition" href="<?php print $_SERVER["PHP_SELF"].'?id='.$cat->id.'&amp;action=editline&amp;#line_'.$cat->id; ?>">
				<?php print img_edit(); ?>
			</a><?php
		}
	}
	print '</td>';
	// if (! $i) $totalarray['nbfield']++;

	print '</tr>';
}

/**
 * Displays one column corresponding to a field.
 */
function mobilecat_list_print_table_content_line_field (
	&$object, &$arrayfields, &$fulltree,
	&$mobileforms,
	$ids_to_edit, $is_edit_mode, $is_edit_multiple, $permissionedit,
	&$cat, &$mobilecat, $label,
	$line_edit_prefix, $is_line_edited,
	$key, $val
) {
	global $db, $langs;

	if (empty($arrayfields['t.'.$key]['checked'])) {
		// Hidden field.
		return;
	}

	$cssforfield=(empty($val['css'])?'':$val['css']);
	if (in_array($val['type'], array('date','datetime','timestamp'))) {
		$cssforfield.=($cssforfield?' ':'').'center';
	} elseif ($key == 'status') {
		$cssforfield.=($cssforfield?' ':'').'center';
	}

	if (in_array($val['type'], array('timestamp'))) {
		$cssforfield.=($cssforfield?' ':'').'nowrap';
	}	elseif ($key == 'ref') {
		$cssforfield.=($cssforfield?' ':'').'nowrap';
	}

	if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $key != 'status') {
		$cssforfield.=($cssforfield?' ':'').'right';
	}


	print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
	if ($key === 'fk_category') {
		// print '<span class="noborderoncategories" '.($cat->color?' style="background: #'.$cat->color.';"':' style="background: #aaa"').'>';
		print '<a href="'.DOL_URL_ROOT.'/categories/viewcat.php?id='.urlencode($cat->id).'&type=product">';
		print $label;
		print '</a>';
		// print '</span>';
		if ($is_line_edited) {
			// Adding an hidden field, so we can know which lines are submited (when editing multiple)
			print '<input type="hidden" name="edit_line[]" value="'.htmlspecialchars($line_edit_prefix).'">';
		}
	} elseif ($is_line_edited && empty($val['noteditable'])) {
		// Edit mode.
		print $object->showInputField(
			null,
			$key,
			GETPOSTISSET($line_edit_prefix.$key)
				? GETPOST($line_edit_prefix.$key, 'alpha')
				: (
					empty($mobilecat)
						? null
						: $mobilecat->$key
					),
			'',
			'',
			$line_edit_prefix
		);
	} elseif (!empty($mobilecat)) {
		if ($key == 'form') {
			if (empty($mobilecat->form)) {
				print '-';
			} else if (array_key_exists($mobilecat->form, $mobileforms)) {
				print htmlspecialchars($mobileforms[$mobilecat->form]);
			} else {
				print htmlspecialchars($mobilecat->form);
			}
		} elseif ($key == 'status') print $mobilecat->getLibStatut(5);
		elseif (in_array($val['type'], array('date','datetime','timestamp'))) print $mobilecat->showOutputField($val, $key, $db->jdate($obj->$key), '');
		else print $mobilecat->showOutputField($val, $key, $mobilecat->$key, '');
	}
	print '</td>';
	// if (! $i) $totalarray['nbfield']++;
	// if (! empty($val['isameasure']))
	// {
	// 	if (! $i) $totalarray['pos'][$totalarray['nbfield']]='t.'.$key;
	// 	$totalarray['val']['t.'.$key] += $obj->$key;
	// }
}

$db->close();
