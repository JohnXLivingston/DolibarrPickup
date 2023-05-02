<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2021-2022		John Livingston		<license@john-livingston.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       pickup/pickupindex.php
 *	\ingroup    pickup
 *	\brief      Home page of pickup top menu
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

// Load translation files required by the page
$langs->loadLangs(array('pickup@pickup', 'products', 'categories', 'stocks'));

dol_include_once('/core/lib/files.lib.php');
dol_include_once('/pickup/lib/export.lib.php');
dol_include_once('/pickup/lib/import.lib.php');


// Security check
if ($user->socid) {
	$socid = $user->socid;
}
if (empty($conf->pickup->enabled)) {
  accessforbidden('Module not enabled');
}
if ($user->societe_id > 0) {	// Protection if external user
  accessforbidden();
}
if (!$user->rights->pickup->importexport) {
  accessforbidden();
}

$socid=GETPOST('socid', 'int');
if (isset($user->societe_id) && $user->societe_id > 0)
{
	$socid = $user->societe_id;
}

$conf_importexport_what = [
  'cat' => [
    'label' => $langs->transnoentities('ProductsCategoryShort'),
    'default_checked' => true
  ]
];
if (!empty($conf->global->PICKUP_IMPORTEXPORT_ALL)) {
  $conf_importexport_what = array_merge($conf_importexport_what,
    [
      'pickup_conf' => [
        'label' => $langs->transnoentities('PickupSetup')
      ],
      'entrepot' => [
        'label' => $langs->transnoentities('Warehouses')
      ],
      'product' => [
        'label' => $langs->transnoentities('Products')
      ]
    ]
  );
}

$action = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : '';
$importexport_what = [];
$param_importexport_what = GETPOST('importexport_what', 'array');
foreach ($conf_importexport_what as $what => $cw) {
  if (in_array($what, $param_importexport_what)) {
    $importexport_what[$what] = true;
  }
}
unset($param_importexport_what);

if ($action === 'do_import' && empty($_SESSION['pickup_import_data'])) {
  $action = '';
}

/*
 * Actions
 */
if ($action === 'export') {
  print_pickup_export($importexport_what);
  exit;
}

$import_result = null;
if ($action === 'import') {
  if (!empty($_FILES) && !empty($_FILES['userfile']) && !empty($_FILES['userfile']['tmp_name'])) {
    $json = file_get_contents($_FILES['userfile']['tmp_name']);
    dol_delete_file($_FILES['userfile']['tmp_name']);

    $_SESSION['pickup_import_data'] = [
      'json' => $json,
      'what' => $importexport_what
    ];

    $import_result = pickup_import($json, true, $importexport_what);
  } else {
    $action = '';
  }
} else if ($action === 'do_import') {
  $import_result = pickup_import(
    $_SESSION['pickup_import_data']['json'],
    false,
    $_SESSION['pickup_import_data']['what']
  );
  
  unset($_SESSION['pickup_import_data']);

  $_SESSION['pickup_import_result'] = $import_result;
  header('Location: '.$_SERVER["PHP_SELF"]);
  exit;
} else {
  unset($_SESSION['pickup_import_data']);
}

if ($action == '' && empty($import_result) && !empty($_SESSION['pickup_import_result'])) {
  $import_result = $_SESSION['pickup_import_result'];
  unset($_SESSION['pickup_import_result']);
}

/*
 * View
 */

llxHeader("", $langs->trans("PickupImportExportTitle"));

print load_fiche_titre($langs->trans("PickupImportExportTitle"), '', 'pickup.png@pickup');

print '<div class="info">' . $langs->trans('PickupImportExportHelp') . '</div>';

if (!empty($import_result)) {
  print '<table class="valid centpercent">';
  print ' <tr class="valid">';
  print '   <th class="valid">OBJECT TYPE</th>';
  print '   <th class="valid">OBJECT</th>';
  print '   <th class="valid">ACTION</th>';
  print '   <th class="valid">MESSAGE</th>';
  print ' </tr>';

  foreach ($import_result['actions'] as $ac) {
    print ' <tr class="valid">';
    foreach (['object_type', 'object', 'action', 'message'] as $field) {
      print '   <td class="valid">';
      if (!empty($ac[$field])) { print htmlspecialchars($ac[$field]); }
      print '   </td>';
    }
    print ' </tr>';
  }

  print ' <tr class="valid">';
  print '   <td class="valid" colspan="4">';
  if ($import_result['status'] === 'ok') {
    print "OK";
  } else {
    print "ERROR: ".($import_result['error'] ?? ' Unknown error');
  }
  print '   </td>';
  print ' </tr>';

  if ($action === 'import') {
    print '<tr class="valid">';
    print '<td class="valid center" colspan="4">';

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
    print '<input type="hidden" name="action" value="">'."\n";

    print '<a class="butActionDelete" ';
    print ' onclick="$(this).closest(\'form\').find(\'[name=action]\').val(\'\'); $(this).closest(\'form\').submit();" ';
    print '>'.$langs->trans('Cancel').'</a>';
    // Note: type="button" to prevent this button to be trigger on enter in a field from the form under it.
    print '<input type="button" class="button" value="'.$langs->trans("Validate").'" ';
    print ' onclick="$(this).closest(\'form\').find(\'input[name=action]\').val(\'do_import\'); $(this).closest(\'form\').submit();" ';
    print '>';

    print '</form>';
    print '</td>';
    print '</tr>';
  }

  print '</table>';
}

if ($action !== 'import' && $action !== 'doimport') {
  print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
  print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
  print '<input type="hidden" name="action" value="export">'."\n";
  
  print ' <table class="border tableforfield centpercent">'."\n";
  print ' <tr>';
  print ' <td width="110">'.$langs->trans('PickupExport').'</td>';
  print ' <td>';
  foreach ($conf_importexport_what as $what => $wc) {
    print '  <label>';
    print '    <input type="checkbox" '.($wc['default_checked'] ? 'checked="checked"' : '').' name="importexport_what[]" value="'.htmlspecialchars($what).'">';
    print '    ' . htmlspecialchars($wc['label']);
    print '  </label>';
    print '  </br>';
  }
  print ' </td>';
  print ' </tr>'."\n";
  print ' </table/>';
  print ' <div class="center"><input type="submit" class="button" name="submit" value="'.$langs->trans("PickupExport").'"></div>';
  
  print '</form>';
  
  
  print '<form method="POST" enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'">'."\n";
  print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
  print '<input type="hidden" name="action" value="import">'."\n";
  
  print ' <table class="border tableforfield centpercent">'."\n";
  print ' <tr>';
  print ' <td width="110">'.$langs->trans('PickupImport').'</td>';
  print ' <td>';
  foreach ($conf_importexport_what as $what => $wc) {
    print '  <label>';
    print '    <input type="checkbox" '.($wc['default_checked'] ? 'checked="checked"' : '').' name="importexport_what[]" value="'.htmlspecialchars($what).'">';
    print '    ' . htmlspecialchars($wc['label']);
    print '  </label>';
    print '  </br>';
  }
  print ' </td>';
  print ' </tr>'."\n";
  print ' <tr>';
  print ' <td></td>';
  print ' <td>';
  print '   <input type="file" name="userfile" accept="application/json">';
  print ' </td>';
  print ' </tr>'."\n";
  print ' </table/>';
  print ' <div class="center"><input type="submit" class="button" name="submit" value="'.$langs->trans("PickupImport").'"></div>';
  
  print '</form>';
}

// End of page
llxFooter();
$db->close();
