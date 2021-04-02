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
 * $object (pickup)
 * $line (pickupline)
 * $conf
 * $langs
 * $line_product (product)
 * 
 * $stock_movement if the line is already in stock
 * $extrafields, $extralabels
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}


// Define colspan for the button 'Add'
$colspan = 2; // Col col edit + col delete

print "<!-- BEGIN PHP TEMPLATE pickup/pickupline_edit.tpl.php -->\n";

$coldisplay = 0;
?>
<tr class="oddeven tredited">
	<td>
		<?php $coldisplay++; ?>
		<div id="line_<?php echo $line->id; ?>"></div>

		<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">

		<?php if ($line->fk_product > 0) {
      print $form->textwithtooltip($line_product->getNomUrl(1), '', 3, '', '', $i, 0, '');
			print '<br>';
		}	?>

		<?php
			// if (is_object($hookmanager)) // TODO: necessary ?
			// {
			// 	$fk_parent_line = (GETPOST('fk_parent_line') ? GETPOST('fk_parent_line') : $line->fk_parent_line);
			// 	// FIXME: there is no $dateSelector in this file. Nor $seller or $buyer.
			// 	$parameters = array('line'=>$line, 'fk_parent_line'=>$fk_parent_line, 'var'=>$var, 'dateSelector'=>$dateSelector, 'seller'=>$seller, 'buyer'=>$buyer);
			// 	$reshook = $hookmanager->executeHooks('formEditProductOptions', $parameters, $object, $action);
			// }

			print $line->showInputField(null, 'description', GETPOSTISSET("description") ? GETPOST('description', 'none') : $line->description);
		?>
	</td>
	<td class="right">
		<?php $coldisplay++;
			print $line->showInputField(null, 'qty', GETPOSTISSET("qty") ? GETPOST('qty', 'int') : $line->qty);
		?>
	</td>
	<td class="nowrap right">
    <?php $coldisplay++; ?>
    <?php if (!empty($line_product) && !empty($line_product->weight)) {
      print $line_product->weight . ' ' . measuringUnitString(0, "weight", $line_product->weight_units);
    } ?>
  </td>
  <td class="nowrap right">
    <?php $coldisplay++; ?>
    <?php if (!empty($line_product) && !empty($line_product->weight)) {
      print ($line_product->weight * $line->qty) . ' ' . measuringUnitString(0, "weight", $line_product->weight_units);
    } ?>
  </td>
  <td class="nowrap">
    <?php $coldisplay++; ?>
    <?php
      if (!empty($line_product) && $line_product->array_options['options_deee']) {
        print $extrafields->showOutputField('type_deee', $line_product->array_options['options_type_deee'], '', $line_product->table_element);
      } else {
        print '-';
      }
    ?>
  </td>
  <td class="nowrap">
    <?php $coldisplay++; ?>
    <?php if (! empty($stock_movement)) { print $stock_movement->getNomUrl(1); } ?>
  </td>

	<!-- colspan for this td because it replace total_ht+3 td for buttons+... -->
	<td class="center valignmiddle" colspan="<?php echo $colspan; ?>">
		<?php $coldisplay += $colspan; ?>
		<input type="submit" class="button buttongen marginbottomonly" id="savelinebutton marginbottomonly" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
		<input type="submit" class="button buttongen marginbottomonly" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
	</td>
</tr>

<?php
if (!empty($extrafields)) {
	print $line->showOptionals($extrafields, 'edit', array('class'=>'tredited', 'colspan'=>$coldisplay), '', '', 1);
}
?>

<!-- END PHP TEMPLATE pickup/pickupline_edit.tpl.php -->