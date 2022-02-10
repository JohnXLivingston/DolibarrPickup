<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021		Jonathan Dollé		<license@jonathandolle.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    pickup/admin/setup.php
 * \ingroup pickup
 * \brief   Pickup setup page.
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
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/pickup.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "pickup@pickup"));

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

if (GETPOSTISSET('fk_entrepot')) {
	// This is a hack for the PICKUP_DEFAULT_STOCK field
	if ($_POST['fk_entrepot'] == '-1') {
		$_POST['fk_entrepot'] = '';
	}
	$_POST['PICKUP_DEFAULT_STOCK'] = $_POST['fk_entrepot'];
}

$pickup_extrafields = array(
	'pickup_deee' => array(
		'label' => 'DEEE',
		'type' => 'boolean',
		'pos' => 15,
		'size' => '',
		'elementype' => 'product',
		'visibility' => '5'
		// This field is computed by a trigger.

		// 'computed' => '(
		// 	(
		// 		$resql = $db->query("select pickup_deee_type from ".MAIN_DB_PREFIX."product_extrafields p where p.fk_object = " . $db->escape($obj->id ? $obj->id : ($obj->rowid ? $obj->rowid : $object->id)))
		// 	)
		// 	?
		// 		((($objsql = $db->fetch_object($resql)) && !empty($objsql->pickup_deee_type) && $objsql->pickup_deee_type != "0" ) ? 1 : 0)
		// 	:
		// 		0)',
		// 'totalizable' => 1,
	),
	'pickup_deee_type' => array(
		'label' => 'DEEEType',
		'type' => 'select',
		'pos' => 16,
		'size' => '',
		'elementype' => 'product',
		'default_value' => '',
		'param' => array('options' => array(
			'gef' => 'GEF (Gros Electroménager Froid)',
			'ghf' => 'GHF (Gros électroménager Hors Froid)',
			'pam' => 'PAM (Petits Appareils Ménager)',
			'pam_pro' => 'PAM Pro (Petits Appareils Ménager Pro)',
			'ecr' => 'ECR (Ecran < 1m2 )',
			'ecr_pro' => 'ECR Pro (Ecran > 1m2 )'
		)),
		'always_editable' => 1,
		'visibility' => '-1',
		'LRDS' => array(
			'migrate' => true,
			'lrds_name' => 'type_deee',
			'map' => array(
				'1' => 'gef',
				'2' => 'ghf',
				'3' => 'pam',
				'4' => 'pam_pro',
				'5' => 'ecr',
				'6' => 'ecr_pro'
			)
		)
	),
	'pickup_pbrand' => array(
		'label' => 'PBrand',
		'type' => 'varchar',
		'pos' => 12,
		'size' => 25,
		'elementype' => 'product',
		'default_value' => '',
		'always_editable' => 1,
		'visibility' => '1',
		'LRDS' => array(
			'migrate' => true,
			'lrds_name' => 'marque'
		)
	)
);

$extrafields = new ExtraFields($db);
$fetched_element_types = array();
foreach ($pickup_extrafields as $key => $val) {
	if ($fetched_element_types[$val['elementype']]) { continue; }
	$extrafields->fetch_name_optionals_label($val['elementype']);
	$fetched_element_types[$val['elementype']] = true;
}


$arrayofparameters=array(
	'PICKUP_DEFAULT_STOCK' => array('enabled'=>1),
	'PICKUP_USE_DEEE' => array('enabled' => 1, 'type' => 'boolean', 'extrafields' => array('pickup_deee', 'pickup_deee_type')),
	'PICKUP_USE_PBRAND' => array('enabled' => 1, 'type' => 'boolean', 'extrafields' => array('pickup_pbrand'))
);


