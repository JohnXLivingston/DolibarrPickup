<?php
/* Copyright (C) 2021 Jonathan Dollé
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);

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

// Load translation files required by the page
$langs->loadLangs(array("pickup@pickup"));

// Securite acces client
if (!$user->rights->pickup->create) accessforbidden();

$conf->dol_hide_topmenu = 1; // hide the top menu. Redundant with define('NOREQUIREMENU', 1);
$conf->dol_hide_leftmenu = 1; // hide left menu

dol_include_once('/pickup/core/modules/modPickup.class.php');
$modulePickup = new modPickup($db);

function mobile_header () {
  global $langs, $conf;
  global $modulePickup;
  // Previously we were calling:
  //    $jsfile = '/pickup/js/mobile.js.php';
  //    $cssfile = '/pickup/css/mobile.css.php';
  //    llxHeader('', $langs->trans('Pickups'), '', '', 0, 0, [$jsfile], [$cssfile]);
  // But this is way to much.
  top_httphead();
  print '<!doctype html>'."\n";
  print '<html lang="'.substr($langs->defaultlang, 0, 2).'">'."\n";
  print "<head>\n";
  print '<meta charset="UTF-8">'."\n";
  print '<meta name="robots" content="noindex'.($disablenofollow?'':',nofollow').'">'."\n";	// Do not index
  print '<meta name="viewport" content="width=device-width, initial-scale=1">'."\n";

  $favicon = DOL_URL_ROOT.'/theme/dolibarr_256x256_color.png';
  if (!empty($conf->global->MAIN_FAVICON_URL)) $favicon = $conf->global->MAIN_FAVICON_URL;
  print '<link rel="shortcut icon" type="image/x-icon" href="'.$favicon.'"/>'."\n";

  print '<title>' . dol_htmlentities($langs->trans('Pickups')) . "</title>\n";

  $ext='layout='.$conf->browser->layout.'&version='.urlencode(DOL_VERSION);
  $extpickup = 'version='.urlencode($modulePickup->version);

  // FIXME: clean or remove this:
  if (true) {
    print '<!-- Includes CSS for JQuery (Ajax library) -->'."\n";
    $jquerytheme = 'base';
    if (!empty($conf->global->MAIN_USE_JQUERY_THEME)) $jquerytheme = $conf->global->MAIN_USE_JQUERY_THEME;
    if (constant('JS_JQUERY_UI')) print '<link rel="stylesheet" type="text/css" href="'.JS_JQUERY_UI.'css/'.$jquerytheme.'/jquery-ui.min.css'.($ext ? '?'.$ext : '').'">'."\n"; // Forced JQuery
    else print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/css/'.$jquerytheme.'/jquery-ui.css'.($ext ? '?'.$ext : '').'">'."\n"; // JQuery
    if (!defined('DISABLE_JQUERY_JNOTIFY')) print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/jnotify/jquery.jnotify-alt.min.css'.($ext ? '?'.$ext : '').'">'."\n"; // JNotify
    if (!defined('DISABLE_SELECT2') && (!empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT')))     // jQuery plugin "mutiselect", "multiple-select", "select2"...
    {
      $tmpplugin = empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) ?constant('REQUIRE_JQUERY_MULTISELECT') : $conf->global->MAIN_USE_JQUERY_MULTISELECT;
      print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/'.$tmpplugin.'/dist/css/'.$tmpplugin.'.css'.($ext ? '?'.$ext : '').'">'."\n";
    }
  }

  print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/pickup/css/mobile.css', 1).($extpickup?'?'.$extpickup:'').'">'."\n";


  // FIXME: clean or remove this:
  if (true) {
    // JQuery. Must be before other includes
    if (defined('JS_JQUERY') && constant('JS_JQUERY')) print '<script src="'.JS_JQUERY.'jquery.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
    else print '<script src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
    if (defined('JS_JQUERY_UI') && constant('JS_JQUERY_UI')) print '<script src="'.JS_JQUERY_UI.'jquery-ui.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
    else print '<script src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-ui.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
    if (!defined('DISABLE_SELECT2') && (!empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT')))     // jQuery plugin "mutiselect", "multiple-select", "select2", ...
    {
      $tmpplugin = empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) ?constant('REQUIRE_JQUERY_MULTISELECT') : $conf->global->MAIN_USE_JQUERY_MULTISELECT;
      print '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/'.$tmpplugin.'/dist/js/'.$tmpplugin.'.full.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n"; // We include full because we need the support of containerCssClass
    }
    if (! defined('DISABLE_MULTISELECT'))     // jQuery plugin "mutiselect" to select with checkboxes. Can be removed once we have an enhanced search tool
    {
      print '<script src="'.DOL_URL_ROOT.'/includes/jquery/plugins/multiselect/jquery.multi-select.js'.($ext?'?'.$ext:'').'"></script>'."\n";
    }
  }

  print '<script src="'.dol_buildpath('/pickup/js/mobile.js.php', 1).($extpickup?'?'.$extpickup:'').'"></script>'."\n";
  print "</head>\n\n";
}
mobile_header();

?>
<body>
  <div pickupmobileapp-container class="container"
    data-user-id="<?php print htmlspecialchars($user->id) ?>"
    data-entrepot-id="<?php if(!empty($conf->global->PICKUP_DEFAULT_STOCK)) { print $conf->global->PICKUP_DEFAULT_STOCK; } ?>"
  ></div>
</body></html>
