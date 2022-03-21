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
 *
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}

print "<!-- BEGIN PHP TEMPLATE pickup/pickupline_title.tpl.php -->\n";

?>
<thead>
	<tr class="liste_titre nodrag nodrop">
		<td rowspan="2" class="linecoldescription">
			<?php print $langs->trans('Description'); ?>
		</td>
		<td rowspan="2" class="right">
			<?php print $langs->trans('Qty'); ?>
		</td>

		<?php if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) { ?>
			<td rowspan="1" colspan="2" class="center">
				<?php print $langs->trans('Weight'); ?>
			</td>
		<?php } ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_LENGTH)) { ?>
			<td rowspan="1" colspan="2" class="center">
				<?php print $langs->trans('Length'); ?>
			</td>
		<?php } ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_SURFACE)) { ?>
			<td rowspan="1" colspan="2" class="center">
				<?php print $langs->trans('Surface'); ?>
			</td>
		<?php } ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_VOLUME)) { ?>
			<td rowspan="1" colspan="2" class="center">
				<?php print $langs->trans('Volume'); ?>
			</td>
		<?php } ?>

		<?php if (!empty($conf->global->PICKUP_USE_DEEE)) { ?>
			<td rowspan="2" class="">
				<?php print $langs->trans('DEEE'); ?>
			</td>
		<?php } ?>
		<td rowspan="2" class="">
			<?php if ($object->status == Pickup::STATUS_STOCK) { ?>
				<a href="<?php print dol_buildpath('product/stock/movement_card.php', 1) ?>?id=<?php print $object->fk_entrepot ?>&search_inventorycode=<?php print urlencode($object->ref) ?>">
					<?php print $langs->trans('StockMovement'); ?>
				</a>
			<?php } else { ?>
				<?php print $langs->trans('StockMovement'); ?>
			<?php } ?>
		</td>
		<td rowspan="2" class="linecoledit" style="width: 10px"></td>
		<td rowspan="2" class="linecoledit" style="width: 10px"></td>
		<td rowspan="2" class="linecoldelete" style="width: 10px"></td>
		<?php if ($action == 'selectlines') { ?>
			<td rowspan="2" class="linecolcheckall center">
				<input type="checkbox" class="linecheckboxtoggle" />
				<script>
					$(document).ready(function() {
						$(".linecheckboxtoggle").click(function() {
							var checkBoxes = $(".linecheckbox");
							checkBoxes.prop("checked", this.checked);
						})
					});
				</script>
			</td>
		<?php } ?>
	</tr>
	<tr class="liste_titre nodrag nodrop">
		<?php if (!empty($conf->global->PICKUP_UNITS_WEIGHT)) { ?>
			<td class="right"><?php print $langs->trans('PickupUnitValue'); ?></td>
			<td class="right"><?php print $langs->trans('Total'); ?></td>
		<?php } ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_LENGTH)) { ?>
			<td class="right"><?php print $langs->trans('PickupUnitValue'); ?></td>
			<td class="right"><?php print $langs->trans('Total'); ?></td>
		<?php } ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_SURFACE)) { ?>
			<td class="right"><?php print $langs->trans('PickupUnitValue'); ?></td>
			<td class="right"><?php print $langs->trans('Total'); ?></td>
		<?php } ?>
		<?php if (!empty($conf->global->PICKUP_UNITS_VOLUME)) { ?>
			<td class="right"><?php print $langs->trans('PickupUnitValue'); ?></td>
			<td class="right"><?php print $langs->trans('Total'); ?></td>
		<?php } ?>
	</tr>
</thead>

<!-- END PHP TEMPLATE pickup/pickupline_title.tpl.php -->
