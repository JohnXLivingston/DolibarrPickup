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
  public $data = null;
  public $label = null; 
  private $categorie = null;
  private $mobilecat = null;
  public $children = [];

  function __construct($parent, $label) {
    $this->parent = $parent;
    $this->label = $label;
  }

  public function ensureChild($label) {
    if (!array_key_exists($label, $this->children)) {
      $child = new ImportCatTreeNode($this, $label);
      $this->children[$label] = $child;
      return $child;
    }
    return $this->children[$label];
  }

  public function setData(&$data) {
    $this->data = $data;
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
    if (!$categorie) {
      if ($this->parent) { // dont create for the tree root
        $this->createCategorie($result, $simulate);
      }
    } else {
      if (!empty($this->data)) {
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
    // if ($categorie->fetch($obj['rowid'], '' , 0) <= 0) {
    if ($categorie->fetch($obj['rowid']) <= 0) {
      return null;
    }
    $this->categorie = $categorie;

    $mobilecat = new PickupMobileCat($db);
	  if ($mobilecat->fetchByCategory($categorie->id) < 0) {
      $this->mobilecat->fk_category = $categorie->id;
      $this->mobilecat->active = 0;
    }
    $this->mobilecat = $mobilecat;

    return $categorie;
  }

  private function createCategorie(&$result, $simulate) {
    global $user, $db, $langs;

    $categorie = new Categorie($db);
    $categorie->fk_parent = $this->getParentCatId() ?? 0;
    $categorie->type = 0; // product
    $categorie->label = $this->label;
    $this->categorie = $categorie;

    $mobilecat = new PickupMobileCat($db);
    $this->mobilecat = $mobilecat;

    if (!empty($this->data)) {
      $modified_fields = $this->applyData($categorie, $mobilecat);
    } else {
      $modified_fields = [];
    }

    $actions = [
      'object_type' =>  $langs->transnoentities('ProductsCategoryShort'),
      'object' => implode(' >> ', $this->getPath()),
      'action' => 'CREATE',
      'message' => implode(', ', array_keys($modified_fields))
    ];

    $result['actions'][] = $actions;
    if ($simulate) {
      return;
    }

    if ($categorie->create($user) <= 0) {
      throw new Error('Failed to create categorie.');
    }

    $actions['message'].= ' Category Saved.';

    if (!empty($data) && property_exists($this->data, 'active') && $this->data->active) {
      $mobilecat->fk_category = $categorie->id;
      $mobilecat->create($user);
      $actions['message'].= ' MobileCat Created.';
    }
  }

  private function updateCategorie(&$result, $simulate) {
    global $user, $db, $langs;

    $categorie = $this->categorie;
    $mobilecat = $this->mobilecat;
    $modified_fields = $this->applyData($categorie, $mobilecat);

    $actions = [
      'object_type' => $langs->transnoentities('ProductsCategoryShort'),
      'object' => implode(' >> ', $this->getPath()),
      'action' => 'UPDATE',
      'message' => count($modified_fields) > 0 ? implode(', ', array_keys($modified_fields)) : '-'
    ];
    $result['actions'][] = $actions;
    if ($simulate) {
      return;
    }

    if (count($modified_fields) <= 0) {
      return;
    }

    $categorie->update($user);
    $actions['message'].= ' Category Updated.';
    if ($mobilecat->id) {
      $mobilecat->update($user);
      $actions['message'].= ' MobileCat updated.';
    } else {
      if ($mobilecat->active) {
        $mobilecat->create($user);
        $actions['message'].= ' MobileCat created.';
      }
    }
  }

  private function applyData($categorie, $mobilecat) {
    global $db;

    $modified_fields = [];
    if (!$this->data) { return $modified_fields; }

    foreach (['color', 'description'] as $field) {
      if (!property_exists($this->data, $field)) { continue; }
      if ($categorie->$field != $this->data->$field) {
        $categorie->$field = $this->data->$field;
        $modified_fields[$field] = ($categorie->$field ?? '') . ' => ' . ($this->data->$field ?? '');
      }
    }

    if (property_exists($this->data, 'active')) {
      if ($this->data->active) {
        if (!$mobilecat->active) {
          $mobilecat->active = 1;
          $modified_fields['active'] = '1';
        }
        foreach (['batch_constraint', 'deee_constraint'] as $field) {
          if (!property_exists($this->data, $field)) { continue; }
          if ($mobilecat->$field != $this->data->$field) {
            $mobilecat->$field = $this->data->$field;
            $modified_fields[$field] = ($mobilecat->$field ?? '') . ' => ' . ($this->data->$field ?? '');
          }
        }
      } else {
        if ($mobilecat->active) {
          $mobilecat->active = 0;
          $modified_fields['active'] = '0';
        }
      }
    }
    return $modified_fields;
  }
}