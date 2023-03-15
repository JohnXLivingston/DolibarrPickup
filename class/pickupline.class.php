<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2021-2022		John Livingston		<license@john-livingston.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/pickupline.class.php
 * \ingroup     pickup
 * \brief       This file is a CRUD class file for PickupLine (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
dol_include_once('/pickup/class/pickup.class.php');
dol_include_once('/pickup/class/pbatch.class.php');

/**
 * Class for PickupLine
 */
class PickupLine extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'pickupline';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'pickup_pickupline';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for pickupline. Must be the part after the 'object_' into object_pickupline.png
	 */
	public $picto = 'pickupline@pickup';

	/**
	 * @var array	List of child tables. To know object to delete on cascade.
	 */
	protected $childtablesoncascade=array('@PBatch:pickup/class/pbatch.class.php:fk_pickupline');


	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'index'=>1, 'comment'=>"Id"),
		'fk_pickup' => array('type'=>'integer:Pickup:custom/pickup/class/pickup.class.php', 'label'=>'Pickup', 'enabled'=>'1', 'position'=>15, 'notnull'=>1, 'visible'=>-1, 'index'=>1, 'foreignkey'=>'pickup_pickup.rowid',),
		'fk_product' => array(
			// FIXME: seems that filters do not work (dolibarr 12.0.4)
			'type'=>'integer:Product:product/class/product.class.php:1:(fk_product_type=0)',
			'label'=>'Product', 'enabled'=>'1', 'position'=>32, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'foreignkey'=>'product.rowid',
		),
		'description' => array('type'=>'html', 'label'=>'Description', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>-1,),
		'qty' => array('type'=>'integer', 'label'=>'Quantité', 'enabled'=>'1', 'position'=>45, 'notnull'=>1, 'visible'=>1, 'default'=>'1', 'isameasure'=>'1', 'css'=>'maxwidth75imp',),
		// 'batch' => array('type' => 'varchar(128)', 'label' => 'Batch', 'enabled' => '1', 'position' => 50, 'visible' => 1, 'notnull' => -1),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>140, 'notnull'=>1, 'visible'=>-1,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>45, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>50, 'notnull'=>-1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'position' => array('type'=>'integer', 'label'=>'Rank', 'enabled'=>'1', 'position'=>200, 'notnull'=>1, 'visible'=>0,),
		'fk_stock_movement' => array('type'=>'integer:MouvementStock:product/stock/class/mouvementstock.class.php', 'label'=>'StockMovement', 'enabled'=>'1', 'position'=>300, 'notnull'=>0, 'visible'=>5, 'index'=>1, 'foreignkey'=>'stock_mouvement.rowid',),
		'weight' => array(
			'type' => 'double(24,8)', 'label'=>'ProductWeight',
			'enabled'=>'$conf->global->PICKUP_UNITS_WEIGHT',
			'position'=>100, 'notnull'=>0,'visible'=>1,'isameasure'=>'1'
		),
		'weight_units' => array(
			'type' => 'integer', 'label'=>'',
			'enabled'=>'$conf->global->PICKUP_UNITS_WEIGHT',
			'position'=>101,'notnull'=>0,'visible'=>1
		),
		'length' => array(
			'type' => 'double(24,8)', 'label'=>'',
			'enabled'=>'$conf->global->PICKUP_UNITS_LENGTH',
			'position'=>102, 'notnull'=>0,'visible'=>1,'isameasure'=>'1'
		),
		'length_units' => array(
			'type' => 'integer', 'label'=>'',
			'enabled'=>'$conf->global->PICKUP_UNITS_LENGTH',
			'position'=>103,'notnull'=>0,'visible'=>1
		),
		'surface' => array(
			'type' => 'double(24,8)', 'label'=>'',
			'enabled'=>'$conf->global->PICKUP_UNITS_SURFACE',
			'position'=>104, 'notnull'=>0,'visible'=>1,'isameasure'=>'1'
		),
		'surface_units' => array(
			'type' => 'integer', 'label'=>'',
			'enabled'=>'$conf->global->PICKUP_UNITS_SURFACE',
			'position'=>105,'notnull'=>0,'visible'=>1
		),
		'volume' => array(
			'type' => 'double(24,8)', 'label'=>'ProductVolume',
			'enabled'=>'$conf->global->PICKUP_UNITS_VOLUME',
			'position'=>106, 'notnull'=>0,'visible'=>1,'isameasure'=>'1'
		),
		'volume_units' => array(
			'type' => 'integer', 'label'=>'',
			'enabled'=>'$conf->global->PICKUP_UNITS_VOLUME',
			'position'=>107,'notnull'=>0,'visible'=>1
		),
		'deee' => array('type' => 'integer', 'label'=>'DEEE', 'enabled'=>1,'position'=>108,'notnull'=>0,'visible'=>1),
		'deee_type' => array('type' => 'varchar(255)', 'label'=>'DEEEType', 'position'=>109,'enabled'=>1,'notnull'=>0,'visible'=>1),
	);
	public $rowid;
	public $fk_pickup;
	public $fk_product;
	public $description;
	public $qty;
	// public $batch;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $position;
	public $fk_stock_movement;


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		if (empty($conf->global->PICKUP_USE_PICKUPLINE_DESCRIPTION)) {
			$this->fields['description']['visible'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->pickup->pickupline->read) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs))
		{
			foreach ($this->fields as $key => $val)
			{
				if (is_array($val['arrayofkeyval']))
				{
					foreach ($val['arrayofkeyval'] as $key2 => $val2)
					{
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		global $db, $conf;
		$result = $this->createCommon($user, $notrigger);
		if ($result <= 0) {
			return $result;
		}
		// We must create pbatch(es) if needed.
		if (empty($this->fk_product)) { return $result; }

		$product = new Product($db);
		if ($product->fetch($this->fk_product) <= 0) { return $result; }
		if ($product->hasbatch()) {
			$default_batch = '';
			if ($product->status_batch == 2) {
				$default_batch = $conf->global->PICKUP_DEFAULT_UNIQUE_BATCH;
			} else {
				$default_batch = $conf->global->PICKUP_DEFAULT_BATCH;
			}
			if ($default_batch === 'pickup_ref') {
				$pickup = new Pickup($db);
				if ($pickup->fetch($this->fk_pickup) <= 0) {
					return $result;
				}
				$this->updateAssociatedBatch($pickup->ref, $user);
			} else if ($default_batch === 'generate') {
				$batch_numbers = [];
				if ($product->status_batch == 2) {
					// must generate unique batch numbers.
					$qty = intval($this->qty);
					for ($cpt = 0; $cpt < $qty; $cpt++) {
						$batch_numbers[] = PBatch::getNextPBatchNumber();
					}
				} else {
					// must generate only one batch number.
					$batch_numbers[] = PBatch::getNextPBatchNumber();
				}
				$this->updateAssociatedBatch($batch_numbers, $user);
			}
		}
		return $result;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id)
	{
		$result = $this->fetchCommon($id);
		return $result;
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key.'='.$value;
				}
				elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				}
				elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				}
				else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num))
			{
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.$id;
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}

	/**
	 * Returns categories label to display for the product.
	 */
	public function getProductCategoriesLabels() {
		global $db;

		if (!$this->fk_product) {
			return array();
		}
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$cat = new Categorie($db);
		$cats = $cat->containing($this->fk_product, Categorie::TYPE_PRODUCT, 'object');
		if ($cats <= 0) { return array(); }

		$result = array();
		foreach ($cats as $cat) {
			$allways = $cat->get_all_ways();
			foreach ($allways as $way) {
        foreach ($way as $parent_cat) {
					array_push($result, $parent_cat->label);
        }
			}
		}

		$result = array_unique($result, SORT_STRING);

		return $result;
	}

	public function fetchAssociatedBatch() {
		global $db;
		$pbatch = new PBatch($db);
		$result = $pbatch->fetchAll('ASC', 'rowid', 0, 0, array('fk_pickupline' => $this->id), 'AND');
		if (is_array($result)) {
			$result = array_values($result); // to reindex from 0.
		}
		return $result;
	}

	public function updateAssociatedBatch($batch_numbers, $user) {
		global $db;
		if (empty($this->id)) {
			dol_syslog(__CLASS__.'::'.__METHOD__.': cant call this method on an unfetched object', LOG_ERR);
			return;
		}

		$pbatches = $this->fetchAssociatedBatch();

		if (empty($batch_numbers)) {
			foreach ($pbatches as $pbatch) {
				$pbatch->delete($user);
			}
			return;
		}

		if (!is_array($batch_numbers)) {
			$batch_numbers = [$batch_numbers];
		}
		$pbatches_by_number = [];
		foreach ($pbatches as $pbatch) {
			$pbatches_by_number[$pbatch->batch_number] = $pbatch;
		}
		$seen_batch_numbers = [];

		// first create missing batch_numbers
		foreach ($batch_numbers as $batch_number) {
			$batch_number = trim($batch_number);
			if (empty($batch_number)) { continue; }
			$seen_batch_numbers[$batch_number] = true;
			if (array_key_exists($batch_number, $pbatches_by_number)) { continue; }
			$pbatch = new PBatch($db);
			$pbatch->batch_number = $batch_number;
			$pbatch->fk_product = $this->fk_product;
			$pbatch->fk_pickupline = $this->id;
			$pbatch->create($user);
		}

		// then delete obsolete batch_numbers.
		foreach ($pbatches as $pbatch) {
			if (array_key_exists($pbatch->batch_number, $seen_batch_numbers)) { continue; }
			$pbatch->delete($user);
		}
	}

	public function showPBatchInputField($product, $pbatches, $field_name) {
		if ($product->status_batch == 1) { // non unique batch => 1 line field
			$field_type = 'varchar(255)';
		} else {
			$field_type = 'text';
		}
		require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
		$common_object = new GenericObject($this->db);
		$common_object->fields = [
			$field_name => array('type' => $field_type, 'label' => 'Batch', 'enabled' => '1', 'visible' => 1, 'notnull' => -1)
		];
		return $common_object->showInputField(
			null,
			$field_name,
			GETPOSTISSET($field_name)
			? GETPOST($field_name, 'alpha')
			: implode(
				$field_type === 'text' ? "\n" : ',',
				array_map(function ($pbatch) { return $pbatch->batch_number; }, $pbatches)
			)
		);
	}
}
