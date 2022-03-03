<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2021-2022		John Livingston		<license@john-livingston.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   pickup     Module Pickup
 *  \brief      Pickup module descriptor.
 *
 *  \file       htdocs/pickup/core/modules/modPickup.class.php
 *  \ingroup    pickup
 *  \brief      Description and activation file for module Pickup
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Pickup
 */
class modPickup extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs,$conf;
        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 469001;
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'pickup';
        // Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
        // It is used to group modules by family in module setup page
        $this->family = "products";
        // Module position in the family on 2 digits ('01', '10', '20', ...)
        $this->module_position = '90';
        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        //$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
        // Module label (no space allowed), used if translation string 'ModulePickupName' not found (Pickup is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModulePickupDesc' not found (Pickup is name of module).
        $this->description = "Pickup Module";
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = "Pickup Module";
        $this->editor_name = 'John Livingston';
        $this->editor_url = 'https://github.com/JohnXLivingston';
        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '1.2.0';
        // Url to the file with your last numberversion of this module
        //$this->url_last_version = 'http://www.example.com/versionmodule.txt';

        // Key used in llx_const table to save module status enabled/disabled (where PICKUP is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto='pickup@pickup';
        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = array(
            // Set this to 1 if module has its own trigger directory (core/triggers)
            'triggers' => 1,
            // Set this to 1 if module has its own login method file (core/login)
            'login' => 0,
            // Set this to 1 if module has its own substitution function file (core/substitutions)
            'substitutions' => 0,
            // Set this to 1 if module has its own menus handler directory (core/menus)
            'menus' => 0,
            // Set this to 1 if module overwrite template dir (core/tpl)
            'tpl' => 0,
            // Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'barcode' => 0,
            // Set this to 1 if module has its own models directory (core/modules/xxx)
            'models' => 1,
            // Set this to 1 if module has its own theme directory (theme)
            'theme' => 0,
            // Set this to relative path of css file if module has its own css file
            'css' => array(
                //    '/pickup/css/pickup.css.php',
            ),
            // Set this to relative path of js file if module must load a js on all pages
            'js' => array(
                //   '/pickup/js/pickup.js.php',
            ),
            // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
            'hooks' => array(
                'data' => array('pickupcard')
                //   'data' => array(
                //       'hookcontext1',
                //       'hookcontext2',
                //   ),
                //   'entity' => '0',
            ),
            // Set this to 1 if features of module are opened to external users
            'moduleforexternal' => 0,
        );
        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/pickup/temp","/pickup/subdir");
        $this->dirs = array("/pickup/temp");
        // Config pages. List of php page, stored into pickup/admin directory, to use to setup module.
        $this->config_page_url = array("setup.php@pickup");
        // Dependencies
        // A condition to hide module
        $this->hidden = false;
        // List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
        $this->depends = array('always1' => 'modProduct', 'always2' => 'modSociete', 'always3' => 'modStock', 'always4' => 'modCategorie');
        $this->requiredby = array();	// List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = array();	// List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
        $this->langfiles = array("pickup@pickup");
        $this->phpmin = array(5,5);					    // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(12,0);		// Minimum version of Dolibarr required by module
        $this->warnings_activation = array();			// Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        $this->warnings_activation_ext = array();		// Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        //$this->automatic_activation = array('FR'=>'PickupWasAutomaticallyActivatedBecauseOfYourCountryChoice');
        //$this->always_enabled = true;								// If true, can't be disabled

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example: $this->const=array(1 => array('PICKUP_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
        //                             2 => array('PICKUP_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
        // );
        $this->const = array(
            // 1 => array('PICKUP_MYCONSTANT', 'chaine', 'avalue', 'This is a constant to add', 1, 'allentities', 1)
        );

        // Some keys to add into the overwriting translation tables
        /*$this->overwrite_translation = array(
            'en_US:ParentCompany'=>'Parent company or reseller',
            'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
        )*/

        if (! isset($conf->pickup) || ! isset($conf->pickup->enabled)) {
            $conf->pickup=new stdClass();
            $conf->pickup->enabled=0;
        }

        // Array to add new pages in new tabs
        $this->tabs = array();
        $this->tabs[] = array(
            'data' => 'categories_product:+pickupmobilecat:TabTitleMobilePickup:pickup@pickup:!empty($conf->global->PICKUP_USE_PCAT) && $user->rights->pickup->configure:/pickup/tabs/mobilecat.php?id=__ID__'
        );
        $this->tabs[] = array(
            'data' => 'product:+pickups:Pickups:pickup@pickup:$object->isProduct() && $user->rights->pickup->read:/pickup/tabs/product_pickups.php?id=__ID__'
        );
        // Example:
        // $this->tabs[] = array('data'=>'product:+tabname1:Title1:mylangfile@pickup:$user->rights->pickup->read:/pickup/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
        // $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@pickup:$user->rights->othermodule->read:/pickup/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        // $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
        //
        // Where objecttype can be
        // 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
        // 'contact'          to add a tab in contact view
        // 'contract'         to add a tab in contract view
        // 'group'            to add a tab in group view
        // 'intervention'     to add a tab in intervention view
        // 'invoice'          to add a tab in customer invoice view
        // 'invoice_supplier' to add a tab in supplier invoice view
        // 'member'           to add a tab in fundation member view
        // 'opensurveypoll'	  to add a tab in opensurvey poll view
        // 'order'            to add a tab in customer order view
        // 'order_supplier'   to add a tab in supplier order view
        // 'payment'		  to add a tab in payment view
        // 'payment_supplier' to add a tab in supplier payment view
        // 'product'          to add a tab in product view
        // 'propal'           to add a tab in propal view
        // 'project'          to add a tab in project view
        // 'stock'            to add a tab in stock view
        // 'thirdparty'       to add a tab in third party view
        // 'user'             to add a tab in user view

        // Dictionaries
        $this->dictionaries=array();
        /* Example:
        $this->dictionaries=array(
            'langs'=>'mylangfile@pickup',
            // List of tables we want to see into dictonnary editor
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),
            // Label of tables
            'tablib'=>array("Table1","Table2","Table3"),
            // Request to select fields
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),
            // Sort order
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),
            // List of fields (result of select to show dictionary)
            'tabfield'=>array("code,label","code,label","code,label"),
            // List of fields (list of fields to edit a record)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),
            // List of fields (list of fields for insert)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),
            // Name of columns with primary key (try to always name it 'rowid')
            'tabrowid'=>array("rowid","rowid","rowid"),
            // Condition to show each dictionary
            'tabcond'=>array($conf->pickup->enabled,$conf->pickup->enabled,$conf->pickup->enabled)
        );
        */

        // Boxes/Widgets
        // Add here list of php file(s) stored in pickup/core/boxes that contains a class to show a widget.
        $this->boxes = array(
            //  0 => array(
            //      'file' => 'pickupwidget1.php@pickup',
            //      'note' => 'Widget provided by Pickup',
            //      'enabledbydefaulton' => 'Home',
            //  ),
            //  ...
        );

        // Cronjobs (List of cron jobs entries to add when module is enabled)
        // unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
        $this->cronjobs = array(
            //  0 => array(
            //      'label' => 'MyJob label',
            //      'jobtype' => 'method',
            //      'class' => '/pickup/class/pickup.class.php',
            //      'objectname' => 'Pickup',
            //      'method' => 'doScheduledJob',
            //      'parameters' => '',
            //      'comment' => 'Comment',
            //      'frequency' => 2,
            //      'unitfrequency' => 3600,
            //      'status' => 0,
            //      'test' => '$conf->pickup->enabled',
            //      'priority' => 50,
            //  ),
        );
        // Example: $this->cronjobs=array(
        //    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->pickup->enabled', 'priority'=>50),
        //    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->pickup->enabled', 'priority'=>50)
        // );

        // Permissions provided by this module
        $this->rights = array();
        $r=0;
        // Add here entries to declare new permissions
        /* BEGIN PERMISSIONS */
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Read objects of Pickup';	// Permission label
        $this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Create Pickups';	// Permission label
        $this->rights[$r][4] = 'create';				// In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Create/Update all Pickups';	// Permission label
        $this->rights[$r][4] = 'write';				// In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Delete Pickups';	// Permission label
        $this->rights[$r][4] = 'delete';				// In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Processing';	// Permission label
        $this->rights[$r][4] = 'workflow';				// In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $this->rights[$r][5] = 'processing';				    // In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Insert in stock';	// Permission label
        $this->rights[$r][4] = 'workflow';				// In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $this->rights[$r][5] = 'stock';				    // In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Sign';	// Permission label
        $this->rights[$r][4] = 'workflow';				// In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $this->rights[$r][5] = 'sign';				    // In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Configure Pickup';	// Permission label
        $this->rights[$r][4] = 'configure';				// In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $r++;
        $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
        $this->rights[$r][1] = 'Reports';	// Permission label
        $this->rights[$r][4] = 'reports';				// In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->pickup->level1->level2)
        $r++;
        /* END PERMISSIONS */

        // Main menu entries to add
        $this->menu = array();
        $r=0;
        // Add here entries to declare new menus
        /* BEGIN TOPMENU */
        $this->menu[$r++]=array(
            'fk_menu'=>'',                          // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'top',                          // This is a Top menu entry
            'titre'=>'Pickup',
            'mainmenu'=>'pickup',
            'leftmenu'=>'',
            'url'=>'/pickup/pickupindex.php',
            'langs'=>'pickup@pickup',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000+$r,
            'enabled'=>'$conf->pickup->enabled',  // Define condition to show or hide menu entry. Use '$conf->pickup->enabled' if entry must be visible if module is enabled.
            'perms'=>'$user->rights->pickup->read',			                // Use 'perms'=>'$user->rights->pickup->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0,				                // 0=Menu for internal users, 1=external users, 2=both
        );
        /* END TOPMENU */
        /* BEGIN LEFTMENU PICKUP */
		$this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=pickup',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>'PickupMenuList',
            'mainmenu'=>'pickup',
            'leftmenu'=>'pickup_list',
            'url'=>'/pickup/pickup_list.php',
            'langs'=>'pickup@pickup',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1100+$r,
            'enabled'=>'$conf->pickup->enabled',  // Define condition to show or hide menu entry. Use '$conf->pickup->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=>'$user->rights->pickup->read',			                // Use 'perms'=>'$user->rights->pickup->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		$this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=pickup,fk_leftmenu=pickup_list',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>'PickupNew',
            'mainmenu'=>'pickup',
            'leftmenu'=>'pickup_list_new',
            'url'=>'/pickup/pickup_card.php?action=create',
            'langs'=>'pickup@pickup',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1100+$r,
            'enabled'=>'$conf->pickup->enabled',  // Define condition to show or hide menu entry. Use '$conf->pickup->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=>'$user->rights->pickup->create',			                // Use 'perms'=>'$user->rights->pickup->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=pickup',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>'PickupMobileApp',
            'mainmenu'=>'pickup',
            'leftmenu'=>'pickup_mobile',
            'url'=>'/pickup/mobile.php',
            'langs'=>'pickup@pickup',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1100+$r,
            'enabled'=>'$conf->pickup->enabled',  // Define condition to show or hide menu entry. Use '$conf->pickup->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=>'$user->rights->pickup->create',               // Use 'perms'=>'$user->rights->pickup->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=pickup',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>'PickupMenuReports',
            'mainmenu'=>'pickup',
            'leftmenu'=>'pickup_reports',
            'url'=>'/pickup/pickup_reports.php',
            'langs'=>'pickup@pickup',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1100+$r,
            'enabled'=>'$conf->pickup->enabled',  // Define condition to show or hide menu entry. Use '$conf->pickup->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=>'$user->rights->pickup->reports',               // Use 'perms'=>'$user->rights->pickup->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=pickup,fk_leftmenu=pickup_reports',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>'PickupMenuSocReports',
            'mainmenu'=>'pickup',
            'leftmenu'=>'pickup_soc_reports',
            'url'=>'/pickup/pickup_soc_reports.php',
            'langs'=>'pickup@pickup',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1100+$r,
            'enabled'=>'$conf->pickup->enabled',  // Define condition to show or hide menu entry. Use '$conf->pickup->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=>'$user->rights->pickup->reports',			                // Use 'perms'=>'$user->rights->pickup->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=pickup',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>'PickupMenuTags',
            'mainmenu'=>'pickup',
            'leftmenu'=>'pickup_tags',
            'url'=>'/pickup/mobilecat_list.php',
            'langs'=>'pickup@pickup',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1100+$r,
            'enabled'=>'$conf->pickup->enabled && !empty($conf->global->PICKUP_USE_PCAT)',  // Define condition to show or hide menu entry. Use '$conf->pickup->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            'perms'=>'$user->rights->pickup->configure',               // Use 'perms'=>'$user->rights->pickup->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
        $this->menu[$r++]=array(
            'fk_menu'=>'fk_mainmenu=pickup',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>'PickupMenuHelp',
            'mainmenu'=>'pickup',
            'leftmenu'=>'pickup_help',
            'url'=>'/pickup/documentation/public/index.html',
            'langs'=>'pickup@pickup',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1100+$r,
            'enabled'=>'$conf->pickup->enabled',  // Define condition to show or hide menu entry. Use '$conf->pickup->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
            // 'perms'=>'$user->rights->pickup->read',               // Use 'perms'=>'$user->rights->pickup->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
            
        /* END LEFTMENU PICKUP */
    }

    /**
     *  Function called when module is enabled.
     *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *  It also creates data directories
     *
     *  @param      string  $options    Options when enabling module ('', 'noboxes')
     *  @return     int             	1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $conf;

        $result=$this->_load_tables('/pickup/sql/');
        if ($result < 0) return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

        // Create extrafields during init
        //include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        //$extrafields = new ExtraFields($this->db);
        //$result1=$extrafields->addExtraField('myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'pickup@pickup', '$conf->pickup->enabled');
        //$result2=$extrafields->addExtraField('myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'pickup@pickup', '$conf->pickup->enabled');
        //$result3=$extrafields->addExtraField('myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'pickup@pickup', '$conf->pickup->enabled');
        //$result4=$extrafields->addExtraField('myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'pickup@pickup', '$conf->pickup->enabled');
        //$result5=$extrafields->addExtraField('myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'pickup@pickup', '$conf->pickup->enabled');

        $sql=array(
            "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard_pickup' AND type = 'pickup' AND entity = ".$conf->entity,
            "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_pickup','pickup',".$conf->entity." )",
            // migration for version 0.9:
            array(
                'sql' => "UPDATE ".MAIN_DB_PREFIX."pickup_pickupline as l LEFT OUTER JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product = p.rowid set l.weight = IFNULL(p.weight, 0), l.weight_units = IFNULL(p.weight_units, 0) where l.weight is null and l.weight_units is null",
                'ignoreerror' => true
            ),
            // migration for version 1.0.0:
            array(
                'sql' => "UPDATE ".MAIN_DB_PREFIX."pickup_pickupline as l LEFT OUTER JOIN ".MAIN_DB_PREFIX."product_extrafields as e ON l.fk_product = e.fk_object set l.deee = IFNULL(e.deee, 0), l.deee_type = e.type_deee where l.deee is null and l.deee_type is null",
                'ignoreerror' => true
            ),
            array(
                // Update pickup_deee_type if needed. May fail if fields does not exists.
                'sql' => "UPDATE ".MAIN_DB_PREFIX."product_extrafields SET pickup_deee = IF(pickup_deee_type is not null and pickup_deee_type != '' and pickup_deee_type != '0','1','0')",
                'ignoreerror' => true
            )
        );

        return $this->_init($sql, $options);
    }

    /**
     *  Function called when module is disabled.
     *  Remove from database constants, boxes and permissions from Dolibarr database.
     *  Data directories are not deleted
     *
     *  @param      string	$options    Options when enabling module ('', 'noboxes')
     *  @return     int                 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
