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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file        class/pickup.class.php
 * \ingroup     pickup
 * \brief       This file is a CRUD class file for Pickup (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
dol_include_once('/pickup/class/pickupline.class.php');

// Note: starting with Dolibarr ~19, there was a syntax change...
// Dolibarr 20 fixes the retro compatibilitly, but not Dolibarr 19.
// As it is used in a static attribute declaration, we can't use variables... This is why we use a constant:
define('_DOLIPICKUP_HACK_PICKUP_TYPE',
	(defined('DOL_VERSION') && intval(DOL_VERSION) >= 19)
		? 'sellist:c_pickup_type:label:rowid::(active:=:1)'
		: 'sellist:c_pickup_type:label:rowid::active=1'
);

/**
 * Class for Pickup
 */
class Pickup extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'pickup';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'pickup_pickup';

	/**
	 * @var int  Does pickup support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does pickup support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for pickup. Must be the part after the 'object_' into object_pickup.png
	 */
	public $picto = 'pickup@pickup';


	const STATUS_DRAFT = 0;
	const STATUS_PROCESSING = 1;
	const STATUS_STOCK = 3;
	const STATUS_SIGNED = 4;
	const STATUS_DISABLED = 9;


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
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Reference of object", 'noteditable' => 0),
		'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>4, 'searchall'=>1, 'showoncombobox'=>'1',),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php:1', 'label'=>'ThirdParty', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
		'date_pickup' => array('type'=>'date', 'label'=>'PickupDate', 'enabled'=>'1', 'position'=>55, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
		'fk_pickup_type' => array(
			'type' => _DOLIPICKUP_HACK_PICKUP_TYPE,
			'label' => 'PickupType',
			'enabled' => '$conf->global->PICKUP_USE_PICKUP_TYPE',
			'position'=>57,
			'notnull'=>-1, // '' must be converted to null
			'visible'=>1,
			'index'=>1,
		),
		'description' => array('type'=>'html', 'label'=>'Description', 'enabled'=>'1', 'position'=>60, 'notnull'=>-1, 'visible'=>-1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>-1, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>4, 'foreignkey'=>'user.rowid', 'noteditable'=>1),
		'fk_user_modif' => array('type'=>'integer', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'status' => array(
			'type'=>'integer', 'label'=>'Status', 'default' => Pickup::STATUS_DRAFT, 'enabled'=>'1',
			'position'=>1000, 'notnull'=>1, 'visible'=>4, 'index'=>1,
			'arrayofkeyval'=>array(
				Pickup::STATUS_DRAFT=>'Draft',
				Pickup::STATUS_PROCESSING=>'PickupStatusProcessing',
				Pickup::STATUS_STOCK=>'PickupStatusStock',
				Pickup::STATUS_SIGNED=>'PickupStatusSigned',
				Pickup::STATUS_DISABLED=>'Disabled'
			),
			'noteditable'=>1
		),
		'fk_entrepot' => array('type'=>'integer:Entrepot:product/stock/class/entrepot.class.php', 'label'=>'Entrepôt', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>-1,),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>161, 'notnull'=>-1, 'visible'=>-2,),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>162, 'notnull'=>-1, 'visible'=>-2,),
		'model_pdf' => array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>1, 'visible'=>0, 'notnull'=>-1, 'position'=>1010),
	);
	public $rowid;
	public $ref;
	public $label;
	public $fk_soc;
	public $date_pickup;
	public $fk_pickup_type;
	public $description;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $status;
	public $fk_entrepot;
	public $note_public;
	public $note_private;
	public $model_pdf;

	// If this object has a subtable with lines

	/**
	 * @var int    Name of subtable line
	 */
	public $table_element_line = 'pickup_pickupline';

	/**
	 * @var int    Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_pickup';

	/**
	 * @var int    Name of subtable class that manage subtable lines
	 */
	public $class_element_line = 'PickupLine';

	/**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	//protected $childtables=array();

	/**
	 * @var array	List of child tables. To know object to delete on cascade.
	 */
	//protected $childtablesoncascade=array('pickup_pickupdet');
	protected $childtablesoncascade = array('pickup_pickupline');

	/**
	 * @var PickupLine[]     Array of subtable lines
	 */
	public $lines = array();



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible']=0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']=0;

		if (!empty($conf->global->PICKUP_DEFAULT_STOCK)) {
			$this->fields['fk_entrepot']['default'] = $conf->global->PICKUP_DEFAULT_STOCK;
		}
		if (empty($conf->global->PICKUP_USE_PICKUP_TYPE)) {
			$this->fields['fk_pickup_type']['visible'] = 0;
		}

		// Unset fields that are disabled
		foreach($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		foreach($this->fields as $key => $val)
		{
			if (isset($val['arrayofkeyval']) && is_array($val['arrayofkeyval']))
			{
				foreach($val['arrayofkeyval'] as $key2 => $val2)
				{
					$this->fields[$key]['arrayofkeyval'][$key2]=$langs->trans($val2);
				}
			}
		}


	}

	/**
	 *    Check properties of pickup are ok.
	 *    All properties must be already loaded on object.
	 *
	 * @return int        0 if OK, <0 if KO
	 */
	public function verify() {
		global $conf;

		if (!empty($this->date_pickup) && empty($conf->global->PICKUP_ALLOW_FUTURE)) {
			$now = dol_print_date(dol_now(), '%Y-%m-%d');
			$val = dol_print_date($this->date_pickup, '%Y-%m-%d');
			if ($val > $now) {
				array_push($this->errors, 'PickupCantBeInFutureError');
				return -1;
			}
		}
		return 0;
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
		global $db;

		dol_include_once('/pickup/core/modules/pickup/modules_pickup.php');
		$modele_num_ref = new ModeleNumRefPickup();
		$this->ref = $modele_num_ref->getNextValue($this);

		if (empty($this->label)) {
			$this->label = '';
			if (!empty($this->fk_soc)) {
				dol_include_once('societe/class/societe.class.php');
				$societe = new Societe($db);
				$societe->fetch($this->fk_soc);
				$this->label .= $societe->name. ' ';
			}
			$this->label .= dol_print_date($this->date_pickup, '%d/%m/%Y');
		}

		$result = $this->verify();
		if ($result < 0) {
			dol_syslog(get_class($this)."::create fails verify ".join(',', $this->errors), LOG_WARNING);
			return $result;
		}

		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
	    $error = 0;

	    dol_syslog(__METHOD__, LOG_DEBUG);

	    $object = new self($this->db);

	    $this->db->begin();

	    // Load source object
	    $result = $object->fetchCommon($fromid);
	    if ($result > 0 && ! empty($object->table_element_line)) $object->fetchLines();

	    // get lines so they will be clone
	    //foreach($this->lines as $line)
	    //	$line->fetch_optionals();

	    // Reset some properties
	    unset($object->id);
	    unset($object->fk_user_creat);
	    unset($object->import_key);


	    // Clear fields
	    $object->ref = "copy_of_".$object->ref;
	    $object->title = $langs->trans("CopyOf")." ".$object->title;
	    // ...
	    // Clear extrafields that are unique
	    if (is_array($object->array_options) && count($object->array_options) > 0)
	    {
	    	$extrafields->fetch_name_optionals_label($this->element);
	    	foreach($object->array_options as $key => $option)
	    	{
	    		$shortkey = preg_replace('/options_/', '', $key);
	    		if (! empty($extrafields->attributes[$this->element]['unique'][$shortkey]))
	    		{
	    			//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
	    			unset($object->array_options[$key]);
	    		}
	    	}
	    }

	    // Create clone
		$object->context['createfromclone'] = 'createfromclone';
	    $result = $object->createCommon($user);
	    if ($result < 0) {
	        $error++;
	        $this->error = $object->error;
	        $this->errors = $object->errors;
	    }

	    if (! $error)
	    {
	    	// copy internal contacts
	    	if ($this->copy_linked_contact($object, 'internal') < 0)
	    	{
	    		$error++;
	    	}
	    }

	    if (! $error)
	    {
	    	// copy external contacts if same company
	    	if (property_exists($this, 'socid') && $this->socid == $object->socid)
	    	{
	    		if ($this->copy_linked_contact($object, 'external') < 0)
	    			$error++;
	    	}
	    }

	    unset($object->context['createfromclone']);

	    // End
	    if (!$error) {
	        $this->db->commit();
	        return $object;
	    } else {
	        $this->db->rollback();
	        return -1;
	    }
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines=array();

		$result = $this->fetchLinesCommon();
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
		if (empty($this->oldcopy)) {
			// This will be used in a trigger to rename the dir_output if ref changes.
			$oldcopy = new self($this->db);
			$oldcopy->fetch($this->id);
			$this->oldcopy = $oldcopy;
		}

		$result = $this->verify();
		if ($result < 0) {
			dol_syslog(get_class($this)."::update fails verify ".join(',', $this->errors), LOG_WARNING);
			return $result;
		}

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
	 *  Delete a line of object in database
	 *
	 *	@param  User	$user       User that delete
	 *  @param	int		$idline		Id of line to delete
	 *  @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 *  @return int         		>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = false)
	{
		if ($this->status < 0)
		{
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		$objectline = new PickupLine($this->db);
		$result = $objectline->fetch($idline);
		if ($result <= 0) {
			return -1;
		}
		// we must use the line->delete method, because there are subobjects.
		return $objectline->delete($user, $notrigger);
		// return $this->deleteLineCommon($user, $idline, $notrigger);
	}

    /**
     *  Return a link to the object card (with optionaly the picto)
     *
     *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
     *  @param  string  $option                     On what the link point to ('nolink', ...)
     *  @param  int     $notooltip                  1=Disable tooltip
     *  @param  string  $morecss                    Add more css on link
     *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *  @return	string                              String with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $conf, $langs, $hookmanager;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result = '';

        $label = '<u>' . $langs->trans("Pickup") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

        $url = dol_buildpath('/pickup/pickup_card.php', 1).'?id='.$this->id;

        if ($option != 'nolink')
        {
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
            if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
        }

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowPickup");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';

            /*
             $hookmanager->initHooks(array('pickupdao'));
             $parameters=array('id'=>$this->id);
             $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
             if ($reshook > 0) $linkclose = $hookmanager->resPrint;
             */
        }
        else $linkclose = ($morecss?' class="'.$morecss.'"':'');

		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= $this->ref;
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action,$hookmanager;
		$hookmanager->initHooks(array('pickupdao'));
		$parameters=array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook=$hookmanager->executeHooks('getNomUrl', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
    }

	/**
	 *  Return label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $conf;
		if (empty($this->labelStatus) || empty($this->labelStatusShort))
		{
			global $langs;
			$langs->load("pickup");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatus[self::STATUS_PROCESSING] = $langs->trans('PickupStatusProcessing');
			$this->labelStatus[self::STATUS_STOCK] = $langs->trans('PickupStatusStock');
			$this->labelStatus[self::STATUS_SIGNED] = $langs->trans('PickupStatusSigned');
			$this->labelStatus[self::STATUS_DISABLED] = $langs->trans('Disabled');

			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatusShort[self::STATUS_PROCESSING] = $langs->trans('PickupStatusShortProcessing');
			$this->labelStatusShort[self::STATUS_STOCK] = $langs->trans('PickupStatusShortStock');
			$this->labelStatusShort[self::STATUS_SIGNED] = $langs->trans('PickupStatusSigned');
			$this->labelStatusShort[self::STATUS_DISABLED] = $langs->trans('Disabled');

			if (empty($conf->global->PICKUP_NO_SIGN_STATUS)) {
				$this->labelStatus[self::STATUS_STOCK] = $langs->trans('PickupStatusStockBis');
				$this->labelStatusShort[self::STATUS_STOCK] = $langs->trans('PickupStatusShortStockBis');
			}
		}

		if (!empty($conf->global->PICKUP_NO_SIGN_STATUS) && $status == self::STATUS_STOCK) {
			$statusType = 'status4';
		} else {
			$statusType = 'status'.$status;
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
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
		$sql.= ' fk_user_creat, fk_user_modif';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql.= ' WHERE t.rowid = '.$id;
		$result=$this->db->query($sql);
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
					$this->user_creation   = $cuser;
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
					$this->user_cloture   = $cluser;
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
	 * 	Create an array of lines
	 *
	 * 	@return array|int		array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
	    $this->lines=array();

	    $objectline = new PickupLine($this->db);
	    $result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_pickup = '.$this->id));

	    if (is_numeric($result))
	    {
	        $this->error = $this->error;
	        $this->errors = $this->errors;
	        return $result;
	    }
	    else
	    {
	        $this->lines = $result;
	        return $this->lines;
	    }
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf,$langs;

		$langs->load("pickup@pickup");

		if (! dol_strlen($modele)) {

			$modele = 'standard_pickup';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (! empty($conf->global->PICKUP_ADDON_PDF)) {
				$modele = $conf->global->PICKUP_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/pickup/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	//public function doScheduledJob($param1, $param2, ...)
	public function doScheduledJob()
	{
		global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		$error = 0;
		$this->output = '';
		$this->error='';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		return $error;
	}



	public function canEditPickup() {
		global $user, $conf;

		$pickup_rights = $this->getRights();

		if ($this->status == Pickup::STATUS_DRAFT) {
			if ($pickup_rights->create) {
				// only if it is mine...
				if ($this->fk_user_creat == $user->id) {
					return 1;
				}
				// or if it is not saved yet
				if (empty($this->id)) {
					return 1;
				}
			}
			if ($pickup_rights->write) return 1;
		} else if ($this->status == Pickup::STATUS_PROCESSING) {
			if ($pickup_rights->write) return 1;
		} else if ($this->status == Pickup::STATUS_STOCK) {
			if (empty($conf->global->PICKUP_NO_SIGN_STATUS)) {
				if ($pickup_rights->write) return 1;
			}
		} else if ($this->status == Pickup::STATUS_SIGNED) {

		} else if ($this->status == Pickup::STATUS_DISABLED) {

		}
		return 0;
	}

	public function canCreatePickupPdf() {
		global $user;
		if (
			$this->status == Pickup::STATUS_DRAFT ||
			$this->status == Pickup::STATUS_PROCESSING ||
			$this->status == Pickup::STATUS_STOCK
		) {
			$pickup_rights = $this->getRights();
			if ($pickup_rights->workflow->stock) { // Editing the pdf needs this right
				return 1;
			}
		}
		return 0;
	}
	
	public function initPickupLine($fk_product, $qty = 1) {
		global $db, $conf;

		$line = new PickupLine($db);
		$line->fk_pickup = $this->id;
		$line->fk_product = $fk_product;
		$line->qty = $qty;
		$line->position = $this->line_max(0) + 1;

		$product = new Product($db);
		if ($product->fetch($fk_product) <= 0) {
			dol_syslog(__METHOD__ . ' ' . 'Product '.$fk_product.' not found', LOG_ERR);
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

			// if ($product->hasbatch() && !empty($conf->global->PICKUP_DEFAULT_BATCH_PICKUP_REF)) {
			// 	$line->batch = $this->ref;
			// }
		}

		return $line;
	}

	public function computeQtyTotals () {
		global $db;

		// NB: we compute deee_qty even if !PICKUP_USE_DEEE to simplify code. It will be ignored later on.
		$result = array(
			'qty' => 0,
			'deee_qty' => 0,
		);
		if (empty($this->id)) {
			return $result;
		}

		$sql = 'SELECT sum(l.qty) as qty';
		$sql.= ' FROM ' . MAIN_DB_PREFIX . $this->table_element_line. ' as l';
		$sql.= ' WHERE';
		$sql.= ' l.fk_pickup = \'' . $this->db->escape($this->id).'\'';
		$resql = $this->db->query($sql);
		if ($resql) {
			$line = $this->db->fetch_object($resql);
			if (!empty($line) && !empty($line->qty)) {
					$result['qty'] = (int) $line->qty;
			}
			$this->db->free($resql);
		} else {
			dol_syslog(__METHOD__ . ' Error ' . $this->db->lasterror(), LOG_ERR);
		}

		$sql.= ' AND l.deee';
		$resql = $this->db->query($sql);
		if ($resql) {
			$line = $this->db->fetch_object($resql);
			if (!empty($line) && !empty($line->qty)) {
					$result['deee_qty'] = (int) $line->qty;
			}
			$this->db->free($resql);
		} else {
			dol_syslog(__METHOD__ . ' Error ' . $this->db->lasterror(), LOG_ERR);
		}

		return $result;
	}
	
	public function computeTotals() {
		global $db, $conf;

		// NB: we compute deee_xxx even if !PICKUP_USE_DEEE to simplify code. It will be ignored later on.
		$result = array(
			'qty' => 0,
			'weights' => array(),
			'lengths' => array(),
			'surfaces' => array(),
			'volumes' => array(),
			'deee_type_weights' => array(), // deee_type label => array(unit => value)
			'deee_weights' => array()
		);

		// Settings 0 for all default units:
		$result['weights'][$conf->global->PICKUP_WEIGHT_UNIT] = 0;
		$result['lengths'][$conf->global->PICKUP_SIZE_UNIT] = 0;
		$result['surfaces'][$conf->global->PICKUP_SURFACE_UNIT] = 0;
		$result['volumes'][$conf->global->PICKUP_VOLUME_UNIT] = 0; // -3 = L
		$result['deee_weights'][$conf->global->PICKUP_WEIGHT_UNIT] = 0;

		if (empty($this->id)) {
			return $result;
		}

		$sql = 'SELECT l.qty, ';
		$sql.= ' l.weight, l.weight_units, l.length, l.length_units, l.surface, l.surface_units, l.volume, l.volume_units, ';
		$sql.= ' l.deee, l.deee_type ';
		$sql.= ' FROM ' . MAIN_DB_PREFIX . $this->table_element_line. ' as l';
		$sql.= ' WHERE';
		$sql.= ' l.fk_pickup = \'' . $this->db->escape($this->id).'\'';

		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($db);
		$extrafields->fetch_name_optionals_label('product');

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
      $i = 0;
			while ($i < $num) {
			  $line = $this->db->fetch_object($resql);
				if (!empty($line->qty)) {
					$qty = (int) $line->qty;
					$result['qty']+= (int) $line->qty;

					foreach (['weight', 'length', 'surface', 'volume'] as $unit) {
						$unit_units = $unit . '_units';
						$total_key = $unit . 's';
						if (!empty($line->$unit)) {
							$value = (double) $line->$unit;
							$value_units = (int) $line->$unit_units;
							$line_value = $qty * $value;
							if (!array_key_exists($value_units, $result[$total_key])) {
								$result[$total_key][$value_units] = 0;
							}
							$result[$total_key][$value_units]+= $line_value;
	
							if ($unit === 'weight' && !empty($conf->global->PICKUP_USE_DEEE) && $line->deee) {
								$deee_type = strval($line->deee_type);
								$deee_type = trim($extrafields->showOutputField('pickup_deee_type', $deee_type, '', 'product'));
								if (!array_key_exists($value_units, $result['deee_weights'])) {
									$result['deee_weights'][$value_units] = 0;
								}
								$result['deee_weights'][$value_units]+= $line_value;
	
								if (!array_key_exists($deee_type, $result['deee_type_weights'])) {
									$result['deee_type_weights'][$deee_type] = array(0 => 0);
								}
								if (!array_key_exists($value_units, $result['deee_type_weights'][$deee_type])) {
									$result['deee_type_weights'][$deee_type][$value_units] = 0;
								}
								$result['deee_type_weights'][$deee_type][$value_units]+= $line_value;
							}
						}
					}
				}

				$i++;
			}
			$this->db->free($resql);
		} else {
			dol_syslog(__METHOD__ . ' Error ' . $this->db->lasterror(), LOG_ERR);
		}

		return $result;
	}

	public function getPickupTypeLabel () {
		global $db, $conf;
		if (empty($conf->global->PICKUP_USE_PICKUP_TYPE)) {
			return null;
		}
		if (empty($this->fk_pickup_type)) {
			return null;
		}
		$sql = "SELECT label ";
		$sql .= " FROM ".MAIN_DB_PREFIX.'c_pickup_type ';
		$sql .= " WHERE rowid = '".$db->escape($this->fk_pickup_type)."'";

		$resql = $db->query($sql);
		if ($resql)	{
			$obj = $db->fetch_object($resql);
			$db->free($resql);
			if ($obj) return $obj->label;
			return null;
		} else {
			return null;
		}
	}

	public static function getPickupTypeOptions () {
		global $db, $conf;
		if (empty($conf->global->PICKUP_USE_PICKUP_TYPE)) {
			return array();
		}
		$sql = 'SELECT rowid, label FROM '.MAIN_DB_PREFIX.'c_pickup_type';
		$sql.= " WHERE active = '1' ";
		if ($conf->entity) {
			$sql.= " AND entity = '".$db->escape($conf->entity)."'";
		}
		$sql.= " ORDER BY label ASC";

		$result = array();

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_object($resql);

				array_push($result, array(
					'value' => $row->rowid,
					'label' => $row->label
				));
				$i++;
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		return $result;
	}

	public function printLabelButton() {
		global $langs;
		$button = '<a class="button buttongen"';
		$button.= ' onclick="window.dolibarrPickup.printPickupLabels(this, \''.htmlspecialchars($this->id).'\');"';
		$button.= ' title="'.$langs->trans('PickupPrintLabel').'"';
		$button.= ' style="min-width: 34px;"';
		$button.= '>';
		$button.= '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="16" fill="currentColor" viewBox="0 0 32 16">';
		$button.= '<g id="bars" fill="currentColor" stroke="none">';
		$button.= '	<rect x="0" y="0" width="4" height="30"></rect>';
		$button.= '	<rect x="6" y="0" width="2" height="30"></rect>';
		$button.= '	<rect x="12" y="0" width="2" height="30"></rect>';
		$button.= '	<rect x="22" y="0" width="4" height="30"></rect>';
		$button.= '	<rect x="28" y="0" width="6" height="30"></rect>';
		$button.= '</g>';
		$button.= '</svg>';
		$button.= '</a>';
		return $button;
	}
}
