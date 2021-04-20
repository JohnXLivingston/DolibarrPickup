<?php
/* Copyright (C) 2021		Jonathan Dollé		<license@jonathandolle.fr>
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
 * \file    pickup/class/actions_pickup.class.php
 * \ingroup pickup
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

require_once DOL_DOCUMENT_ROOT.'/custom/pickup/class/pickup.class.php';

/**
 * Class ActionsPickup
 */
class ActionsPickup
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	// /**
	//  * Execute action
	//  *
	//  * @param	array			$parameters		Array of parameters
	//  * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	//  * @param	string			$action      	'add', 'update', 'view'
	//  * @return	int         					<0 if KO,
	//  *                           				=0 if OK but we want to process standard actions too,
	//  *                            				>0 if OK and we want to replace standard actions.
	//  */
	// public function getNomUrl($parameters, &$object, &$action)
	// {
	// 	global $db, $langs, $conf, $user;
	// 	$this->resprints = '';
	// 	return 0;
	// }

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager) {
		if ($object->table_element != 'pickup_pickup') {
			return 0;
		}
		global $db, $conf, $user, $langs;

		$errors = array(); // Error counter
		$pickup_rights = $object->getRights();

		// Action addline
		if ($action == 'addline' && $parameters['permissionedit'] && !empty($object->id)) {
			$langs->load('errors');

			$fk_product = GETPOST('fk_product', 'int');
			$qty = price2num(GETPOST('qty', 'int'));

			if ($qty == '') {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')));
			}
			if (!($fk_product > 0)) {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Product')));
			}

			if (!count($errors)) {
				$line = $object->initPickupLine($fk_product, $qty);
				$result = $line->create($user);
				if ($result <= 0) {
					if (!empty($line->error)) {
						array_push($errors, $line->error);
					}
					if (!empty($line->errors)) {
						$errors = array_merge($errors, $line->errors);
					}
				} else {
					$action = '';
					unset($_POST['fk_product']);
					unset($_POST['qty']);

					$object->fetchLines();
				}
			}
		}

		// Action updateline
		if ($action == 'updateline' && !empty(GETPOST('cancel', 'aZ09'))) {
			$action = '';
		}
		if ($action == 'updateline' && $parameters['permissionedit'] && !empty($object->id)) {
			$langs->load('errors');

			$lineid   = GETPOST('lineid', 'int');

			$line_desc = GETPOST('description', 'none');
			$qty = price2num(GETPOST('qty', 'int'));
			$weight = price2num(GETPOST('weight'));
			$weight_units = GETPOST('weight_units', 'int');
			$deee_type = GETPOST('options_type_deee', 'alpha');

			if ($qty == '') {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')));
			}
			if ($weight == '') {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Weight')));
			}

			if (!count($errors)) {
				$line = new PickupLine($db);
				if ($line->fetch($lineid) <= 0) {
					if (!empty($line->error)) {
						array_push($errors, $line->error);
					}
					if (!empty($line->errors)) {
						$errors = array_merge($errors, $line->errors);
					}
				} else if ($line->fk_pickup !== $object->id) {
					dol_syslog(__METHOD__ . ' ' . 'Line '.$line->fk_pickup.' is not from pickup '.$object->id, LOG_ERR);
				} else {
					$line->qty = $qty;
					$line->description = $line_desc;
					$line->weight = $weight;
					$line->weight_units = $weight_units;
					if (!$deee_type) {
						$line->deee = 0;
						$line->deee_type = NULL;
					} else {
						$line->deee = 1;
						$line->deee_type = $deee_type;
					}

					$result = $line->update($user);
					if ($result <= 0) {
						if (!empty($line->error)) {
							array_push($errors, $line->error);
						}
						if (!empty($line->errors)) {
							$errors = array_merge($errors, $line->errors);
						}
					} else {
						unset($_POST['qty']);
						unset($_POST['weight']);
						unset($_POST['weight_units']);
						unset($_POST['description']);

						$object->fetchLines();
					}
				}
			}

			if (count($errors)) {
				$action = 'editline';
			} else {
				$action = '';
			}
		}

		if ($action == 'fixline' && $parameters['permissionedit'] && !empty($object->id)) {
			dol_syslog(__METHOD__ . ' ' . 'fixline action', LOG_DEBUG);

			$lineid   = GETPOST('lineid', 'int');

			$line = new PickupLine($db);
			if ($line->fetch($lineid) <= 0) {
				if (!empty($line->error)) {
					array_push($errors, $line->error);
				}
				if (!empty($line->errors)) {
					$errors = array_merge($errors, $line->errors);
				}
			} else if ($line->fk_pickup !== $object->id) {
				dol_syslog(__METHOD__ . ' ' . 'Line '.$line->fk_pickup.' is not from pickup '.$object->id, LOG_ERR);
			} else {
				require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
				$product = new Product($db);
				if ($product->fetch($line->fk_product) <= 0) {
					dol_syslog(__METHOD__ . ' ' . 'Product '.$line->fk_product.' not found', LOG_ERR);
				} else {
					$line->weight = $product->weight;
					$line->weight_units = $product->weight_units;
					$line->deee = $product->array_options['options_deee'];
					$line->deee_type = $product->array_options['options_type_deee'];

					$result = $line->update($user);
					if ($result <= 0) {
						if (!empty($line->error)) {
							array_push($errors, $line->error);
						}
						if (!empty($line->errors)) {
							$errors = array_merge($errors, $line->errors);
						}
					}
				}
			}
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}

		if ($action == 'confirm_processing' && GETPOST('confirm') == 'yes' && $pickup_rights->workflow->processing) {
			$object->status = Pickup::STATUS_PROCESSING;
			if ($object->update($user) <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}
		if ($action == 'confirm_settodraft' && GETPOST('confirm') == 'yes' && $pickup_rights->workflow->processing) {
			$object->status = Pickup::STATUS_DRAFT;
			if ($object->update($user) <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}

		if ($action == 'confirm_includeinstock' && GETPOST('confirm') == 'yes' && $pickup_rights->workflow->stock) {
			$object->getLinesArray();
			$nb_ok = 0;
			$movement_label = empty($object->label) ? $object->ref : $object->ref . ' - ' . $object->label;
			$inventorycode = $object->ref;

			foreach ($object->lines as $line) {
				if (empty($line->fk_stock_movement)) {
					require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
					$product = new Product($db);
					if ($product->fetch($line->fk_product) <= 0) {
						setEventMessages($product->error, $product->errors, 'errors');
						continue;
					}

					$result = $product->correct_stock($user, $object->fk_entrepot, $line->qty, 0, $movement_label, 0, $inventorycode);
					if ($result <= 0) {
						setEventMessages($product->error, $product->errors, 'errors');
					} else {
						$nb_ok++;

						// Now we have to find the stock_movement.... because correct_stock does not return it.
						require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
						$stock_movement = new MouvementStock($db);
						$sql = 'SELECT t.rowid FROM '.MAIN_DB_PREFIX.$stock_movement->table_element.' as t';
						$sql.= " WHERE t.inventorycode = '".$db->escape($inventorycode)."'";
						$sql.= " AND t.fk_product = '".$db->escape($line->fk_product)."'";
						$sql.= " ORDER BY t.rowid DESC"; // if the same product is there multiple times, last inserted should be first.

						$resql = $db->query($sql);
        		if ($resql && $db->num_rows($resql) > 0) {
              $obj = $this->db->fetch_object($resql);
							$line->fk_stock_movement = $obj->rowid;
							$line->update($user);
						} else {
							setEventMessages($langs->trans('PickupIncludeInStockMovementNotFoundError'), null, 'errors');
						}
						$db->free($resql);
					}
				}
			}

			if ($nb_ok > 0) {
				$object->status = Pickup::STATUS_STOCK;
				if ($object->update($user) <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}

			if ($nb_ok > 0 ) {
				setEventMessages($langs->trans('PickupIncludeInStockOk'), null, 'mesgs');
			}
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=builddoc');
			exit;
		}

		if ($action == 'confirm_sign' && GETPOST('confirm') == 'yes' && $pickup_rights->workflow->sign) {
			$object->status = Pickup::STATUS_SIGNED;
			if ($object->update($user) <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}

		// /* print_r($parameters); print_r($object); echo "action: " . $action; */
		// if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2')))	    // do something only for the context 'somecontext1' or 'somecontext2'
		// {
		// 	// Do what you want here...
		// 	// You can for example call global vars like $fieldstosearchall to overwrite them, or update database depending on $action and $_POST values.
		// }
		if (!count($errors)) {
			$this->results = array();
			$this->resprints = '';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors = $errors;
			return -1;
		}
	}


	// /**
	//  * Overloading the doMassActions function : replacing the parent's function with the one below
	//  *
	//  * @param   array           $parameters     Hook metadatas (context, etc...)
	//  * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	//  * @param   string          $action         Current action (if set). Generally create or edit or null
	//  * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	//  * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	//  */
	// public function doMassActions($parameters, &$object, &$action, $hookmanager)
	// {
	// 	global $conf, $user, $langs;

	// 	$error = 0; // Error counter

	// 	/* print_r($parameters); print_r($object); echo "action: " . $action; */
	// 	if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
	// 	{
	// 		foreach ($parameters['toselect'] as $objectid)
	// 		{
	// 			// Do action on each object id
	// 		}
	// 	}

	// 	if (!$error) {
	// 		$this->results = array('myreturn' => 999);
	// 		$this->resprints = 'A text to show';
	// 		return 0; // or return 1 to replace standard code
	// 	} else {
	// 		$this->errors[] = 'Error message';
	// 		return -1;
	// 	}
	// }


	// /**
	//  * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	//  *
	//  * @param   array           $parameters     Hook metadatas (context, etc...)
	//  * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	//  * @param   string          $action         Current action (if set). Generally create or edit or null
	//  * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	//  * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	//  */
	// public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	// {
	// 	global $conf, $user, $langs;

	// 	$error = 0; // Error counter

	// 	/* print_r($parameters); print_r($object); echo "action: " . $action; */
	// 	if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
	// 	{
	// 		$this->resprints = '<option value="0"'.($disabled ? ' disabled="disabled"' : '').'>'.$langs->trans("PickupMassAction").'</option>';
	// 	}

	// 	if (!$error) {
	// 		return 0; // or return 1 to replace standard code
	// 	} else {
	// 		$this->errors[] = 'Error message';
	// 		return -1;
	// 	}
	// }



	// /**
	//  * Execute action
	//  *
	//  * @param	array	$parameters     Array of parameters
	//  * @param   Object	$object		   	Object output on PDF
	//  * @param   string	$action     	'add', 'update', 'view'
	//  * @return  int 		        	<0 if KO,
	//  *                          		=0 if OK but we want to process standard actions too,
	//  *  	                            >0 if OK and we want to replace standard actions.
	//  */
	// public function beforePDFCreation($parameters, &$object, &$action)
	// {
	// 	global $conf, $user, $langs;
	// 	global $hookmanager;

	// 	$outputlangs = $langs;

	// 	$ret = 0; $deltemp = array();
	// 	dol_syslog(get_class($this).'::executeHooks action='.$action);

	// 	/* print_r($parameters); print_r($object); echo "action: " . $action; */
	// 	if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
	// 	{
	// 	}

	// 	return $ret;
	// }

	// /**
	//  * Execute action
	//  *
	//  * @param	array	$parameters     Array of parameters
	//  * @param   Object	$pdfhandler     PDF builder handler
	//  * @param   string	$action         'add', 'update', 'view'
	//  * @return  int 		            <0 if KO,
	//  *                                  =0 if OK but we want to process standard actions too,
	//  *                                  >0 if OK and we want to replace standard actions.
	//  */
	// public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	// {
	// 	global $conf, $user, $langs;
	// 	global $hookmanager;

	// 	$outputlangs = $langs;

	// 	$ret = 0; $deltemp = array();
	// 	dol_syslog(get_class($this).'::executeHooks action='.$action);

	// 	/* print_r($parameters); print_r($object); echo "action: " . $action; */
	// 	if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
	// 	{
	// 	}

	// 	return $ret;
	// }

	public function pdf_writelinedesc($parameters, &$object, &$action) {
		if ($object->table_element != 'pickup_pickup') {
			return 0;
		}

		global $db;
		$pdf = $parameters['pdf'];
		$i = $parameters['i'];
		$outputlangs = $parameters['outputlangs'];
		$w = $parameters['w'];
		$h = $parameters['h'];
		$posx = $parameters['posx'];
		$posy = $parameters['posy'];
		$hideref = $parameters['hideref'];
		$hidedesc = $parameters['hidedesc'];

		$line = $object->lines[$i];
		$text = '';

		$product = new Product($db);
		$product->fetch($line->fk_product);
		$cats = $line->getProductCategoriesLabels();

		if (count($cats) > 0) {
			$text.= join(', ', $cats) . '<br>';
		}
		if ($product->label != $product->ref) {
			$text.= $product->label . ' - ';
		}
		$text.= $product->ref;

		$text.= '<br>';
		$text.= dol_htmlentitiesbr($line->description);

		// Fix bug of some HTML editors that replace links <img src="http://localhostgit/viewimage.php?modulepart=medias&file=image/efd.png" into <img src="http://localhostgit/viewimage.php?modulepart=medias&amp;file=image/efd.png"
		// We make the reverse, so PDF generation has the real URL.
		$text = preg_replace('/(<img[^>]*src=")([^"]*)(&amp;)([^"]*")/', '\1\2&\4', $text, -1, $nbrep);

		$pdf->writeHTMLCell($w, $h, $posx, $posy, $outputlangs->convToOutputCharset($text), 0, 1, false, true, 'J', true);
		$this->resPrint = $text;
		return 1;
	}


	// /**
	//  * Overloading the loadDataForCustomReports function : returns data to complete the customreport tool
	//  *
	//  * @param   array           $parameters     Hook metadatas (context, etc...)
	//  * @param   string          $action         Current action (if set). Generally create or edit or null
	//  * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	//  * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	//  */
	// public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	// {
	// 	global $conf, $user, $langs;

	// 	$langs->load("pickup@pickup");

	// 	$this->results = array();

	// 	$head = array();
	// 	$h = 0;

	// 	if ($parameters['tabfamily'] == 'pickup') {
	// 		$head[$h][0] = dol_buildpath('/module/index.php', 1);
	// 		$head[$h][1] = $langs->trans("Home");
	// 		$head[$h][2] = 'home';
	// 		$h++;

	// 		$this->results['title'] = $langs->trans("Pickup");
	// 		$this->results['picto'] = 'pickup@pickup';
	// 	}

	// 	$head[$h][0] = 'customreports.php?objecttype='.$parameters['objecttype'].(empty($parameters['tabfamily']) ? '' : '&tabfamily='.$parameters['tabfamily']);
	// 	$head[$h][1] = $langs->trans("CustomReports");
	// 	$head[$h][2] = 'customreports';

	// 	$this->results['head'] = $head;

	// 	return 1;
	// }



	// /**
	//  * Overloading the restrictedArea function : check permission on an object
	//  *
	//  * @param   array           $parameters     Hook metadatas (context, etc...)
	//  * @param   string          $action         Current action (if set). Generally create or edit or null
	//  * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	//  * @return  int 		      			  	<0 if KO,
	//  *                          				=0 if OK but we want to process standard actions too,
	//  *  	                            		>0 if OK and we want to replace standard actions.
	//  */
	// public function restrictedArea($parameters, &$action, $hookmanager)
	// {
	// 	global $user;

	// 	if ($parameters['features'] == 'myobject') {
	// 		if ($user->rights->pickup->myobject->read) {
	// 			$this->results['result'] = 1;
	// 			return 1;
	// 		} else {
	// 			$this->results['result'] = 0;
	// 			return 1;
	// 		}
	// 	}

	// 	return 0;
	// }

	/* Add here any other hooked methods... */
	public function printObjectLineTitle($parameters, &$object, &$action) {
		if ($parameters['table_element_line'] != 'pickup_pickupline') {
			return 0;
		}
		global $conf, $user, $langs, $hookmanager, $extrafields;
		$tpl = dol_buildpath('custom/pickup/core/tpl/pickupline_title.tpl.php');
		if (empty($conf->file->strict_mode)) {
			@include $tpl;
		} else {
			include $tpl; // for debug
		}
		return 1;
	}

	public function printObjectLine($parameters, &$object, &$action) {
		if ($parameters['table_element_line'] != 'pickup_pickupline') {
			return 0;
		}
		global $conf, $user, $langs, $hookmanager, $extrafields;
		global $form;
		global $db;
		$line = $parameters['line'];
		$i = $parameters['i'];
		$selected = $parameters['selected'];
		$line_product = 0;

		$stock_movement = 0;
		if ($line->fk_stock_movement) {
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
			$stock_movement = new MouvementStock($db);
			$stock_movement->fetch($line->fk_stock_movement);
		}

		if ($line->fk_product > 0) {
			$line_product = new Product($this->db);
			$line_product->fetch($line->fk_product);
			$line_product->fetch_optionals();
		}
		
		if ($object->status == Pickup::STATUS_DRAFT && $action == 'editline' && $selected == $line->id) {
			$tpl = dol_buildpath('custom/pickup/core/tpl/pickupline_edit.tpl.php');
		} else if ($action != 'editline' || $selected != $line->id) {
			if ($object->status != Pickup::STATUS_DRAFT) {
				$disableedit = 1;
				$disableremove = 1;
			}
			$tpl = dol_buildpath('custom/pickup/core/tpl/pickupline_view.tpl.php');
		}

		if (empty($tpl)) {
			return 0;
		}

		if (empty($conf->file->strict_mode)) {
			@include $tpl;
		} else {
			include $tpl; // for debug
		}
		return 1;
	}

	public function printObjectSubLine($parameters, &$object, &$action) {
		if ($parameters['table_element_line'] != 'pickup_pickupline') {
			return 0;
		}
		$this->error = 'There should not be sublines for PickupLine';
		return -1;
	}

	public function formAddObjectLine($parameters, &$object, &$action) {
		if ($parameters['table_element_line'] != 'pickup_pickupline') {
			return 0;
		}
		global $conf, $user, $langs, $hookmanager, $extrafields;
		global $form;
		$tpl = dol_buildpath('custom/pickup/core/tpl/pickupline_create.tpl.php');
		if (empty($conf->file->strict_mode)) {
			@include $tpl;
		} else {
			include $tpl; // for debug
		}
		return 0;
	}

	public function addMoreActionsButtons($parameters, &$object, &$action) {
		if ($object->table_element != 'pickup_pickup') {
			return 0;
		}
		global $langs;
		$pickup_rights = $object->getRights();
		if ($object->status == Pickup::STATUS_DRAFT && $pickup_rights->workflow->processing) {
			if (!empty($object->lines)) { // assuming lines were fetched before. If not, no button, thats not a problem.
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=processing">'.$langs->trans("PickupStatusProcessing").'</a>'."\n";
			}
		}
		if ($object->status == Pickup::STATUS_PROCESSING && $pickup_rights->workflow->stock) {
			if (!empty($object->lines)) { // assuming lines were fetched before. If not, no button, thats not a problem.
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=includeinstock">'.$langs->trans("PickupActionIncludeInStock").'</a>'."\n";
			}
		}
		if ($object->status == Pickup::STATUS_PROCESSING && $pickup_rights->workflow->processing) {
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=settodraft">'.$langs->trans("SetToDraft").'</a>'."\n";
		}
		if ($object->status == Pickup::STATUS_STOCK && $pickup_rights->workflow->sign) {
			// FIXME: there must be at least one attachment...
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=sign">'.$langs->trans("PickupActionSign").'</a>'."\n";
		}
		return 0;
	}

	public function formConfirm($parameters, &$object, &$action) {
		if ($object->table_element != 'pickup_pickup') {
			return 0;
		}
		if ($action == 'processing') {
			global $form, $langs;
			$this->resprints = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('Processing'), $langs->trans('ConfirmPickupActionProcessing'), 'confirm_processing', '', 0, 1);
		}
		if ($action == 'settodraft') {
			global $form, $langs;
			$this->resprints = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('TitleSetToDraft'), $langs->trans('ConfirmSetToDraft'), 'confirm_settodraft', '', 0, 1);
		}
		if ($action == 'includeinstock') {
			global $form, $langs;
			$this->resprints = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('PickupActionIncludeInStock'), $langs->trans('ConfirmPickupActionIncludeInStock'), 'confirm_includeinstock', '', 0, 1);
		}
		if ($action == 'sign') {
			global $form, $langs;
			$this->resprints = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('PickupActionSign'), $langs->trans('ConfirmPickupActionSign'), 'confirm_sign', '', 0, 1);
		}
		return 0;
	}
}
