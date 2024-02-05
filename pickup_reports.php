<?php
/* Copyright (C) 2021-2022		John Livingston		<license@john-livingston.fr>
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('societe/class/societe.class.php');
dol_include_once('/pickup/class/pickup.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php'; // for measuringUnitString

// Load translation files required by the page
$langs->loadLangs(array("pickup@pickup"));

// Securite acces client
if (! $user->rights->pickup->reports) accessforbidden();
$socid=GETPOST('socid', 'int');
if (isset($user->societe_id) && $user->societe_id > 0)
{
	$socid = $user->societe_id;
}

$status_filter = GETPOST('status', 'int');
$pickup_type_filter = GETPOST('pickup_type', 'int');

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

// TODO: filter on soc?
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
function pickup_report_header($reportname, $period, $periodlink, $builddate, $status_filter, $pickup_type_filter)
{
	global $langs, $db, $conf;

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

  // Pickup type
  if (!empty($conf->global->PICKUP_USE_PICKUP_TYPE)) {
    print '<tr>';
    print '<td>'.$langs->trans('PickupType').'</td>';
    print '<td>';
    $pickup_type_options = Pickup::getPickupTypeOptions();
    print '<select name="pickup_type">';
    print '<option value=""></option>';
    foreach ($pickup_type_options as $pickup_type_option) {
      print '<option ';
      print ' value="'.htmlspecialchars($pickup_type_option['value']).'"';
      if ($pickup_type_filter !== '' && intval($pickup_type_filter) === intval($pickup_type_option['value'])) {
        print ' selected="selected" ';
       }
       print '>'.htmlspecialchars($pickup_type_option['label']).'</option>';
    }
    print '</select>';
    print '</td>';
    if ($periodlink) print '<td></td>';
    print '</tr>'."\n";
  }

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
pickup_report_header($langs->trans("PickupMenuReports"), $period, $periodlink, $builddate, $status_filter, $pickup_type_filter);


/*
 * Retrieve data
 */

function deee_types() {
  global $db, $conf;
  if (empty($conf->global->PICKUP_USE_DEEE)) {
    return array();
  }
  // This is an extrafield...
  require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
  $extrafields = new ExtraFields($db);
  $extrafields->fetch_name_optionals_label('product');

  $options = $extrafields->attributes['product']['param']['pickup_deee_type']['options'];
  $result = array();
  foreach ($options as $key => $label) {
    $result[strval($key)] = $label;
  }
  return $result;
}
$deee_types = deee_types();

