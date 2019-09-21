<?php

use CRM_Synopsis_ExtensionUtil as E;

/**
 * Synopsis.Maintenance API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_synopsis_Maintenance_spec(&$spec) {
  $spec['operation']['api.required'] = 1;
}

/**
 * Synopsis.Maintenance API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_synopsis_Maintenance($params) {
  $results = [];

  switch ($params['operation']) {
    case 'delete_stored_config':
      // Delete the stored configuration
      $destructiveConfig = [];
      $formvalues['field_configuration'] = json_encode($destructiveConfig);

      try {
        // Don't use `Civi::settings()->set` directly as it will override the stored settings!
        // We're using a tree like structure to minimize the stored DB variables
        CRM_Synopsis_Config::singleton()->setParams($formvalues);
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
  }
  return civicrm_api3_create_success($results, $params, 'Synopsis', 'Maintenance');
}
