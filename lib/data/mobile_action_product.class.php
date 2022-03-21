<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');

class DataMobileActionProduct extends DataMobileAction {
  public function action_list() {
    global $conf;

    dol_syslog(__METHOD__, LOG_DEBUG);
    $db = $this->db;
    require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
    $object = new Product($db);

    $sql = 'SELECT t.rowid, t.ref ';
    if (!empty($conf->global->PICKUP_USE_PBRAND)) {
      $sql.= ', ef.pickup_pbrand as pbrand ';
    }
    $sql.= ' FROM '.MAIN_DB_PREFIX.'product as t ';
    if (!empty($conf->global->PICKUP_USE_PBRAND)) {
      $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields as ef on t.rowid = ef.fk_object ';
    }
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

  public function action_get() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    global $langs, $conf;

    $db = $this->db;
    require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
    global $object; // has to be this... LRDS has a weird module that needs this to fetch without error...
    $object = new Product($db);

    $id = GETPOST('id', 'int');
    if ($object->fetch($id) <= 0) {
      return 0;
    }

    require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$cat = new Categorie($db);
    $cats = $cat->containing($id, Categorie::TYPE_PRODUCT, 'object');
    $cats_labels = array();
		if (!($cats <= 0)) {
      foreach ($cats as $cat) {
        $allways = $cat->get_all_ways();
        foreach ($allways as $way) {
          foreach ($way as $parent_cat) {
            array_push($cats_labels, $parent_cat->label);
          }
        }
      }
    }
    $cats_labels = array_unique($cats_labels, SORT_STRING);

    require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php'; // for measuringUnitString
    $langs->loadLangs(array("other"));
    $weight = $object->weight;
    if (!empty($weight)) { $weight.= ' ' . measuringUnitString(0, "weight", $object->weight_units); }
    $length = $object->length;
    if (!empty($length)) { $length.= ' ' . measuringUnitString(0, 'size', $object->length_units); }
    $surface = $object->surface;
    if (!empty($surface)) { $surface.= ' ' . measuringUnitString(0, 'surface', $object->surface_units); }
    $volume = $object->volume;
    if (!empty($volume)) { $volume.= ' ' . measuringUnitString(0, 'volume', $object->volume_units); }
    

    $result = array(
      'rowid' => $object->id,
      'ref' => $object->ref,
      'description' => $object->description,
      'label' => $object->label,
      'pcats' => join(', ', $cats_labels),
      // FIXME: should be weight + weight_units (and so on...)... But it is simplier like that for now
      'weight_txt' => $weight,
      'length_txt' => $length,
      'surface_txt' => $surface,
      'volume_txt' => $volume
    );
    if (!empty($conf->global->PICKUP_USE_PBRAND)) {
      $result['pbrand'] = $object->array_options['options_pickup_pbrand'];
    }
    if (!empty($conf->global->PICKUP_USE_DEEE)) {
      require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
      $extrafields = new ExtraFields($db);
      $extrafields->fetch_name_optionals_label('product');
      $result['deee_type'] = $extrafields->showOutputField('pickup_deee_type', $object->array_options['options_pickup_deee_type'], '', $object->table_element);
    }
    return $result;
  }

  public function action_save() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    $db = $this->db;
    global $user, $conf, $langs;

    require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
    $product = new Product($db);

    $product->type = Product::TYPE_PRODUCT;
    $product->ref = GETPOST('product_ref');
    $product_label = GETPOST('product_label');
    if (empty($product_label)) {
      $product_label = GETPOST('product_ref');
    }
    $product->label = $product_label;
    $product->description = htmlentities(trim(GETPOST('product_description')));

    if (!empty($conf->global->PICKUP_UNITS_WEIGHT) && $conf->global->PICKUP_UNITS_WEIGHT !== '0') {
      $product->weight = GETPOST('weight', 'int'); // yes... for dolibarr floats are 'int'
      $product->weight_units = 0;
    }
    if (!empty($conf->global->PICKUP_UNITS_LENGTH) && $conf->global->PICKUP_UNITS_LENGTH !== '0') {
      $product->length = GETPOST('length', 'int'); // yes... for dolibarr floats are 'int'
      $product->length_units = 0;
    }
    if (!empty($conf->global->PICKUP_UNITS_SURFACE) && $conf->global->PICKUP_UNITS_SURFACE !== '0') {
      $product->surface = GETPOST('surface', 'int'); // yes... for dolibarr floats are 'int'
      $product->surface_units = 0;
    }
    if (!empty($conf->global->PICKUP_UNITS_VOLUME) && $conf->global->PICKUP_UNITS_VOLUME !== '0') {
      $product->volume = GETPOST('volume', 'int'); // yes... for dolibarr floats are 'int'
      $product->volume_units = -3; // L
    }
    
    if (!empty($conf->global->PICKUP_USE_PBRAND)) {
      $product->array_options['options_pickup_pbrand'] = GETPOST('product_pbrand');
    }
    if (!empty($conf->global->PICKUP_USE_DEEE)) {
      $deee_type = GETPOST('product_deee_type', 'alpha');
      if (!empty($deee_type)) {
        $product->array_options['options_pickup_deee_type'] = $deee_type;
      }
    }
    if (!empty($conf->productbatch->enabled)) {
      if ($conf->global->PICKUP_DEFAULT_HASBATCH === '1') {
        $product->status_batch = 1;
      } else if ($conf->global->PICKUP_DEFAULT_HASBATCH === 'ask') {
        $product->status_batch = GETPOST('product_hasbatch') === '1' ? 1 : 0;
      } else {
        $product->status_batch = 0;
      }
    }
  
    $product_id = $product->create($user);
    if (!$product_id || $product_id <= 0) {
      $this->_log_object_errors(__METHOD__, $product);
      if ($product->error === 'ErrorProductAlreadyExists') {
        $langs->loadLangs(array('products'));
        return $this->_error_response('ErrorProductAlreadyExists', $langs->transnoentitiesnoconv('ErrorProductAlreadyExists', $product->ref));
      }
      return 0;
    }

    // Now we have to set the category
    if (!empty($conf->global->PICKUP_USE_PCAT)) {
      $pcat = GETPOST('pcat', 'int');
      $product->setCategories(array($pcat));
    }

    $result = array("rowid" => $product_id, "ref" => $product->ref);
    if (!empty($conf->global->PICKUP_USE_PBRAND)) {
      $result['pbrand'] = $product->array_options['options_pickup_pbrand'];
    }
    return $result;
  }
}
