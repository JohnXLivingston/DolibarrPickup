<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');

class DataMobileActionPickupline extends DataMobileAction {
  public function action_save() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    global $user;
    $db = $this->db;

    dol_include_once('/pickup/class/pickup.class.php');
    dol_include_once('/pickup/class/pickupline.class.php');
    
    $pickup = new Pickup($db);
    if ($pickup->fetch(GETPOST('pickup', 'int')) <= 0) {
      return 0;
    }

    $product_id = GETPOST('product', 'int');
    $qty = GETPOST('qty', 'int');
    $pickupline = $pickup->initPickupLine($product_id, $qty);
    $id = $pickupline->create($user);
    if (!$id || $id <= 0) {
      $this->_log_object_errors(__METHOD__, $pickupline);
      return 0;
    }

    return array("rowid" => $id);
  }
}
