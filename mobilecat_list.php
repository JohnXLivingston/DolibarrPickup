<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021-2022		John Livingston		<license@john-livingston.fr>
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


require_once DOL_DOCUMENT_ROOT.'/custom/pickup/lib/mobile_forms.php';

$mobileforms = mobileListProductForms();

$help_url='';
$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("MobileCats"));

$fulltree = $categstatic->get_full_arbo('product');

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

foreach ($fulltree as $key => $val)
{
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

	foreach ($allways as $way)
	{
		$label = array();
		foreach ($way as $parent_cat)
		{
			$label[] = $parent_cat->label;
		}
		$label = implode(' &gt;&gt; ', $label);

		print '<tr class="oddeven">';
		print '<td>';
		// print '<span class="noborderoncategories" '.($cat->color?' style="background: #'.$cat->color.';"':' style="background: #aaa"').'>';
		print '<a href="'.DOL_URL_ROOT.'/custom/pickup/tabs/mobilecat.php?id='.$cat->id.'&backto=mobilecat_list">';
		print $label;
		print '</a>';
		// print '</span>';
		print '</td>';
		print '<td><input type="checkbox" disabled="disabled" '.($mobilecat->id && $mobilecat->active ? 'checked="checked"' : '').'></td>';
		print '<td>';
		if ($mobilecat->id)
		{
			if (!$mobilecat->active)
			{
				print '<span style="color: grey">';
			}
			if (empty($mobilecat->form)) {
				print '-';
			} else if (array_key_exists($mobilecat->form, $mobileforms)) {
				print htmlspecialchars($mobileforms[$mobilecat->form]);
			} else {
			  print htmlspecialchars($mobilecat->form);
			}
			if (!$mobilecat->active)
			{
				print '</span>';
			}
		}
		print '</td>';
		print '</tr>';
	}
}

print '</table>';
print '</div>';

// End of page
llxFooter();
$db->close();
