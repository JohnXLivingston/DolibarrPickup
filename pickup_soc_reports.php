<?php
/* Copyright (C) 2021		Jonathan Dollé		<license@jonathandolle.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('societe/class/societe.class.php');
dol_include_once('/pickup/class/pickup.class.php');
dol_include_once('/product/class/product.class.php');
dol_include_once('/product/stock/class/mouvementstock.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php'; // for measuringUnitString

// Load translation files required by the page
$langs->loadLangs(array("stocks", "pickup@pickup"));

// Securite acces client
if (! $user->rights->pickup->reports) accessforbidden();
$socid=GETPOST('socid', 'int');
if (isset($user->societe_id) && $user->societe_id > 0)
{
	$socid = $user->societe_id;
}

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label('product');

$status_filter = GETPOST('status', 'int');
$soc_filter = GETPOST('soc', 'int');

$date_startmonth = GETPOST('date_startmonth', 'int');
$date_startday = GETPOST('date_startday', 'int');
$date_startyear = GETPOST('date_startyear', 'int');
$date_endmonth = GETPOST('date_endmonth', 'int');
$date_endday = GETPOST('date_endday', 'int');
$date_endyear = GETPOST('date_endyear', 'int');

$nbofyear = 1;

// Date range
$year = GETPOST('year', 'int');
if (empty($year))
{
	$year_current = strftime("%Y", dol_now());
	$month_current = strftime("%m", dol_now());
	$year_start = $year_current - ($nbofyear - 1);
} else {
	$year_current = $year;
	$month_current = strftime("%m", dol_now());
	$year_start = $year - ($nbofyear - 1);
}
$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

// We define date_start and date_end
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q = GETPOST("q") ?GETPOST("q") : 0;
	if ($q == 0)
	{
		// We define date_start and date_end
		$year_end = $year_start + ($nbofyear - 1);
		$month_start = GETPOST("month") ?GETPOST("month") : 1; // january by default
		if (!GETPOST('month'))
		{
			if (!GETPOST("year") && $month_start > $month_current)
			{
				$year_start--;
				$year_end--;
			}
			$month_end = $month_start - 1;
			if ($month_end < 1) $month_end = 12;
			else $year_end++;
		}
		else $month_end = $month_start;
		$date_start = dol_get_first_day($year_start, $month_start, false);
    $date_end = dol_get_last_day($year_end, $month_end, false);
	}
	if ($q == 1) { $date_start = dol_get_first_day($year_start, 1, false); $date_end = dol_get_last_day($year_start, 3, false); }
	if ($q == 2) { $date_start = dol_get_first_day($year_start, 4, false); $date_end = dol_get_last_day($year_start, 6, false); }
	if ($q == 3) { $date_start = dol_get_first_day($year_start, 7, false); $date_end = dol_get_last_day($year_start, 9, false); }
	if ($q == 4) { $date_start = dol_get_first_day($year_start, 10, false); $date_end = dol_get_last_day($year_start, 12, false); }
}

// $date_start and $date_end are defined. We force $year_start and $nbofyear
$tmps = dol_getdate($date_start);
$year_start = $tmps['year'];
$tmpe = dol_getdate($date_end);
$year_end = $tmpe['year'];
$nbofyear = ($year_end - $year_start) + 1;
//var_dump("year_start=".$year_start." year_end=".$year_end." nbofyear=".$nbofyear." date_start=".dol_print_date($date_start, 'dayhour')." date_end=".dol_print_date($date_end, 'dayhour'));



$form = new Form($db);

llxHeader("", $langs->trans("ModulePickupName"));

function get_soc_list() {
  global $db;
  $sql = 'SELECT distinct(fk_soc) as soc FROM '.MAIN_DB_PREFIX.'pickup_pickup';

  $result = array();

  $resql = $db->query($sql);
  if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num) {
      $row = $db->fetch_object($resql);

      $soc = new Societe($db);
      $soc->fetch($row->soc);
      $result[strval($row->soc)] = $soc->name;
      $i++;
    }
    $db->free($resql);
  } else {
    dol_print_error($db);
  }

  asort($result);
  return $result;
}

$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);
$periodlink = $periodlink = ($year_start ? "<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear - 2)."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start + $nbofyear)."'>".img_next()."</a>" : "");
$builddate = dol_now();
/**
 *	Show header of a pickup report.
 *  Code inspired by core/lib/report.lib.php
 *
 *	@param	string				$reportname     Name of report
 *	@param 	string				$period         Period of report
 *	@param 	string				$periodlink     Link to switch period
 *	@param 	integer	      $builddate      Date generation
 *	@return	void
 */
