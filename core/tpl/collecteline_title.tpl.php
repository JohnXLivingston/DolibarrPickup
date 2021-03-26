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
 * $object (collecte)
 *
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}

print "<!-- BEGIN PHP TEMPLATE collecte/collecteline_title.tpl.php -->\n";

?>
<thead>
	<tr class="liste_titre nodrag nodrop">
		<td class="linecoldescription">
			<?php print $langs->trans('Description'); ?>
		</td>
		<td class="right">
			<?php print $langs->trans('Qty'); ?>
		</td>
		<td class="right">
			<?php print $langs->trans('Weight'); ?>
		</td>
		<td class="">
			<?php print $langs->trans('StockMovement'); ?>
		</td>
		<td class="linecoledit"></td><?php // No width to allow autodim ?>
		<td class="linecoldelete" style="width: 10px"></td>
		<?php if ($action == 'selectlines') { ?>
			<td class="linecolcheckall center">
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
</thead>

<!-- END PHP TEMPLATE collecte/collecteline_title.tpl.php -->
