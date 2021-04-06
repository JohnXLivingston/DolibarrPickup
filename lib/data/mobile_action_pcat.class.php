<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');

class DataMobileActionPcat extends DataMobileAction {
  public function action_list() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    $db = $this->db;
    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

    // FIXME: should add an entity id?
    $sql  = "SELECT c.rowid as rowid, dc.form as form, dc.notes as notes ";
    $sql .= " FROM ".MAIN_DB_PREFIX."categorie as c, ".MAIN_DB_PREFIX."collecte_dolinputcat as dc ";
    $sql .= " WHERE c.rowid = dc.fk_category ";
    $sql .= " AND dc.active = 1 ";
    $sql .= " AND c.type = 0 "; // 0=Product

    $resql = $db->query($sql);
    if (!$resql) {
      $this->_log_sql_errors(__METHOD__, $db);
      return 0;
    }

    $response = array();
    while ($line = $db->fetch_object($resql)) {
      $cat = new Categorie($db);
      if ($cat->fetch($line->rowid) < 0) {
        dol_syslog('Can retrieve category '+$line->rowid, LOG_ERR);
        return 0;
      }
  
      $allways = $cat->get_all_ways();
      foreach ($allways as $way) {
        $label = array();
        foreach ($way as $parent_cat) {
          $label[] = $parent_cat->label;
        }
        $label = implode(' >> ', $label);
  
        $data = array(
          'rowid' => $cat->id,
          'label' => $label,
          'form' => $line->form,
          'notes' => $line->notes
        );
        array_push($response, $data);
      }
  
    }
    $db->free($resql);
    return $response;
  }
}