function count_extra_fields_to_migrate($extrafield_name) {
	global $db, $extrafields, $pickup_extrafields;

	if (empty($pickup_extrafields[$extrafield_name])) {
		return null;
	}

	$ef_definition = $pickup_extrafields[$extrafield_name];
	if (empty($ef_definition['LRDS']) || !$ef_definition['LRDS']['migrate']) {
		return null;
	}

	if (!array_key_exists($ef_definition['elementype'], $extrafields->attributes)) {
		return null;
	}
	if (!array_key_exists($ef_definition['LRDS']['lrds_name'], $extrafields->attributes[$ef_definition['elementype']]['type'])) {
		return null;
	}

	$sql = 'SELECT  count(*) as nb ';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'product_extrafields WHERE ';
	if (!empty($ef_definition['LRDS']['map'])) {
		$sql.= ' ('.$extrafield_name.' = "" OR '.$extrafield_name.' = "0" OR '.$extrafield_name.' is NULL) ';
		$sql.= ' AND ';
		$sql.= ' ('.$ef_definition['LRDS']['lrds_name'].') ';
	} else {
		$sql.= ' ('.$extrafield_name.' = "" OR '.$extrafield_name.' is NULL) ';
		$sql.= ' AND ';
		$sql.= ' ('.$ef_definition['LRDS']['lrds_name'].' != "") ';
	}
	$resql = $db->query($sql);
	if (!$resql) {
		return null;
	}

	$row = $db->fetch_object($resql);
	return $row->nb;
}

function migrate_extra_fields_to_migrate($extrafield_name) {
	global $db, $extrafields, $pickup_extrafields;

	if (empty($pickup_extrafields[$extrafield_name])) {
		return null;
	}

	$ef_definition = $pickup_extrafields[$extrafield_name];
	if (empty($ef_definition['LRDS']) || !$ef_definition['LRDS']['migrate']) {
		return null;
	}

	if (!array_key_exists($ef_definition['elementype'], $extrafields->attributes)) {
		return null;
	}
	if (!array_key_exists($ef_definition['LRDS']['lrds_name'], $extrafields->attributes[$ef_definition['elementype']]['type'])) {
		return null;
	}

	dol_syslog('pickup module: migrate extrafield: '.$extrafield_name, LOG_INFO);

	$map = $ef_definition['LRDS']['map'];
	if (empty($map)) {
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'product_extrafields ';
		$sql.= ' SET '.$extrafield_name.' = '.$ef_definition['LRDS']['lrds_name']. ' ';
		$sql.=' WHERE ';
		$sql.= ' ('.$extrafield_name.' = "" OR '.$extrafield_name.' is NULL) ';
		$sql.= ' AND ';
		$sql.= ' ('.$ef_definition['LRDS']['lrds_name'].' != "") ';
		$resql = $db->query($sql);
		if (!$resql) {
			dol_syslog('pickup module: Failed to migrate extrafield: '.$db->lasterror(), LOG_ERR);
			return -1;
		}
	} else {
		foreach ($map as $oldv => $newv) {
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'product_extrafields ';
			$sql.= ' SET '.$extrafield_name.' = \''.$db->escape($newv). '\' ';
			$sql.=' WHERE ';
			$sql.= ' ('.$extrafield_name.' = "" OR '.$extrafield_name.' = "0" OR '.$extrafield_name.' is NULL) ';
			$sql.= ' AND ';
			$sql.= ' ('.$ef_definition['LRDS']['lrds_name'].' = \''.$db->escape($oldv).'\') ';
			$resql = $db->query($sql);
			if (!$resql) {
				dol_syslog('pickup module: Failed to migrate extrafield: '.$db->lasterror(), LOG_ERR);
				return -1;
			}
		}
	}
	return 1;
}


/*
 * Actions
 */


