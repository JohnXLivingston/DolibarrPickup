<?php
/* Copyright (C) 2021-2022 John Livingston
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
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
if (!$user->rights->pickup->read) accessforbidden();
if (empty($conf->global->PICKUP_USE_PRINTABLE_LABEL)) accessforbidden();

$conf->dol_hide_topmenu = 1; // hide the top menu. Redundant with define('NOREQUIREMENU', 1);
$conf->dol_hide_leftmenu = 1; // hide left menu

dol_include_once('/pickup/core/modules/modPickup.class.php');
$modulePickup = new modPickup($db);
dol_include_once('/pickup/class/pickup.class.php');
$objectPickup = new Pickup($db);

dol_include_once('/core/class/genericobject.class.php');
dol_include_once('/core/modules/barcode/doc/tcpdfbarcode.modules.php');

function printable_label_header() {
  global $langs, $conf;
  global $modulePickup;
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

  print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('/pickup/css/printlabel.css', 1).($extpickup?'?'.$extpickup:'').'">'."\n";

  print "</head>\n\n";
}
printable_label_header();

function print_barcode($barcode_type, $code) {
  global $conf, $db;

  if (empty($barcode_type)) {
    $barcode_type = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
  }
  if (is_numeric($barcode_type)) {
    // Get encoder (barcode_type_coder) from barcode type id (barcode_type)
		$stdobject = new GenericObject($db);
		$stdobject->barcode_type = $barcode_type;
		if ($stdobject->fetch_barcode() <= 0) {
      dol_syslog(__FUNCTION__.': unknown barcode type '.$barcode_type, LOG_ERR);
      print 'ERROR';
      return;
    }
    $barcode_type = $stdobject->barcode_type_code;
  }

  $barcodeGenerator = new modTcpdfbarcode();
  $encoding = $barcodeGenerator->getTcpdfEncodingType($barcode_type);
  if (empty($encoding)) {
    dol_syslog(__FUNCTION__.': unknown barcode type '.$barcode_type, LOG_ERR);
    print 'ERROR';
    return;
  }
  if ($barcodeGenerator->is2d) { // this is set by getTcpdfEncodingType
    require_once TCPDF_PATH.'tcpdf_barcodes_2d.php';
    $barcodeobj = new TCPDF2DBarcode($code, $encoding);
  } else {
    require_once TCPDF_PATH.'tcpdf_barcodes_1d.php';
    $barcodeobj = new TCPDFBarcode($code, $encoding);
  }
  // print $barcodeobj->getBarcodeSVGcode();
  print '<img src="data:image/png;base64,';
  print base64_encode($barcodeobj->getBarcodePNGData());
  print '">';
}

function print_labels($labels_info) {
  foreach ($labels_info as $label_info) {
    ?><div class="page"><?php
    if (!empty($label_info['blocks']) && is_array($label_info['blocks'])) {
      foreach ($label_info['blocks'] as $block_info) {
        print_block($block_info);
      }
    }
    ?></div><?php
  }
}

function print_block($block_info) {
  ?><span class="block"><?php
  if (!empty($block_info['barcode'])) {
    print_barcode($block_info['barcode']['barcode_type'], $block_info['barcode']['code']);
  }
  if (!empty($block_info['label'])) {
    ?><label><?php
    print htmlspecialchars($block_info['label']);
    ?></label><?php
  }
  ?></span><?php
}

function get_infos() {
  $what = GETPOST('what');
  if ($what === 'test') {
    return get_test_infos();
  }

  if ($what === 'product') {
    return get_product_infos();
  }
}

function get_test_infos() {
  return [
    [
      'blocks' => [
        [
          'barcode' => ['barcode_type' => 'DATAMATRIX', 'code' => 'MARSHALL MX100'],
          'label' => 'MARSHALL MX100'
        ],
        [
          'barcode' => ['barcode_type' => 'C128', 'code' => 'SN0123456789'],
          'label' => 'SN0123456789'
        ]
      ]
    ],
    [
      'blocks' => [
        [
          'barcode' => ['barcode_type' => 'DATAMATRIX', 'code' => 'UNE_REF_BIDON'],
          'label' => 'UNE_REF_BIDON'
        ],
        [
          'barcode' => ['barcode_type' => 'C128', 'code' => 'SN0123456790'],
          'label' => 'SN0123456790'
        ]
      ]
    ],
    [
      'blocks' => [
        [
          'barcode' => ['barcode_type' => null, 'code' => 'UNE_REF_BIDON'],
          'label' => 'BARCODE_DEFAULT_TYPE'
        ]
      ]
    ],
    [
      'blocks' => [
        [
          'barcode' => ['barcode_type' => '7', 'code' => 'UNE_REF_BIDON'],
          'label' => 'BARCODE_NUMERIC'
        ]
      ]
    ]
  ];
}

function get_product_infos() {
  global $db;
  $labels_info = [];
  dol_include_once('/product/class/product.class.php');
  $pids = GETPOST('product_id', 'array');
  foreach ($pids as $pid) {
    $pid = intval($pid);
    if (empty($pid)) {
      continue;
    }
    $product = new Product($db);
    if ($product->fetch($pid) <= 0) {
      dol_syslog(__FUNCTION__.': product not found. pid='.$pid, LOG_ERR);
      continue;
    }

    $label = empty($product->barcode) ? $product->ref : $product->barcode;
    $barcode = null;
    if (!empty($product->barcode)) {
      $barcode = [
        'barcode_type' => $product->barcode_type,
        'code' => $product->barcode
      ];
    }

    $labels_info[] = [
      'blocks' => [
        ['label' => $label, 'barcode' => $barcode]
      ]
    ];
  }

  return $labels_info;
}

$labels_info = get_infos($labels_info) ?? [];

?>
<body>
  <?php print_labels($labels_info); ?>
</body>
</html>
