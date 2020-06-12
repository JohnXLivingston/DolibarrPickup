<?php

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';

$langs->loadlangs(array('categories'));

$id=GETPOST('id', 'int');
$type = Categorie::TYPE_PRODUCT;

if ($id == "")
{
    dol_print_error('', 'Missing parameter id');
    exit();
}

// if ($user->societe_id) $socid=$user->societe_id;
if (!$user->rights->collecte->configure) {
  accessforbidden();
}

// Standard Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);
if ($id > 0)
{
	$result = $object->fetch($id);
}

/*
 * View
 */

llxHeader("", "", $langs->trans("Categories"));

if ($object->id)
{
	if ($type == Categorie::TYPE_PRODUCT)       $title=$langs->trans("ProductsCategoryShort");
	elseif ($type == Categorie::TYPE_SUPPLIER)  $title=$langs->trans("SuppliersCategoryShort");
	elseif ($type == Categorie::TYPE_CUSTOMER)  $title=$langs->trans("CustomersCategoryShort");
	elseif ($type == Categorie::TYPE_MEMBER)    $title=$langs->trans("MembersCategoryShort");
	elseif ($type == Categorie::TYPE_CONTACT)   $title=$langs->trans("ContactCategoriesShort");
	elseif ($type == Categorie::TYPE_ACCOUNT)   $title=$langs->trans("AccountsCategoriesShort");
	elseif ($type == Categorie::TYPE_PROJECT)   $title=$langs->trans("ProjectsCategoriesShort");
	elseif ($type == Categorie::TYPE_USER)      $title=$langs->trans("UsersCategoriesShort");
	else                                        $title=$langs->trans("Category");

  $head = categories_prepare_head($object, $type);

  dol_fiche_head($head, 'dolinput', $title, -1, 'category');

  $linkback = '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("BackToList").'</a>';

	$object->ref = $object->label;
	$morehtmlref='<br><div class="refidno"><a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
	$ways = $object->print_all_ways(" &gt;&gt; ", '', 1);
	foreach ($ways as $way)
	{
	    $morehtmlref.=$way."<br>\n";
	}
	$morehtmlref.='</div>';

  dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);
  
  print '<br>';

  print '<div class="fichecenter">';
  print '<div class="underbanner clearboth"></div>';
  print '<table width="100%" class="border">';

  print '</table>';
  print '</div>';

  dol_fiche_end();

  /*
  * Boutons actions
  */

  print "<div class='tabsAction'>\n";

  // if ($user->rights->collecte->configure)
  // {
  //   // $socid = ($object->socid ? "&amp;socid=".$object->socid : "");
  //   // print "<a class='butAction' href='edit.php?id=".$object->id.$socid."&amp;type=".$type."'>".$langs->trans("Modify")."</a>";
  // }

  print "</div>";
}

// End of page
llxFooter();
$db->close();
?>

