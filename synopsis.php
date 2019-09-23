<?php

require_once 'synopsis.civix.php';

use CRM_Synopsis_ExtensionUtil as E;
use CRM_Synopsis_BAO_Synopsis as Syn;
use CRM_Synopsis_Config as C;

function synopsis_civicrm_fieldOptions($entity, $field, &$options, $params) {

  if (class_exists('CRM_Synopsis_Config')) {
    if ($entity == 'Contact') {
      $configuration = C::singleton()->getParams();
      $availableSelectors = $configuration['field_selectors'];

      if (isset($field) && array_key_exists($field, $availableSelectors)) {
        switch ($availableSelectors[$field]) {
          case 'campaign':
            // TODO: Load all campaigns into an array in cache so that we can avoid delays
            $allCamps = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE); // Load all campaigns
            $options = $allCamps;
            break;
          default:
            $options = Syn::fetchAvailableOptionValues($availableSelectors);
        }
      }
    }
  }
}

/**
 * Implementation of hook_civicrm_pageRun
 *
 * Add link to manage synopsis
 */
function synopsis_civicrm_pageRun($page) {
  if (CRM_Core_Permission::check('administer Synopsis') && $page->getVar('_name') == 'CRM_Contact_Page_View_CustomData') {
    $configuration = C::singleton()->getParams();
    $SynopsisGID = $configuration['table_configuration']['field_table_id'];
    $contactID = $page->getVar('_contactId');
    $currentGID = $page->getVar('_groupId');
    if ($SynopsisGID == $currentGID) {

      CRM_Core_Region::instance('custom-data-view-Synopsis_Fields')->add(array(
        'markup' => '
      <a class="no-popup button" href="' . CRM_Utils_System::url('civicrm/admin/setting/synopsis') . '">
        <span>
          <i class="crm-i fa-wrench"></i>&nbsp; ' . E::ts('Configure Synopsis') . '
        </span>
      </a>
    ',
      ));
      CRM_Core_Region::instance('page-header')->add(array('template' => 'CRM/Synopsis/Custom/ContactSynopsis.tpl'));
    }
  }
}

/**
 * Implementation of hook_civicrm_permission.
 *
 * @param string or int or object... $permissions
 */