function pickup_report_header($reportname, $period, $periodlink, $builddate, $soc_filter, $status_filter)
{
	global $langs, $db;

	print "\n\n<!-- start banner of report -->\n";

	$head = array();

	$h = 0;
	$head[$h][0] = $_SERVER["PHP_SELF"].$varlink;
	$head[$h][1] = $langs->trans("Report");
	$head[$h][2] = 'report';

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">'."\n";

	dol_fiche_head($head, 'report');

	print '<table class="border tableforfield centpercent">'."\n";

	// Title line
	print '<tr>';
	print '<td width="110">'.$langs->trans("ReportName").'</td>';
	print '<td>';
	print $reportname;
	print '</td>';
	if ($periodlink) print '<td></td>';
	print '</tr>'."\n";

	// Period line
	print '<tr>';
	print '<td>'.$langs->trans("ReportPeriod").'</td>';
	print '<td>';
	if ($period) print $period;
	if ($periodlink) print '<td class="nowraponall">'.$periodlink.'</td>';
	print '</td>';
	print '</tr>'."\n";

  // Thirdparty
  print '<tr>';
  print '<td>'.$langs->trans('ThirdParty').'</td>';
  print '<td>';
  print '<select name="soc"><option value=""></option>';
  $soc_list = get_soc_list();
  foreach ($soc_list as $id => $label) {
    print '<option value="'.$id.'"'.($soc_filter == $id ? ' selected="selected"' : '').'>';
    print $label;
    print '</option>';
  }
  print '</select>';
  print '</td>';

  // Status
  print '<tr>';
  print '<td>'.$langs->trans('Status').'</td>';
  print '<td>';
  $tmp_pickup = new Pickup($db);
  $status_options = array();
  foreach (array($tmp_pickup::STATUS_DRAFT, $tmp_pickup::STATUS_PROCESSING, $tmp_pickup::STATUS_STOCK, $tmp_pickup::STATUS_SIGNED, $tmp_pickup::STATUS_DISABLED) as $status) {
    $status_options[$status] = $tmp_pickup->LibStatut($status);
  }
  print '<select name="status">';
  print '<option value=""></option>';
  foreach ($status_options as $status => $label) {
    print '<option value="'.$status.'"'.($status_filter !== '' && intval($status_filter) === $status ? ' selected="selected"' : '').'>'.$label.'</option>';
  }
  print '</select>';
  print '</td>';
  if ($periodlink) print '<td></td>';
  print '</tr>'."\n";

	// Export line
	print '<tr>';
	print '<td>'.$langs->trans("GeneratedOn").'</td>';
	print '<td>';
	print dol_print_date($builddate, 'dayhour');
	print '</td>';
	if ($periodlink) print '<td></td>';
	print '</tr>'."\n";

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="submit" value="'.$langs->trans("Refresh").'"></div>';

	print '</form>';
	print '<br>';

	print "\n<!-- end banner of report -->\n\n";
}
pickup_report_header($langs->trans("PickupMenuSocReports"), $period, $periodlink, $builddate, $soc_filter, $status_filter);


/*
 * Retrieve data
 */

