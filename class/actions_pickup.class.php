<?php
/* Copyright (C) 2021-2022		John Livingston		<license@john-livingston.fr>
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
 * \file    pickup/class/actions_pickup.class.php
 * \ingroup pickup
 * \brief   Example hook overload.
 *
 */

dol_include_once('/pickup/class/pickup.class.php');

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

			if ($conf->global->PICKUP_UNITS_EDIT_MODE === 'pickupline') {
				$weight = price2num(GETPOST('weight'));
				$weight_units = GETPOST('weight_units', 'int');
				$length = price2num(GETPOST('length'));
				$length_units = GETPOST('length_units', 'int');
				$surface = price2num(GETPOST('surface'));
				$surface_units = GETPOST('surface_units', 'int');
				$volume = price2num(GETPOST('volume'));
				$volume_units = GETPOST('volume_units', 'int');

				if ($conf->global->PICKUP_UNITS_WEIGHT === 'mandatory' && $weight == '') {
					array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Weight')));
				}
				if ($conf->global->PICKUP_UNITS_LENGTH === 'mandatory' && $length == '') {
					array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Length')));
				}
				if ($conf->global->PICKUP_UNITS_SURFACE === 'mandatory' && $surface == '') {
					array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Surfance')));
				}
				if ($conf->global->PICKUP_UNITS_VOLUME === 'mandatory' && $volume == '') {
					array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Volume')));
				}
			} else {
				$weight = '';
				$weight_units = '';
				$length = '';
				$length_units = '';
				$surface = '';
				$surface_units = '';
				$volume = '';
				$volume_units = '';
			}

			if (!count($errors)) {
				$line = $object->initPickupLine($fk_product, $qty);

				if ($conf->global->PICKUP_UNITS_EDIT_MODE === 'pickupline') {
					if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) {
						$line->weight = $weight;
						$line->weight_units = $weight_units;
					}
					if (!empty($conf->global->PICKUP_UNITS_LENGTH)) {
						$line->length = $length;
						$line->length_units = $length_units;
					}
					if (!empty($conf->global->PICKUP_UNITS_SURFACE)) {
						$line->surface = $surface;
						$line->surface_units = $surface_units;
					}
					if (!empty($conf->global->PICKUP_UNITS_VOLUME)) {
						$line->volume = $volume;
						$line->volume_units = $volume_units;
					}
				}

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
					unset($_POST['weight']);
					unset($_POST['weight_units']);
					unset($_POST['length']);
					unset($_POST['length_units']);
					unset($_POST['surface']);
					unset($_POST['surface_units']);
					unset($_POST['volume']);
					unset($_POST['volume_units']);
					unset($_POST['description']);

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

			$line_description = '';
			if (!empty($conf->global->PICKUP_USE_PICKUPLINE_DESCRIPTION)) {
				$line_description = GETPOST('description', 'none');
			}

			$qty = price2num(GETPOST('qty', 'int'));

			$weight = price2num(GETPOST('weight'));
			$weight_units = GETPOST('weight_units', 'int');
			$length = price2num(GETPOST('length'));
			$length_units = GETPOST('length_units', 'int');
			$surface = price2num(GETPOST('surface'));
			$surface_units = GETPOST('surface_units', 'int');
			$volume = price2num(GETPOST('volume'));
			$volume_units = GETPOST('volume_units', 'int');

			$deee_type = GETPOST('options_pickup_deee_type', 'alpha');
			$batch = GETPOSTISSET('batch') ? GETPOST('batch', 'alpha') : null;

			if ($qty == '') {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')));
			}
			if ($conf->global->PICKUP_UNITS_WEIGHT === 'mandatory' && $weight == '') {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Weight')));
			}
			if ($conf->global->PICKUP_UNITS_LENGTH === 'mandatory' && $length == '') {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Length')));
			}
			if ($conf->global->PICKUP_UNITS_SURFACE === 'mandatory' && $surface == '') {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Surfance')));
			}
			if ($conf->global->PICKUP_UNITS_VOLUME === 'mandatory' && $volume == '') {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Volume')));
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
					dol_syslog(__METHOD__ . ' ' . 'Line '.$line->id.' is not from pickup '.$object->id, LOG_ERR);
				} else {
					$line->qty = $qty;
					if (!empty($conf->global->PICKUP_USE_PICKUPLINE_DESCRIPTION)) {
						$line->description = $line_description;
					}
					if (!empty($conf->global->PICKUP_USE_DEEE)) {
						if (!$deee_type) {
							$line->deee = 0;
							$line->deee_type = NULL;
						} else {
							$line->deee = 1;
							$line->deee_type = $deee_type;
						}
					}

					if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) {
						$line->weight = $weight;
						$line->weight_units = $weight_units;
					}
					if (!empty($conf->global->PICKUP_UNITS_LENGTH)) {
						$line->length = $length;
						$line->length_units = $length_units;
					}
					if (!empty($conf->global->PICKUP_UNITS_SURFACE)) {
						$line->surface = $surface;
						$line->surface_units = $surface_units;
					}
					if (!empty($conf->global->PICKUP_UNITS_VOLUME)) {
						$line->volume = $volume;
						$line->volume_units = $volume_units;
					}

					$line->batch = $batch;

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
						unset($_POST['length']);
						unset($_POST['length_units']);
						unset($_POST['surface']);
						unset($_POST['surface_units']);
						unset($_POST['volume']);
						unset($_POST['volume_units']);
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

		if ($action == 'setlinebatch' && $parameters['permissionedit'] && !empty($object->id)) {
			dol_syslog(__METHOD__ . ' ' . 'setlinebatch action', LOG_DEBUG);

			$lineid   = GETPOST('lineid', 'int');

			$line = new PickupLine($db);
			if ($line->fetch($lineid) <= 0) {
				dol_syslog(__METHOD__ . ' ' . 'Line '.$lineid.' not fetched', LOG_ERR);
				if (!empty($line->error)) {
					array_push($errors, $line->error);
				}
				if (!empty($line->errors)) {
					$errors = array_merge($errors, $line->errors);
				}
			} else if ($line->fk_pickup !== $object->id) {
				dol_syslog(__METHOD__ . ' ' . 'Line '.$line->id.' is not from pickup '.$object->id, LOG_ERR);
			} else {
				$line->batch = GETPOST('batch', 'alpha');
				dol_syslog(__METHOD__ . ' ' . 'Line '.$line->is. ' changing batch to '.$line->batch, LOG_DEBUG);
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
			if (!count($errors)) {
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
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
				dol_syslog(__METHOD__ . ' ' . 'Line '.$line->id.' is not from pickup '.$object->id, LOG_ERR);
			} else {
				require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
				$product = new Product($db);
				if ($product->fetch($line->fk_product) <= 0) {
					dol_syslog(__METHOD__ . ' ' . 'Product '.$line->fk_product.' not found', LOG_ERR);
				} else {
					if (!empty($conf->global->PICKUP_USE_DEEE)) {
						$line->deee = $product->array_options['options_pickup_deee'];
						$line->deee_type = $product->array_options['options_pickup_deee_type'];
					}

					if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) {
						$line->weight = $product->weight;
						$line->weight_units = $product->weight_units;
					}
					if (!empty($conf->global->PICKUP_UNITS_LENGTH)) {
						$line->length = $product->length;
						$line->length_units = $product->length_units;
					}
					if (!empty($conf->global->PICKUP_UNITS_SURFACE)) {
						$line->surface = $product->surface;
						$line->surface_units = $product->surface_units;
					}
					if (!empty($conf->global->PICKUP_UNITS_VOLUME)) {
						$line->volume = $product->volume;
						$line->volume_units = $product->volume_units;
					}

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
			$db->begin();
			$object->getLinesArray();
			$nb_ok = 0;
			$nb_error = 0;
			$movement_label = empty($object->label) ? $object->ref : $object->ref . ' - ' . $object->label;
			$inventorycode = $object->ref;

			foreach ($object->lines as $line) {
				if (empty($line->fk_stock_movement)) {
					require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
					$product = new Product($db);
					if ($product->fetch($line->fk_product) <= 0) {
						$nb_error++;
						setEventMessages($product->error, $product->errors, 'errors');
						continue;
					}

					if (!empty($conf->productbatch->enabled) && ($product->hasbatch() || !empty($line->batch))) {
						$result = $product->correct_stock_batch($user, $object->fk_entrepot, $line->qty, 0, $movement_label, 0, '', '', $line->batch, $inventorycode, 'Pickup@pickup', $object->id);
					} else {
						$result = $product->correct_stock($user, $object->fk_entrepot, $line->qty, 0, $movement_label, 0, $inventorycode, 'Pickup@pickup', $object->id);
					}
					if ($result <= 0) {
						$nb_error++;
						setEventMessages($product->error, $product->errors, 'errors');
					} else {
						$nb_ok++;

						// Now we have to find the stock_movement.... because correct_stock does not return it.
						require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
						$stock_movement = new MouvementStock($db);
						$sql = 'SELECT t.rowid, t.origintype, t.fk_origin FROM '.MAIN_DB_PREFIX.$stock_movement->table_element.' as t';
						$sql.= " WHERE t.inventorycode = '".$db->escape($inventorycode)."'";
						$sql.= " AND t.fk_product = '".$db->escape($line->fk_product)."'";
						$sql.= " ORDER BY t.rowid DESC"; // if the same product is there multiple times, last inserted should be first.

						$resql = $db->query($sql);
        		if ($resql && $db->num_rows($resql) > 0) {
              $mov_obj = $this->db->fetch_object($resql);
							$line->fk_stock_movement = $mov_obj->rowid;
							if ($line->update($user) <= 0) {
								$nb_error++;
							}
							if ($mov_obj->origintype === '' && $mov_obj->fk_origin == 0) {
								// Before Dolibarr 15, there is a bug in correct_stock and correct_stock_batch, that does not save these informations...
								// Fixing it...
								dol_syslog(__METHOD__ . ' ' . 'confirm_includeinstock action: correcting the movement '.$mov_obj->rowid.' origin', LOG_DEBUG);
								$sql_mov = "UPDATE " . MAIN_DB_PREFIX."stock_mouvement SET ";
								$sql_mov.= " origintype = '".$db->escape('Pickup@pickup')."' , ";
								$sql_mov.= " fk_origin = '".$db->escape($object->id)."' ";
								$sql_mov.= " WHERE rowid = '".$db->escape($mov_obj->rowid)."'";
								$resql_mov = $db->query($sql_mov);
								if (!$resql_mov) {
									dol_syslog(__METHOD__ . ' ' . 'confirm_includeinstock action: Failed to fix movement origin : '.$db->lasterror(), LOG_ERR);
								}
							} else {
								dol_syslog(__METHOD__ . ' ' . 'confirm_includeinstock action: no need to correct movement '.$mov_obj->rowid.' origin', LOG_DEBUG);
							}
						} else {
							$nb_error++;
							setEventMessages($langs->trans('PickupIncludeInStockMovementNotFoundError'), null, 'errors');
						}
						$db->free($resql);
					}
				}
			}

			if ($nb_error === 0 && $nb_ok > 0) {
				$object->status = Pickup::STATUS_STOCK;
				if ($object->update($user) <= 0) {
					$nb_error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}

			if ($nb_error === 0 && $nb_ok > 0 ) {
				setEventMessages($langs->trans('PickupIncludeInStockOk'), null, 'mesgs');
			}

			if ($nb_error === 0) {
				$db->commit();
			} else {
				$db->rollback();
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

		global $db, $conf;
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

		if (!empty($conf->global->PICKUP_USE_PICKUPLINE_DESCRIPTION)) {
			$text.= '<br>';
			$text.= dol_htmlentitiesbr($line->description);
		}

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
		global $langs, $conf;
		$pickup_rights = $object->getRights();
		if ($object->status == Pickup::STATUS_DRAFT) {
			print dolGetButtonAction(
				'',
				$langs->trans('PickupStatusProcessing'),
				'default',
				$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=processing&token='.newToken(),
				'',
				(
					!empty($object->lines) // assuming lines were fetched before. If not, no button, thats not a problem.
					&&
					$pickup_rights->workflow->processing
				)
			);
		}
		if (($object->status == Pickup::STATUS_DRAFT || $object->status == Pickup::STATUS_PROCESSING)) {
			print dolGetButtonAction(
				'',
				$langs->trans('PickupActionIncludeInStock'),
				'default',
				$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=includeinstock&token='.newToken(),
				'',
				(
					!empty($object->lines) // assuming lines were fetched before. If not, no button, thats not a problem.
					&&
					$pickup_rights->workflow->stock
				)
			);
		}
		if ($object->status == Pickup::STATUS_PROCESSING) {
			print dolGetButtonAction(
				'',
				$langs->trans('SetToDraft'),
				'default',
				$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=settodraft&token='.newToken(),
				'',
				$pickup_rights->workflow->processing
			);
		}
		if ($object->status == Pickup::STATUS_STOCK && empty($conf->global->PICKUP_NO_SIGN_STATUS)) {
			// FIXME: there must be at least one attachment...
			print dolGetButtonAction(
				'',
				$langs->trans('PickupActionSign'),
				'default',
				$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=sign&token='.newToken(),
				'',
				$pickup_rights->workflow->sign
			);
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

	/**
	 * Hook to add email element template
	 *
	 * @param array 		$parameters   Parameters
	 * @param 		$object       Object for action
	 * @param string 		$action       Action string
	 * @param HookManager 	$hookmanager  Hookmanager object
	 * @return int
	 */
	public function emailElementlist($parameters, &$object, &$action, $hookmanager) {
		global $langs, $conf;
		$langs->load("pickup@pickup");

		$error = 0;

		if (!empty($conf->pickup->enabled) && !empty($conf->global->PICKUP_SEND_MAIL)) {
			$this->results = array('pickup' => $langs->trans('MailToSendPickup'));
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}
}
