<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');

$cpt = 1;
$examples = array();
array_push($examples, array(
  'rowid' => $cpt++,
  'field1' => 'Orange',
  'field2' => 'Dog'
));
array_push($examples, array(
  'rowid' => $cpt++,
  'field1' => 'Orange',
  'field2' => 'Cat'
));
array_push($examples, array(
  'rowid' => $cpt++,
  'field1' => 'Red',
  'field2' => 'Bird'
));
array_push($examples, array(
  'rowid' => $cpt++,
  'field1' => 'Yellow',
  'field2' => 'Mocking jay'
));
array_push($examples, array(
  'rowid' => $cpt++,
  'field1' => 'Blue',
  'field2' => 'Lezard'
));
array_push($examples, array(
  'rowid' => $cpt++,
  'field1' => 'Black',
  'field2' => 'Mamba'
));

class DataMobileActionDemo extends DataMobileAction {
  public function action_list() {
    global $examples;

    dol_syslog(__METHOD__, LOG_DEBUG);
    
    return $examples;
  }

  public function action_get() {
    global $examples;

    dol_syslog(__METHOD__, LOG_DEBUG);
    $id = GETPOST('id', 'int');
    $result = $examples[$id - 1];
    return $result;
  }

  public function action_save() {
    dol_syslog(__METHOD__, LOG_DEBUG);
    return array(
      'rowid' => 42,
      'field1' => GETPOST('field1', 'alpha'),
      'field2' => GETPOST('field2', 'alpha')
    );
  }
}
