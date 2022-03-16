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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * Need to have following variables defined:
 * $object (pickup)
 * $conf
 * $form
 * $action
 * $disableedit, $disablemove (not used for now because of bugs in dolibarr), $disableremove
 *
 * $line (pickupline)
 * $line_product (product)
 * $stock_movement if the line is already in stock
 * $extrafields, $extralabels
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php'; // for measuringUnitString

global $mysoc;

$usemargins = 0;

// add html5 elements
$domData  = ' data-element="'.$line->element.'"';
$domData .= ' data-id="'.$line->id.'"';
$domData .= ' data-qty="'.$line->qty.'"';

$product_warnings = 0;

$coldisplay = 0; ?>
<!-- BEGIN PHP TEMPLATE pickup/pickupline_view.tpl.php -->
<tr id="row-<?php print $line->id?>" class="drag drop oddeven" <?php print $domData; ?> >
	<td class="linecoldescription minwidth300imp"><?php $coldisplay++; ?>
    <div id="line_<?php print $line->id; ?>"></div>
    <?php
      if ($line->fk_product > 0) {
        $cats = $line->getProductCategoriesLabels();
        if (count($cats) > 0) {
          print join(', ', $cats);
          print ('<br>');
        }
        // print $form->textwithtooltip($line_product->getNomUrl(1), '', 3, '', '', $i, 0, '');
        print $form->textwithtooltip($line_product->getNomUrl(1), '');

        if ($line_product->hasbatch() || !empty($line->batch)) {
          print '<br>';
          $batch_warning = ($object->status == $object::STATUS_PROCESSING && empty($line->batch));
          $hide_batch_number = false;

          print '<div style="white-space: nowrap; '.($batch_warning ? 'color: orange;' : '').'">';

          print $langs->trans('Batch') . ': ';

          if (($object->status == $object::STATUS_PROCESSING || $object->status == $object::STATUS_DRAFT) && $object->canEditPickup()) {
            if ($action === 'editlinebatch' && GETPOST('lineid', 'int') == $line->id) {
              $hide_batch_number = true;
              // Note: the <form> is printed by pickup_card.php
              print '<input type="hidden" name="lineid" value="'.$line->id.'">';
              print $line->showInputField(null, 'batch', GETPOSTISSET("batch") ? GETPOST('batch') : $line->batch);
              print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
            } else {
              print ' <a class="editfielda" href="';
              print $_SERVER["PHP_SELF"];
              print '?id='.$object->id.'&amp;action=editlinebatch&amp;lineid='.$line->id.'#line_'.$line->id;
              print '"">'.img_edit($langs->transnoentitiesnoconv('Batch'), 0).'</a> ';
            }
          }

          if (!$hide_batch_number) {
            print htmlspecialchars($line->batch ?? '');
          }

          if ($batch_warning) {
            print img_warning($langs->trans('PickupFixLineBatch'));
          }

          print '</div>';
        }
      }

      // print '<br>';
      // print dol_htmlentitiesbr($line->description);
    ?>
  </td>
	<td class="nowrap right">
    <?php $coldisplay++; ?>
    <?php
	    print price($line->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
    ?>
  </td>
  <td class="nowrap right"
    <?php if (floatval($line->weight) != floatval($line_product->weight) || intval($line->weight_units) != intval($line_product->weight_units)) {
      $product_warnings = 1;
      ?>
      style="color: orange;"
      title="<?php print htmlentities($langs->trans('Product') . ': ' . $line_product->weight . ' ' . measuringUnitString(0, "weight", $line_product->weight_units)); ?>"
    <?php } ?>
  >
    <?php $coldisplay++; ?>
    <?php if (!empty($line->weight)) {
      print $line->weight . ' ' . measuringUnitString(0, "weight", $line->weight_units);
    } ?>
  </td>
  <td class="nowrap right">
    <?php $coldisplay++; ?>
    <?php if (!empty($line->weight)) {
      print ($line->weight * $line->qty) . ' ' . measuringUnitString(0, "weight", $line->weight_units);
    } ?>
  </td>
  <?php if (!empty($conf->global->PICKUP_USE_DEEE)) { ?>
    <td class="nowrap"
      <?php if (intval($line->deee) != intval($line_product->array_options['options_pickup_deee']) || strval($line->deee_type) != strval($line_product->array_options['options_pickup_deee_type'])) {
        $product_warnings = 1;
        ?>
        style="color: orange;"
        title="<?php
          print htmlentities($langs->trans('Product') . ': ');
          if ($line_product->array_options['options_pickup_deee']) {
            print htmlentities($extrafields->showOutputField('pickup_deee_type', $line_product->array_options['options_pickup_deee_type'], '', $line_product->table_element));
          } else {
            print '-';
          }
        ?>"
      <?php } ?>
    >
      <?php $coldisplay++; ?>
      <?php
        if ($line->deee) {
          // this field is defined as en extrafield on the product table.
          print $extrafields->showOutputField('pickup_deee_type', $line->deee_type, '', $line_product->table_element);
        } else {
          print '-';
        }
      ?>
    </td>
  <?php } ?>
  <td class="nowrap">
    <?php $coldisplay++; ?>
    <?php if (! empty($stock_movement)) { print $stock_movement->getNomUrl(1); } ?>
  </td>

  <?php if ($object->status == $object::STATUS_DRAFT && $object->canEditPickup() && $action != 'selectlines') { ?>
    <td class="linecoledit center">
      <?php $coldisplay++; ?>
      <?php if (empty($disableedit) && $product_warnings) { ?>
        <a class="" href="<?php print $_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=fixline&amp;lineid='.$line->id.'#line_'.$line->id; ?>">
          <?php print img_warning($langs->trans('PickupFixLine')); ?>
        </a>
      <?php } ?>
    </td>
    <td class="linecoledit center">
      <?php $coldisplay++; ?>
      <?php if (empty($disableedit)) { ?>
        <a class="editfielda reposition" href="<?php print $_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=editline&amp;lineid='.$line->id.'#line_'.$line->id; ?>">
          <?php print img_edit(); ?>
        </a>
      <?php } ?>
    </td>
    <td class="linecoldelete center">
      <?php $coldisplay++; ?>
      <?php if (empty($disableremove)) { ?>
        <a class="reposition"
          href="<?php print $_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=deleteline&amp;lineid='.$line->id; ?>"
        >
          <?php print img_delete(); ?>
        </a>
      <?php } ?>
    </td>
  <?php } else { ?>
    <?php $coldisplay = $coldisplay + 3; ?>
    <td colspan="3"></td>
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
<!-- END PHP TEMPLATE pickup/pickupline_view.tpl.php -->