// We don't use the generic actions_setmoduleoptions template, as it does not permit to change the constants types.
// if ((float) DOL_VERSION >= 6)
// {
// 	include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
// }
if ($action == 'update' && is_array($arrayofparameters))
{
	$db->begin();

	$ok = true;
	foreach ($arrayofparameters as $key => $val)
	{
		if ($val['type'] === 'boolean') {
			if (GETPOSTISSET($key.'_hidden')) {
				// Modify constant only if key was posted (avoid resetting key to the null value)
				$checkbox_value = GETPOST($key, 'alpha') === '1' ? '1':'0';
				$result = dolibarr_set_const($db, $key, $checkbox_value, 'yesno', 0, '', $conf->entity);
				if ($result < 0) {
					$ok = false;
					break;
				}

				if ($checkbox_value === '1' && !empty($val['extrafields'])) {
					foreach ($val['extrafields'] as $extrafield_name) {
						if (!empty($pickup_extrafields[$extrafield_name])) {
							$ef_definition = $pickup_extrafields[$extrafield_name];
							$ef_result = $extrafields->addExtraField(
								$extrafield_name,
								$ef_definition['label'],
								$ef_definition['type'],
								$ef_definition['pos'],
								$ef_definition['size'],
								$ef_definition['elementype'],
								$ef_definition['unique'] ?? 0,
								$ef_definition['required'] ?? 0,
								$ef_definition['default_value'] ?? '',
								$ef_definition['param'] ?? '',
								$ef_definition['always_editable'] ?? 0,
								$ef_definition['perms'] ?? '',
								$ef_definition['visibility'] ?? '-1',
								$ef_definition['help'] ?? '',
								$ef_definition['computed'] ?? '',
								$conf->entity,
								'pickup@pickup',
								$ef_definition['enabled'] ?? '1',
								$ef_definition['totalizable'] ?? 0,
								$ef_definition['printable'] ?? 0
							);
							if ($ef_result < 0) {
								$ok = false;
								break 2;
							}
						}
					}
				}
			}
		} else if (GETPOSTISSET($key)) {
			// Modify constant only if key was posted (avoid resetting key to the null value)
			$result = dolibarr_set_const($db, $key, GETPOST($key, 'alpha'), 'chaine', 0, '', $conf->entity);
			if ($result < 0) {
				$ok = false;
				break;
			}
		}
	}

	if (!$error && $ok)
	{
		$db->commit();
		if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		$db->rollback();
		if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
	}
}
if ($action === 'updateExtraFields' && $arrayofparameters && GETPOSTISSET('extrafields_for')) {
	$extrafields_for = GETPOST('extrafields_for', 'alpha');
	if (!empty($arrayofparameters[$extrafields_for]) && !empty($arrayofparameters[$extrafields_for]['extrafields'])) {
		$db->begin();
		$ok = true;

		foreach ($arrayofparameters[$extrafields_for]['extrafields'] as $extrafield_name) {
			if (!empty($pickup_extrafields[$extrafield_name])) {
				$ef_definition = $pickup_extrafields[$extrafield_name];
				$extrafields = new ExtraFields($db);
				$ef_result = $extrafields->update(
					$extrafield_name,
					$ef_definition['label'],
					$ef_definition['type'],
					$ef_definition['size'],
					$ef_definition['elementype'],
					$ef_definition['unique'] ?? 0,
					$ef_definition['required'] ?? 0,
					$ef_definition['pos'],
					$ef_definition['param'] ?? '',
					$ef_definition['always_editable'] ?? 0,
					$ef_definition['perms'] ?? '',
					$ef_definition['visibility'] ?? '-1',
					$ef_definition['help'] ?? '',
					$ef_definition['default_value'] ?? '',
					$ef_definition['computed'] ?? '',
					$conf->entity,
					'pickup@pickup',
					$ef_definition['enabled'] ?? '1',
					$ef_definition['totalizable'] ?? 0,
					$ef_definition['printable'] ?? 0
				);
				if ($ef_result < 0) {
					$ok = false;
					break;
				}
			}
		}

		if (!$error && $ok)
		{
			$db->commit();
			if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		}
		else
		{
			$db->rollback();
			if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
		}
	}
}
if ($action === 'migrateExtraFields' && $pickup_extrafields) {
	$field_to_migrate = GETPOST('extrafield', 'alpha');
	if (!empty($pickup_extrafields[$field_to_migrate])) {
		$db->begin();
		$result = migrate_extra_fields_to_migrate($field_to_migrate);
		if ($result<0) {
			$db->rollback();
			if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
		} else {
			$db->commit();
			if (empty($nomessageinupdate)) setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		}
	}
}