function synopsis_civicrm_permission(&$permissions) {
  $prefix = E::ts('Synopsis') . ': ';
  $permissions += [
    'administer Synopsis' => [
      $prefix . E::ts('Administer Synopsis'),
      E::ts('Administer Synopsis'),
    ],
  ];
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function synopsis_civicrm_config(&$config) {
  _synopsis_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function synopsis_civicrm_xmlMenu(&$files) {
  _synopsis_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function synopsis_civicrm_install() {
  _synopsis_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function synopsis_civicrm_postInstall() {
  $session = CRM_Core_Session::singleton();
  $tableInstallation = _synopsis_ensure_table_exists();
  C::singleton()->setParams($tableInstallation);
  $msg = E::ts("The extension is enabled. Please go to Administer -> Customize Data and Screens -> Synopsis Fields to configure it.");
  $session->setStatus($msg);
  _synopsis_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function synopsis_civicrm_uninstall() {
  $params = [
    'operation' => "delete_stored_customfields",
  ];
  _synopsis_maintenance($params);
  $params = [
    'operation' => "delete_customgroup",
  ];
  _synopsis_maintenance($params);
  $params = [
    'operation' => "delete_synopsis_config",
  ];
  _synopsis_maintenance($params);
  _synopsis_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function synopsis_civicrm_enable() {
  $tableValidation = _synopsis_ensure_table_exists();
  _synopsis_civix_civicrm_enable();
  // Store the settings
  C::singleton()->setParams($tableValidation);
}

/*
 * Ensure that the customgroup is there. If not, create it
 * Returns an array of table id and table name
 *
 */

function _synopsis_ensure_table_exists() {

  $gName = 'Synopsis_Fields';
  // Check if we already have a customgroup by that name

  try {
    $result = civicrm_api3('CustomGroup', 'get', [
      'name' => $gName
    ]);
  }
  catch (Exception $ex) {

  }
  if (isset($result['id']) && $result['id'] > 0) {
    // CustomGroup already exists, just update the configuration
    $params = [
      'table_configuration' => [
        'field_table_id' => $result['id'],
        'field_table_name' => $result['values'][$result['id']]['table_name'],
      ],
    ];
  }
  else {
    // CustomGroup doesn't exist, create it
    $table_id = Syn::synopsis_create_custom_group();
    if ($table_id) {
      try {
        $cGroups = civicrm_api3('CustomGroup', 'getsingle', [
          'sequential' => 1,
          'return' => ["table_name"],
          'id' => $table_id,
        ]);
      }
      catch (Exception $ex) {

      }
      if (isset($cGroups['table_name'])) {
        $table_name = $cGroups['table_name'];
      }
    }
    // Save the configuration
    $params = [
      'table_configuration' => [
        'field_table_id' => $table_id,
        'field_table_name' => $table_name,
      ],
    ];
  }
  // Return the array of settings
  return $params;
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function synopsis_civicrm_disable() {
  _synopsis_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function synopsis_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _synopsis_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function synopsis_civicrm_managed(&$entities) {
  _synopsis_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function synopsis_civicrm_caseTypes(&$caseTypes) {
  _synopsis_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function synopsis_civicrm_angularModules(&$angularModules) {
  _synopsis_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function synopsis_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _synopsis_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function synopsis_civicrm_entityTypes(&$entityTypes) {
  _synopsis_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 *
  function synopsis_civicrm_preProcess($formName, &$form) {

  } // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function synopsis_civicrm_navigationMenu(&$menu) {
  $path = "Administer";
  _synopsis_civix_insert_navigation_menu($menu, $path, array(
    'label' => E::ts('Synopsis Fields'),
    'name' => 'Synopsis Fields',
    'url' => 'civicrm/admin/settings/synopsis',
    'permission' => 'administer Synopsis',
    'operator' => '',
    'separator' => '0'
  ));
  _synopsis_civix_navigationMenu($menu);
}

function _synopsis_maintenance($params) {
  $results = [];

  switch ($params['operation']) {
    case 'delete_synopsis_config':
      $sql = "DELETE FROM civicrm_setting WHERE name = 'synopsis_config'";
      CRM_Core_DAO::executeQuery($sql);
      break;
    case 'delete_stored_config':
      // Delete the stored configuration
      $destructiveConfig = [];
      $formvalues['field_configuration'] = json_encode($destructiveConfig);

      try {
        $storedSettings = Civi::settings()->get('synopsis_config');
        Civi::settings()->set('synopsis_config', array_merge($storedSettings, $formvalues));
      }
      catch (Exception $ex) {
        // Throw the error to the status popup
        throw new API_Exception(/* errorMessage */ 'Error saving the configuration', $ex->getMessage());
      }
      $results['status'] = 'DB configuration has been deleted';
      break;
    case 'delete_stored_customfields':
      $cnt = 0;
      // Remove all stored customfields from CiviCRM that are under CustomGroup "Synopsis"
      // Fetch all children IDs
      try {
        $result = civicrm_api3('CustomField', 'get', ['return' => ["id"], 'custom_group_id' => "Synopsis_Fields"]);
      }
      catch (Exception $ex) {
        throw new API_Exception('Error using CustomField.get API', $ex->getMessage());
      }
      // Loop over the results
      if (isset($result['count']) && $result['count'] > 0) {
        foreach ($result['values'] as $childKey) {
          if (isset($childKey['id'])) {
            $result = civicrm_api3('CustomField', 'delete', ['id' => $childKey['id']]);
          }
          $cnt++;
        }
      }
      $results['status'] = ($cnt > 0) ? 'CustomFields deleted' : 'No CustomFields found to delete';
      $results['count'] = $cnt;
      break;
    case 'delete_customgroup':
      try {
        $resultCG = civicrm_api3('CustomGroup', 'getsingle', ['name' => "Synopsis_Fields"]);
      }
      catch (Exception $ex) {

      }
      if (isset($resultCG['id'])) {
        $result = civicrm_api3('CustomGroup', 'delete', ['id' => $resultCG['id']]);
      }
      break;
  }
  return $results;
}
