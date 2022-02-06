<?php
/* Copyright (C) 2022		Jonathan Dollé		<license@jonathandolle.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_21_modProduct_PickupDEEE.class
 *
 * This trigger computes the product DEEE field if necessary.
 *
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for MyModule module
 */
class InterfacePickupDEEE extends DolibarrTriggers
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
		$this->description = "Pickup module triggers.";
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
		global $db;
		if (empty($conf->pickup->enabled)) return 0; // If module is not enabled, we do nothing

		switch ($action) {
			// Products
			case 'PRODUCT_CREATE':
			case 'PRODUCT_MODIFY':
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				if (empty($object->array_options)) {
					dol_syslog("Trigger '".$this->name."': no array_options, assuming extrafields where not modified.");
					return 0;
				}
				if (!array_key_exists('options_pickup_deee', $object->array_options)) {
					dol_syslog("Trigger '".$this->name."': no options_pickup_deee fields. Dont recompute.");
					return 0;
				}
				if (!array_key_exists('options_pickup_deee_type', $object->array_options)) {
					dol_syslog("Trigger '".$this->name."': no options_pickup_deee_type fields. Dont recompute.");
					return 0;
				}
				$new_deee = !empty($object->array_options['options_pickup_deee_type']) && $object->array_options['options_pickup_deee_type'] != '0' ? '1' : '0';
				dol_syslog("Trigger '".$this->name."': Recompte pickup_deee to ".$new_deee.".");
				$object->array_options['options_pickup_deee'] = $new_deee;
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'product_extrafields ';
				$sql.= ' SET pickup_deee = \''.$db->escape($new_deee).'\' ';
				$sql.= ' WHERE fk_object = \'' . $db->escape($object->id) . '\'';
				$resql = $db->query($sql);
				if (!$resql) {
					dol_syslog("Trigger '".$this->name."' for action '$action' failed on id=".$object->id.': '.$db->lasterror(), LOG_ERR);
					return -1;
				}
				return 1;
			default:
				//dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
}
