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
 * $this (ActionsCollecte)
 * $object (collecte)
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
$colspan = 2; // Col col edit + col delete

$line = new CollecteLine($this->db);

print "<!-- BEGIN PHP TEMPLATE collecte/collecteline_create.tpl.php -->\n";

$nolinesbefore = (count($object->lines) == 0 || $forcetoshowtitlelines);
if ($nolinesbefore) {
    // TODO: print title line, or always display the collecteline_view template (even when no lines).
}

$coldisplay = 0;
?>

<tr class="pair nodrag nodrop nohoverpair <?php print (($nolinesbefore) ? '' : ' liste_titre_create'); ?>">
	<td class="bordertop nobottom linecoldescription minwidth500imp">
		<?php $coldisplay++; ?>
		<span class="prod_entry_mode_predef">
		<?php
			$filtertype = '0'; // product
			$statustoshow = -1; // all products
			// FIXME: should not lit products with tobatch=1
			$form->select_produits(GETPOST('idprod', 'int'), 'idprod', $filtertype, $conf->product->limit_size, null, $statustoshow);		
		?>
		</span>
	</td>
	<td class="bordertop nobottom right">
		<?php $coldisplay++;
			print $line->showInputField(null, 'qty', GETPOSTISSET("qty") ? GETPOST('qty', 'int') : 1);
		?>
	</td>
	<td class="bordertop nobottom right">
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

<!-- END PHP TEMPLATE collecte/collecteline_create.tpl.php -->
