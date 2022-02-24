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
require_once DOL_DOCUMENT_ROOT.'/custom/pickup/class/mobilecat.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pickup/lib/pickup_mobilecat.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/pickup/lib/mobile_forms.php';


$langs->loadLangs(array("pickup@pickup", "categories", "other"));

$id=GETPOST('id', 'int');
$type = Categorie::TYPE_PRODUCT;
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backto', 'alpha');

if ($id == "")
{
    dol_print_error('', 'Missing parameter id');
    exit();
}

// if ($user->societe_id) $socid=$user->societe_id;
if (!$user->rights->pickup->configure) {
  accessforbidden();
}

// Standard Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);
$mobilecat = new PickupMobileCat($db);
if ($id > 0)
{
  $result = $object->fetch($id);
  if ($result < 0) {
    dol_print_error($db);
    exit;
  }
  if ($mobilecat->fetchByCategory($object->id) < 0)
	{
		dol_print_error($db);
		exit;
	}
}
else
{
  dol_print_error('', 'Invalid parameter id');
  exit();
}

$type=$object->type;
if (is_numeric($type)) $type=Categorie::$MAP_ID_TO_CODE[$type];	// For backward compatibility

$common_uri = $_SERVER["PHP_SELF"].'?id='.$object->id.'&';
if ($object->socid)
{
  $common_uri.= 'socid='.$object->socid.'&';
}
if ($backtopage === 'mobilecat_list') {
  $common_uri.= 'backto=mobilecat_list&';
}

/*
 * Actions
 */

if ($action === 'activate')
{
  if (!$mobilecat->id)
  {
    $mc = new PickupMobileCat($db);
    $mc->fk_category = $object->id;
    $mc->active = 1;
    $new_id = $mc->create($user);
    if (!$new_id || $new_id <= 0)
    {
      dol_print_error($db);
		  exit;
    }

    if ($mc->fetch($new_id) <= 0)
    {
      dol_print_error($db);
		  exit;
    }
    $mobilecat = $mc;
  }
  else
  {
    $mobilecat->active = 1;
    if ($mobilecat->update($user) < 0)
    {
      dol_print_error($db);
		  exit;
    }

    // No need to fetch again.
  }
}
elseif ($action === 'unactivate')
{
  if ($mobilecat->id)
  {
    $mobilecat->active = 0;
    if ($mobilecat->update($user) < 0)
    {
      dol_print_error($db);
		  exit;
    }

    // No need to fetch again.
  }
}
elseif ($action === 'delete')
{
  require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
  $form = new Form($db);
  print $form->formconfirm($common_uri, $langs->trans('Delete'), $langs->trans('ConfirmDelete'), 'confirm_delete');
}
elseif ($action === 'confirm_delete')
{
  if ($mobilecat->id)
  {
    if ($mobilecat->delete($user) < 0)
    {
      dol_print_error($db);
		  exit;
    }
    $mobilecat = new PickupMobileCat($db);
  }
}
elseif ($action === 'edit')
{
  $new_form = GETPOST('form', 'alpha');
  $new_notes = GETPOST('notes', 'nohtml');
  $forms = mobileListProductForms();
  if (empty($new_form) || array_key_exists($new_form, $forms))
  {
    $mobilecat->form = empty($new_form) ? null : $new_form;
    $mobilecat->notes = empty($new_notes) || $new_notes === '' ? null : $new_notes;
    if ($mobilecat->update($user) < 0)
    {
      dol_print_error($db);
		  exit;
    }

    // No need to fetch again.
  }
  else
  {
    dol_print_error('', 'Invalid parameter form');
    exit();
  }
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

  dol_fiche_head($head, 'pickupmobilecat', $title, -1, 'category');

  if ($backtopage === 'mobilecat_list') $linkback = '<a href="'.DOL_URL_ROOT.'/custom/pickup/mobilecat_list.php">'.$langs->trans("BackToList").'</a>';
  else $linkback = '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("BackToList").'</a>';

  $object->ref = $object->label;
  // $object->next_prev_filter=" type = ".$object->type;
	$morehtmlref='<br><div class="refidno"><a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
	$ways = $object->print_all_ways(' &gt;&gt; ', '', 1);
	foreach ($ways as $way)
	{
	    $morehtmlref.=$way."<br>\n";
	}
	$morehtmlref.='</div>';

  // dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);
  // dol_banner_tab($object, 'id', $linkback, ($user->societe_id?0:1), '', '', $morehtmlref, '', 0, '', '', 1);
  // dol_banner_tab($object, 'id', $linkback, ($user->societe_id?0:1), 'rowid', 'id', $morehtmlref, '', 0, '', '', 1);
  dol_banner_tab($object, 'id', $linkback, 0, '', '', $morehtmlref, '', 0, '', '', 1);
  
  print '<br>';

  print '<div class="fichecenter">';
  print '<div class="underbanner clearboth"></div>';
  
  $forms = mobileListProductForms();

  if ($mobilecat->id)
  {
    print '<form method="POST" action="'.$common_uri.'">';
    ?>
      <input type="hidden" name="action" value="edit">
      <table width="100%" class="border">
        <tr>
          <td class="titlefield notopnoleft">
            <?php echo $langs->trans('MobileCatForm') ?>
          </td>
          <td>
            <select name="form">
              <?php
              foreach ($forms as $form => $form_label) {
                print '<option value="'.htmlspecialchars($form).'" ';
                if ($mobilecat->form === $form)
                {
                  print ' selected="selected" ';
                }
                print '>'.htmlspecialchars($form_label).'</option>';
              }
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <td class="titlefield notopnoleft">
            <?php echo $langs->trans('MobileCatDescription') ?>
          </td>
          <td>
              <textarea name="notes"><?php print htmlspecialchars($mobilecat->notes) ?></textarea>
          </td>
        </tr>
        <tr>
          <td class="titlefield notopnoleft">
          </td>
          <td>
            <input type="submit" value="Enregistrer">
          </td>
        </tr>
      <table>
    <?php
    print '</form>';
  }

  print '</div>';

  dol_fiche_end(-1);

  /*
  * Boutons actions
  */

  print "<div class='tabsAction'>\n";

  if ($user->rights->pickup->configure)
  {
    if (!$mobilecat->id || !$mobilecat->active)
    {
      print '<form method="POST" action="'.$common_uri.'">';
      print '<input type="hidden" name="action" value="activate">';
      print '<input type="submit" value="' . $langs->trans('MobileCatEnable') . '" >';
      print '</form>';
    }
    else
    {
      print '<form method="POST" action="'.$common_uri.'">';
      print '<input type="hidden" name="action" value="unactivate">';
      print '<input type="submit" value="' . $langs->trans('MobileCatDisable') . '" >';
      print '</form>';

      print '<form method="POST" action="'.$common_uri.'">';
      print '<input type="hidden" name="action" value="delete">';
      print '<input type="submit" value="Supprimer" >';
      print '</form>';
    }
  //   // $socid = ($object->socid ? "&amp;socid=".$object->socid : "");
  //   // print "<a class='butAction' href='edit.php?id=".$object->id.$socid."&amp;type=".$type."'>".$langs->trans("Modify")."</a>";
  }

  print "</div>";

  if (!$mobilecat->id)
  {
    print '<div class="opacitymedium">' . $langs->trans('MobileCatNotEnabled') . '</div>';
  }
}
else
{
    print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
?>

