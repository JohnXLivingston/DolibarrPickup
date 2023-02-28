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
    if ($what == 'pickup_type') {
      $sql  = "SELECT f.rowid as id, f.label as label ";
      $sql .= " FROM ".MAIN_DB_PREFIX."c_pickup_type as f ";
      $sql .= " WHERE f.entity = '".$db->escape($conf->entity)."' AND f.active = 1 ";
      $sql .= " ORDER BY label";
    } else if ($what == 'forme_juridique_code') {
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
      if (empty($conf->global->PICKUP_USE_DEEE)) {
        return array();
      }
      // This is an extrafield...
      require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
      $extrafields = new ExtraFields($db);
      $extrafields->fetch_name_optionals_label('product');

      $pcat_filter_id = GETPOST('pcat', 'int');
      $acceptable_values = null;
      if (!empty($pcat_filter_id)) {
        dol_include_once('/pickup/class/mobilecat.class.php');
        $mobilecat = new PickupMobileCat($db);
        if ($mobilecat->fetchByCategory($pcat_filter_id) <= 0) {
          dol_syslog(__METHOD__." Mobilecat not found for pcat=$pcat_filter_id", LOG_ERR);
          return 0;
        }
        if ($mobilecat->active && !empty($mobilecat->form)) {
          $pcat_form = $mobilecat->form;
          switch ($pcat_form) {
            case 'create_product_deee_off':
              $acceptable_values = [];
              break;
            case 'create_product_deee_gef':
              $acceptable_values = ['gef'];
              break;
            case 'create_product_deee_ghf':
              $acceptable_values = ['ghf'];
              break;
            case 'create_product_deee_pam':
              $acceptable_values = ['pam'];
              break;
            case 'create_product_deee_pampro':
              $acceptable_values = ['pam_pro'];
              break;
            case 'create_product_deee_ecr':
              $acceptable_values = ['ecr'];
              break;
            case 'create_product_deee_ecrpro':
              $acceptable_values = ['ecr_pro'];
              break;
            case 'create_product_deee_pam_or_pampro':
              $acceptable_values = ['pam', 'pam_pro'];
              break;
            case 'create_product_deee_ecr_or_ecrpro':
              $acceptable_values = ['ecr', 'ecr_pro'];
              break;
          }
        }
      }

      $options = $extrafields->attributes['product']['param']['pickup_deee_type']['options'];
      $result = array();
      if (empty($acceptable_values)) { // includes the case where $acceptable_values = [];
        array_push($result, [
          'value' => '',
          'label' => '-'
        ]);
      }
      foreach ($options as $key => $val) {
        if ($acceptable_values !== null) {
          if (!in_array(strval($key), $acceptable_values)) {
            continue;
          }
        }
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
