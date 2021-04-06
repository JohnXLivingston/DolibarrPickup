<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');

class DataMobileActionPickup extends DataMobileAction {
  protected function pickup2json($pickup) {
    return array(
      'rowid' => $pickup->rowid,
      'display' => $pickup->ref.' '.$pickup->label
    );
  }

  public function action_list() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    global $user;

    dol_include_once('/pickup/class/pickup.class.php');
    $object = new Pickup($this->db);

    $where = array();
    $where[] = 't.fk_user_creat = \''.$object->db->escape($user->id).'\'';
    $where[] = 't.status = \''.$object->db->escape($object::STATUS_DRAFT).'\'';
    $filters = array(
      'customsql' => '('.implode(' AND ', $where).')'
    );
    $pickups = $object->fetchAll('DESC', 't.ref', 0, 0, $filters, 'AND');
    if (is_numeric($pickups)) {
      return 0;
    }

    $result = array();
    foreach ($pickups as $pickup) {
      $r = $this->pickup2json($pickup);
      array_push($result, $r);
    }
    return $result;
  }

  public function action_save() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    global $user;

    dol_include_once('/pickup/class/pickup.class.php');
    $object = new Pickup($this->db);

    $object->fk_entrepot = GETPOST('entrepot');
    $object->fk_soc = GETPOST('soc');
    $object->date_pickup = GETPOST('date_pickup');
    $object->description = GETPOST('description');
  
    $id = $object->create($user);
    if (!$id || $id <= 0) {
      $this->_log_object_errors(__METHOD__, $object);
      return 0;
    }

    $object->fetch($id);
    return $this->pickup2json($object);
  }
}
