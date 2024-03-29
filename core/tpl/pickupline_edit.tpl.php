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
 * $line (pickupline)
 * $conf
 * $langs
 * $form
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
$colspan = 3; // Col fix + edit + delete

print "<!-- BEGIN PHP TEMPLATE pickup/pickupline_edit.tpl.php -->\n";

$coldisplay = 0;
?>
<tr class="oddeven tredited">
	<td>
		<?php $coldisplay++; ?>
		<div id="line_<?php echo $line->id; ?>"></div>

		<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">

		<?php if ($line->fk_product > 0) {
      // print $form->textwithtooltip($line_product->getNomUrl(1), '', 3, '', '', $i, 0, '');
			print $form->textwithtooltip($line_product->getNomUrl(1), '');
			print '<br>';
			if (!empty($conf->global->PICKUP_USE_PBRAND)) {
				if (!empty($line_product->array_options['options_pickup_pbrand'])) {
					print htmlspecialchars($line_product->array_options['options_pickup_pbrand']);
					print ' - ';
				}
			}
			if (!empty($line_product->label)) {
				print htmlspecialchars($line_product->label);
			}
		}	?>

		<?php
			// if (is_object($hookmanager)) // TODO: necessary ?
			// {
			// 	$fk_parent_line = (GETPOST('fk_parent_line') ? GETPOST('fk_parent_line') : $line->fk_parent_line);
			// 	// FIXME: there is no $dateSelector in this file. Nor $seller or $buyer.
			// 	$parameters = array('line'=>$line, 'fk_parent_line'=>$fk_parent_line, 'var'=>$var, 'dateSelector'=>$dateSelector, 'seller'=>$seller, 'buyer'=>$buyer);
			// 	$reshook = $hookmanager->executeHooks('formEditProductOptions', $parameters, $object, $action);
			// }
		?>
		<?php
			$pbatches = $line->fetchAssociatedBatch();
			if ($line_product->hasbatch() || !empty($pbatches)) {
				print $langs->trans('Batch') . ': ';
				print $line->showPBatchInputField($line_product, $pbatches, 'batch');
			}
		?>
	</td>
	<td class="right">
		<?php $coldisplay++;
			print $line->showInputField(null, 'qty', GETPOSTISSET("qty") ? GETPOST('qty', 'int') : $line->qty);
		?>
	</td>

	<?php if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) { ?>
		<td class="nowrap right" colspan="2">
			<?php $coldisplay++; ?><?php $coldisplay++; ?>
			<?php
				print $line->showInputField(null, 'weight', GETPOSTISSET("weight") ? price2num(GETPOST('weight')) : $line->weight);

				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				print $formproduct->selectMeasuringUnits('weight_units', 'weight', GETPOSTISSET('weight_units') ? GETPOST('weight_units', 'int') : $line->weight_units, 0, 2);
			?>
		</td>
	<?php } ?>
	<?php if (!empty($conf->global->PICKUP_UNITS_LENGTH)) { ?>
		<td class="nowrap right" colspan="2">
			<?php $coldisplay++; ?><?php $coldisplay++; ?>
			<?php
				print $line->showInputField(null, 'length', GETPOSTISSET("length") ? price2num(GETPOST('length')) : $line->length);

				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				print $formproduct->selectMeasuringUnits('length_units', 'size', GETPOSTISSET('length_units') ? GETPOST('length_units', 'int') : $line->length_units, 0, 2);
			?>
		</td>
	<?php } ?>
	<?php if (!empty($conf->global->PICKUP_UNITS_SURFACE)) { ?>
		<td class="nowrap right" colspan="2">
			<?php $coldisplay++; ?><?php $coldisplay++; ?>
			<?php
				print $line->showInputField(null, 'surface', GETPOSTISSET("surface") ? price2num(GETPOST('surface')) : $line->surface);

				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				print $formproduct->selectMeasuringUnits('surface_units', 'surface', GETPOSTISSET('surface_units') ? GETPOST('surface_units', 'int') : $line->surface_units, 0, 2);
			?>
		</td>
	<?php } ?>
	<?php if (!empty($conf->global->PICKUP_UNITS_VOLUME)) { ?>
		<td class="nowrap right" colspan="2">
			<?php $coldisplay++; ?><?php $coldisplay++; ?>
			<?php
				print $line->showInputField(null, 'volume', GETPOSTISSET("volume") ? price2num(GETPOST('volume')) : $line->volume);

				require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
				$formproduct = new FormProduct($db);
				print $formproduct->selectMeasuringUnits('volume_units', 'volume', GETPOSTISSET('volume_units') ? GETPOST('volume_units', 'int') : $line->volume_units, 0, 2); // -3 = L
			?>
		</td>
	<?php } ?>

	<?php if (!empty($conf->global->PICKUP_USE_DEEE)) { ?>
		<td class="nowrap" colspan="2">
			<?php $coldisplay++; ?><?php $coldisplay++; ?>
			<?php
				// this field is defined as an extrafield on the product table.
				// print $extrafields->showInputField('pickup_deee', GETPOSTISSET('deee') ? GETPOST('deee', 'int') : $line->deee, '', '', '', 0, $line_product->table_element);
				// this field is defined as an extrafield on the product table.
				print $extrafields->showInputField('pickup_deee_type', GETPOSTISSET('options_pickup_deee_type') ? GETPOST('options_pickup_deee_type', 'alpha') : $line->deee_type, '', '', '', 0, $line_product->table_element);
			?>
		</td>
	<?php } ?>

	<!-- colspan for this td because it replace total_ht+3 td for buttons+... -->
	<td class="center valignmiddle" colspan="<?php echo $colspan; ?>">
		<?php $coldisplay += $colspan; ?>
		<input type="submit" class="button buttongen marginbottomonly" id="savelinebutton marginbottomonly" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
		<input type="submit" class="button buttongen marginbottomonly" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
	</td>
</tr>
<?php if (!empty($conf->global->PICKUP_USE_PICKUPLINE_DESCRIPTION)) { ?>
	<tr class="oddeven tredited">
		<td colspan="<?php echo $coldisplay ?>">
			<?php print $line->showInputField(null, 'description', GETPOSTISSET("description") ? GETPOST('description', 'none') : $line->description); ?>
		</td>
	</tr>
<?php } ?>
<?php
if (!empty($extrafields)) {
	print $line->showOptionals($extrafields, 'edit', array('class'=>'tredited', 'colspan'=>$coldisplay), '', '', 1);
}
?>

<!-- END PHP TEMPLATE pickup/pickupline_edit.tpl.php -->
