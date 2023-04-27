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

dol_include_once('/categories/class/categorie.class.php');
dol_include_once('/pickup/class/mobilecat.class.php');

class ImportCatTreeNode {
  public $parent;
  public $data;
  public $label = null; 
  private $categorie = null;
  public $children = [];

  function __construct($parent, $label, &$data = null) {
    $this->parent = $parent;
    $this->label = $label;
    $this->data = &$data;
  }

  public function ensureChild($label) {
    if (!array_key_exists($label, $this->children)) {
      $child = new ImportCatTreeNode($this, $label);
      $this->children[$label] = $child;
      return $child;
    }
    return $this->children[$label];
  }

  public function getCatId() {
    if (!$this->parent) {
      // we are the tree root
      return 0;
    }
    if ($this->categorie) {
      return $this->categorie->id;
    }
    return null; // not created yet!
  }

  public function getParentCatId() {
    if ($this->parent) { return $this->parent->getCatId(); }
    return 0;
  }

  public function getPath() {
    if (!$this->parent) {
      return [];
    }
    $a = $this->parent->getPath();
    $a[] = $this->label;
    return $a;
  }

  public function doActions(&$result, $simulate) {
    $categorie = $this->getCategorie($simulate);
    if (!empty($this->data)) {
      if (!$categorie) {
        $this->createCategorie($result, $simulate);
      } else {
        $this->updateCategorie($result, $simulate);
      }
    }
    foreach ($this->children as $child) {
      $child->doActions($result, $simulate);
    }
  }

  private function getCategorie($simulate) {
    global $db;

    $fk_parent = $this->getParentCatId();
    if ($fk_parent === null) {
      if ($simulate) {
        // we are in simulation mode, here the parent is not created, so...
        return null;
      }
      throw new Error('Should have created the parent first');
    }

    $sql = "SELECT c.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."categorie as c ";
		$sql .= " WHERE c.entity IN (".getEntity('category').")";
		$sql .= " AND c.type = 0"; // product
		$sql .= " AND c.fk_parent = ".((int) $fk_parent);
		$sql .= " AND c.label = '".$db->escape($this->label)."'";

    $resql = $db->query($sql);
		if (!$resql || $db->num_rows($resql) <= 0) {
      return null;
    }
    $obj = $db->fetch_array($resql);

    $categorie = new Categorie($db);
    if ($categorie->fetch($obj['rowid'], '' , 0) <= 0) {
      return null;
    }
    $this->categorie = $categorie;
    return $categorie;
  }

  private function createCategorie(&$result, $simulate) {
    global $db;
    $actions = [
      'message' => 'Create ' . implode(' >> ', $this->getPath())
    ];
    $result['actions'][] = $actions;
    if ($simulate) {
      return;
    }
    // TODO
  }

  private function updateCategorie(&$result, $simulate) {
    global $db;
    $actions = [
      'message' => 'Update ' . implode(' >> ', $this->getPath())
    ];
    $result['actions'][] = $actions;
    if ($simulate) {
      return;
    }
    // TODO
  }
}