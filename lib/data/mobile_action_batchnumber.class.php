<?php
dol_include_once('/product/class/product.class.php');
dol_include_once('/pickup/lib/data/mobile_action.class.php');

// Note: despite the filename (mobile_action_...), this action can be called from the classic Dolibarr app.

class DataMobileActionBatchNumber extends DataMobileAction {
  public function testRights($user, $action) {
    if ($action === 'generate') {
      if ($user->rights->pickup->create) {
        return true;
      }
      if ($user->rights->stock->mouvement->creer) {
        return true;
      }
      return false;
    }
    return parent::testRights($user, $action);
  }
  public function action_generate() {
    global $conf, $user, $db;

    dol_syslog(__METHOD__, LOG_DEBUG);
    dol_include_once('/pickup/class/pbatch.class.php');

    $product = null;
    $product_id = GETPOST('product_id', 'int');
    if ($product_id) {
      $product = new Product($db);
      if ($product->fetch($product_id) <=0) {
        return 0;
      }
    }
    if (!empty($product) && $product->status_batch == 1 && $conf->global->PICKUP_DEFAULT_BATCH === 'generate_per_product') {
      $batch_number = PBatch::getPBatchPerProduct($product->id, $user);
    } else {
      $batch_number = PBatch::getNextPBatchNumber();
    }


    $response = array();
    $response['batch_number'] = $batch_number;
    return $response;
  }
}
