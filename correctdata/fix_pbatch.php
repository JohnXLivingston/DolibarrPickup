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

/**
 *	\brief      Undocumented script to fix some data.
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

dol_include_once('/product/class/product.class.php');
dol_include_once('/product/stock/class/productlot.class.php');
dol_include_once('/pickup/class/pbatch.class.php');

$do_action = GETPOST('do_action', 'int') == '1';


// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service', 0, 'product&product');
if (empty($conf->pickup->enabled)) {
  accessforbidden('Module not enabled');
}
if ($user->societe_id > 0) {	// Protection if external user
  accessforbidden();
}
if (!$user->rights->pickup->correctdata) {
  accessforbidden();
}

$product_obj = new Product($db);

// On cherche tous les produits avec status_batch/tobatch == 1
$sql = ' SELECT p.rowid as product_id ';
// On cherche les produits avec status_batch/tobatch == 1
$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p ';
$sql.= ' WHERE p.tobatch = 1 ';
$sql.= ' ORDER BY p.rowid ';

// $sql = ' SELECT p.rowid as product_id, pb.batch_number ';
// // On cherche les produits avec status_batch/tobatch == 1
// $sql.= ' FROM '.MAIN_DB_PREFIX.'product as p ';
// // Pour lesquels on n'a pas de ligne pickup_batch.fk_pickupline is null
// $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'pickup_batch as pb ';
// $sql.= ' ON pb.fk_pickupline is null AND pb.fk_product = p.rowid ';
// // Et pour lesquels on a pourtant un numéro de lot qui existe !
// $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_lot as pl ';
// $sql.= ' ON pl.fk_product = p.rowid AND pl.batch is not null and pl.batch not in ("00000", "000000", "0000000", "00000000")';
// $sql.= '';
// $sql.= ' WHERE p.tobatch = 1 ';
// $sql.= ' ORDER BY p.rowid ';

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

function get_theorique_pbatch($id_product) {
  global $db;
  $pbatch = new PBatch($db);
  $result = $pbatch->fetchAll('ASC', 'rowid', 0, 0, array(
    'customsql' => 'fk_pickupline IS NULL AND fk_product = \''.$db->escape($id_product).'\''
  ), 'AND');
  if (is_array($result)) {
    $pbatch = array_shift($result);
    if ($pbatch) {
      return $pbatch->batch_number;
    }
  }
  return null;
}

function get_product_lot_batch($id_product) {
  global $db;
  // Maintenant on cherche tous les numéros de lots dans le stock.
  $sql = ' SELECT batch ';
  // $sql.= ' FROM '.MAIN_DB_PREFIX.'product_stock as ps ';
  $sql.= ' FROM '.MAIN_DB_PREFIX.'product_lot as pl ';
  // $sql.= ' ON ps.'
  $sql.= ' WHERE pl.fk_product = ' . $id_product . ' ';
  $resql2 = $db->query($sql);
  if (!$resql2) {
    dol_print_error($db);
    exit;
  }

  $batches = [];
  $j = 0;
  $num2 = $db->num_rows($resql2);
  while ($j < $num2) {
    $j++;
    $obj2 = $db->fetch_object($resql2);
    // On va ignorer tous les numéros de la forme 0000.
    // Ils ne sont pas normaux, mais ce script ne vise pas à corriger ça.
    if (preg_match('/^0+$/', $obj2->batch)) { continue; }
    if (empty($obj2->batch)) { continue; }
    $batches[] = $obj2->batch;
  }
  return $batches;
}

function get_pickupline_batch ($id_product) {
  global $db;
  // Maintenant on cherche tous les numéros de lots dans le stock.
  $sql = ' SELECT batch_number ';
  $sql.= ' FROM '.MAIN_DB_PREFIX.'pickup_pickupline as pl ';
  $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'pickup_batch as pb';
  $sql.= ' ON pb.fk_pickupline = pl.rowid ';
  $sql.= ' WHERE pl.fk_product = ' . $id_product . ' ';
  $sql.= ' AND pb.rowid is not null ';
  $resql2 = $db->query($sql);
  if (!$resql2) {
    dol_print_error($db);
    exit;
  }

  $batches = [];
  $j = 0;
  $num2 = $db->num_rows($resql2);
  while ($j < $num2) {
    $j++;
    $obj2 = $db->fetch_object($resql2);
    $batches[] = $obj2->batch_number;
  }
  return $batches;
}

function count_product_with_batch($batch) {
  global $db;
  // Maintenant on cherche tous les numéros de lots dans le stock.
  $sql = ' SELECT count(*) as count ';
  $sql.= ' FROM '.MAIN_DB_PREFIX.'product_lot as pl ';
  $sql.= ' WHERE pl.batch = "' . $db->escape($batch) . '" ';
  $resql2 = $db->query($sql);
  if (!$resql2) {
    dol_print_error($db);
    exit;
  }
  $obj = $db->fetch_object($resql2);
  return $obj->count;
}

?>
<style>
  td {
    border: 1px solid black;
  }
</style>
<?php
print '<table>';
?><tr>
  <th>Product id</th>
  <th>Est kit?</th>
  <th>Numéro(s) de lot existant (stock/fiches lot)</th>
  <th>Numéro(s) de lot sur collectes (pas forcément déjà dans le stock)</th>
  <th>Numéro de lot attribué par le module</th>
  <th>Nb de fiches produits avec ce numéro de lot</th>
  <th>Action suggérée</th>
</tr><?php

$i = 0;
$num = $db->num_rows($resql);
while ($i < $num) {
  $i++;
	$obj = $db->fetch_object($resql);

  $pbatch = get_theorique_pbatch($obj->product_id);
  $batches = get_product_lot_batch($obj->product_id);
  $pl_batches = get_pickupline_batch($obj->product_id);

  $all_batches = [];
  foreach ($batches as $b) {
    $all_batches[$b] = $b;
  }
  foreach ($pl_batches as $b) {
    $all_batches[$b] = $b;
  }

  if (
    (
      (
        count($batches) === 1
        && $batches[0] === $pbatch
      )
      || (
        count($batches) === 0
      )
    )
    && (
      (
        count($pl_batches) === 1
        && $pl_batches[0] === $pbatch
      )
      || count($pl_batches) === 0
    )
  ) {
    // tout va bien
    continue;
  }

  // On peut aussi ignorer les produits pour lequels on n'a ni ligne de collecte, ni product_lot.
  // (même si on a un pbtach attribué)
  if (count($batches) === 0 && count($pl_batches) === 0) {
    continue;
  }

  // Est ce que je suis un kit ??
  $est_kit = false;
  $childs = $product_obj->getChildsArbo($obj->product_id);
  if (count($childs)) { $est_kit = true; }

  $action = '';
  if (empty($pbatch)) {
    // Je n'ai pas le pbatch qui va bien...

    if (count($all_batches) === 1) {
      // Si j'ai exactement 1 numéro de batch qui apparait dans product_lot/pickupline...

      // Par sécurité, je vérifie que je n'ai pas d'ambiguité sur le produit...
      $wanted_batch = array_shift(array_values($all_batches));
      if (count_product_with_batch($wanted_batch)) {

        // Je vais ignorer les kits.
        if (!$est_kit) {
          // tout va bien
          $action = 'Initialiser une ligne pickup_batch pour '.$wanted_batch;

          if ($do_action) {
            $pbatch = new PBatch($db);
            $pbatch->batch_number = $wanted_batch;
            $pbatch->fk_product = $obj->product_id;
            $pbatch->fk_pickupline = null;
            $pbatch->create($user);
          }
        }
      }

    }
  }
  // if (empty($action)) { continue; }

  ?>
    <tr>
      <td>
        <a href="../../../product/card.php?id=<?php print $obj->product_id; ?>">
          <?php print $obj->product_id; ?>
        </a>
      </td>
      <td><?php if ($est_kit) { print "OUI"; } ?></td>
      <td><?php print htmlspecialchars(join(', ', $batches)); ?></td>
      <td><?php print htmlspecialchars(join(', ', $pl_batches)); ?></td>
      <td><?php print htmlspecialchars($pbatch); ?></td>
      <td>
        <?php
        foreach ($all_batches as $b) {
          print htmlspecialchars($b).': '.count_product_with_batch($b).'<br>';
        }
        ?>
      </td>
      <td><?php print htmlspecialchars($action); ?></td>
    </tr>
  <?php
}
print '</table>';
