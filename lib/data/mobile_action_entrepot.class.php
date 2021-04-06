<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');

class DataMobileActionEntrepot extends DataMobileAction {
  public function action_list() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    $db = $this->db;
    require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
    $object = new Entrepot($db);

    $sql = 'SELECT t.rowid, t.ref ';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'entrepot as t ';
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
}
