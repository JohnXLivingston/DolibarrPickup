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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $element     (used to test $user->rights->$element->write)
 * $permtoedit  (used to replace test $user->rights->$element->write)
 * $object_rights->write initialized from = $object->getRights()
 * $disableedit, $disablemove (not used for now because of bugs in dolibarr), $disableremove
 *
 * $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}

global $mysoc;

$usemargins = 0;

// add html5 elements
$domData  = ' data-element="'.$line->element.'"';
$domData .= ' data-id="'.$line->id.'"';
$domData .= ' data-qty="'.$line->qty.'"';


$coldisplay = 0; ?>
<!-- BEGIN PHP TEMPLATE collecte/objectline_view.tpl.php -->
<tr id="row-<?php print $line->id?>" class="drag drop oddeven" <?php print $domData; ?> >
	<td class="linecoldescription minwidth300imp"><?php $coldisplay++; ?>
    <div id="line_<?php print $line->id; ?>"></div>
    <?php
      if ($line->fk_product > 0)
      {
        print $form->textwithtooltip($text, $description, 3, '', '', $i, 0, (!empty($line->fk_parent_line) ?img_picto('', 'rightarrow') : ''));
      }

      // Add description in form
      if ($line->fk_product > 0 && !empty($conf->global->PRODUIT_DESC_IN_FORM))
      {
        print (!empty($line->description) && $line->description != $line->product_label) ? '<br>'.dol_htmlentitiesbr($line->description) : '';
      }
    ?>
  </td>
	<td class="nowrap right">
    <?php $coldisplay++; ?>
    <?php
	    print price($line->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
    ?>
  </td>
  <td class="nowrap right">
    <?php $coldisplay++; ?>
    <?php print $line->weight . ' ' . measuringUnitString(0, "weight", $line->weight_units); ?>
  </td>

  <?php if ($this->statut == 0 && ($object_rights->write) && $action != 'selectlines') { ?>
    <td class="linecoledit center">
      <?php $coldisplay++; ?>
      <?php if (empty($disableedit)) { ?>
        <a class="editfielda reposition" href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=editline&amp;lineid='.$line->id.'#line_'.$line->id; ?>">
          <?php print img_edit(); ?>
        </a>
      <?php } ?>
    </td>
    <td class="linecoldelete center">
      <?php $coldisplay++; ?>
      <?php if (empty($disableremove)) { ?>
        <a class="reposition"
          href="<?php print $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=ask_deleteline&amp;lineid='.$line->id; ?>"
        >
          <?php print img_delete(); ?>
        </a>
      <?php } ?>
    </td>
  <?php } else { ?>
    <?php $coldisplay = $coldisplay + 2; ?>
    <td colspan="2"></td>
  <?php } ?>

  <?php if ($action == 'selectlines') { ?>
    <td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php print $i + 1; ?>]" value="<?php print $line->id; ?>" ></td>
  <?php } ?>
</tr>

<?php
//Line extrafield
if (!empty($extrafields))
{
	print $line->showOptionals($extrafields, 'view', array('style'=>'class="drag drop oddeven"', 'colspan'=>$coldisplay), '', '', 1);
}
?>
<!-- END PHP TEMPLATE collecte/objectline_view.tpl.php -->
