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
require_once DOL_DOCUMENT_ROOT.'/custom/pickup/class/dolinputcat.class.php';
// FIXME: should include: require_once DOL_DOCUMENT_ROOT.'/custom/dolinput/lib/dolinput.lib.php';

$langs->loadlangs(array('categories'));

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
$dolinputcat = new Dolinputcat($db);
if ($id > 0)
{
  $result = $object->fetch($id);
  if ($result < 0) {
    dol_print_error($db);
    exit;
  }
  if ($dolinputcat->fetchByCategory($object->id) < 0)
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
if ($backtopage === 'dolinputcat_list') {
  $common_uri.= 'backto=dolinputcat_list&';
}

/*
 * Actions
 */

if ($action === 'activate')
{
  if (!$dolinputcat->id)
  {
    $dc = new Dolinputcat($db);
    $dc->fk_category = $object->id;
    $dc->active = 1;
    $new_id = $dc->create($user);
    if (!$new_id || $new_id <= 0)
    {
      dol_print_error($db);
		  exit;
    }

    if ($dc->fetch($new_id) <= 0)
    {
      dol_print_error($db);
		  exit;
    }
    $dolinputcat = $dc;
  }
  else
  {
    $dolinputcat->active = 1;
    if ($dolinputcat->update($user) < 0)
    {
      dol_print_error($db);
		  exit;
    }

    // No need to fetch again.
  }
}
elseif ($action === 'unactivate')
{
  if ($dolinputcat->id)
  {
    $dolinputcat->active = 0;
    if ($dolinputcat->update($user) < 0)
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
  if ($dolinputcat->id)
  {
    if ($dolinputcat->delete($user) < 0)
    {
      dol_print_error($db);
		  exit;
    }
    $dolinputcat = new Dolinputcat($db);
  }
}
elseif ($action === 'edit')
{
  $new_form = GETPOST('form', 'alpha');
  $new_notes = GETPOST('notes', 'nohtml');
  $forms = array(); // FIXME: should be dolinputListProductForms();
  if (empty($new_form) || in_array($new_form, $forms))
  {
    $dolinputcat->form = empty($new_form) ? null : $new_form;
    $dolinputcat->notes = empty($new_notes) || $new_notes === '' ? null : $new_notes;
    if ($dolinputcat->update($user) < 0)
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

  dol_fiche_head($head, 'dolinput', $title, -1, 'category');

  if ($backtopage === 'dolinputcat_list') $linkback = '<a href="'.DOL_URL_ROOT.'/custom/pickup/dolinputcat_list.php">'.$langs->trans("BackToList").'</a>';
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
  
  $forms = array(); // FIXME: should be dolinputListProductForms();

  if ($dolinputcat->id)
  {
    print '<form method="POST" action="'.$common_uri.'">';
    ?>
      <input type="hidden" name="action" value="edit">
      <table width="100%" class="border">
        <tr>
          <td class="titlefield notopnoleft">
            Formulaire à utiliser dans Dolinput
          </td>
          <td>
            <select name="form">
              <option value="" <?php if (empty($dolinputcat->form)) print 'selected="selected"'; ?>>Formulaire par defaut</option>
              <?php
              foreach ($forms as $form) {
                print '<option value="'.htmlspecialchars($form).'" ';
                if ($dolinputcat->form === $form)
                {
                  print ' selected="selected" ';
                }
                print '>'.htmlspecialchars($form).'</option>';
              }
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <td class="titlefield notopnoleft">
            Notes à afficher dans Dolinput à coté du champs «Description du produit»
          </td>
          <td>
              <textarea name="notes"><?php print htmlspecialchars($dolinputcat->notes) ?></textarea>
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

  dol_fiche_end();

  /*
  * Boutons actions
  */

  print "<div class='tabsAction'>\n";

  if ($user->rights->pickup->configure)
  {
    if (!$dolinputcat->id || !$dolinputcat->active)
    {
      print '<form method="POST" action="'.$common_uri.'">';
      print '<input type="hidden" name="action" value="activate">';
      print '<input type="submit" value="Utiliser dans Dolinput" >';
      print '</form>';
    }
    else
    {
      print '<form method="POST" action="'.$common_uri.'">';
      print '<input type="hidden" name="action" value="unactivate">';
      print '<input type="submit" value="Désactiver dans Dolinput" >';
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

  if (!$dolinputcat->id)
  {
    print '<div class="opacitymedium">Ce tag n\'est actuellement pas utilisé dans Dolinput.</div>';
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

