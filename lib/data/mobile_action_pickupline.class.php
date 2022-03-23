<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');

class DataMobileActionPickupline extends DataMobileAction {
  public function action_get() {
    dol_syslog(__METHOD__, LOG_DEBUG);

    $id = GETPOST('id', 'int');
    dol_include_once('/pickup/class/pickupline.class.php');
    $pickupline = new PickupLine($this->db);
    if ($pickupline->fetch($id) <= 0) {
      return 0;
    }

    require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
    $product = new Product($db);
    $product->fetch($line->fk_product);

    $r = array(
      'rowid' => $pickupline->id,
      'name' => $product->ref,
      'qty' => $pickupline->qty
    );
    // For now, no need to have more values.

    return $r;
  }

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

    $pickup_line_id = GETPOST('pickup_line_id', 'int');
    $qty = GETPOST('qty', 'int');
    if (!empty($pickup_line_id)) {
      // This is an edition
      $pickupline = new PickupLine($this->db);
      if ($pickupline->fetch($pickup_line_id) <= 0) {
        $this->_log_object_errors(__METHOD__, $pickupline);
        return 0;
      }

      $pickupline->qty = $qty;
      $result = $pickupline->update($user);
      if ($result <= 0) {
        $this->_log_object_errors(__METHOD__, $pickupline);
        return 0;
      }

      $id = $pickupline->id;
    } else {
      // This is a creation
      $product_id = GETPOST('product', 'int');

      $pickupline = $pickup->initPickupLine($product_id, $qty);
      $id = $pickupline->create($user);
      if (!$id || $id <= 0) {
        $this->_log_object_errors(__METHOD__, $pickupline);
        return 0;
      }
    }

    return array("rowid" => $id);
  }
}
