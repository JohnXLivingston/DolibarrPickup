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
$colspan = 3; // Col col edit + col delete + move button

print "<!-- BEGIN PHP TEMPLATE collecte/objectline_edit.tpl.php -->\n";

$coldisplay = 0;
?>
<tr class="oddeven tredited">
<?php if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
		<td class="linecolnum center"><?php $coldisplay++; ?><?php echo ($i + 1); ?></td>
<?php }

$coldisplay++;
?>
	<td>
	<div id="line_<?php echo $line->id; ?>"></div>

	<input type="hidden" name="lineid" value="<?php echo $line->id; ?>">
	<input type="hidden" id="product_id" name="idprod" value="<?php echo (!empty($line->fk_product) ? $line->fk_product : 0); ?>" />

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
	if (is_object($hookmanager)) // TODO: necessary ?
	{
		$fk_parent_line = (GETPOST('fk_parent_line') ? GETPOST('fk_parent_line') : $line->fk_parent_line);
		// FIXME: there is no $dateSelector in this file. Nor $seller or $buyer.
		$parameters = array('line'=>$line, 'fk_parent_line'=>$fk_parent_line, 'var'=>$var, 'dateSelector'=>$dateSelector, 'seller'=>$seller, 'buyer'=>$buyer);
		$reshook = $hookmanager->executeHooks('formEditProductOptions', $parameters, $this, $action);
	}

	// Do not allow editing during a situation cycle
	if ($line->fk_prev_id == null) // TODO: necessary?
	{
		// editor wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$nbrows = ROWS_2;
		if (!empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows = $conf->global->MAIN_INPUT_DESC_HEIGHT;
		$enable = (isset($conf->global->FCKEDITOR_ENABLE_DETAILS) ? $conf->global->FCKEDITOR_ENABLE_DETAILS : 0);
		$toolbarname = 'dolibarr_details';
		if (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) $toolbarname = 'dolibarr_notes';
		$doleditor = new DolEditor('product_desc', $line->description, '', (empty($conf->global->MAIN_DOLEDITOR_HEIGHT) ? 164 : $conf->global->MAIN_DOLEDITOR_HEIGHT), $toolbarname, '', false, true, $enable, $nbrows, '98%');
		$doleditor->Create();
	} else {
		print '<textarea id="product_desc" class="flat" name="product_desc" readonly style="width: 200px; height:80px;">'.$line->description.'</textarea>';
	}

	?>
	</td>
	<td class="right">
	<?php $coldisplay++;
		print '<input size="3" type="text" class="flat right" name="qty" id="qty" value="'.$line->qty.'"';
		if ($line->fk_prev_id != null) print ' readonly'; // FIXME: necessary?
		print '>';
	?>
	</td>

	<?php
	// if ($conf->global->PRODUCT_USE_UNITS) // FIXME: necessary?
	// {
	//     $coldisplay++;
	// 	print '<td class="left">';
	// 	print $form->selectUnits($line->fk_unit, "units");
	// 	print '</td>';
	// }
	?>

	<td class="right">
	<?php $coldisplay++;
		print '<input size="3" type="text" class="flat right" name="weight" id="weight" value="'.$line->weight.'">';
	?>
	</td>

	<!-- colspan for this td because it replace total_ht+3 td for buttons+... -->
	<td class="center valignmiddle" colspan="<?php echo $colspan; ?>"><?php $coldisplay += $colspan; ?>
		<input type="submit" class="button buttongen marginbottomonly" id="savelinebutton marginbottomonly" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
		<input type="submit" class="button buttongen marginbottomonly" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
	</td>
</tr>

<?php
//Line extrafield
if (!empty($extrafields))
{
	print $line->showOptionals($extrafields, 'edit', array('class'=>'tredited', 'colspan'=>$coldisplay), '', '', 1);
}
?>

<?php
print "<!-- END PHP TEMPLATE collecte/objectline_edit.tpl.php -->\n";
?>
