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

  public function action_save() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    $db = $this->db;
    global $user;

    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
    $object = new Societe($db);

    $name = GETPOST('name');
    $name_alias = GETPOST('name_alias');
    if ( $name ) {
      $object->name = $name;
      $object->name_alias = $name_alias;
    } else {
      $object->name = $name_alias;
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
    
    // TODO $object->fournisseur = '0'; // hardcoded

    $id = $object->create($user);
    if (!$id || $id <= 0) {
      $this->_log_object_errors(__METHOD__, $object);
      return 0;
    }

    return array("rowid" => $id, "name" => $object->name);
  }
}
