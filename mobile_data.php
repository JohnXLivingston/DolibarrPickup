<?php
/* Copyright (C) 2021-2022 John Livingston
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');


// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/../main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/../main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

if (! $user->rights->pickup->create) { // mobile app needs create rights.
  http_response_code(403);
  accessforbidden();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache');

$key = GETPOST('key', 'alpha');
$action = GETPOST('action', 'alpha');

dol_syslog('Calling action "'.$action.'" on key "'.$key.'".', LOG_DEBUG);

if (preg_match('/^[a-z]+$/', $key) && preg_match('/^\w+$/', $action)) {
  $lib_file = './lib/data/mobile_action_'.$key.'.class.php';
  if (file_exists($lib_file)) {
    include_once($lib_file);
    $className = 'DataMobileAction'.ucfirst($key);
    $actionMethod = 'action_'.$action;
    if(class_exists($className)) {
      $obj = new $className($db);
      if(method_exists($obj, $actionMethod)) {
        dol_syslog("Calling method $actionMethod on class $className", LOG_DEBUG);
        $json = $obj->$actionMethod();
        if (!is_array($json)) {
          dol_syslog('The action '.$key.'->'.$action.' returns an empty object. Returning a 500 error.', LOG_ERR);
          http_response_code(500);
          exit(0);
        }
        dol_syslog('The action '.$key.'->'.$action.' returned a value.', LOG_DEBUG);
        print json_encode($json);
        exit(0);
      }
      dol_syslog('pickup_mobile_data: method '.$actionMethod.' do not exist on class '.$className, LOG_ERR);
    } else {
      dol_syslog('pickup_mobile_data: Class '.$className.' not found', LOG_ERR);
    }
  } else {
    dol_syslog('pickup_mobile_data: file '.$lib_file.' does not exist', LOG_ERR);
  }
} else {
  dol_syslog('pickup_mobile_data: invalid key or action', LOG_ERR);
}


dol_syslog('pickup_mobile_data: Unknown entry point ('.$key.'->'.$action.')', LOG_ERR);
http_response_code(400);
exit(0);
