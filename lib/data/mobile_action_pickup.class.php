<?php

class DataPickup {
  protected $db;

  public function __construct($db) {
    $this->db = $db;
  }

  public function action_list() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    global $user;

    dol_include_once('/pickup/class/pickup.class.php');
    $object = new Pickup($this->db);

    $where = array();
    $where[] = 't.fk_user_creat = '.$object->db->escape($user->id);
    $where[] = 't.status = '.$object->db->escape($object::STATUS_DRAFT);
    $filters = array(
      'customsql' => '('.implode(' AND ', $where).')'
    );
    $pickups = $object->fetchAll('DESC', 't.ref', 0, 0, $filters, 'AND');
    if (is_numeric($pickups)) {
      return 0;
    }

    $result = array();
    foreach ($pickups as $pickup) {
      $r = array(
        'rowid' => $pickup->rowid,
        'display' => $pickup->ref.' '.$pickup->label
      );
      array_push($result, $r);
    }
    return $result;
  }
}
