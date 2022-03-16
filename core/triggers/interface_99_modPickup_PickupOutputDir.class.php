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
 * \file    core/triggers/interface_99_modPickup_PickupOutputDir.class
 *
 * This trigger change the directory where documents are stored if the ref is changed.
 *
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for MyModule module
 */
class InterfacePickupOutputDir extends DolibarrTriggers
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
		$this->family = "pickup";
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
			case 'PICKUP_MODIFY':
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        if (!is_object($object->oldcopy)) { return 0; }
        if ($object->oldcopy->ref === $object->ref) { return 0; }
				if (empty($conf->pickup->dir_output)) { return 0; }

        $olddir = $conf->pickup->dir_output.'/pickup/'.dol_sanitizeFileName($object->oldcopy->ref);
        $newdir = $conf->pickup->dir_output.'/pickup/'.dol_sanitizeFileName($object->ref);
        if (!file_exists($olddir)) { return 0; }
        if ($olddir === $newdir) { return 0; }

        dol_syslog("Trigger '".$this->name."': renaming document dir from .".$olddir." to ".$newdir.".");
        $res = @rename($olddir, $newdir);
        if (!$res) {
          dol_syslog("Trigger '".$this->name."': failed to rename document dir from .".$olddir." to ".$newdir.".", LOG_ERR);
          $langs->load("errors");
          $object->error = $langs->trans('ErrorFailToRenameDir', $olddir, $newdir);
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
