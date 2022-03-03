<?php
/* Copyright (C) 2021-2022		John Livingston		<license@john-livingston.fr>
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

require_once DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

class ModeleNumRefPickup {
  var $error='';
  var $version = '1.0.0';
  var $previx = 'COL';

	/**
	 * 	Return if a module can be used or not
	 *
	 * 	@return		boolean	 true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**
	 * 	Returns the model description
	 *
	 * 	@return	 string	  Description
	 */
	function info()
	{
		global $langs;
		$langs->load("pickup@pickup");
		return $langs->trans("PickupNumRefModelDesc", $this->prefix);
	}

	/**
	 * 	Return an example value
	 *
	 * 	@return	 string	  Example
	 */
	function getExample()
	{
		global $conf, $langs;

    $numExample = $this->getNextValue('');

    if (!$numExample)
    {
        $numExample = 'NotConfigured';
    }
    return $numExample;
	}

	/**
	 * 	Test if there is no conflict in database
	 *
	 * 	@return	 boolean	 
	 */
	function canBeActivated()
	{
    // FIXME: test if there is available numeration
		return true;
	}

	/**
   * Return next value
   *
   * @param   Pickup		$pickup	Object pickup
   * @param   string		$mode       'next' for next value or 'last' for last value
   * @return  string      			Value if OK, 0 if KO
   */
  public function getNextValue($pickup, $mode = 'next')
	{
    // TODO: do like mod_facture_mercure.php '{yyyy}{mm}-{0000}'
    global $db, $conf;

    require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

    // Get Mask value
    $mask = 'P{yyyy}{mm}-{0000@0}';
    $where = '';

    $numFinal = get_next_value($db, $mask, 'pickup_pickup', 'ref', $where, '', $pickup->date_pickup, $mode, false, null, null);
    if (!preg_match('/([0-9])+/', $numFinal)) $this->error = $numFinal;

    return  $numFinal;
	}

  /**
   * Return next free value
   *
   * @param	string		$objforref		Object for number to search
   * @param   string		$mode       	'next' for next value or 'last' for last value
   * @return  string      				Next free value
   */
  public function getNumRef($objforref, $mode = 'next')
  {
      return $this->getNextValue($objforref, $mode);
  }

	/**
	 * 	Renvoi version du module numerotation
	 *
	 * 	@return	 string	  Valeur
	 */
	function getVersion()
	{
		return $this->version;
	}
}

abstract class ModelePDFPickup extends CommonDocGenerator {
	/**
	 *	Return list of active generation modules
	 *
	 *  @param	DoliDB	$db	 			Database handler
	 *  @param  string	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	static function liste_modeles($db, $maxfilenamelength=0) {
		global $conf;

		$type = 'pickup';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}
}