/*
 * View
 */

$page_name = "PickupSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage?$backtopage:DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_pickup@pickup');

// Configuration header
$head = pickupAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "pickup@pickup");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("PickupSetupPage").'</span><br><br>';


if ($action == 'edit')
{
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach($arrayofparameters as $key => $val)
	{
		print '<tr class="oddeven"><td>';
		print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));
		print '</td><td>';
		if ($key == 'PICKUP_DEFAULT_STOCK') {
			dol_include_once('/pickup/class/pickup.class.php');
			$pickup = new Pickup($db);
			print $pickup->showInputField($pickup->fields['fk_entrepot'], 'fk_entrepot', $conf->global->$key);
		} else if ($val['type'] === 'boolean') {
			print '<input type="hidden" name="'.$key.'_hidden" value="1">';
			print '<input type="checkbox" name="'.$key.'"  class="flat '.(empty($val['css'])?'':$val['css']).'" value="1" ' . ($conf->global->$key ? 'checked':'') . '>';
		} else {
			print '<input name="'.$key.'"  class="flat '.(empty($val['css'])?'minwidth200':$val['css']).'" value="' . $conf->global->$key . '">';
		}
		print '</td></tr>';
	}
	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
}
else
{
	if (! empty($arrayofparameters))
	{
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

		foreach($arrayofparameters as $key => $val)
		{
			print '<tr class="oddeven"><td>';
			print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));
			print '</td><td>';
			if ($key == 'PICKUP_DEFAULT_STOCK') {
				if (!empty($conf->global->$key)) {
					dol_include_once('/product/stock/class/entrepot.class.php');
					$entrepot = new Entrepot($db);
					$entrepot->fetch($conf->global->$key);
					print $entrepot->getNomUrl(1);
				}
			} else if ($val['type'] === 'boolean') {
				print '<input type="checkbox" disabled name="'.$key.'"  class="flat '.(empty($val['css'])?'':$val['css']).'" ' . ($conf->global->$key ? 'checked':'') . '>';

				if (!empty($val['extrafields']) && $conf->global->$key) {
					print '&nbsp;<form style="display:inline" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="updateExtraFields">';
					print '<input type="hidden" name="extrafields_for" value="'.$key.'">';
					print '<input type="submit" name="" value="'.dol_escape_htmltag($langs->trans("PICKUP_UPDATE_EXTRAFIELDS")).'">';
					print '</form>';

					foreach ($val['extrafields'] as $extrafield_name) {
						if (!empty($pickup_extrafields[$extrafield_name])) {
							$count = count_extra_fields_to_migrate($extrafield_name);
							if ($count) {
								print ' <form style="display:inline" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
								print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
								print '<input type="hidden" name="action" value="migrateExtraFields">';
								print '<input type="hidden" name="extrafield" value="'.$extrafield_name.'">';
								print '<input type="submit" name="" value="'.dol_escape_htmltag($langs->trans("PICKUP_MIGRATE_EXTRAFIELDS").' ('.$pickup_extrafields[$extrafield_name]['label'].': '.$count.')').'">';
								print '</form>';
							}
						}
					}
				}

			} else {
				print $conf->global->$key;
			}
			print '</td></tr>';
		}

		print '</table>';

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
		print '</div>';
	}
	else
	{
		print '<br>'.$langs->trans("NothingToSetup");
	}
}


// Page end
dol_fiche_end();

llxFooter();
$db->close();