function init_values_array($unit = 0) {
  $unit = intval($unit);
  return array($unit => 0.0);
}
function sort_pickup_report_lines($a, $b) {
  return strcmp($a['soc']->name, $b['soc']->name);
}
function sum_line($row, &$data_for_soc, $default_unit, $row_col, $row_unit_col, $total_col) {
  $unit = empty($row->$row_unit_col) ? $default_unit : intval($row->$row_unit_col);
  $value = empty($row->$row_col) ? 0.0 : $row->$row_col;
  if (!array_key_exists($unit, $data_for_soc[$total_col])) {
    $data_for_soc[$total_col][$unit] = 0.0;
  }
  $data_for_soc[$total_col][$unit] += $value;
}
function retrieve_data() {
  // TODO: add indexes in DB?
  global $db, $conf;
  global $date_start, $date_end, $deee_types, $status_filter, $pickup_type_filter;

  // NB: We can get deee and deee_type even if !PICKUP_USE_DEEE. Fields will just be empty or ignored later.
  $sql = 'SELECT p.fk_soc, ';
  $sql.= ' pl.deee, pl.deee_type, ';
  $sql.= ' pl.qty, ';
  $sql.= ' pl.weight_units, sum(pl.weight * pl.qty) as line_weight, ';
  $sql.= ' pl.length_units, sum(pl.length * pl.qty) as line_length, ';
  $sql.= ' pl.surface_units, sum(pl.surface * pl.qty) as line_surface, ';
  $sql.= ' pl.volume_units, sum(pl.volume * pl.qty) as line_volume ';
  $sql.= ' FROM '.MAIN_DB_PREFIX.'pickup_pickup as p';
  $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'pickup_pickupline as pl ON pl.fk_pickup = p.rowid';
  $sql.= ' WHERE';
  $sql.= " p.date_pickup >= '".$db->escape(dol_print_date($date_start, "%Y-%m-%d"))."'";
  $sql.= " AND p.date_pickup <= '".$db->escape(dol_print_date($date_end, "%Y-%m-%d"))."'";
  if ($status_filter !== '') {
    $sql.= " AND p.status = '".$db->escape($status_filter)."'";
  }
  if (!empty($conf->global->PICKUP_USE_PICKUP_TYPE) && !empty($pickup_type_filter)) {
    $sql.= " AND p.fk_pickup_type = '".$db->escape($pickup_type_filter)."'";
  }
  $sql.= ' GROUP BY p.fk_soc, pl.deee, pl.deee_type, pl.weight_units, pl.length_units, pl.surface_units, pl.volume_units';

  $data = array();
  $data_total = array(
    'qty' => 0,
    'per_deee_type' => array(),
    'deee_total' => init_values_array($conf->global->PICKUP_WEIGHT_UNIT),
    'weight_total' => init_values_array($conf->global->PICKUP_WEIGHT_UNIT),
    'length_total' => init_values_array($conf->global->PICKUP_SIZE_UNIT),
    'surface_total' => init_values_array($conf->global->PICKUP_SURFACE_UNIT),
    'volume_total' => init_values_array($conf->global->PICKUP_VOLUME_UNIT) // -3 = L
  );
  $resql = $db->query($sql);
  if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num) {
      $row = $db->fetch_object($resql);
      $fk_soc = $row->fk_soc;
      if (!array_key_exists($fk_soc, $data)) {
        $soc = new Societe($db);
        $soc->fetch($fk_soc);
        $data[$fk_soc] = array(
          'soc' => $soc,
          'qty' => 0,
          'per_deee_type' => array(),
          'deee_total' => init_values_array($conf->global->PICKUP_WEIGHT_UNIT),
          'weight_total' => init_values_array($conf->global->PICKUP_WEIGHT_UNIT),
          'length_total' => init_values_array($conf->global->PICKUP_SIZE_UNIT),
          'surface_total' => init_values_array($conf->global->PICKUP_SURFACE_UNIT),
          'volume_total' => init_values_array($conf->global->PICKUP_VOLUME_UNIT) // -3 = L
        );
        foreach ($deee_types as $deee_type_key => $label) {
          $data[$fk_soc]['per_deee_type'][$deee_type_key] = init_values_array($conf->global->PICKUP_WEIGHT_UNIT);
          if (!array_key_exists($deee_type_key, $data_total['per_deee_type'])) {
            $data_total['per_deee_type'][$deee_type_key] = init_values_array($conf->global->PICKUP_WEIGHT_UNIT);
          }
        }
      }

      $data[$fk_soc]['qty'] += $row->qty;
      $data_total['qty'] += $row->qty;

      sum_line($row, $data[$fk_soc], 0, 'line_weight', 'weight_units', 'weight_total');
      sum_line($row, $data[$fk_soc], 0, 'line_length', 'length_units', 'length_total');
      sum_line($row, $data[$fk_soc], 0, 'line_surface', 'surface_units', 'surface_total');
      sum_line($row, $data[$fk_soc], -3, 'line_volume', 'volume_units', 'volume_total'); // -3 = L

      sum_line($row, $data_total, 0, 'line_weight', 'weight_units', 'weight_total');
      sum_line($row, $data_total, 0, 'line_length', 'length_units', 'length_total');
      sum_line($row, $data_total, 0, 'line_surface', 'surface_units', 'surface_total');
      sum_line($row, $data_total, -3, 'line_volume', 'volume_units', 'volume_total'); // -3 = L

      $deee_type = $row->deee ? strval($row->deee_type) : '';
      if (!empty($deee_type)) {
        $weight_units = empty($row->weight_units) ? 0 : intval($row->weight_units);
        $weight = empty($row->line_weight) ? 0.0 : $row->line_weight;
        if (!array_key_exists($weight_units, $data[$fk_soc]['deee_total'])) {
          $data[$fk_soc]['deee_total'][$weight_units] = 0.0;
        }
        $data[$fk_soc]['deee_total'][$weight_units] += $weight;

        if (!array_key_exists($deee_type, $data[$fk_soc]['per_deee_type'])) {
          $data[$fk_soc]['per_deee_type'][$deee_type] = init_values_array($conf->global->PICKUP_WEIGHT_UNIT);
        }

        if (!array_key_exists($weight_units, $data[$fk_soc]['per_deee_type'][$deee_type])) {
          $data[$fk_soc]['per_deee_type'][$deee_type][$weight_units] = 0.0;
        }
        $data[$fk_soc]['per_deee_type'][$deee_type][$weight_units] += $weight;


        if (!array_key_exists($weight_units, $data_total['deee_total'])) {
          $data_total['deee_total'][$weight_units] = 0.0;
        }
        $data_total['deee_total'][$weight_units] += $weight;

        if (!array_key_exists($deee_type, $data_total['per_deee_type'])) {
          $data_total['per_deee_type'][$deee_type] = init_values_array($conf->global->PICKUP_WEIGHT_UNIT);
        }

        if (!array_key_exists($weight_units, $data_total['per_deee_type'][$deee_type])) {
          $data_total['per_deee_type'][$deee_type][$weight_units] = 0.0;
        }
        $data_total['per_deee_type'][$deee_type][$weight_units] += $weight;
      }

      $i++;
    }
    $db->free($resql);
  } else {
    dol_print_error($db);
  }

  usort($data, "sort_pickup_report_lines");
  return [$data, $data_total];
}
list ($data, $data_total) = retrieve_data();



