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
 * $conf
 * $langs
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
    print "Error: this template page cannot be called directly as an URL";
    exit;
}


global $forcetoshowtitlelines;

// Define colspan for the button 'Add'
$colspan = 3; // Col col edit + col delete + move button

// Lines for extrafield
$objectline = new Collecteline($this->db);

print "<!-- BEGIN PHP TEMPLATE collecte/objectline_create.tpl.php -->\n";

$nolinesbefore = (count($this->lines) == 0 || $forcetoshowtitlelines);
if ($nolinesbefore) {
    // TODO: print title line.
}
print '<tr class="pair nodrag nodrop nohoverpair'.(($nolinesbefore) ? '' : ' liste_titre_create').'">';
$coldisplay = 0;

// Adds a line numbering column
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
    $coldisplay++;
    echo '<td class="bordertop nobottom linecolnum center"></td>';
}

$coldisplay++;
print '<td class="bordertop nobottom linecoldescription minwidth500imp">';

// Predefined product/service
if (!empty($conf->product->enabled))
{
	echo '<span class="prod_entry_mode_predef">';
	$filtertype = '0'; // product

	$statustoshow = -1; // all products
	$form->select_produits(GETPOST('idprod', 'int'), 'idprod', $filtertype, $conf->product->limit_size, null, $statustoshow);

	echo '</span>';
}



$coldisplay++;
print '<td class="bordertop nobottom linecolqty right"><input type="text" size="2" name="qty" id="qty" class="flat right" value="'.(GETPOSTISSET("qty") ? GETPOST("qty", 'alpha', 2) : 1).'">';
print '</td>';

// if ($conf->global->PRODUCT_USE_UNITS) // TODO: necessary?
// {
//     $coldisplay++;
// 	print '<td class="nobottom linecoluseunit left">';
// 	print $form->selectUnits($line->fk_unit, "units");
// 	print '</td>';
// }

$coldisplay++;
print '<td class="bordertop nobottom right"><input type="text" size="2" name="weight" id="weight" class="flat right" value="'.(GETPOSTISSET("weight") ? GETPOST("weight", 'float') : 0).'">';
print '</td>';

$coldisplay += $colspan;
print '<td class="bordertop nobottom linecoledit center valignmiddle" colspan="'.$colspan.'">';
print '<input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline" id="addline">';
print '</td>';
print '</tr>';

if (is_object($objectline)) {
	print $objectline->showOptionals($extrafields, 'edit', array('style'=>$bcnd[$var], 'colspan'=>$coldisplay), '', '', 1);
}
?>

<!-- END PHP TEMPLATE collecte/objectline_create.tpl.php -->
