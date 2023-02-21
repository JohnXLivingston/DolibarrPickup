<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');

class DataMobileActionSoc extends DataMobileAction {
  public function action_list() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    $db = $this->db;
    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
    $object = new Societe($db);

    $sql = 'SELECT t.rowid, t.nom ';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'societe as t ';
    if ($object->ismultientitymanaged == 1) {
      $sql.= ' WHERE t.entity IN ('.getEntity($object->element).') ';
    } else {
      $sql.= ' WHERE 1 = 1 ';
    }

    $resql = $db->query($sql);
    if (!$resql) {
      $this->_log_sql_errors(__METHOD__, $db);
      return 0;
    }

    $response = array();
    while ($line = $db->fetch_object($resql)) {
      array_push($response, $line);
    }
    $db->free($resql);
    return $response;
  }

  public function action_get() {
    dol_syslog(__METHOD__, LOG_DEBUG);

    $id = GETPOST('id', 'int');
    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
    $soc = new Societe($this->db);
    if ($soc->fetch($id) <= 0) {
      return 0;
    }

    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
    $formcompany = new FormCompany($this->db);
    $arr_typent = $formcompany->typent_array(1);
    $typent_libelle = $arr_typent[$soc->typent_code];

    return array(
      'rowid' => $soc->id,
      'name' => $soc->name,
      'name_alias' => $soc->name_alias,
      'complete_address' => dol_htmlentitiesbr($soc->address . ' ' . $soc->zip . ' ' . $soc->town),
      'email' => $soc->email,
      'phone' => $soc->phone,
      'typent' => $soc->typent_code,
      'typent_libelle' => $typent_libelle,
      'forme_juridique' => $soc->forme_juridique
    );
  }

  public function action_save() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    $db = $this->db;
    global $user;

    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
    $object = new Societe($db);

    $name = GETPOST('name');
    $name_alias = GETPOST('name_alias');
    $object->name = $name;
    if (!empty($name_alias)) {
      $object->name_alias = $name_alias;
    }
    $object->country_id = 1; // 'FR' hardcoded.
    $object->address = GETPOST('address');
    $object->zip = GETPOST('zip');
    $object->town = GETPOST('town');
    $object->email = GETPOST('email');
    $object->phone = GETPOST('phone');
    $object->client = 1; // 'client' hardcoded
    $object->code_client = -1; // automatic assignment
    
    $object->forme_juridique_code = GETPOST('forme_juridique_code', 'int');
    $object->typent_id = GETPOST('typent_id', 'int');
    
    $object->fournisseur = '0'; // hardcoded
    $object->client = '3'; // hardcoded: client/prospect

    $id = $object->create($user);
    if (!$id || $id <= 0) {
      $this->_log_object_errors(__METHOD__, $object);
      return 0;
    }

    return array("rowid" => $id, "name" => $object->name);
  }
}
