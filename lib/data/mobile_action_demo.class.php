<?php
dol_include_once('/pickup/lib/data/mobile_action.class.php');

$cpt = 1;
$examples = array();
array_push($examples, array(
  'rowid' => strval($cpt++),
  'field1' => 'Orange',
  'field2' => 'Dog',
  'fieldbool' => true,
  'fielddate' => '2022-01-01',
  'fieldinteger' => 12,
  'fieldfloat' => 1.2,
  'fieldradio' => 'o1',
  'fieldselect' => 'o1',
  'fieldtext' => 'This is the text'
));
array_push($examples, array(
  'rowid' => strval($cpt++),
  'field1' => 'Orange',
  'field2' => 'Cat',
  'fieldbool' => true,
  'fielddate' => '2022-01-01',
  'fieldinteger' => 12,
  'fieldfloat' => 1.2,
  'fieldradio' => 'o1',
  'fieldselect' => 'o1',
  'fieldtext' => 'This is the text'
));
array_push($examples, array(
  'rowid' => strval($cpt++),
  'field1' => 'Red',
  'field2' => 'Bird',
  'fieldbool' => true,
  'fielddate' => '2022-01-01',
  'fieldinteger' => 12,
  'fieldfloat' => 1.2,
  'fieldradio' => 'o1',
  'fieldselect' => 'o1',
  'fieldtext' => 'This is the text'
));
array_push($examples, array(
  'rowid' => strval($cpt++),
  'field1' => 'Yellow',
  'field2' => 'Mocking jay',
  'fieldbool' => true,
  'fielddate' => '2022-01-01',
  'fieldinteger' => 12,
  'fieldfloat' => 1.2,
  'fieldradio' => 'o1',
  'fieldselect' => 'o1',
  'fieldtext' => 'This is the text'
));
array_push($examples, array(
  'rowid' => strval($cpt++),
  'field1' => 'Blue',
  'field2' => 'Lezard',
  'fieldbool' => true,
  'fielddate' => '2022-01-01',
  'fieldinteger' => 12,
  'fieldfloat' => 1.2,
  'fieldradio' => 'o1',
  'fieldselect' => 'o1',
  'fieldtext' => 'This is the text'
));
array_push($examples, array(
  'rowid' => strval($cpt++),
  'field1' => 'Black',
  'field2' => 'Mamba',
  'fieldbool' => true,
  'fielddate' => '2022-01-01',
  'fieldinteger' => 12,
  'fieldfloat' => 1.2,
  'fieldradio' => 'o1',
  'fieldselect' => 'o1',
  'fieldtext' => 'This is the text'
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
      'rowid' => '42',
      'field1' => GETPOST('field1', 'alpha'),
      'field2' => GETPOST('field2', 'alpha'),
      'fieldbool' => GETPOST('fieldbool', 'alpha') === '1',
      'fielddate' => GETPOST('fielddate', 'alpha'),
      'fieldinteger' => GETPOST('fieldinteger', 'int'),
      'fieldfloat' => GETPOST('fieldfloat', 'int'),
      'fieldradio' => GETPOST('fieldradio', 'alpha'),
      'fieldselect' => GETPOST('fieldselect', 'alpha'),
      'fieldtext' => GETPOST('fieldtext', 'alpha'),
    );
  }
}
