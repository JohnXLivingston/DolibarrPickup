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
 * \file    collecte/class/actions_collecte.class.php
 * \ingroup collecte
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsCollecte
 */
class ActionsCollecte
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
		global $db, $conf, $user, $langs;

		$errors = array(); // Error counter

		// Action addline
		if ($action == 'addline' && $parameters['permissiontoadd'] && !empty($object->id)) {
			$langs->load('errors');

			$idprod = GETPOST('idprod', 'int');
			$qty = price2num(GETPOST('qty', 'int'));

			if ($qty == '') {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')));
			}
			if (!($idprod > 0)) {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Product')));
			}

			if (!count($errors)) {
				$line = $object->initCollecteLine($idprod, $qty);
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
					unset($_POST['idprod']);
					unset($_POST['qty']);

					$object->fetchLines();
				}
			}
		}

		// Action updateline
		if ($action == 'updateline' && !empty(GETPOST('cancel', 'aZ09'))) {
			$action = '';
		}
		if ($action == 'updateline' && $parameters['permissiontoadd'] && !empty($object->id)) {
			$langs->load('errors');

			$lineid   = GETPOST('lineid', 'int');

			$line_desc = GETPOST('description', 'nohtml');
			$qty = price2num(GETPOST('qty', 'int'));
			$weight = price2num(GETPOST('weight'));
			// $weight_units = GETPOST('weight_units', 'int');

			if ($qty == '') {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')));
			}
			if ($weight == '') {
				array_push($errors, $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Weight')));
			}

			if (!count($errors)) {
				$line = new CollecteLine($db);
				if ($line->fetch($lineid) <= 0) {
					if (!empty($line->error)) {
						array_push($errors, $line->error);
					}
					if (!empty($line->errors)) {
						$errors = array_merge($errors, $line->errors);
					}
				} else {
					$line->qty = $qty;
					$line->description = $line_desc;
					$line->weight = $weight;
					// $line->weight_units = $weight_units;

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
						// unset($_POST['weight_units']);
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
	// 		$this->resprints = '<option value="0"'.($disabled ? ' disabled="disabled"' : '').'>'.$langs->trans("CollecteMassAction").'</option>';
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

	// 	$langs->load("collecte@collecte");

	// 	$this->results = array();

	// 	$head = array();
	// 	$h = 0;

	// 	if ($parameters['tabfamily'] == 'collecte') {
	// 		$head[$h][0] = dol_buildpath('/module/index.php', 1);
	// 		$head[$h][1] = $langs->trans("Home");
	// 		$head[$h][2] = 'home';
	// 		$h++;

	// 		$this->results['title'] = $langs->trans("Collecte");
	// 		$this->results['picto'] = 'collecte@collecte';
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
	// 		if ($user->rights->collecte->myobject->read) {
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
		if ($parameters['table_element_line'] != 'collecte_collecteline') {
			return 0;
		}
		global $conf, $user, $langs, $hookmanager, $extrafields;
		$tpl = dol_buildpath('custom/collecte/core/tpl/collecteline_title.tpl.php');
		if (empty($conf->file->strict_mode)) {
			@include $tpl;
		} else {
			include $tpl; // for debug
		}
		return 1;
	}

	public function printObjectLine($parameters, &$object, &$action) {
		if ($parameters['table_element_line'] != 'collecte_collecteline') {
			return 0;
		}
		global $conf, $user, $langs, $hookmanager, $extrafields;
		global $form;
		global $db;
		$line = $parameters['line'];
		$i = $parameters['i'];
		$selected = $parameters['selected'];
		$object_rights = $object->getRights();

		$stock_movement = 0;
		if ($line->fk_stock_movement) {
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
			$stock_movement = new MouvementStock($db);
			$stock_movement->fetch($line->fk_stock_movement);
		}

		if ($object->status == 0 && $action == 'editline' && $selected == $line->id) {
			$tpl = dol_buildpath('custom/collecte/core/tpl/collecteline_edit.tpl.php');
		} else if ($action != 'editline' || $selected != $line->id) {
			$product_static = new Product($this->db);
			$product_static->fetch($line->fk_product);

			$text = $product_static->getNomUrl(1);
			$description = $line->description;

			if ($object->status != 0) {
				$disableedit = 1;
				$disableremove = 1;
			}
			$tpl = dol_buildpath('custom/collecte/core/tpl/collecteline_view.tpl.php');
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
		if ($parameters['table_element_line'] != 'collecte_collecteline') {
			return 0;
		}
		$this->error = 'There should not be sublines for CollecteLine';
		return -1;
	}

	public function formAddObjectLine($parameters, &$object, &$action) {
		if ($parameters['table_element_line'] != 'collecte_collecteline') {
			return 0;
		}
		global $conf, $user, $langs, $hookmanager, $extrafields;
		global $form;
		$tpl = dol_buildpath('custom/collecte/core/tpl/collecteline_create.tpl.php');
		if (empty($conf->file->strict_mode)) {
			@include $tpl;
		} else {
			include $tpl; // for debug
		}
		return 0;
	}

	public function addMoreActionsButtons($parameters, &$object, &$action) {
		if ($object->table_element != 'collecte_collecte') {
			return 0;
		}
		global $langs;
		if ($object->status == Collecte::STATUS_VALIDATED) {
			if (!empty($object->lines)) { // assuming lines were fetched before. If not, no button, thats not a problem.
				// is there at least one line with no fk_stock_movement?
				foreach ($object->lines as $line) {
					if (empty($line->fk_stock_movement)) {
						// display the button...
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=includeinstock">'.$langs->trans("CollecteLineIncludeInStock").'</a>'."\n";
						break;
					}
				}
			}
		}
		return 0;
	}

	public function formConfirm($parameters, &$object, &$action) {
		if ($object->table_element != 'collecte_collecte') {
			return 0;
		}
		if ($action == 'includeinstock') {
			global $form, $langs;
			$this->resprints = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CollecteLineIncludeInStock'), $langs->trans('ConfirmCollecteLineIncludeInStock'), 'confirm_includeinstock', '', 0, 1);
		}
		return 0;
	}
}
