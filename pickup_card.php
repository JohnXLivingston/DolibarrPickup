<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       pickup_card.php
 *		\ingroup    pickup
 *		\brief      Page to create/edit/view pickup
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
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU','1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML','1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX','1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN",'1');						// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK','1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT','auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE','aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN',1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP','none');					// Disable all Content Security Policies


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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php'; // for measuringUnitString
dol_include_once('/pickup/class/pickup.class.php');
dol_include_once('/pickup/class/pickupline.class.php');
dol_include_once('/pickup/lib/pickup_pickup.lib.php');


// Load translation files required by the page
$langs->loadLangs(array("pickup@pickup","other"));

// Get parameters
$id			= GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action		= GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'pickupcard';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object=new Pickup($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction=$conf->pickup->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('pickupcard','globalcard'));     // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
$extrafields->fetch_name_optionals_label('product');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all", 'alpha'));
$search=array();
foreach($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha')) $search[$key]=GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action='view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once.

// Security check - Protection if external user
if ($user->societe_id > 0) access_forbidden();
//if ($user->societe_id > 0) $socid = $user->societe_id;
$isdraft = (($object->status == Pickup::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'pickup', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);

$permissionnote = 0;
$permissiondellink = 0;
$permissionedit = 0;
$permissiontoadd = 0;

if ($object->canEditPickup()) {
	$permissionnote = 1;	// Used by the include of actions_setnotes.inc.php
	$permissiondellink = 1;	// Used by the include of actions_dellink.inc.php
	$permissionedit = 1; // Used by the include of actions_lineupdown.inc.php
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_builddoc.inc.php
}

if ($id > 0 || ! empty($ref)) $upload_dir = $conf->pickup->multidir_output[$object->entity?$object->entity:1] . "/pickup/" . dol_sanitizeFileName($object->ref);


/*
 * Actions
 */

$parameters=array(
	'permissiontoadd' => $permissiontoadd,
	'permissionedit' => $permissionedit,
);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$langs->load('errors');
	$error = 0;

	$permissiontodelete = $user->rights->pickup->delete;
	$backurlforlist = dol_buildpath('/pickup/pickup_list.php', 1);
	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/pickup/pickup_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
		}
	}
	$triggermodname = 'PICKUP_PICKUP_MODIFY';	// Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, delete or clone
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions for pdf and attachments
	// Dirty Fix: when «signed status» is disabled, here $permissiontoadd is false when «in stock».
	// 		But we need to generate the pdf anyway!
	//		So we are overriding its value when calling actions_builddoc.
	$previous_permissiontoadd = $permissiontoadd;
	$permissiontoadd = $object->canCreatePickupPdf();
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
	$permissiontoadd = $previous_permissiontoadd;

	if (!empty($conf->global->PICKUP_SEND_MAIL)) {
		// // Actions to send emails
		$triggersendname = 'PICKUP_SENTBYMAIL';
		$autocopy = 'MAIN_MAIL_AUTOCOPY_PICKUP_TO';
		$trackid = 'pickup'.$object->id;
		include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
	}
}




/*
 * View
 */

$form=new Form($db);
$formfile=new FormFile($db);

llxHeader('', $langs->trans('Pickup'), '');

// // Example : Adding jquery code
// print '<script type="text/javascript" language="javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';


