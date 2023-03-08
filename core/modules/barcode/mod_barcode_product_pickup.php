<?php
/* Copyright (C) 2023      John Livingston		<license@john-livingston.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/modules_barcode.class.php';


/**
 *	Class to manage barcode with standard rule
 */
class mod_barcode_product_pickup extends ModeleNumRefBarCode
{
	public $name = 'DolibarrPickup'; // Model Name

  public $version = '1.0.0';

  public $code_auto = 1;

  public $code_null = 1; // Optional code

	// public $code_modifiable; // Editable code

	// public $code_modifiable_invalide; // Modified code if it is invalid

	// public $code_modifiable_null; // Modified code if it is null

	// public $searchcode; // Search string

	// public $numbitcounter; // Number of digits the counter

	// public $prefixIsRequired; // The prefix field of third party must be filled when using {pre}


	/**		Return description of module
	 *
	 * 		@param	Translate 		$langs		Object langs
	 * 		@return string      			Description of module
	 */
	public function info($langs)
	{
		global $conf;
		global $form;

		$langs->load("pickup");

		$text = $langs->trans('PickupBarCodeModelDesc');

    $tooltip = $langs->trans('PickupBarCodeModelTooltip');

		return $form->textwithpicto($text, $tooltip);
	}


	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	Translate	$langs			Object langs
	 * @param	Product		$objproduct		Object product
	 * @return	string						Return string example
	 */
	public function getExample($langs, $objproduct = 0)
	{
		$examplebarcode = $this->getNextValue(!empty($objproduct) ? $objproduct : 'generateExample', '');
		if (!$examplebarcode) {
			$examplebarcode = $langs->trans('NotConfigured');
		}

		return $examplebarcode;
	}

	/**
	 *  Check that this is a supported format
	 *
	 *	@param	Database    $db         Database
	 *  @param  int  		$type       Type of barcode (EAN, ISBN, ...) as rowid
	 *  @return string
	 */
	protected function checkBarcodeTypeIsSupported($db, $type = '')
	{
		global $conf;

		$sql = "SELECT rowid, code, libelle as label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_barcode_type";
		$sql .= " WHERE rowid = '".$db->escape($type)."'";
		$sql .= " AND entity = ".((int) $conf->entity);
		$result = $db->query($sql);
		if (!$result) {
      dol_print_error($db);
    }

    $num = $db->num_rows($result);
		if ($num <= 0) {
      return false;
    }
    $obj = $db->fetch_object($result);
    return in_array($obj->code, ['DATAMATRIX', 'QRCODE']);
	}

	/**
	 * Return next value
	 *
	 * @param	Product		$objproduct     Object product
	 * @param	string		$type       	Type of barcode (EAN, ISBN, ...)
	 * @return 	string      				Value if OK, '' if module not configured, <0 if KO
	 */
	public function getNextValue($objproduct, $type = '')
	{
		global $db, $conf;

		if (empty($type)) {
			$type = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
		} //get barcode type configuration for products if $type not set

    if (!$this->checkBarcodeTypeIsSupported($db, $type)) {
      return null;
    }

    if ($objproduct === 'generateExample') {
      return 'FILIP_X100';
    }

    if (!is_object($objproduct)) {
      return null;
    }
    if (!property_exists($objproduct, 'ref')) {
      return null;
    }
    $code = $objproduct->ref;

    $code = mb_strtoupper(trim($code));
    $code = preg_replace('/^\s+/', '_', $code);

		if (!$this->checkIfAvailable($db, $code, $objproduct)) {
			// We will append a counter.
			$cpt = 1;
			$new_code = $code.'_'.substr('0000'.$cpt, -4);
			while (!$this->checkIfAvailable($db, $new_code, $objproduct)) {
				$ctp++;
				$new_code = $code.'_'.substr('0000'.$cpt, -4);
			}
			$code = $new_code;
		}

    return $code;
	}


	/**
	 * 	Check validity of code according to its rules
	 *
	 *	@param	DoliDB		$db					Database handler
	 *	@param	string		$code				Code to check/correct
	 *	@param	Product		$product			Object product
	 *  @param  int		  	$thirdparty_type   	0 = customer/prospect , 1 = supplier
	 *  @param	string		$type       	    type of barcode (EAN, ISBN, ...)
	 *  @return int								0 if OK
	 * 											-1 ErrorBadBarCodeSyntax
	 * 											-2 ErrorBarCodeRequired
	 * 											-3 ErrorBarCodeAlreadyUsed
	 */
	public function verif($db, &$code, $product, $thirdparty_type, $type)
	{
		global $db, $conf;

		$result = 0;
		$code = mb_strtoupper(trim($code));

		if (empty($code) && $this->code_null) {
			$result = 0;
		} elseif (empty($code) && (!$this->code_null)) {
			$result = -2;
		} else {
      // FIXME: should be an error about the type... but Dolibarr has no return code for this.
      // if (!$this->checkBarcodeTypeIsSupported($db, $type)) {
      //   $result = -1;
      // }
			if (!$this->checkIfAvailable($db, $code, $product)) {
				$result = -3;
			}
		}

		dol_syslog(get_class($this)."::verif type=".$thirdparty_type." result=".$result);
		return $result;
	}


	/**
	 *	Return if a code is used (by other element)
	 *
	 *	@param	DoliDB		$db			Handler acces base
	 *	@param	string		$code		Code to check
	 *	@param	Product		$product	Objet product
	 *	@return	boolean						true if available, false if KO
	 */
	public function checkIfAvailable($db, $code, $product = null)
	{
		$sql = "SELECT barcode FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE barcode = '".$db->escape($code)."'";
		if (!empty($product) && $product->id > 0) {
			$sql .= " AND rowid != ".$db->escape($product->id);
		}

		$resql = $db->query($sql);
		if ($resql) {
			return $db->num_rows($resql) == 0;
		} else {
			return false;
		}
	}
}
