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

// for other modules
//dol_include_once('/othermodule/class/otherobject.class.php');
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
$backtopage = GETPOST('backtopage', 'alpha');											// Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ');												// Option for the css output (always '' except when 'print')

$id = GETPOST('id', 'int');

// Note: no pagination on this page, always display all results.
$page = 0;
$limit = 0;
// No search neither.
$sortfield = '';
$sortorder = '';


$object = new PickupMobileCat($db);
$extrafields = new ExtraFields($db);
$categstatic = new Categorie($db);
$hookmanager->initHooks(array('mobilecatlist'));     // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('mobilecat');	// Load $extrafields->attributes['mobilecat']

// Security check
if (empty($conf->pickup->enabled)) accessforbidden('Module not enabled');
$socid=0;
if ($user->societe_id > 0)	// Protection if external user
{
	//$socid = $user->societe_id;
	accessforbidden();
}
if (!$user->rights->pickup->configure) {
  accessforbidden();
}

dol_include_once('/pickup/lib/mobile_forms.php');
$mobileforms = mobileListProductForms();

// Definition of fields for list
$arrayfields=array();
foreach($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (! empty($val['visible'])) {
		$arrayfields['t.'.$key]=array('label'=>$val['label'], 'checked'=>(($val['visible']<0)?0:1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
	}
}
// // Extra fields
// if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0) {
// 	foreach($extrafields->attributes[$object->table_element]['label'] as $key => $val)
// 	{
// 		if (! empty($extrafields->attributes[$object->table_element]['list'][$key]))
// 			$arrayfields["ef.".$key]=array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key]<0)?0:1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key])!=3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
// 	}
// }
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	// // Selection of new fields
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


/*
 * View
 */

$help_url='';
$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("MobileCats"));

$fulltree = $categstatic->get_full_arbo('product');

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

$num = count($fulltree);
$nbtotalofrecords = $num;
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'category', 0, '', '', $limit);

print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach($object->fields as $key => $val)
{
	$cssforfield=(empty($val['css'])?'':$val['css']);
	if ($key == 'status') $cssforfield.=($cssforfield?' ':'').'center';
	elseif (in_array($val['type'], array('date','datetime','timestamp'))) $cssforfield.=($cssforfield?' ':'').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield.=($cssforfield?' ':'').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price'))) $cssforfield.=($cssforfield?' ':'').'right';
	if (! empty($arrayfields['t.'.$key]['checked']))
	{
		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield?'class="'.$cssforfield.'"':''), $sortfield, $sortorder, ($cssforfield?$cssforfield.' ':''), 1)."\n";
	}
}
// // Extra fields
// include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// // Hook fields
// $parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
// $reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
// print $hookmanager->resPrint;
// // Action column
// print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
print '</tr>'."\n";

foreach ($fulltree as $key => $val) {
	$cat = new Categorie($db);
	$cat->fetch($val['id']);
	$allways = $cat->get_all_ways();

	// $mobilecats = $object->fetchAll('', '', 1, 0, ['customsql' => 't.fk_category=\''.$object->db->escape($cat->id)]).'\'';
	// if ($mobilecats < 0)
	// {
	// 	dol_print_error($db);
	// 	exit;
	// }
	// $mobilecat = count($mobilecats) > 0 ? $mobilecats[0] : 0;
	
	$mobilecat = new PickupMobileCat($db);
	if ($mobilecat->fetchByCategory($cat->id) < 0)
	{
		dol_print_error($db);
		exit;
	}
	if (empty($mobilecat->id)) {
		$mobilecat = null; // this makes following code clearer.
	}

	foreach ($allways as $way) {
		$label = array();
		foreach ($way as $parent_cat)
		{
			$label[] = $parent_cat->label;
		}
		$label = implode(' &gt;&gt; ', $label);

		print '<tr class="oddeven">';

		// Two main case:
		// - mobilecat was found: display informations
		// - mobilecat was not found: only display the fk_category field ($label)

		foreach($object->fields as $key => $val) {
			$cssforfield=(empty($val['css'])?'':$val['css']);
			if (in_array($val['type'], array('date','datetime','timestamp')))	$cssforfield.=($cssforfield?' ':'').'center';
			elseif ($key == 'status') $cssforfield.=($cssforfield?' ':'').'center';

			if (in_array($val['type'], array('timestamp'))) $cssforfield.=($cssforfield?' ':'').'nowrap';
			elseif ($key == 'ref') $cssforfield.=($cssforfield?' ':'').'nowrap';

			if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $key != 'status') $cssforfield.=($cssforfield?' ':'').'right';

			if (! empty($arrayfields['t.'.$key]['checked'])) {
				print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
				if ($key === 'fk_category') {
					// print '<span class="noborderoncategories" '.($cat->color?' style="background: #'.$cat->color.';"':' style="background: #aaa"').'>';
					print '<a href="'.DOL_URL_ROOT.'/custom/pickup/tabs/mobilecat.php?id='.$cat->id.'&backto=mobilecat_list">';
					print $label;
					print '</a>';
					// print '</span>';
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
		// // Action column
		// print '<td class="nowrap center">';
		// if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		// {
		// 	$selected=0;
		// 	if (in_array($obj->rowid, $arrayofselected)) $selected=1;
		// 	print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
		// }
		// print '</td>';
		// if (! $i) $totalarray['nbfield']++;

		print '</tr>';
	}
}

print '</table>';
print '</div>';

// End of page
llxFooter();
$db->close();
