<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021		Jonathan Dollé		<license@jonathandolle.fr>
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
 *  \file       dolinputcat_document.php
 *  \ingroup    pickup
 *  \brief      Tab for documents linked to Dolinputcat
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

accessforbidden();

// require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
// require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
// require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
// require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
// dol_include_once('/pickup/class/dolinputcat.class.php');
// dol_include_once('/pickup/lib/pickup_dolinputcat.lib.php');

// // Load translation files required by the page
// $langs->loadLangs(array("pickup@pickup","companies","other","mails"));


// $action=GETPOST('action', 'aZ09');
// $confirm=GETPOST('confirm');
// $id=(GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int'));
// $ref = GETPOST('ref', 'alpha');

// // Security check - Protection if external user
// //if ($user->societe_id > 0) access_forbidden();
// //if ($user->societe_id > 0) $socid = $user->societe_id;
// //$result = restrictedArea($user, 'pickup', $id);

// // Get parameters
// $sortfield = GETPOST("sortfield", 'alpha');
// $sortorder = GETPOST("sortorder", 'alpha');
// $page = GETPOST("page", 'int');
// if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
// $offset = $conf->liste_limit * $page;
// $pageprev = $page - 1;
// $pagenext = $page + 1;
// if (! $sortorder) $sortorder="ASC";
// if (! $sortfield) $sortfield="name";
// //if (! $sortfield) $sortfield="position_name";

// // Initialize technical objects
// $object=new Dolinputcat($db);
// $extrafields = new ExtraFields($db);
// $diroutputmassaction=$conf->pickup->dir_output . '/temp/massgeneration/'.$user->id;
// $hookmanager->initHooks(array('dolinputcatdocument','globalcard'));     // Note that conf->hooks_modules contains array
// // Fetch optionals attributes and labels
// $extralabels = $extrafields->fetch_name_optionals_label('dolinputcat');

// // Load object
// include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// //if ($id > 0 || ! empty($ref)) $upload_dir = $conf->pickup->multidir_output[$object->entity?$object->entity:1] . "/dolinputcat/" . dol_sanitizeFileName($object->id);
// if ($id > 0 || ! empty($ref)) $upload_dir = $conf->pickup->multidir_output[$object->entity?$object->entity:1] . "/dolinputcat/" . dol_sanitizeFileName($object->ref);


// /*
//  * Actions
//  */

// include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


// /*
//  * View
//  */

// $form = new Form($db);

// $title=$langs->trans("Dolinputcat").' - '.$langs->trans("Files");
// $help_url='';
// //$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
// llxHeader('', $title, $help_url);

// if ($object->id)
// {
// 	/*
// 	 * Show tabs
// 	 */
// 	$head = dolinputcatPrepareHead($object);

// 	dol_fiche_head($head, 'document', $langs->trans("Dolinputcat"), -1, $object->picto);


// 	// Build file list
// 	$filearray=dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC), 1);
// 	$totalsize=0;
// 	foreach($filearray as $key => $file)
// 	{
// 		$totalsize+=$file['size'];
// 	}

// 	// Object card
// 	// ------------------------------------------------------------
// 	$linkback = '<a href="' .dol_buildpath('/pickup/dolinputcat_list.php', 1) . '?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

// 	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

//     print '<div class="fichecenter">';

//     print '<div class="underbanner clearboth"></div>';
// 	print '<table class="border centpercent">';

// 	// Number of files
// 	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

// 	// Total size
// 	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

// 	print '</table>';

// 	print '</div>';

// 	dol_fiche_end();

// 	$modulepart = 'pickup';
// 	//$permission = $user->rights->pickup->create;
// 	$permission = 1;
// 	//$permtoedit = $user->rights->pickup->create;
// 	$permtoedit = 1;
// 	$param = '&id=' . $object->id;

// 	//$relativepathwithnofile='dolinputcat/' . dol_sanitizeFileName($object->id).'/';
// 	$relativepathwithnofile='dolinputcat/' . dol_sanitizeFileName($object->ref).'/';

// 	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
// }
// else
// {
// 	accessforbidden('', 0, 1);
// }

// // End of page
// llxFooter();
// $db->close();