// Part to create
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Pickup")));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	dol_fiche_head(array(), '');

	print '<table class="border centpercent">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print '<input type="'.($backtopage?"submit":"button").'" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage?'':' onclick="javascript:history.go(-1)"').'>';	// Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print load_fiche_titre($langs->trans("Pickup"));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	dol_fiche_head();

	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
  $res = $object->fetch_optionals();

	$head = pickupPrepareHead($object);
	dol_fiche_head($head, 'card', $langs->trans("Pickup"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete')
	{
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeletePickup'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ToClone'), $langs->trans('ConfirmClonePickup', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx')
	{
		$formquestion=array();
	    /*
		$forcecombo=0;
		if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
	    $formquestion = array(
	        // 'text' => $langs->trans("ConfirmClone"),
	        // array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
	        // array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
	        // array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
        );
	    */
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}

	// Call Hook formConfirm
	$parameters = array('lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' .dol_buildpath('/pickup/pickup_list.php', 1) . '?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref='<div class="refidno">';
	/*
	// Ref bis
	$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->pickup->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->pickup->creer, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
	// Project
	if (! empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	    if ($user->rights->pickup->write)
	    {
	        if ($action != 'classify')
	            $morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
            if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref.='<input type="hidden" name="action" value="classin">';
                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                $morehtmlref.='</form>';
            } else {
                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	        }
	    } else {
	        if (! empty($object->fk_project)) {
	            $proj = new Project($db);
	            $proj->fetch($object->fk_project);
	            $morehtmlref.=$proj->getNomUrl();
	        } else {
	            $morehtmlref.='';
	        }
	    }
	}
	*/
	$morehtmlref.='</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	dol_fiche_end();


	/*
	 * Lines
	 */

	if (!empty($object->table_element_line))
	{
		// Show object lines
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline' && $action != 'editlinebatch') ? '#addline' : '#line_'.GETPOST('lineid', 'int')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . ($action == 'editlinebatch' ? 'setlinebatch': (($action != 'editline') ? 'addline' : 'updateline')).'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == Pickup::STATUS_DRAFT) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow" width="100%">';

		$object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1);

		if ($action != 'editline' && $action != 'selectlines') {
			// print the total line.
			$totals = $object->computeTotals();
			?>
				<tr class="nodrag nodrop liste_total">
					<td class="">
					</td>
					<td class="right">
						<?php print price($totals['qty'], 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price ?>
					</td>

					<?php if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) { ?>
						<td class="right">
						</td>
						<td class="right nowrap">
							<?php
								foreach ($totals['weights'] as $weights_units => $weights) {
									print ($weights) . ' ' . measuringUnitString(0, "weight", $weights_units) . '<br>';
								}
							?>
						</td>
					<?php } ?>
					<?php if (!empty($conf->global->PICKUP_UNITS_LENGTH)) { ?>
						<td class="right">
						</td>
						<td class="right nowrap">
							<?php
								foreach ($totals['lengths'] as $lengths_units => $lengths) {
									print ($lengths) . ' ' . measuringUnitString(0, "size", $lengths_units) . '<br>';
								}
							?>
						</td>
					<?php } ?>
					<?php if (!empty($conf->global->PICKUP_UNITS_SURFACE)) { ?>
						<td class="right">
						</td>
						<td class="right nowrap">
							<?php
								foreach ($totals['surfaces'] as $surfaces_units => $surfaces) {
									print ($surfaces) . ' ' . measuringUnitString(0, "surface", $surfaces_units) . '<br>';
								}
							?>
						</td>
					<?php } ?>
					<?php if (!empty($conf->global->PICKUP_UNITS_VOLUME)) { ?>
						<td class="right">
						</td>
						<td class="right nowrap">
							<?php
								foreach ($totals['volumes'] as $volumes_units => $volumes) {
									print ($volumes) . ' ' . measuringUnitString(0, "volume", $volumes_units) . '<br>';
								}
							?>
						</td>
					<?php } ?>

					<?php if (!empty($conf->global->PICKUP_USE_DEEE)) { ?>
						<td class="nowrap">
							<?php
								foreach ($totals['deee_type_weights'] as $deee_type => $deee_type_weights) {
									$tmp = array();
									print $deee_type . ': ';
									foreach ($totals['deee_type_weights'][$deee_type] as $weights_units => $weights) {
										array_push($tmp, ($weights) . ' ' . measuringUnitString(0, "weight", $weights_units));
									}
									print join(', ', $tmp);
									print '<br />';
								}

								$tmp = array();
								foreach ($totals['deee_weights'] as $weights_units => $weights) {
									array_push($tmp, ($weights) . ' ' . measuringUnitString(0, "weight", $weights_units));
								}
								print join(', ', $tmp);
							?>
						</td>
					<?php } ?>
					<td colspan="4"></td>
				</tr>
			<?php
		}

		// Form to add new line
		if ($object->status == Pickup::STATUS_DRAFT && $permissionedit && $action != 'selectlines')
		{
			if ($action != 'editline' && $action != 'editlinebatch')
			{
				$parameters = array('table_element_line' => $object->table_element_line);
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			}
		}

		print '</table>';
		print '</div>';

		print "</form>\n";
	}


	// Buttons for actions

	if ($action != 'presend' && $action != 'editline' && $action != 'editlinebatch') {
    	print '<div class="tabsAction">'."\n";
			// the print label button
			if (!empty($conf->global->PICKUP_USE_PRINTABLE_LABEL)) {
				print $object->printLabelButton();
			}

    	$parameters=array(
				'permissiontoadd' => $permissiontoadd,
				'permissionedit' => $permissionedit
			);
    	$reshook=$hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
    	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    	if (empty($reshook))
    	{
    	  // Send
				if (!empty($conf->global->PICKUP_SEND_MAIL)) {
					if (empty($user->socid) && $object->canCreatePickupPdf()) { // Considering you can send mail if you can generate PDF
						print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init&token='.newToken().'#formmailbeforetitle');
					}
				}

        // Modify
				print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissionedit);

				// Delete (need delete permission)
				print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', !empty($user->rights->pickup->delete));
    	}
    	print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend')
	{
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$filedir = $conf->pickup->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
			$genallowed = $object->canCreatePickupPdf();
			$delallowed = 0; // FIXME: buggy... should be: $permissiontoadd;
			$model_pdf = 'standard_pickup';
			print $formfile->showdocuments(
				'pickup', // FIXME: should be 'pickup:Pickup', but it does not work
				$object->element.'/'.$objref,
				$filedir,
				$urlsource,
				$genallowed,
				$delallowed,
				$model_pdf, // default model
				1, // allowgenifempty
				0, // forcenomultilang
				0, // iconPDF Deprecated
				28, // notused
				0, // noform (Do not output html form tags)
				'', // More param on http links
				'', // Title to show on top of form. Example: '' (Default to "Documents") or 'none'
				'', // Label on submit button
				$langs->defaultlang, // default lang
				'', // morepicto
				$object,
				0, // hide if empty
				'remove_file' // remove action
			);
		}

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('pickup'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		$MAXEVENT = 10;

		$morehtmlright = '<a href="'.dol_buildpath('/pickup/pickup_agenda.php', 1).'?id='.$object->id.'">';
		$morehtmlright.= $langs->trans("SeeAll");
		$morehtmlright.= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'pickup', $socid, 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}

	if (!empty($conf->global->PICKUP_SEND_MAIL)) {
		if ($action === 'presend') {
			$object->fetchLines(); // could be called for PDF generation (if the file does not exist yet).
		}
		// Presend form
		$modelmail = 'pickup';
		$defaulttopic = 'PickupMailTopic';
		$diroutput = $conf->pickup->dir_output.'/pickup';
		$trackid = 'pickup'.$object->id;

		include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	}
}

// End of page
llxFooter();
$db->close();
