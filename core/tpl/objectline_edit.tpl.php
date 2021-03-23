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
 * $line (collecteline)
 * $conf
 * $langs
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}


// Define colspan for the button 'Add'
$colspan = 2; // Col col edit + col delete

print "<!-- BEGIN PHP TEMPLATE collecte/objectline_edit.tpl.php -->\n";

$coldisplay = 0;
?>
<tr class="oddeven tredited">
	<td>
		<?php $coldisplay++; ?>
		<div id="line_<?php echo $line->id; ?>"></div>

		<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">

		<?php if ($line->fk_product > 0) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/product/card.php?id='.$line->fk_product; ?>">
			<?php
			if ($line->product_type == 1) echo img_object($langs->trans('ShowService'), 'service');
			else print img_object($langs->trans('ShowProduct'), 'product');
			echo ' '.$line->ref;
			?>
			</a>
			<?php
			echo ' - '.nl2br($line->product_label);
			?>

			<br><br>

		<?php }	?>

		<?php
			// if (is_object($hookmanager)) // TODO: necessary ?
			// {
			// 	$fk_parent_line = (GETPOST('fk_parent_line') ? GETPOST('fk_parent_line') : $line->fk_parent_line);
			// 	// FIXME: there is no $dateSelector in this file. Nor $seller or $buyer.
			// 	$parameters = array('line'=>$line, 'fk_parent_line'=>$fk_parent_line, 'var'=>$var, 'dateSelector'=>$dateSelector, 'seller'=>$seller, 'buyer'=>$buyer);
			// 	$reshook = $hookmanager->executeHooks('formEditProductOptions', $parameters, $this, $action);
			// }

			print $line->showInputField(null, 'description', GETPOSTISSET("description") ? GETPOST('description', 'none') : $line->description);
		?>
	</td>
	<td class="right">
		<?php $coldisplay++;
			print $line->showInputField(null, 'qty', GETPOSTISSET("qty") ? GETPOST('qty', 'int') : $line->qty);
		?>
	</td>
	<td class="right">
		<?php $coldisplay++;
			print $line->showInputField(null, 'weight', GETPOSTISSET("weight") ? GETPOST('weight', 'float') : $line->weight);
		?>
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

<!-- END PHP TEMPLATE collecte/objectline_edit.tpl.php -->