function retrieve_data() {
  // TODO: add indexes in DB?
  global $db;
  global $date_start, $date_end, $soc_filter, $status_filter;

  if (empty($soc_filter)) {
    return array();
  }

  $sql = 'SELECT p.rowid';
  $sql.= ' FROM '.MAIN_DB_PREFIX.'pickup_pickup as p';
  $sql.= ' WHERE';
  $sql.= " p.date_pickup >= '".$db->escape(dol_print_date($date_start, "%Y-%m-%d"))."'";
  $sql.= " AND p.date_pickup <= '".$db->escape(dol_print_date($date_end, "%Y-%m-%d"))."'";
  $sql.= " AND p.fk_soc = '".$db->escape($soc_filter)."'";
  if ($status_filter !== '') {
    $sql.= " AND p.status = '".$db->escape($status_filter)."'";
  }
  $sql.= " ORDER BY p.ref";

  $data = array();
  $resql = $db->query($sql);
  if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num) {
      $row = $db->fetch_object($resql);
      $pickup_id = $row->rowid;
      $pickup = new Pickup($db);
      $pickup->fetch($pickup_id);
      $pickup->getLinesArray();
      foreach ($pickup->lines as $pickupline) {
        array_push($data, array(
          'pickup' => $pickup,
          'pickupline' => $pickupline
        ));
      }

      $i++;
    }
    $db->free($resql);
  } else {
    dol_print_error($db);
  }

  return $data;
}
$data = retrieve_data();



/*
 * Show result array
 */

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">'."\n";

print '<tr class="liste_titre">';
print '<td align="" class="liste_titre">' . $langs->trans('Ref') . '</td>';
print '<td align="" class="liste_titre">' . $langs->trans('Date') . '</td>';
print '<td align="" class="liste_titre">' . $langs->trans('Status') . '</td>';
print '<td align="" class="liste_titre">' . $langs->trans('Description') . '</td>';
print '<td align="" class="liste_titre">' . $langs->trans('Qty') . '</td>';
print '<td align="" class="liste_titre right">' . $langs->trans('ProductWeight') . '</td>';
print '<td align="" class="liste_titre right">' . $langs->trans('Weight') . '</td>';
if (!empty($conf->global->PICKUP_USE_DEEE)) {
  print '<td align="" class="liste_titre">' . $langs->trans('DEEE') . '</td>';
}
print '<td align="" class="liste_titre">' . $langs->trans('StockMovement') . '</td>';
print '</tr>';

foreach ($data as $line) {
  $pickup = $line['pickup'];
  $pickupline = $line['pickupline'];

  $stock_movement = 0;
  if ($pickupline->fk_stock_movement) {
    
    $stock_movement = new MouvementStock($db);
    $stock_movement->fetch($pickupline->fk_stock_movement);
  }

  $line_product = 0;
  if ($pickupline->fk_product > 0) {
    $line_product = new Product($db);
    $line_product->fetch($pickupline->fk_product);
  }
  
  $cats = $pickupline->getProductCategoriesLabels();

  print '<tr class="oddeven">';
  print '<td class="nowrap">'.$pickup->getNomUrl(1).'</td>';
  print '<td class="nowrap">'.$pickup->showOutputField($pickup->fields['date_pickup'], 'date_pickup', $pickup->date_pickup).'</td>';
  print '<td>'.$pickup->showOutputField($pickup->fields['status'], 'status', $pickup->status).'</td>';
  print '<td class="nowrap">';
    if (count($cats) > 0) {
      print join(', ', $cats);
      print ('<br>');
    }
    if (!empty($line_product)) {
      print $form->textwithtooltip($line_product->getNomUrl(1), '');
    }
  print '</td>';
  print '<td class="nowrap right">';
    print price($pickupline->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
  print '</td>';
  print '<td class="nowrap right">';
  if (!empty($pickupline->weight)) {
    print $pickupline->weight . ' ' . measuringUnitString(0, "weight", $pickupline->weight_units);
  }
  print '</td>';
  print '<td class="nowrap right">';
  if (!empty($pickupline->weight)) {
    print ($pickupline->qty * $pickupline->weight) . ' ' . measuringUnitString(0, "weight", $pickupline->weight_units);
  }
  print '</td>';
  if (!empty($conf->global->PICKUP_USE_DEEE)) {
    print '<td class="nowrap">';
    if ($pickupline->deee) {
      // this field is defined as en extrafield on the product table.
      print $extrafields->showOutputField('pickup_deee_type', $pickupline->deee_type, '', 'product');
    } else {
      print '-';
    }
    print '</td>';
  }
  print '<td>';
  if (! empty($stock_movement)) { print $stock_movement->getNomUrl(1); }
  print '</td>';
  print '</tr>';
}

// TODO: total line?

print "</table>";
print '</div>';

// End of page
llxFooter();
$db->close();
