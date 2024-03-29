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

    require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
    $product_extrafields = new ExtraFields($db);
    $product_extrafields->fetch_name_optionals_label('product');

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
    $width = $object->width;
    if (!empty($width)) { $width.= ' ' . measuringUnitString(0, 'size', $object->width_units); }
    $height = $object->height;
    if (!empty($height)) { $height.= ' ' . measuringUnitString(0, 'size', $object->height_units); }
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
      'width_txt' => $width,
      'height_txt' => $height,
      'surface_txt' => $surface,
      'volume_txt' => $volume,
      // Numeric values for weight, length, ... : only if the unit is the good one (required for edit mode)
      'weight' => $object->weight_units == $conf->global->PICKUP_WEIGHT_UNIT ? $object->weight : null,
      'length' => $object->length_units == $conf->global->PICKUP_SIZE_UNIT ? $object->length : null,
      'width' => $object->width_units == $conf->global->PICKUP_SIZE_UNIT ? $object->width : null,
      'height' => $object->height_units == $conf->global->PICKUP_SIZE_UNIT ? $object->height : null,
      'surface' => $object->surface_units == $conf->global->PICKUP_SURFACE_UNIT ? $object->surface : null,
      'volume' => $object->volume_units == $conf->global->PICKUP_VOLUME_UNIT ? $object->volume : null,
      'sellprice' => $object->price
    );
    if (!empty($conf->global->PICKUP_USE_PBRAND)) {
      $result['pbrand'] = $object->array_options['options_pickup_pbrand'];
    }
    if (property_exists($conf, 'rental') && !empty($conf->rental->enabled)) {
      $result['rentalprice'] = $object->array_options['options_rental_price'];
    }
    if (!empty($conf->global->PICKUP_SPECIFIC_MODE) && $conf->global->PICKUP_SPECIFIC_MODE === 'ressourcerie_cinema') {
      // Champs spécifiques La Ressourcerie Du Cinéma
      $result['lrdc_diametre'] = $object->array_options['options_diametre'] ?? '';
      $result['lrdc_epaisseur'] = $object->array_options['options_epaisseur'] ?? '';
      $result['lrdc_matiereproduit'] = $object->array_options['options_matiereproduit'] ?? '';
      $result['lrdc_pxcommerce'] = $object->array_options['options_pxcommerce'] ?? '';
      $result['lrdc_couleur'] = $object->array_options['options_couleur'] ?? '';
      $result['lrdc_style'] = $object->array_options['options_style'] ?? '';
      $result['lrdc_conditionnement'] = $object->array_options['options_conditionnement'] ?? '';
      $result['lrdc_conditionnement_txt'] = $product_extrafields->showOutputField('conditionnement', $object->array_options['options_conditionnement'], '', $object->table_element);
    }
    if (!empty($conf->global->PICKUP_USE_DEEE)) {
      $result['deee_type'] = $object->array_options['options_pickup_deee_type'];
      $result['deee_type_txt'] = $product_extrafields->showOutputField('pickup_deee_type', $object->array_options['options_pickup_deee_type'], '', $object->table_element);
    }
    if (!empty($conf->productbatch->enabled)) {
      $langs->loadLangs(array('productbatch'));
      $result['hasbatch'] = $object->status_batch;

      switch ($object->status_batch) {
        case 2:
          $result['hasbatch_txt'] = $langs->transnoentities('ProductStatusOnSerial');
          break;
        case 1:
          $result['hasbatch_txt'] = $langs->transnoentities('ProductStatusOnBatch');
          break;
        default:
          $result['hasbatch_txt'] = $langs->transnoentities('ProductStatusNotOnBatch');
      }
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

    $old_sellprice = null;
    $new_sellprice = null;

    $subaction = GETPOST('subaction');
    if (!empty($subaction)) {
      $is_creation = false;

      $product_id = GETPOST('product', 'int');
      if ($product->fetch($product_id) <= 0) {
        $this->_log_object_errors(__METHOD__, $product);
        return 0;
      }

      $old_sellprice = $product->price;

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
      if (empty($conf->global->PICKUP_PRODUCT_REF_AUTO)) {
        $product->ref = GETPOST('product_ref');
        $product_label = GETPOST('product_label');
        if (empty($product_label)) {
          $product_label = GETPOST('product_ref');
        }
        $product->label = $product_label;
      } else {
        $product->ref = '';
        $product_label = GETPOST('product_label');
        $product->label = $product_label;
      }
      if (!empty($conf->global->PICKUP_PRODUCT_DEFAULT_TOSELL)) {
        $product->status = 1; // to sell
        // Note: have defined a sell price (see bellow) could also set status to 1.
      }

      if (!empty($conf->global->PICKUP_USE_PBRAND)) {
        $product->array_options['options_pickup_pbrand'] = GETPOST('product_pbrand');
      }
    }

    if ($save_common_attributes) {
      $product->description = htmlentities(trim(GETPOST('product_description')));

      if ($conf->global->PICKUP_UNITS_EDIT_MODE !== 'pickupline') {
        if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) {
          $product->weight = GETPOST('weight', 'int'); // yes... for dolibarr floats are 'int'
          $product->weight_units = GETPOST('weight_unit', 'alpha');
        }
        if (!empty($conf->global->PICKUP_UNITS_LENGTH)) {
          $product->length = GETPOST('length', 'int'); // yes... for dolibarr floats are 'int'
          $product->length_units = GETPOST('length_unit', 'alpha');;
        }
        if (!empty($conf->global->PICKUP_UNITS_WIDTH)) {
          $product->width = GETPOST('width', 'int'); // yes... for dolibarr floats are 'int'
          $product->width_units = GETPOST('width_unit', 'alpha');;
        }
        if (!empty($conf->global->PICKUP_UNITS_HEIGHT)) {
          $product->height = GETPOST('height', 'int'); // yes... for dolibarr floats are 'int'
          $product->height_units = GETPOST('height_unit', 'alpha');;
        }
        if (!empty($conf->global->PICKUP_UNITS_SURFACE)) {
          $product->surface = GETPOST('surface', 'int'); // yes... for dolibarr floats are 'int'
          $product->surface_units = GETPOST('surface_unit', 'alpha');;
        }
        if (!empty($conf->global->PICKUP_UNITS_VOLUME)) {
          $product->volume = GETPOST('volume', 'int'); // yes... for dolibarr floats are 'int'
          $product->volume_units = GETPOST('volume_unit', 'alpha'); // -3 = L
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

      if (!empty($conf->global->PICKUP_PRODUCT_SELLPRICE)) {
        $new_sellprice = GETPOST('sellprice', 'int'); // yes... for dolibarr floats are 'int'
        if ($new_sellprice > 0) {
          if ($is_creation) {
            // When !is_creation, we will use updatePrice to update the price (see later on).
            $product->price = $new_sellprice;
          }
          $product->status = 1; // to sell
        }
      }
      if (!empty($conf->global->PICKUP_PRODUCT_RENTALPRICE) && property_exists($conf, 'rental') && $conf->rental->enabled) {
        $rental_price = GETPOST('rentalprice', 'int'); // yes... for dolibarr floats are 'int'
        $product->array_options['options_rental_price'] = $rental_price;
        if ($rental_price > 0) {
          $product->array_options['options_rental_product'] = 1;
        }
      }

      if (!empty($conf->global->PICKUP_SPECIFIC_MODE) && $conf->global->PICKUP_SPECIFIC_MODE === 'ressourcerie_cinema') {
        // Champs spécifiques La Ressourcerie Du Cinéma
        $product->array_options['options_diametre'] = GETPOST('lrdc_diametre');
        $product->array_options['options_epaisseur'] = GETPOST('lrdc_epaisseur');
        $product->array_options['options_matiereproduit'] = GETPOST('lrdc_matiereproduit');
        $product->array_options['options_pxcommerce'] = GETPOST('lrdc_pxcommerce');
        $product->array_options['options_couleur'] = GETPOST('lrdc_couleur');
        $product->array_options['options_style'] = GETPOST('lrdc_style');
        $product->array_options['options_conditionnement'] = GETPOST('lrdc_conditionnement');
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

        // On met à jour le prix si nécessaire
        if (!empty($conf->global->PICKUP_PRODUCT_SELLPRICE)) {
          if ($old_sellprice != $new_sellprice && $new_sellprice > 0) {
            $product->updatePrice($new_sellprice, $product->price_base_type, $user);
          }
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

          // must generate missing pbatches in certain cases.
          $line->ensurePBatches($user);
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
