<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');

class DataMobileActionPickupline extends DataMobileAction {
  public function action_get() {
    global $conf;
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
      'qty' => $pickupline->qty,
      // For weight/length/..., we only add the value if the unit is the standard one.
      'line_weight' => $pickupline->weight_units == 0 ? $pickupline->weight : null,
      'line_length' => $pickupline->length_units == 0 ? $pickupline->length : null,
      'line_surface' => $pickupline->surface_units == 0 ? $pickupline->surface : null,
      'line_volume' => $pickupline->volume_units == -3 ? $pickupline->volume : null
    );
    if (!empty($conf->global->PICKUP_USE_PICKUPLINE_DESCRIPTION)) {
      $r['line_description'] = $pickupline->description;
    }
    // For now, no need to have more values.

    return $r;
  }

  public function action_save() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    global $user, $conf;
    $db = $this->db;

    dol_include_once('/pickup/class/pickup.class.php');
    dol_include_once('/pickup/class/pickupline.class.php');
    
    $pickup = new Pickup($db);
    if ($pickup->fetch(GETPOST('pickup', 'int')) <= 0) {
      return 0;
    }

    $pickup_line_id = GETPOST('pickup_line_id', 'int');
    $qty = GETPOST('qty', 'int');
    $line_description = GETPOST('line_description', 'none');
    if (!empty($pickup_line_id)) {
      // This is an edition
      $pickupline = new PickupLine($this->db);
      if ($pickupline->fetch($pickup_line_id) <= 0) {
        $this->_log_object_errors(__METHOD__, $pickupline);
        return 0;
      }

      $pickupline->qty = $qty;
      if (!empty($conf->global->PICKUP_USE_PICKUPLINE_DESCRIPTION)) {
        $pickupline->description = $line_description;
      }

      $this->fill_units_values($pickupline);

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
      if (!empty($conf->global->PICKUP_USE_PICKUPLINE_DESCRIPTION)) {
        $pickupline->description = $line_description;
      }
      $this->fill_units_values($pickupline);
      $id = $pickupline->create($user);
      if (!$id || $id <= 0) {
        $this->_log_object_errors(__METHOD__, $pickupline);
        return 0;
      }
    }

    return array("rowid" => $id);
  }

  protected function fill_units_values($pickupline) {
    global $conf;

    if ($conf->global->PICKUP_UNITS_EDIT_MODE !== 'pickupline') {
      return;
    }

    if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) {
      $pickupline->weight = GETPOST('line_weight', 'int'); // yes... for dolibarr floats are 'int'
      $pickupline->weight_units = 0;
    }
    if (!empty($conf->global->PICKUP_UNITS_LENGTH)) {
      $pickupline->length = GETPOST('line_length', 'int'); // yes... for dolibarr floats are 'int'
      $pickupline->length_units = 0;
    }
    if (!empty($conf->global->PICKUP_UNITS_SURFACE)) {
      $pickupline->surface = GETPOST('line_surface', 'int'); // yes... for dolibarr floats are 'int'
      $pickupline->surface_units = 0;
    }
    if (!empty($conf->global->PICKUP_UNITS_VOLUME)) {
      $pickupline->volume = GETPOST('line_volume', 'int'); // yes... for dolibarr floats are 'int'
      $pickupline->volume_units = -3; // L
    }
  }
}
