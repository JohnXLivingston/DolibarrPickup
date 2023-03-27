<?php
/* Copyright (C) 2023		John Livingston		<license@john-livingston.fr>
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

dol_include_once('/pickup/lib/printable_label.lib.php');

$langs->loadLangs(array("pickup@pickup", 'products', 'stocks', 'productbatch', "other"));

$action = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'scan';
$search = GETPOST('search', 'alphanohtml') ?? '';


// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service', 0, 'product&product');



/*
 * View
 */

$helpurl='';
$title = $langs->trans('PickupPrintableLabelSearchTitle');
llxHeader("", $title, $helpurl);

if ($action === 'scan') {
  print_scan_form(false, $search);
} else if ($action === 'list') {
  print_scan_form(true, $search);
  print_list($search);
}

function print_scan_form($with_search, $search) {
  global $langs;

  print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="scan">'."\n";
  // if ($optioncss != '') {
  //   print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
  // }
  print '<input type="hidden" name="token" value="'.newToken().'">';
  print '<input type="hidden" name="action" value="list">';

  $text = $langs->trans('Product').' / '.$langs->trans('LotSerial');
  print_barre_liste($text, 0, $_SERVER["PHP_SELF"], '', '', '', '', null, '', 'product', 0, '', '', null, 0, 0, 1);

  if (!$with_search) {
    print '<div class="info">'.$langs->trans('PickupPrintableLabelSearchHelp').'</div>';
  }

  $style = $with_search ? 'width:80%; max-height: 50x' : 'width:80%; min-height: 360px';
  print '<textarea name="search" style="'.$style.'">'.$search.'</textarea>';

  print '<br><input type="submit" class="button" value="'.$langs->trans("Search").'">';

  print '</form>';
}

function print_list($search) {
  global $langs;
  $values = preg_split('/\r\n|\r|\n/', $search);
  $result = search_printable_label($values);

  print '<div class="div-table-responsive">';
  print '<table class="tagtable nobottomiftotal liste">';
  print_liste_field_titre("Ref");
  print_liste_field_titre("Label");
  print_liste_field_titre("Batch");
  print '<tr class="liste_titre">';
  print '</tr>';

  foreach ($result as $line) {
    $product = $line['product'];
    $productlot = $line['productlot'];
    print '<tr>';

    // Ref
    print '<td class="nowrap">';
    print $product->getNomUrl(1, '', 16);
    print '</td>';

    // Label
    print '<td>'.htmlspecialchars($product->label).'</td>';

    // Batch
    print '<td>';
    if ($productlot) {
      print $productlot->getNomUrl(1);
    }
    print '</td>';

    print '</tr>';
  }
  print '</table>';
}

// End of page
llxFooter();
$db->close();
