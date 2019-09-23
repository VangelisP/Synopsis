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
      $params = [
        'operation' => "delete_stored_config",
      ];
      $status = _synopsis_maintenance($params);

      $results['status'] = 'DB configuration has been deleted';
      break;
    case 'delete_stored_customfields':
      $params = [
        'operation' => "delete_stored_customfields",
      ];
      $results = _synopsis_maintenance($params);

      break;
  }
  return civicrm_api3_create_success($results, $params, 'Synopsis', 'Maintenance');
}
