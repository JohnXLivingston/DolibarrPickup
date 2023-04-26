<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021-2022		John Livingston		<license@john-livingston.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once '../lib/pickup.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "pickup@pickup", "products", "productbatch", "other"));

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$edit_table = GETPOST('edit_table', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm    = GETPOST('confirm', 'alpha');

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
	),

	// Extrafield on Productlot.
	'pickup_note' => array (
		'label' => 'PickupNote',
		'type' => 'text',
		'pos' => 110,
		'elementype' => 'product_lot',
		'always_editable' => 1,
		'visibility' => '1'
	)
);

$extrafields = new ExtraFields($db);
$fetched_element_types = array();
foreach ($pickup_extrafields as $key => $val) {
	if ($fetched_element_types[$val['elementype']]) { continue; }
	$extrafields->fetch_name_optionals_label($val['elementype']);
	$fetched_element_types[$val['elementype']] = true;
}

$list_of_tables = [
	'main' => $langs->trans('PickupSetup'),
	'product' => $langs->trans('Product'),
	'units' => $langs->trans('PickupSetupUnits'),
	'pickupline_description' => $langs->trans('PickupSetupLineDescription'),
	'batch' => $langs->trans('PickupSetupBatch'),
	'printable_label' => $langs->trans('PickupSetupPrintableLabel')
];

