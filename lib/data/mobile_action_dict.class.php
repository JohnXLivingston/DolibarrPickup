<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');

class DataMobileActionDict extends DataMobileAction {
  public function action_list() {
    global $conf;

    dol_syslog(__METHOD__, LOG_DEBUG);
    $db = $this->db;

    // FIXME: should use entity id?
    $what = GETPOST('what');
    $sql = '';
    if ($what == 'forme_juridique_code') {
      $country_code = GETPOST('country');

      $sql  = "SELECT f.rowid, f.code as code , f.libelle as label, f.active, c.label as country, c.code as country_code";
      $sql .= " FROM ".MAIN_DB_PREFIX."c_forme_juridique as f, ".MAIN_DB_PREFIX."c_country as c";
      $sql .= " WHERE f.fk_pays=c.rowid";
      $sql .= " AND f.active = 1 AND c.active = 1";
      if ($country_code) $sql .= " AND c.code = '".$db->escape($country_code)."'";
      $sql .= " ORDER BY c.code, label";
    } else if ( $what == 'typent_id') {
      $country_codeid = GETPOST('country_id', 'int');

      $sql = "SELECT id, code, libelle";
      $sql.= " FROM ".MAIN_DB_PREFIX."c_typent";
      $sql.= " WHERE active = 1 AND (fk_country IS NULL OR fk_country = ".(empty($country_codeid)?'0':$country_codeid).")";
      // if ($filter) $sql.=" ".$filter;
      $sql.= " ORDER by position, id";
    } else if ($what == 'deee_type') {
      if (!$conf->global->PICKUP_USE_DEEE) {
        return array();
      }
      // This is an extrafield...
      require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
      $extrafields = new ExtraFields($db);
      $extrafields->fetch_name_optionals_label('product');

      $options = $extrafields->attributes['product']['param']['pickup_deee_type']['options'];
      $result = array();
      foreach ($options as $key => $val) {
        array_push($result, array(
          'value' => strval($key),
          'label' => $val
        ));
      }
      return $result;
    } else {
      dol_syslog(__METHOD__." Unknown what=$what", LOG_ERR);
      return 0;
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
