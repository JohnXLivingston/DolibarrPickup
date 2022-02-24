<?php
/* Copyright (C) 2021-2022 John Livingston
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

  print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/pickup/css/mobile.css', 1).($extpickup?'?'.$extpickup:'').'">'."\n";

  print '<script src="'.dol_buildpath('/pickup/js/lib/jquery/jquery.min.js', 1).($extpickup?'?'.$extpickup:'').'"></script>'."\n";
  print '<script src="'.dol_buildpath('/pickup/js/lib/select2/select2.min.js', 1).($extpickup?'?'.$extpickup:'').'"></script>'."\n";
  print '<script src="'.dol_buildpath('/pickup/js/content/mobile.js', 1).($extpickup?'?'.$extpickup:'').'"></script>'."\n";
  print "</head>\n\n";
}
mobile_header();

?>
<body>
  <div pickupmobileapp-container class="container"
    <?php if (!empty(GETPOST('demo', 'alpha'))) { ?>data-demo="1"<?php } ?>
    data-modpickup-version="<?php echo htmlspecialchars($modulePickup->version); ?>"
    data-user-id="<?php print htmlspecialchars($user->id) ?>"
    data-entrepot-id="<?php if(!empty($conf->global->PICKUP_DEFAULT_STOCK)) { print $conf->global->PICKUP_DEFAULT_STOCK; } ?>"
    <?php if (!empty($conf->global->PICKUP_USE_PCAT)) { print ' data-use-pcat="1" '; } ?>
    <?php if (!empty($conf->global->PICKUP_USE_PBRAND)) { print ' data-use-pbrand="1" '; } ?>
    <?php if (!empty($conf->global->PICKUP_USE_DEEE)) { print ' data-use-deee="1" '; } ?>
    <?php if (!empty($conf->productbatch->enabled) && $conf->global->PICKUP_DEFAULT_HASBATCH === 'ask') { print ' data-ask-hasbatch="1" '; } ?>
  ></div>
</body></html>