$arrayofparameters=array(
	'PICKUP_USE_PICKUP_TYPE' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean'),
	'PICKUP_DEFAULT_STOCK' => array('table' => 'main', 'enabled'=>1),
	'PICKUP_ALLOW_FUTURE' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean'),
	'PICKUP_USE_PCAT' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean'),
	'PICKUP_USE_DEEE' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean', 'extrafields' => array('pickup_deee', 'pickup_deee_type')),
	'PICKUP_NO_SIGN_STATUS' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean'),
	'PICKUP_SEND_MAIL' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean'),
	'PICKUP_IMPORTEXPORT_ALL' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean'),

	'PICKUP_PRODUCT_DEFAULT_TOSELL' => array('table' => 'product', 'enabled' => 1, 'type' => 'boolean'),
	'PICKUP_PRODUCT_REF_AUTO' => array('table' => 'product', 'enabled' => 1, 'type' => 'boolean'),
	'PICKUP_USE_PBRAND' => array('table' => 'product', 'enabled' => 1, 'type' => 'boolean', 'extrafields' => array('pickup_pbrand')),

	'PICKUP_UNITS_WEIGHT' => array(
		'table' => 'units',
		'enabled' => 1,
		'type' => 'select',
		'label' => $langs->trans('Weight'),
		'options' => array(
			'0' => $langs->trans('Disabled'),
			'optional' =>  $langs->trans('Enabled'),
			'mandatory' => $langs->trans('Enabled') . ' / ' . $langs->trans('Mandatory')
		)
	),
	'PICKUP_UNITS_LENGTH' => array(
		'table' => 'units',
		'enabled' => 1,
		'type' => 'select',
		'label' => $langs->trans('Length'),
		'options' => array(
			'0' => $langs->trans('Disabled'),
			'optional' =>  $langs->trans('Enabled'),
			'mandatory' => $langs->trans('Enabled') . ' / ' . $langs->trans('Mandatory')
		)
	),
	'PICKUP_UNITS_SURFACE' => array(
		'table' => 'units',
		'enabled' => 1,
		'type' => 'select',
		'label' => $langs->trans('Surface'),
		'options' => array(
			'0' => $langs->trans('Disabled'),
			'optional' =>  $langs->trans('Enabled'),
			'mandatory' => $langs->trans('Enabled') . ' / ' . $langs->trans('Mandatory')
		)
	),
	'PICKUP_UNITS_VOLUME' => array(
		'table' => 'units',
		'enabled' => 1,
		'type' => 'select',
		'label' => $langs->trans('Volume'),
		'options' => array(
			'0' => $langs->trans('Disabled'),
			'optional' =>  $langs->trans('Enabled'),
			'mandatory' => $langs->trans('Enabled') . ' / ' . $langs->trans('Mandatory')
		)
	),
	'PICKUP_UNITS_PIECE' => array(
		'table' => 'units',
		'enabled' => 1,
		'type' => 'select',
		'label' => $langs->trans('unitP'),
		'options' => array(
			'0' => $langs->trans('Disabled'),
			'1' =>  $langs->trans('Enabled'),
		)
	),
	'PICKUP_UNITS_EDIT_MODE' => array(
		'table' => 'units',
		'enabled' => 1,
		'type' => 'select',
		'options' => array(
			'0' => $langs->trans('PICKUP_UNITS_EDIT_MODE_OPTIONS_0'),
			'pickupline' => $langs->trans('PICKUP_UNITS_EDIT_MODE_OPTIONS_pickupline')
		)
	),

	'PICKUP_USE_PICKUPLINE_DESCRIPTION' => array('table' => 'pickupline_description', 'enabled' => 1, 'type' => 'boolean'),
	'PICKUP_USE_PICKUPLINE_DESCRIPTION_IN_PDF' => array('table' => 'pickupline_description', 'enabled' => 1, 'type' => 'boolean'),
	'PICKUP_USE_PICKUPLINE_DESCRIPTION_ON_UNIQUE_PL' => array(
		'table' => 'pickupline_description',
		'enabled' => !empty($conf->productbatch->enabled),
		'type' => 'boolean',
		'extrafields' => array('pickup_note')
	),
	'PICKUP_USE_PICKUPLINE_DESCRIPTION_ON_PL' => array(
		'table' => 'pickupline_description',
		'enabled' => !empty($conf->productbatch->enabled),
		'type' => 'boolean',
	),

	'PICKUP_DEFAULT_HASBATCH' => array(
		'table' => 'batch',
		'enabled' => !empty($conf->productbatch->enabled),
		'type' => 'select',
		'label' => $langs->trans('ManageLotSerial'),
		'options' => array(
			'0' => $langs->trans('PICKUP_DEFAULT_HASBATCH_OPTIONS_0'),
			'ask' =>  $langs->trans('PICKUP_DEFAULT_HASBATCH_OPTIONS_ask'),
			'1' => $langs->trans('PICKUP_DEFAULT_HASBATCH_OPTIONS_1'),
			'2' => $langs->trans('PICKUP_DEFAULT_HASBATCH_OPTIONS_2')
		)
	),
	'PICKUP_DEFAULT_BATCH' => array(
		'table' => 'batch',
		'enabled' => !empty($conf->productbatch->enabled),
		'type' => 'select',
		'options' => [
			'0' => '',
			'pickup_ref' => $langs->trans('PICKUP_DEFAULT_BATCH_OPTIONS_PICKUP_REF'),
			'generate' => $langs->trans('PICKUP_DEFAULT_BATCH_OPTIONS_GENERATE'),
			'generate_per_product' => $langs->trans('PICKUP_DEFAULT_BATCH_OPTIONS_GENERATEPPRODUCT'),
		]
	),
	'PICKUP_DEFAULT_UNIQUE_BATCH' => array(
		'table' => 'batch',
		'enabled' => !empty($conf->productbatch->enabled),
		'type' => 'select',
		'options' => [
			'0' => '',
			'generate' => $langs->trans('PICKUP_DEFAULT_UNIQUE_BATCH_OPTIONS_GENERATE'),
		]
	),

	'PICKUP_USE_PRINTABLE_LABEL' => array(
		'table' => 'printable_label',
		'enabled' => true,
		'type' => 'boolean'
	),
	'PICKUP_PRINTABLE_LABEL_PRODUCTCARD_LINK' => array(
		'table' => 'printable_label',
		'enabled' => true,
		'type' => 'select',
		'options' => [
			'0' => '-',
			'DATAMATRIX' => 'DATAMATRIX',
			'QRCODE' => 'QRCODE',
		]
	),
	'PICKUP_PRINTABLE_LABEL_BATCH' => array(
		'table' => 'printable_label',
		'enabled' => true,
		'type' => 'select',
		'options' => [
			'0' => '-',
			'C39' => 'C39',
			'C39+' => 'C39+',
			'C39E' => 'C39E',
			'C39E+' => 'C39E+',
			// 'S25' => 'S25',
			// 'S25+' => 'S25+',
			// 'I25' => 'I25',
			// 'I25+' => 'I25+',
			'C128' => 'C128',
			'C128A' => 'C128A',
			'C128B' => 'C128B',
			'C128C' => 'C128C',
			// 'EAN2' => 'EAN2',
			// 'EAN5' => 'EAN5',
			// 'EAN8' => 'EAN8',
			// 'EAN13' => 'EAN13',
			// 'ISBN' => 'EAN13',
			// 'UPC' => 'UPCA',
			// 'UPCE' => 'UPCE',
			// 'MSI' => 'MSI',
			// 'MSI+' => 'MSI+',
			// 'POSTNET' => 'POSTNET',
			// 'PLANET' => 'PLANET',
			'RMS4CC' => 'RMS4CC',
			'KIX' => 'KIX',
			// 'IMB' => 'IMB',
			// 'CODABAR' => 'CODABAR',
			// 'CODE11' => 'CODE11',
			// 'PHARMA' => 'PHARMA',
			// 'PHARMA2T' => 'PHARMA2T',
			'DATAMATRIX' => 'DATAMATRIX',
			'QRCODE' => 'QRCODE',
		]
	),
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
	if (!array_key_exists('type', $extrafields->attributes[$ef_definition['elementype']])) {
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

function can_migrate_pbrand_to_Ref() {
	global $db, $extrafields;
	if (!array_key_exists('product', $extrafields->attributes)) {
		return false;
	}
	if (!array_key_exists('type', $extrafields->attributes['product'])) {
		return false;
	}
	if (!array_key_exists('pickup_pbrand', $extrafields->attributes['product']['type'])) {
		return false;
	}
	return true;
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

function migrate_pbrand_to_ref() {
	global $db, $extrafields, $user;
	$sql = 'SELECT fk_object, pickup_pbrand FROM '.MAIN_DB_PREFIX.'product_extrafields ';
	$sql.=' WHERE ';
	$sql.= ' (pickup_pbrand != "" AND pickup_pbrand IS NOT NULL) ';
	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog('pickup module: Failed to get product_extrafields having pbrand attributes: '.$db->lasterror(), LOG_ERR);
		return -1;
	}
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	while ($row = $db->fetch_object($resql)) {
		$pbrand = $row->pickup_pbrand;
		$pbrand = mb_strtoupper($pbrand);
		$pbrand = preg_replace('/^\s+/', '_', $pbrand);
		if (substr($pbrand, -1) !== '_') {
			$pbrand = $pbrand.'_';
		}
		dol_syslog('migrate_pbrand_to_ref: object: '.$row->fk_object.', pbrand: '.$row->pickup_pbrand.' prefix: '.$pbrand, LOG_DEBUG);
		$product = new Product($db);
		if ($product->fetch($row->fk_object) <= 0) {
			dol_syslog('migrate_pbrand_to_ref: cant fetch object: '.$row->fk_object, LOG_ERR);
			continue;
		}
		if (substr(mb_strtoupper($product->ref), 0, strlen($pbrand)) === $pbrand) {
			dol_syslog('migrate_pbrand_to_ref: object: '.$row->fk_object.' already begins with pbrand.', LOG_DEBUG);
			continue;
		}
		if (strlen($pbrand) >= 4) {
			// If the brand name is at least 4 chars, we will also look in the middle of the ref.
			if (false !== mb_strpos(mb_strtoupper($product->ref), $pbrand)) {
				dol_syslog('migrate_pbrand_to_ref: object: '.$row->fk_object.' already contains pbrand.', LOG_DEBUG);
				continue;
			}
		}
		$new_ref = $pbrand.$product->ref;
		dol_syslog('migrate_pbrand_to_ref: object: '.$row->fk_object.' changing ref to: '.$new_ref, LOG_INFO);
		$product->ref = $new_ref;
		$result = $product->update($product->id, $user);
		if ($result <= 0) {
			dol_syslog('migrate_pbrand_to_ref: Failed updating object '.$row->fk_object, LOG_ERR);
			if (!empty($product->error)) dol_syslog($method.' '.$product->error, LOG_ERR);
    	if (!empty($product->errors)) dol_syslog($method.' '.join(',', $product->errors), LOG_ERR);
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
	foreach ($arrayofparameters as $key => $val) {
		if ($val['enabled'] != 1) { continue; }
		if ($val['table'] !== $edit_table) { continue; }

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
			if ($val['type'] === 'select') {
				if (!array_key_exists(GETPOST($key, 'alpha'), $val['options'])) { // test if option exists
					continue;
				}
			}
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
if ($action === 'confirmMigratePBrandToRef' && can_migrate_pbrand_to_Ref()) {
	migrate_pbrand_to_ref();
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

if ($action === 'migratePBrandToRef' && can_migrate_pbrand_to_Ref()) {
	$form=new Form($db);
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans('PICKUP_MIGRATE_PBRAND_TO_REF'), $langs->trans('Confirm'), 'confirmMigratePBrandToRef', '', 0, 1);
}

if ($action == 'edit')
{
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="edit_table" value="'.htmlspecialchars($edit_table).'">';

	function draw_edit_table($table, $title) {
		global $arrayofparameters, $entrepot, $form, $conf, $langs, $pickup_extrafields, $db;

		$filtered_arrayofparameters = array_filter($arrayofparameters, function ($val) use ($table) {
			return $val['enabled'] == 1 && $val['table'] == $table;
		});
		if (count($filtered_arrayofparameters) == 0) {
			return;
		}

		print load_fiche_titre($title, '', '');

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

		foreach($filtered_arrayofparameters as $key => $val) {
			print '<tr class="oddeven"><td>';
			$tooltip = $langs->trans($key.'Tooltip');
			if ($tooltip !== $key.'Tooltip') {
				print $form->textwithpicto($val['label'] ?? $langs->trans($key), $tooltip);
			} else {
				print $val['label'] ?? $langs->trans($key);
			}
			print '</td><td>';
			if ($key == 'PICKUP_DEFAULT_STOCK') {
				dol_include_once('/pickup/class/pickup.class.php');
				$pickup = new Pickup($db);
				print $pickup->showInputField($pickup->fields['fk_entrepot'], 'fk_entrepot', $conf->global->$key);
			} else if ($val['type'] === 'boolean') {
				print '<input type="hidden" name="'.$key.'_hidden" value="1">';
				print '<input type="checkbox" name="'.$key.'"  class="flat '.(empty($val['css'])?'':$val['css']).'" value="1" ' . ($conf->global->$key ? 'checked':'') . '>';
			} else if ($val['type'] === 'select') {
				print '<select name="'.$key.'">';
				foreach ($val['options'] as $ok => $ov) {
					print '<option ';
					print 'value="' . htmlspecialchars($ok) . '"';
					if (strval(($conf->global->$key) ?? '0') === strval($ok)) { print ' selected '; }
					print '>';
					print $ov . '</option>';
				}
				print '</select>';
			} else {
				print '<input name="'.$key.'"  class="flat '.(empty($val['css'])?'minwidth200':$val['css']).'" value="' . $conf->global->$key . '">';
			}
			print '</td></tr>';
		}
		print '</table>';
	}

	if (array_key_exists($edit_table, $list_of_tables)) {
		draw_edit_table($edit_table, $list_of_tables[$edit_table]);
	}

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
}
else
{
	if (! empty($arrayofparameters)) {

		function draw_modify_button($table) {
			global $langs;
			print '<div class="tabsAction">';
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&edit_table='.urlencode($table).'">'.$langs->trans("Modify").'</a>';
			print '</div>';
		}

		function draw_view_table($table, $title) {
			global $arrayofparameters, $entrepot, $form, $conf, $langs, $pickup_extrafields, $db;

			$filtered_arrayofparameters = array_filter($arrayofparameters, function ($val) use ($table) {
				return $val['enabled'] == 1 && $val['table'] == $table;
			});
			if (count($filtered_arrayofparameters) == 0) {
				return;
			}

			print load_fiche_titre($title, '', '');

			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

			foreach($filtered_arrayofparameters as $key => $val) {
				print '<tr class="oddeven"><td>';
				$tooltip = $langs->trans($key.'Tooltip');
				if ($tooltip !== $key.'Tooltip') {
					print $form->textwithpicto($val['label'] ?? $langs->trans($key), $tooltip);
				} else {
					print $val['label'] ?? $langs->trans($key);
				}
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
						print img_help(1, $langs->trans('PICKUP_UPDATE_EXTRAFIELDS_Tooltip'));
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
									print img_help(1, $langs->trans('PICKUP_MIGRATE_EXTRAFIELDS_Tooltip'));
									print '</form>';
								}
							}
						}
					}

					// PBrand is deprecated. Special case to migrate this info back to the Product Ref field.
					if ($key === 'PICKUP_USE_PBRAND' && !$conf->global->$key) {
						if (can_migrate_pbrand_to_Ref()) {
							print ' <form style="display:inline" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
							print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
							print '<input type="hidden" name="action" value="migratePBrandToRef">';
							print '<input type="submit" name="" value="'.dol_escape_htmltag($langs->trans("PICKUP_MIGRATE_PBRAND_TO_REF")).'">';
							print img_help(1, $langs->trans('PICKUP_MIGRATE_PBRAND_TO_REF_Tooltip'));
							print '</form>';
						}
					}

				} else if ($val['type'] === 'select') {
					if (array_key_exists($conf->global->$key ?? '0', $val['options'])) {
						print $val['options'][$conf->global->$key ?? '0'];
					}
				} else {
					print $conf->global->$key;
				}
				print '</td></tr>';
			}

			print '</table>';

			draw_modify_button($table);
		}

		foreach ($list_of_tables as $table_key => $table_title) {
			draw_view_table($table_key, $table_title);
		}
	} else {
		print '<br>'.$langs->trans("NothingToSetup");
	}
}


// Page end
dol_fiche_end();

llxFooter();
$db->close();
