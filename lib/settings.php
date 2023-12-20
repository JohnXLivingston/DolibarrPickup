<?php
/* Copyright (C) 2023		John Livingston		<license@john-livingston.fr>
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
 */

$langs->loadLangs(array("pickup@pickup", "products", "productbatch", "other"));

function getPickupSettingsTables() {
  global $langs;
  return [
    'main' => $langs->trans('PickupSetup'),
    'product' => $langs->trans('Product'),
    'units' => $langs->trans('PickupSetupUnits'),
    'pickupline_description' => $langs->trans('PickupSetupLineDescription'),
    'batch' => $langs->trans('PickupSetupBatch'),
    'printable_label' => $langs->trans('PickupSetupPrintableLabel'),
    'specific_mode' => $langs->trans('PickupSpecificMode')
  ];
}

function getPickupSettings() {
  global $conf, $langs;
  return array(
    'PICKUP_USE_PICKUP_TYPE' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean'),
    'PICKUP_DEFAULT_STOCK' => array('table' => 'main', 'enabled'=>1),
    'PICKUP_ALLOW_FUTURE' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean'),
    'PICKUP_USE_PCAT' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean'),
    'PICKUP_USE_DEEE' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean', 'extrafields' => array('pickup_deee', 'pickup_deee_type')),
    'PICKUP_NO_SIGN_STATUS' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean'),
    'PICKUP_SEND_MAIL' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean'),
    'PICKUP_IMPORTEXPORT_ALL' => array('table' => 'main', 'enabled' => 1, 'type' => 'boolean'),
  
    'PICKUP_PRODUCT_DEFAULT_TOSELL' => array('table' => 'product', 'enabled' => 1, 'type' => 'boolean'),
    'PICKUP_PRODUCT_REF_AUTO' => array('table' => 'product', 'enabled' => 1, 'type' => 'boolean'),
    'PICKUP_USE_PBRAND' => array('table' => 'product', 'enabled' => 1, 'type' => 'boolean', 'extrafields' => array('pickup_pbrand')),
  
    'PICKUP_UNITS_WEIGHT' => array(
      'table' => 'units',
      'enabled' => 1,
      'type' => 'select',
      'label' => $langs->trans('Weight'),
      'options' => array(
        '0' => $langs->trans('Disabled'),
        'optional' =>  $langs->trans('Enabled'),
        'mandatory' => $langs->trans('Enabled') . ' / ' . $langs->trans('Mandatory')
      )
    ),
    'PICKUP_UNITS_LENGTH' => array(
      'table' => 'units',
      'enabled' => 1,
      'type' => 'select',
      'label' => $langs->trans('Length'),
      'options' => array(
        '0' => $langs->trans('Disabled'),
        'optional' =>  $langs->trans('Enabled'),
        'mandatory' => $langs->trans('Enabled') . ' / ' . $langs->trans('Mandatory')
      )
    ),
    'PICKUP_UNITS_SURFACE' => array(
      'table' => 'units',
      'enabled' => 1,
      'type' => 'select',
      'label' => $langs->trans('Surface'),
      'options' => array(
        '0' => $langs->trans('Disabled'),
        'optional' =>  $langs->trans('Enabled'),
        'mandatory' => $langs->trans('Enabled') . ' / ' . $langs->trans('Mandatory')
      )
    ),
    'PICKUP_UNITS_VOLUME' => array(
      'table' => 'units',
      'enabled' => 1,
      'type' => 'select',
      'label' => $langs->trans('Volume'),
      'options' => array(
        '0' => $langs->trans('Disabled'),
        'optional' =>  $langs->trans('Enabled'),
        'mandatory' => $langs->trans('Enabled') . ' / ' . $langs->trans('Mandatory')
      )
    ),
    'PICKUP_UNITS_PIECE' => array(
      'table' => 'units',
      'enabled' => 1,
      'type' => 'select',
      'label' => $langs->trans('unitP'),
      'options' => array(
        '0' => $langs->trans('Disabled'),
        '1' =>  $langs->trans('Enabled'),
      )
    ),
    'PICKUP_UNITS_EDIT_MODE' => array(
      'table' => 'units',
      'enabled' => 1,
      'type' => 'select',
      'options' => array(
        '0' => $langs->trans('PICKUP_UNITS_EDIT_MODE_OPTIONS_0'),
        'pickupline' => $langs->trans('PICKUP_UNITS_EDIT_MODE_OPTIONS_pickupline')
      )
    ),
  
    'PICKUP_USE_PICKUPLINE_DESCRIPTION' => array('table' => 'pickupline_description', 'enabled' => 1, 'type' => 'boolean'),
    'PICKUP_USE_PICKUPLINE_DESCRIPTION_IN_PDF' => array('table' => 'pickupline_description', 'enabled' => 1, 'type' => 'boolean'),
    'PICKUP_USE_PICKUPLINE_DESCRIPTION_ON_UNIQUE_PL' => array(
      'table' => 'pickupline_description',
      'enabled' => !empty($conf->productbatch->enabled),
      'type' => 'boolean',
      'extrafields' => array('pickup_note')
    ),
    'PICKUP_USE_PICKUPLINE_DESCRIPTION_ON_PL' => array(
      'table' => 'pickupline_description',
      'enabled' => !empty($conf->productbatch->enabled),
      'type' => 'boolean',
    ),
  
    'PICKUP_DEFAULT_HASBATCH' => array(
      'table' => 'batch',
      'enabled' => !empty($conf->productbatch->enabled),
      'type' => 'select',
      'label' => $langs->trans('ManageLotSerial'),
      'options' => array(
        '0' => $langs->trans('PICKUP_DEFAULT_HASBATCH_OPTIONS_0'),
        'ask' =>  $langs->trans('PICKUP_DEFAULT_HASBATCH_OPTIONS_ask'),
        '1' => $langs->trans('PICKUP_DEFAULT_HASBATCH_OPTIONS_1'),
        '2' => $langs->trans('PICKUP_DEFAULT_HASBATCH_OPTIONS_2')
      )
    ),
    'PICKUP_DEFAULT_BATCH' => array(
      'table' => 'batch',
      'enabled' => !empty($conf->productbatch->enabled),
      'type' => 'select',
      'options' => [
        '0' => '',
        'pickup_ref' => $langs->trans('PICKUP_DEFAULT_BATCH_OPTIONS_PICKUP_REF'),
        'generate' => $langs->trans('PICKUP_DEFAULT_BATCH_OPTIONS_GENERATE'),
        'generate_per_product' => $langs->trans('PICKUP_DEFAULT_BATCH_OPTIONS_GENERATEPPRODUCT'),
      ]
    ),
    'PICKUP_DEFAULT_UNIQUE_BATCH' => array(
      'table' => 'batch',
      'enabled' => !empty($conf->productbatch->enabled),
      'type' => 'select',
      'options' => [
        '0' => '',
        'generate' => $langs->trans('PICKUP_DEFAULT_UNIQUE_BATCH_OPTIONS_GENERATE'),
      ]
    ),
  
    'PICKUP_USE_PRINTABLE_LABEL' => array(
      'table' => 'printable_label',
      'enabled' => true,
      'type' => 'boolean'
    ),
    'PICKUP_PRINTABLE_LABEL_PRODUCTCARD_LINK' => array(
      'table' => 'printable_label',
      'enabled' => true,
      'type' => 'select',
      'options' => [
        '0' => '-',
        'DATAMATRIX' => 'DATAMATRIX',
        'QRCODE' => 'QRCODE',
      ]
    ),
    'PICKUP_PRINTABLE_LABEL_BATCH' => array(
      'table' => 'printable_label',
      'enabled' => true,
      'type' => 'select',
      'options' => [
        '0' => '-',
        'C39' => 'C39',
        'C39+' => 'C39+',
        'C39E' => 'C39E',
        'C39E+' => 'C39E+',
        // 'S25' => 'S25',
        // 'S25+' => 'S25+',
        // 'I25' => 'I25',
        // 'I25+' => 'I25+',
        'C128' => 'C128',
        'C128A' => 'C128A',
        'C128B' => 'C128B',
        'C128C' => 'C128C',
        // 'EAN2' => 'EAN2',
        // 'EAN5' => 'EAN5',
        // 'EAN8' => 'EAN8',
        // 'EAN13' => 'EAN13',
        // 'ISBN' => 'EAN13',
        // 'UPC' => 'UPCA',
        // 'UPCE' => 'UPCE',
        // 'MSI' => 'MSI',
        // 'MSI+' => 'MSI+',
        // 'POSTNET' => 'POSTNET',
        // 'PLANET' => 'PLANET',
        'RMS4CC' => 'RMS4CC',
        'KIX' => 'KIX',
        // 'IMB' => 'IMB',
        // 'CODABAR' => 'CODABAR',
        // 'CODE11' => 'CODE11',
        // 'PHARMA' => 'PHARMA',
        // 'PHARMA2T' => 'PHARMA2T',
        'DATAMATRIX' => 'DATAMATRIX',
        'QRCODE' => 'QRCODE',
      ]
    ),

    'PICKUP_SPECIFIC_MODE' => array(
      'table' => 'specific_mode',
      'enabled' => true,
      'type' => 'select',
      'options' => [
        '0' => '',
        'ressourcerie_cinema' => 'La ressourcerie du cinéma',
      ]
    )
  );
}