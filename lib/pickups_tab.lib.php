<?php
/* Copyright (C) 2023		John Livingston		<license@john-livingston.fr>
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
 */

/**
 * \file    pickup/lib/pickups_tab.lib.php
 * \ingroup pickup
 * \brief   Library files with common functions for Pickup
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('/pickup/class/pickup.class.php');
dol_include_once('/pickup/class/pickupline.class.php');

$langs->loadLangs(array("pickup@pickup", "products", "other", "stocks"));

class PickupsTabContent {
  protected $db;
  protected $columns;

  public function __construct(DoliDB $db, Conf $conf) {
    $this->db = $db;

    $this->columns = array(
      array('type' => 'pickup', 'col' => 'ref'),
      array('type' => 'pickup', 'col' => 'label'),
      array('type' => 'pickup', 'col' => 'fk_soc'),
      array('type' => 'pickup', 'col' => 'date_pickup'),
      array('type' => 'pickup', 'col' => 'fk_pickup_type', 'hide' => empty($conf->global->PICKUP_USE_PICKUP_TYPE)),
      array(
        'type' => 'pickupline',
        'hide' => empty($conf->productbatch->enabled),
        'label' => 'BatchNumberShort',
        'func' => function ($pickuplinestatic) {
          $pbatches = $pickuplinestatic->fetchAssociatedBatch();
          $out = '';
          foreach ($pbatches as $pbatch) {
            $productlot = $pbatch->getProductLot();
            if (!empty($productlot)) {
              $out.= $productlot->getNomUrl();
            } else {
              $out.= htmlspecialchars($pbatch->batch_number);
            }
            $out.= '<br>';
          }
          return $out;
        }
      ),
      array('type' => 'pickupline', 'col' => 'fk_stock_movement'),
      array('type' => 'pickupline', 'col' => 'qty'),
      array(
        'type' => 'pickupline',
        'label' => 'Weight',
        'css' => 'right nowrap',
        'func' => function ($pickuplinestatic) {
          return ($pickuplinestatic->weight * $pickuplinestatic->qty) . ' ' . measuringUnitString(0, "weight", $pickuplinestatic->weight_units);
        },
        'hide' => empty($conf->global->PICKUP_UNITS_WEIGHT)
      ),
      array(
        'type' => 'pickupline',
        'label' => 'Length',
        'css' => 'right nowrap',
        'func' => function ($pickuplinestatic) {
          return ($pickuplinestatic->length * $pickuplinestatic->qty) . ' ' . measuringUnitString(0, "size", $pickuplinestatic->length_units);
        },
        'hide' => empty($conf->global->PICKUP_UNITS_LENGTH)
      ),
      array(
        'type' => 'pickupline',
        'label' => 'Surface',
        'css' => 'right nowrap',
        'func' => function ($pickuplinestatic) {
          return ($pickuplinestatic->surface * $pickuplinestatic->qty) . ' ' . measuringUnitString(0, "surface", $pickuplinestatic->surface_units);
        },
        'hide' => empty($conf->global->PICKUP_UNITS_SURFACE)
      ),
      array(
        'type' => 'pickupline',
        'label' => 'Volume',
        'css' => 'right nowrap',
        'func' => function ($pickuplinestatic) {
          return ($pickuplinestatic->volume * $pickuplinestatic->qty) . ' ' . measuringUnitString(0, "volume", $pickuplinestatic->volume_units);
        },
        'hide' => empty($conf->global->PICKUP_UNITS_VOLUME)
      ),
      array('type' => 'pickup', 'col' => 'status'),
    );
  }

  public function printContent($sql_where, $sql_join = '') {
    $db = $this->db;

    $pickup = new Pickup($db);
    $pickupline = new PickupLine($db);

    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    foreach ($this->columns as $def) {
      if ($def['hide']) { continue; }
      if (empty($def['col'])) {
        $cssforfield = $def['css'];
        print getTitleFieldOfList($def['label'], 0, '', '', '', '', ($cssforfield?'class="'.$cssforfield.'"':''), '', '', ($cssforfield?$cssforfield.' ':''), 1)."\n";
        continue;
      }
      $this->_printTitle($def['type'] === 'pickupline' ? $pickupline : $pickup, $def['col']);
    }
    print '</tr>';

    $sql = 'SELECT p.rowid as p_rowid, pl.rowid as pl_rowid, ';
    foreach($pickup->fields as $key => $val) {
      $sql.='p.'.$key.' as p_'.$key.', ';
    }
    foreach($pickupline->fields as $key => $val) {
      $sql.='pl.'.$key.' as pl_'.$key.', ';
    }
    $sql =preg_replace('/,\s*$/', '', $sql);
    $sql.= ' FROM '.MAIN_DB_PREFIX.'pickup_pickup as p ';
    $sql.= ' JOIN '.MAIN_DB_PREFIX.'pickup_pickupline as pl ON pl.fk_pickup = p.rowid ';
    $sql.= $sql_join;
    $sql.= ' WHERE ';
    $sql.= '('.$sql_where.')';
    $sql.= $db->order('date_pickup,pl.position,pl.rowid', 'DESC,DESC,DESC');
    $result = $db->query($sql);

    if ($result > 0) {
      while ($data = $db->fetch_object($result)) {
        $pickupstatic = new Pickup($db);
        $pickupstatic->id = $data->p_rowid;
        foreach($pickupstatic->fields as $key => $val) {
          $sql_key = 'p_'.$key;
          if (property_exists($data, $sql_key)) $pickupstatic->$key = $data->$sql_key;
        }
        $pickuplinestatic = new PickupLine($db);
        $pickuplinestatic->id = $data->pl_rowid;
        foreach($pickuplinestatic->fields as $key => $val) {
          $sql_key = 'pl_'.$key;
          if (property_exists($data, $sql_key)) $pickuplinestatic->$key = $data->$sql_key;
        }

        print '<tr class="oddeven">';
        foreach ($this->columns as $def) {
          if ($def['hide']) { continue; }
          $line_obj = $def['type'] === 'pickupline' ? $pickuplinestatic : $pickupstatic;
          if (empty($def['col'])) {
            $cssforfield = $def['css'];
            $content = $def['func']($line_obj);
            print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
            print $content;
            print '</td>';
            continue;
          }
          $this->_printLine($line_obj, $def['col']);
        }
        print '</tr>';
      }
    } else {
      dol_print_error($db);
    }
    $db->free($result);

    print "</table>";

    print '</div>';
    print '</div>';
  }

  protected function _printTitle($object, $key) {
    $val = $object->fields[$key];
    $cssforfield=(empty($val['css'])?'':$val['css']);
    if ($key == 'status') $cssforfield.=($cssforfield?' ':'').'center';
    elseif (in_array($val['type'], array('date','datetime','timestamp'))) $cssforfield.=($cssforfield?' ':'').'center';
    elseif (in_array($val['type'], array('timestamp'))) $cssforfield.=($cssforfield?' ':'').'nowrap';
    elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price'))) $cssforfield.=($cssforfield?' ':'').'right';
    print getTitleFieldOfList($val['label'], 0, '', '', '', '', ($cssforfield?'class="'.$cssforfield.'"':''), '', '', ($cssforfield?$cssforfield.' ':''), 1)."\n";
  }

  protected function _printLine($object, $key) {
    $db = $this->db;

    $val = $object->fields[$key];
    $cssforfield=(empty($val['css'])?'':$val['css']);
    if (in_array($val['type'], array('date','datetime','timestamp'))) $cssforfield.=($cssforfield?' ':'').'center';
    elseif ($key == 'status') $cssforfield.=($cssforfield?' ':'').'center';

    if (in_array($val['type'], array('timestamp'))) $cssforfield.=($cssforfield?' ':'').'nowrap';
    elseif ($key == 'ref') $cssforfield.=($cssforfield?' ':'').'nowrap';

    if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $key != 'status') $cssforfield.=($cssforfield?' ':'').'right';

    print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
    if ($key == 'status') print $object->getLibStatut(5);
    elseif (in_array($val['type'], array('date','datetime','timestamp'))) print $object->showOutputField($val, $key, $db->jdate($object->$key), '');
    else print $object->showOutputField($val, $key, $object->$key, '');
    print '</td>';
    // if (! $i) $totalarray['nbfield']++;
    // if (! empty($val['isameasure'])) {
    //   if (! $i) $totalarray['pos'][$totalarray['nbfield']]='t.'.$key;
    //   $totalarray['val']['t.'.$key] += $object->$key;
    // }
    }
}
