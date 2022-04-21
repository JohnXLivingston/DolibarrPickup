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
 * $this (ActionsPickup)
 * $object (pickup)
 * $conf
 * $langs
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
    print "Error: this template page cannot be called directly as an URL";
    exit;
}


global $forcetoshowtitlelines; // TODO: necessary?

// Define colspan for the button 'Add'
$colspan = 3; // Col fix + edit + delete

$line = new PickupLine($this->db);

print "<!-- BEGIN PHP TEMPLATE pickup/pickupline_create.tpl.php -->\n";

$nolinesbefore = (count($object->lines) == 0 || $forcetoshowtitlelines);
if ($nolinesbefore) {
    // TODO: print title line, or always display the pickupline_view template (even when no lines).
}

$coldisplay = 0;
?>

<tr class="pair nodrag nodrop nohoverpair <?php print (($nolinesbefore) ? '' : ' liste_titre_create'); ?>">
	<td class="bordertop nobottom linecoldescription minwidth500imp">
		<?php $coldisplay++; ?>
		<span class="prod_entry_mode_predef">
		<?php
			print $line->showInputField(null, 'fk_product', GETPOSTISSET("fk_product") ? GETPOST('fk_product', 'int') : '');
		?>
		</span>
	</td>
	<td class="bordertop nobottom right">
		<?php $coldisplay++;
			print $line->showInputField(null, 'qty', GETPOSTISSET("qty") ? GETPOST('qty', 'int') : 1);
		?>
	</td>
	<?php if ($conf->global->PICKUP_UNITS_EDIT_MODE === 'pickupline') { ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) { ?>
			<td class="nowrap right" colspan="2">
				<?php $coldisplay++; ?><?php $coldisplay++; ?>
				<?php
					print $line->showInputField(null, 'weight', GETPOSTISSET("weight") ? price2num(GETPOST('weight')) : '');

					require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
					$formproduct = new FormProduct($db);
					print $formproduct->selectMeasuringUnits('weight_units', 'weight', GETPOSTISSET('weight_units') ? GETPOST('weight_units', 'int') : 0, 0, 2);
				?>
			</td>
		<?php } ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_LENGTH)) { ?>
			<td class="nowrap right" colspan="2">
				<?php $coldisplay++; ?><?php $coldisplay++; ?>
				<?php
					print $line->showInputField(null, 'length', GETPOSTISSET("length") ? price2num(GETPOST('length')) : '');

					require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
					$formproduct = new FormProduct($db);
					print $formproduct->selectMeasuringUnits('length_units', 'size', GETPOSTISSET('length_units') ? GETPOST('length_units', 'int') : 0, 0, 2);
				?>
			</td>
		<?php } ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_SURFACE)) { ?>
			<td class="nowrap right" colspan="2">
				<?php $coldisplay++; ?><?php $coldisplay++; ?>
				<?php
					print $line->showInputField(null, 'surface', GETPOSTISSET("surface") ? price2num(GETPOST('surface')) : '');

					require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
					$formproduct = new FormProduct($db);
					print $formproduct->selectMeasuringUnits('surface_units', 'surface', GETPOSTISSET('surface_units') ? GETPOST('surface_units', 'int') : 0, 0, 2);
				?>
			</td>
		<?php } ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_VOLUME)) { ?>
			<td class="nowrap right" colspan="2">
				<?php $coldisplay++; ?><?php $coldisplay++; ?>
				<?php
					print $line->showInputField(null, 'volume', GETPOSTISSET("volume") ? price2num(GETPOST('volume')) : '');

					require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
					$formproduct = new FormProduct($db);
					print $formproduct->selectMeasuringUnits('volume_units', 'volume', GETPOSTISSET('volume_units') ? GETPOST('volume_units', 'int') : -3, 0, 2);
				?>
			</td>
		<?php } ?>
	<?php } else { ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) { ?>
			<td class="bordertop nobottom">
				<?php $coldisplay++; ?>
			</td>
			<td class="bordertop nobottom">
				<?php $coldisplay++; ?>
			</td>
		<?php } ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_LENGTH)) { ?>
			<td class="bordertop nobottom">
				<?php $coldisplay++; ?>
			</td>
			<td class="bordertop nobottom">
				<?php $coldisplay++; ?>
			</td>
		<?php } ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_SURFACE)) { ?>
			<td class="bordertop nobottom">
				<?php $coldisplay++; ?>
			</td>
			<td class="bordertop nobottom">
				<?php $coldisplay++; ?>
			</td>
		<?php } ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_VOLUME)) { ?>
			<td class="bordertop nobottom">
				<?php $coldisplay++; ?>
			</td>
			<td class="bordertop nobottom">
				<?php $coldisplay++; ?>
			</td>
		<?php } ?>
	<?php } ?>
	<td class="bordertop nobottom">
		<?php $coldisplay++; ?>
	</td>
	<td class="bordertop nobottom">
		<?php $coldisplay++; ?>
	</td>

	<!-- colspan for this td because it replace total_ht+3 td for buttons+... -->
	<td class="bordertop nobottom linecoledit center valignmiddle" colspan="<?php echo $colspan; ?>">
		<?php $coldisplay += $colspan; ?>
		<input type="submit" class="button" value="<?php echo $langs->trans("Add"); ?>" name="addline" id="addline">
	</td>
</tr>

<?php
if (!empty($extrafields)) {
	print $line->showOptionals($extrafields, 'edit', array('class'=>'tredited', 'colspan'=>$coldisplay), '', '', 1);
}
?>

<!-- END PHP TEMPLATE pickup/pickupline_create.tpl.php -->
