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
 * \file    pickup/lib/correctdata_missing_batch_number.lib.php
 * \ingroup pickup
 * \brief   Library files with common functions
 */

// Following include will fail if we are not in Dolibarr context
dol_include_once('/categories/class/categorie.class.php');
dol_include_once('/pickup/class/mobilecat.class.php');
dol_include_once('/product/class/product.class.php');
dol_include_once('/pickup/lib/import/cat.tree.class.php');

function pickup_import(&$json, $simulate, $what) {
  $result = [
    'status' => 'ko',
    'actions' => [],
    'error' => null
  ];
  try {
    $data = json_decode($json);
    if ($data->version !== '1') {
      throw new Error('Data version incompatible');
    }

    if (!empty($what['cat'])) {
      _pickup_import_cats($result, $data, $simulate);
    }

    $result['status'] = 'ok';
  } catch (Throwable $e) {
    $result['error'] = $e->getMessage();
  }
  return $result;
}

function _pickup_import_cats(&$result, &$data, $simulate) {
  $lines = empty($data->categories) ? [] : $data->categories;

  // Building a tree, so we can create categories from top to bottom.
  $tree = new ImportCatTreeNode(null, '');
  foreach ($lines as $line) {
    $path = $line->path;
    if (count($path) == 0) { continue; }
    $node = $tree;
    foreach ($path as $label) {
      $node = $node->ensureChild($label);
    }
    // The last node has to get the data!
    $node->setData($line);
  }

  // Now we must walk through the tree
  $tree->doActions($result, $simulate);
}
