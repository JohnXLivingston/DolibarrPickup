<?php

abstract class DataMobileAction {
  protected $db;

  public function __construct($db) {
    $this->db = $db;
  }

  /**
   * You can override this if you want to test other rights for your action.
   */
  public function testRights($user, $action) {
    return $user->rights->pickup->create ? true : false; // mobile app needs create rights.
  }

  protected function _log_object_errors($method, $object) {
    if (!empty($object->error)) dol_syslog($method.' '.$object->error, LOG_ERR);
    if (!empty($object->errors)) dol_syslog($method.' '.join(',', $object->errors), LOG_ERR);
  }

  protected function _log_sql_errors($db) {
    dol_syslog($method.' '.$db->lasterror(), LOG_ERR);
  }

  protected function _error_response($error_code, $error_message) {
    return array(
      '_pickup_error' => $error_code,
      '_pickup_error_message' => $error_message
    );
  }
}
