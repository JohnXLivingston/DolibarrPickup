<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

class DataMobileActionPickup extends DataMobileAction {
  protected function pickup2json($pickup, $complete = false) {
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

    require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

    $pickup->getLinesArray();
    foreach ($pickup->lines as $line) {
      $product = new Product($db);
      $product->fetch($line->fk_product);
      $rl = array(
        'rowid' => $line->id,
        'name' => $product->ref,
        'qty' => $line->qty,
        'deee' => $line->deee ? true : false
      );
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

    $date_pickup_input = GETPOST('date_pickup', 'alpha');
    $date_pickup_parts = explode('-', $date_pickup_input);
    $date_pickup = dol_mktime(12, 0, 0, $date_pickup_parts[1], $date_pickup_parts[2], $date_pickup_parts[0], true, 1);
    if (empty($date_pickup)) {
      dol_syslog('Invalid date: ' . $date_pickup_input, LOG_ERR);
      return 0;
    }

    dol_include_once('/pickup/class/pickup.class.php');
    $object = new Pickup($this->db);

    $object->fk_entrepot = GETPOSTISSET('entrepot') ? GETPOST('entrepot') : $conf->global->PICKUP_DEFAULT_STOCK;
    $object->fk_soc = GETPOST('soc', 'int');
    $object->date_pickup = $date_pickup;
    $object->description = GETPOST('description', 'none');
  
    $id = $object->create($user);
    if (!$id || $id <= 0) {
      $this->_log_object_errors(__METHOD__, $object);
      return 0;
    }

    $object->fetch($id);
    return $this->pickup2json($object);
  }
}
