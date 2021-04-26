<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');

class DataMobileActionProduct extends DataMobileAction {
  public function action_list() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    $db = $this->db;
    require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
    $object = new Product($db);

    $sql = 'SELECT t.rowid, t.ref, ef.marque as options_marque ';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'product as t ';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields as ef on t.rowid = ef.fk_object ';
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
    global $langs;
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
    $weight = $object->weight . ' ' . measuringUnitString(0, "weight", $object->weight_units);

    require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
    $extrafields = new ExtraFields($db);
    $extrafields->fetch_name_optionals_label('product');
    $deee_type = $extrafields->showOutputField('type_deee', $object->array_options['options_type_deee'], '', $object->table_element);

    return array(
      'rowid' => $object->id,
      'ref' => $object->ref,
      'desc' => $object->description,
      'label' => $object->label,
      'marque' => $object->array_options['options_marque'],
      'pcats' => join(', ', $cats_labels),
      'deee_type' => $deee_type,
      'weight_txt' => $weight // FIXME: should be weight + weight_units... Be it is simplier like that for now
    );
  }

  public function action_save() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    $db = $this->db;
    global $user;

    require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
    $product = new Product($db);

    $product->type = Product::TYPE_PRODUCT;
    $product->ref = GETPOST('product_ref');
    $product_label = GETPOST('product_label');
    if (empty($product_label)) {
      $product_label = GETPOST('product_ref');
    }
    $product->label = $product_label;
    $product->description = htmlentities(GETPOST('product_desc'));
    $product->weight = GETPOST('weight', 'int'); // yes... for dolibarr floats are 'int'
    $product->weight_units = 0;
    
    $product->array_options['options_marque'] = GETPOST('product_marque');
    $deee_type = GETPOST('product_deee_type', 'alpha');
    if (empty($deee_type)) {
      $product->array_options['options_deee'] = 0;
    } else {
      $product->array_options['options_deee'] = 1;
      $product->array_options['options_type_deee'] = $deee_type;
    }
    // FIXME: see specifications for missing fields
  
    $product_id = $product->create($user);
    if (!$product_id || $product_id <= 0) {
      $this->_log_object_errors(__METHOD__, $product);
      return 0;
    }

    // Now we have to set the category
    $pcat = GETPOST('pcat', 'int');
    $product->setCategories(array($pcat));

    return array("rowid" => $product_id, "ref" => $product->ref, "marque" => $product->array_options['options_marque']);
  }
}
