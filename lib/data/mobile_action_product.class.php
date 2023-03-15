<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');
dol_include_once('/pickup/class/mobilecat.class.php');

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
    $cats_labels = [];
    $mobile_cats = [];

		if (!($cats <= 0)) {
      foreach ($cats as $cat) {
        $allways = $cat->get_all_ways();

        // is this a mobile cat?
        $is_mobile_cat = false;
        $mobilecat = new PickupMobileCat($db);
        if (
          $mobilecat->fetchByCategory($cat->id) > 0
          && !empty($mobilecat->id)
          && $mobilecat->active
        ) {
          $is_mobile_cat = true;
        }

        foreach ($allways as $way) {
          $cat_label = [];
          foreach ($way as $parent_cat) {
            $cat_label[] = $parent_cat->label;
          }
          $cat_label = implode(' >> ', $cat_label);
          $cats_labels[] = $cat_label;

          if ($is_mobile_cat) {
            $mobile_cats[] = [
              'label' => $cat_label,
              'cat' => $cat
            ];
          }
        }
      }
    }
    $cats_labels = array_unique($cats_labels, SORT_STRING);
    sort($cats_labels, SORT_STRING);

    $reference_pcat = null; // The product categorie that should be considered as the reference one for mobile app
    // To compute $reference_pcat, we will search the longest label in $mobile_cats.
    // This is an approximation.
    // In the standard case, we are searching the deepest nested category which is active for mobile.
    // This is, in the standard case, equivalent to the longest label (as we implode $allways).
    // This is not true if there are multiple mobile categories attached to the product...
    // But this case is not supposed to happen... (it can... but...)
    $reference_pcat_label = null;
    foreach ($mobile_cats as $mobile_cat_info) {
      if ($reference_pcat_label === null || strlen($reference_pcat_label) < strlen($mobile_cat_info['label'])) {
        $reference_pcat_label = $mobile_cat_info['label'];
        $reference_pcat = $mobile_cat_info['cat'];
      }
    }

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
      'description' => dol_htmlentitiesbr($object->description),
      'label' => $object->label,
      'pcats' => join(', ', $cats_labels),
      'reference_pcat_id' => $reference_pcat ? $reference_pcat->id : null,
      'reference_pcat_label' => $reference_pcat_label,
      // FIXME: should be weight + weight_units (and so on...)... But it is simplier like that for now
      'weight_txt' => $weight,
      'length_txt' => $length,
      'surface_txt' => $surface,
      'volume_txt' => $volume,
      // Numeric values for weight, length, ... : only if the unit is the good one (required for edit mode)
      'weight' => $object->weight_units == 0 ? $object->weight : null,
      'length' => $object->length_units == 0 ? $object->length : null,
      'surface' => $object->surface_units == 0 ? $object->surface : null,
      'volume' => $object->volume_units == -3 ? $object->volume : null
    );
    if (!empty($conf->global->PICKUP_USE_PBRAND)) {
      $result['pbrand'] = $object->array_options['options_pickup_pbrand'];
    }
    if (!empty($conf->global->PICKUP_USE_DEEE)) {
      require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
      $extrafields = new ExtraFields($db);
      $extrafields->fetch_name_optionals_label('product');
      $result['deee_type'] = $object->array_options['options_pickup_deee_type'];
      $result['deee_type_txt'] = $extrafields->showOutputField('pickup_deee_type', $object->array_options['options_pickup_deee_type'], '', $object->table_element);
    }
    if (!empty($conf->productbatch->enabled)) {
      $result['hasbatch'] = $product->status_batch;
    }
    return $result;
  }

  public function action_save() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    $db = $this->db;
    global $user, $conf, $langs;

    require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
    $product = new Product($db);

    $pickup = null;

    $is_creation = true;
    $save_common_attributes = false;
    $save_cat = false;
    $save_product = false;
    $refresh_pickup_line = false;

    $subaction = GETPOST('subaction');
    if (!empty($subaction)) {
      $is_creation = false;

      $product_id = GETPOST('product', 'int');
      if ($product->fetch($product_id) <= 0) {
        $this->_log_object_errors(__METHOD__, $product);
        return 0;
      }

      if ($subaction === 'edit_product_attrs' || $subaction === 'edit_product_attrs_from_pickup') {
        $save_common_attributes = true;
        $save_product = true;

        if ($subaction === 'edit_product_attrs_from_pickup') {
          $refresh_pickup_line = true;
          $pickup_id = GETPOST('pickup', 'int');
          dol_include_once('/pickup/class/pickup.class.php');
          $pickup = new Pickup($db);
          if ($pickup->fetch($pickup_id) <= 0) {
            $this->_log_object_errors(__METHOD__, $pickup);
            return 0;
          }
        }

      } elseif ($subaction === 'edit_product_cat_from_pickup') {
        $save_cat = true;
      } else {
        dol_syslog(__METHOD__.' Invalid sub action: '.$subaction, LOG_ERR);
        return 0;
      }
    } else {
      // Product creation
      $is_creation = true;
      $save_common_attributes = true;
      $save_cat = true;
      $save_product = true;

      $product->type = Product::TYPE_PRODUCT;
      $product->ref = GETPOST('product_ref');
      $product_label = GETPOST('product_label');
      if (empty($product_label)) {
        $product_label = GETPOST('product_ref');
      }
      $product->label = $product_label;

      if (!empty($conf->global->PICKUP_USE_PBRAND)) {
        $product->array_options['options_pickup_pbrand'] = GETPOST('product_pbrand');
      }
    }

    if ($save_common_attributes) {
      $product->description = htmlentities(trim(GETPOST('product_description')));

      if ($conf->global->PICKUP_UNITS_EDIT_MODE !== 'pickupline') {
        if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) {
          $product->weight = GETPOST('weight', 'int'); // yes... for dolibarr floats are 'int'
          $product->weight_units = 0;
        }
        if (!empty($conf->global->PICKUP_UNITS_LENGTH)) {
          $product->length = GETPOST('length', 'int'); // yes... for dolibarr floats are 'int'
          $product->length_units = 0;
        }
        if (!empty($conf->global->PICKUP_UNITS_SURFACE)) {
          $product->surface = GETPOST('surface', 'int'); // yes... for dolibarr floats are 'int'
          $product->surface_units = 0;
        }
        if (!empty($conf->global->PICKUP_UNITS_VOLUME)) {
          $product->volume = GETPOST('volume', 'int'); // yes... for dolibarr floats are 'int'
          $product->volume_units = -3; // L
        }
      }

      if (!empty($conf->global->PICKUP_USE_DEEE)) {
        $deee_type = GETPOST('product_deee_type', 'alpha');
        if (!empty($deee_type) || $deee_type === '') {
          $product->array_options['options_pickup_deee_type'] = $deee_type;
        }
      }
      if (!empty($conf->productbatch->enabled)) {
        if ($conf->global->PICKUP_DEFAULT_HASBATCH === '1') {
          $product->status_batch = 1;
        } else if ($conf->global->PICKUP_DEFAULT_HASBATCH === '2') {
          $product->status_batch = 2;
        } else if ($conf->global->PICKUP_DEFAULT_HASBATCH === 'ask') {
          $status_batch = GETPOST('product_hasbatch');
          if ($status_batch === '1') {
            $product->status_batch = 1;
          } elseif ($status_batch === '2') {
            $product->status_batch = 2;
          } else {
            $product->status_batch = 0;
          }
        } else {
          $product->status_batch = 0;
        }
      }
    }

    if ($save_product) {
      if ($is_creation) {
        $product_id = $product->create($user);
        if (!$product_id || $product_id <= 0) {
          $this->_log_object_errors(__METHOD__, $product);
          if ($product->error === 'ErrorProductAlreadyExists') {
            $langs->loadLangs(array('products'));
            return $this->_error_response('ErrorProductAlreadyExists', $langs->transnoentitiesnoconv('ErrorProductAlreadyExists', $product->ref));
          }
          return 0;
        }
      } else {
        $result = $product->update($product_id, $user);
        if ($result <= 0) {
          $this->_log_object_errors(__METHOD__, $product);
          return 0;
        }
      }
    }

    if ($save_cat) {
      // Now we have to set the category
      if (!empty($conf->global->PICKUP_USE_PCAT)) {
        $pcat = GETPOST('pcat', 'int');
        $product->setCategories(array($pcat));
      }
    }

    if ($refresh_pickup_line && !empty($pickup)) {
      $pickup->getLinesArray();
      foreach ($pickup->lines as $line) {
        if (intval($line->fk_product) === intval($product_id)) {
          if (!empty($conf->global->PICKUP_USE_DEEE)) {
            $line->deee = $product->array_options['options_pickup_deee'];
            $line->deee_type = $product->array_options['options_pickup_deee_type'];
          }

          if ($conf->global->PICKUP_UNITS_EDIT_MODE !== 'pickupline') {
            if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) {
              $line->weight = $product->weight;
              $line->weight_units = $product->weight_units;
            }
            if (!empty($conf->global->PICKUP_UNITS_LENGTH)) {
              $line->length = $product->length;
              $line->length_units = $product->length_units;
            }
            if (!empty($conf->global->PICKUP_UNITS_SURFACE)) {
              $line->surface = $product->surface;
              $line->surface_units = $product->surface_units;
            }
            if (!empty($conf->global->PICKUP_UNITS_VOLUME)) {
              $line->volume = $product->volume;
              $line->volume_units = $product->volume_units;
            }
          }

          if ($line->update($user) <= 0) {
            $this->_log_object_errors(__METHOD__, $line);
            return 0;
          }
        }
      }
    }

    $result = array("rowid" => $product_id, "ref" => $product->ref);
    if (!empty($conf->global->PICKUP_USE_PBRAND)) {
      $result['pbrand'] = $product->array_options['options_pickup_pbrand'];
    }
    return $result;
  }
}