/*
 * Show result array
 */

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">'."\n";

print '<tr class="liste_titre"><td class="liste_titre">&nbsp;</td>';
if (!empty($conf->global->PICKUP_USE_DEEE)) {
  foreach ($deee_types as $label) {
    print '<td align="center" class="liste_titre">' . $label . '</td>';
  }
  print '<td align="center" class="liste_titre">' . $langs->trans('DEEETotal') . '</td>';
}
if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) {
  print '<td align="center" class="liste_titre">' . $langs->trans('PickupTotalWeight') . '</td>';
}
if (!empty($conf->global->PICKUP_UNITS_LENGTH)) {
  print '<td align="center" class="liste_titre">' . $langs->trans('PickupTotalLength') . '</td>';
}
if (!empty($conf->global->PICKUP_UNITS_SURFACE)) {
  print '<td align="center" class="liste_titre">' . $langs->trans('PickupTotalSurface') . '</td>';
}
if (!empty($conf->global->PICKUP_UNITS_VOLUME)) {
  print '<td align="center" class="liste_titre">' . $langs->trans('PickupTotalVolume') . '</td>';
}
if (!empty($conf->global->PICKUP_UNITS_PIECE)) {
  print '<td align="center" class="liste_titre">' . $langs->trans('PickupTotalPiece') . '</td>';
}
print '</tr>';

