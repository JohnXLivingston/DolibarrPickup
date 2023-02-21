<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

class DataMobileActionPickup extends DataMobileAction {
  protected function pickup2json($pickup, $complete = false) {
    global $conf, $langs;

    $r = array(
      'rowid' => $pickup->id,
      'display' => $pickup->ref.' '.$pickup->label
    );
    if (!$complete) {
      return $r;
    }

    global $db;
    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
    $soc = new Societe($db);
    $soc->fetch($pickup->fk_soc);

    require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
    $entrepot = new Entrepot($db);
    $entrepot->fetch($pickup->fk_entrepot);

    $r['date'] = dol_print_date($pickup->date_pickup, 'day');
    $r['soc_name'] = $soc->name;
    $r['description'] = $pickup->description;
    $r['entrepot_name'] = $entrepot->ref;
    $r['lines'] = array();
    if (!empty($conf->global->PICKUP_USE_PICKUP_TYPE)) {
      $r['pickup_type_label'] = $pickup->getPickupTypeLabel();
    }

    require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

    require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php'; // for measuringUnitString
    $langs->loadLangs(array("other"));
    
    $pickup->getLinesArray();
    foreach ($pickup->lines as $line) {
      $unitary = '';
      $weight = $line->weight;
      if (!empty($weight)) {
        $weight.= ' ' . measuringUnitString(0, "weight", $line->weight_units);
        if(($conf->global->PICKUP_UNITS_WEIGHT ?? '0') != '0') {
          $unitary.= htmlspecialchars($weight)."\n";
        }
      }
      $length = $line->length;
      if (!empty($length)) {
        $length.= ' ' . measuringUnitString(0, 'size', $line->length_units);
        if(($conf->global->PICKUP_UNITS_LENGTH ?? '0') != '0') {
          $unitary.= htmlspecialchars($length)."\n";
        }
      }
      $surface = $line->surface;
      if (!empty($surface)) {
        $surface.= ' ' . measuringUnitString(0, 'surface', $line->surface_units);
        if(($conf->global->PICKUP_UNITS_SURFACE ?? '0') != '0') {
          $unitary.= htmlspecialchars($surface)."\n";
        }
      }
      $volume = $line->volume;
      if (!empty($volume)) {
        $volume.= ' ' . measuringUnitString(0, 'volume', $line->volume_units);
        if(($conf->global->PICKUP_UNITS_VOLUME ?? '0') != '0') {
          $unitary.= htmlspecialchars($volume)."\n";
        }
      }

      $product = new Product($db);
      $product->fetch($line->fk_product);
      $rl = array(
        'rowid' => $line->id,
        'name' => $product->ref,
        'label' => $product->label,
        'qty' => $line->qty,
        'line_weight_txt' => $weight,
        'line_length_txt' => $length,
        'line_surface_txt' => $surface,
        'line_volume_txt' => $volume,
        'line_unitary_html' => $unitary
      );
      if (!empty($conf->global->PICKUP_USE_PBRAND)) {
        $rl['pbrand'] = $product->array_options['options_pickup_pbrand'];
      }
      if (!empty($conf->global->PICKUP_USE_DEEE)) {
        $rl['deee'] = $line->deee ? true : false;
      }
      array_push($r['lines'], $rl);
    }
    return $r;
  }

  public function action_list() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    global $user;

    dol_include_once('/pickup/class/pickup.class.php');
    $object = new Pickup($this->db);

    $where = array();
    if (!$user->rights->pickup->write) { // this is the right to update all pickups.
      $where[] = 't.fk_user_creat = \''.$object->db->escape($user->id).'\'';
    }
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

  public function action_get() {
    dol_syslog(__METHOD__, LOG_DEBUG);

    $id = GETPOST('id', 'int');
    dol_include_once('/pickup/class/pickup.class.php');
    $pickup = new Pickup($this->db);
    if ($pickup->fetch($id) <= 0) {
      return 0;
    }
    return $this->pickup2json($pickup, true);
  }

  public function action_save() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    global $user, $conf;

    dol_include_once('/pickup/class/pickup.class.php');
    $object = new Pickup($this->db);

    $pickup_id = GETPOST('pickup_id', 'int');
    if (!empty($pickup_id)) {
      dol_syslog(__METHOD__ . ': modifing an existing pickup.', LOG_DEBUG);
      // We are on an edit.

      // NB: for now, the only edit allowed from mobile app is to set status to STATUS_PROCESSING.
      $status_change = GETPOST('pickup_status', 'int');
      if (intval($status_change) !== Pickup::STATUS_PROCESSING) {
        dol_syslog(__METHOD__ . ': invalid status: ' . strval($status_change), LOG_DEBUG);
        return 0;
      }

      // Checking user rights.
      if (!$object->getRights()->workflow->processing) {
        dol_syslog(__METHOD__ . ': the user has not the right to do so', LOG_DEBUG);
        return 0;
      }

      if ($object->fetch($pickup_id) <= 0) {
        $this->_log_object_errors(__METHOD__, $object);
        return 0;
      }

      $object->status = Pickup::STATUS_PROCESSING;
      if ($object->update($user) <= 0) {
				$this->_log_object_errors(__METHOD__, $object);
        return 0;
			}
      
      dol_syslog(__METHOD__ . ': status changed.', LOG_DEBUG);
      return $this->pickup2json($object);
    }

    dol_syslog(__METHOD__ . ': creating a new pickup...', LOG_DEBUG);

    $date_pickup_input = GETPOST('date_pickup', 'alpha');
    $date_pickup_parts = explode('-', $date_pickup_input);
    $date_pickup = dol_mktime(12, 0, 0, $date_pickup_parts[1], $date_pickup_parts[2], $date_pickup_parts[0], true, 1);
    if (empty($date_pickup)) {
      dol_syslog('Invalid date: ' . $date_pickup_input, LOG_ERR);
      return 0;
    }

    $object->fk_entrepot = GETPOSTISSET('entrepot') ? GETPOST('entrepot') : $conf->global->PICKUP_DEFAULT_STOCK;
    $object->fk_soc = GETPOST('soc', 'int');
    $object->date_pickup = $date_pickup;
    $object->description = GETPOST('description', 'none');
    if (!empty($conf->global->PICKUP_USE_PICKUP_TYPE)) {
      $pickup_type = GETPOST('pickup_type', 'int');
      $object->fk_pickup_type = empty($pickup_type) ? null : $pickup_type;
    }
  
    $id = $object->create($user);
    if (!$id || $id <= 0) {
      $this->_log_object_errors(__METHOD__, $object);
      return 0;
    }

    $object->fetch($id);
    return $this->pickup2json($object);
  }
}
