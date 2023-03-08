<?php
/* Copyright (C) 2022		John Livingston		<license@john-livingston.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_22_modPickup_BarcodeInit.class
 *
 * This trigger computes the product initial barcode if needed (when using mod_barcode_product_pickup).
 * Indeed, when using mod_barcode_product_pickup, we need the object to initiate
 * the barcode.
 * But when creating a product (using product/card), this ref is not set yet.
 * So we will set it at PRODUCT_CREATE event, under certain circonstances.
 *
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for MyModule module
 */
class InterfaceBarcodeInit extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "product";
		$this->description = "Pickup module triggers. Initialiaze product barcode when using DolibarrPickup barcode numbering system.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'pickup@pickup';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		global $db, $user;
		if ($action !== 'PRODUCT_CREATE') {
			// This trigger only concern product creation.
			return 0;
		}
		if (!empty($object->barcode)) {
			// already set.
			return 0;
		}
		if (empty($conf->pickup->enabled)) {
			return 0; // If module is not enabled, we do nothing
		}
		if (empty($conf->barcode->enabled)) {
      dol_syslog("Trigger '".$this->name.': barcode module is not enabled', LOG_DEBUG);
      return 0;
    }
    if (empty($conf->global->BARCODE_PRODUCT_ADDON_NUM)) {
      dol_syslog("Trigger '".$this->name.': no default barcode num class', LOG_DEBUG);
      return 0;
    }
		if ($conf->global->BARCODE_PRODUCT_ADDON_NUM !== 'mod_barcode_product_pickup') {
			dol_syslog("Trigger '".$this->name.': barcode num class is not mod_barcode_product_pickup', LOG_DEBUG);
      return 0;
		}

		if (empty($object->barcode_type)) {
			if (empty($conf->global->PRODUIT_DEFAULT_BARCODE_TYPE)) {
				dol_syslog("Trigger '".$this->name.': no default barcode type', LOG_DEBUG);
				return 0;
			}
			$fk_barcode_type = getDolGlobalInt("PRODUIT_DEFAULT_BARCODE_TYPE");
			if (empty($fk_barcode_type)) {
				dol_syslog("Trigger '".$this->name.': no default barcode type (after getDolGlobalInt call)', LOG_DEBUG);
				return 0;
			}
		} else {
			$fk_barcode_type = $object->barcode_type;
		}

		dol_include_once('/pickup/core/modules/barcode/mod_barcode_product_pickup.php');
		$modBarCodeProduct = new mod_barcode_product_pickup();
		$object->barcode = $modBarCodeProduct->getNextValue($object, $fk_barcode_type);
		dol_syslog("Trigger '".$this->name.': new barcode generated: '.$object->barcode, LOG_DEBUG);

		// and we must save in database...
		$object->update($object->id, $user, true); // no triggers!
		return 1;
	}
}