foreach ($data as $line) {
  print '<tr class="oddeven">';
  print '<td>';
    if (!empty($line['soc'])) {
      print $line['soc']->getNomUrl(1);
    }
  print '</td>';
  if (!empty($conf->global->PICKUP_USE_DEEE)) {
    foreach ($deee_types as $deee_type_key => $label) {
      print '<td align="center" class="nowrap">';
        foreach ($line['per_deee_type'][$deee_type_key] as $weights_units => $weights) {
          print ($weights) . ' ' . measuringUnitString(0, "weight", $weights_units) . '<br>';
        }
      print '</td>';
    }
    print '<td align="center" class="nowrap">';
      foreach ($line['deee_total'] as $weights_units => $weights) {
        print ($weights) . ' ' . measuringUnitString(0, "weight", $weights_units) . '<br>';
      }
    print '</td>';
  }

  if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) {
    print '<td align="center" class="nowrap">';
      foreach ($line['weight_total'] as $weights_units => $weights) {
        print ($weights) . ' ' . measuringUnitString(0, "weight", $weights_units) . '<br>';
      }
    print '</td>';
  }
  if (!empty($conf->global->PICKUP_UNITS_LENGTH)) {
    print '<td align="center" class="nowrap">';
      foreach ($line['length_total'] as $lengths_units => $lengths) {
        print ($lengths) . ' ' . measuringUnitString(0, "size", $lengths_units) . '<br>';
      }
    print '</td>';
  }
  if (!empty($conf->global->PICKUP_UNITS_SURFACE)) {
    print '<td align="center" class="nowrap">';
      foreach ($line['surface_total'] as $surfaces_units => $surfaces) {
        print ($surfaces) . ' ' . measuringUnitString(0, "surface", $surfaces_units) . '<br>';
      }
    print '</td>';
  }
  if (!empty($conf->global->PICKUP_UNITS_VOLUME)) {
    print '<td align="center" class="nowrap">';
      foreach ($line['volume_total'] as $volumes_units => $volumes) {
        print ($volumes) . ' ' . measuringUnitString(0, "volume", $volumes_units) . '<br>';
      }
    print '</td>';
  }
  if (!empty($conf->global->PICKUP_UNITS_PIECE)) {
    print '<td align="center" class="nowrap">';
    print price($line['qty'], 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
    print '</td>';
  }

  print '</tr>';
}

// Total line
print '<tr class="liste_total">';
print '<td>';
print '</td>';
if (!empty($conf->global->PICKUP_USE_DEEE)) {
  foreach ($deee_types as $deee_type_key => $label) {
    print '<td align="center" class="nowrap">';
      foreach ($data_total['per_deee_type'][$deee_type_key] as $weights_units => $weights) {
        print ($weights) . ' ' . measuringUnitString(0, "weight", $weights_units) . '<br>';
      }
    print '</td>';
  }
  print '<td align="center" class="nowrap">';
    foreach ($data_total['deee_total'] as $weights_units => $weights) {
      print ($weights) . ' ' . measuringUnitString(0, "weight", $weights_units) . '<br>';
    }
  print '</td>';
}

if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) {
  print '<td align="center" class="nowrap">';
    foreach ($data_total['weight_total'] as $weights_units => $weights) {
      print ($weights) . ' ' . measuringUnitString(0, "weight", $weights_units) . '<br>';
    }
  print '</td>';
}
if (!empty($conf->global->PICKUP_UNITS_LENGTH)) {
  print '<td align="center" class="nowrap">';
    foreach ($data_total['length_total'] as $lengths_units => $lengths) {
      print ($lengths) . ' ' . measuringUnitString(0, "size", $lengths_units) . '<br>';
    }
  print '</td>';
}
if (!empty($conf->global->PICKUP_UNITS_SURFACE)) {
  print '<td align="center" class="nowrap">';
    foreach ($data_total['surface_total'] as $surfaces_units => $surfaces) {
      print ($surfaces) . ' ' . measuringUnitString(0, "surface", $surfaces_units) . '<br>';
    }
  print '</td>';
}
if (!empty($conf->global->PICKUP_UNITS_VOLUME)) {
  print '<td align="center" class="nowrap">';
    foreach ($data_total['volume_total'] as $volumes_units => $volumes) {
      print ($volumes) . ' ' . measuringUnitString(0, "volume", $volumes_units) . '<br>';
    }
  print '</td>';
}
if (!empty($conf->global->PICKUP_UNITS_PIECE)) {
  print '<td align="center" class="nowrap">';
  print price($data_total['qty'], 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
  print '</td>';
}

print '</tr>';

print "</table>";
print '</div>';

// End of page
llxFooter();
$db->close();
