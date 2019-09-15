<?php

use CRM_Synopsis_ExtensionUtil as E;

/**
 * Synopsis.Calculate API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_synopsis_Calculate_spec(&$spec) {
  $spec['contact_id'] = array(
    'title' => E::ts('Contact ID'),
    'description' => E::ts('If specified, calculator will calculate for a specific contact. Numbers only'),
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  );
}

/**
 * Synopsis.Calculate API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_synopsis_Calculate($params) {
  $single = FALSE;
  if (isset($params['contact_id']) && is_numeric($params['contact_id'])) {
    $CalculateValues = CRM_Synopsis_BAO_Synopsis::executeCalculations($params['contact_id']);
    $single = TRUE;
  }
  else {
    $CalculateValues = CRM_Synopsis_BAO_Synopsis::executeCalculations();
  }

  if (!$CalculateValues['failed'] && !$single) {
    $returnValues = CRM_Synopsis_BAO_Synopsis::StoreCalculations($CalculateValues['temp_table']);
  }
  elseif (!$CalculateValues['failed'] && $single) {
    // This is a single contact request. We'll pass the contact_id as 2nd parameter
    $returnValues = CRM_Synopsis_BAO_Synopsis::StoreCalculations($CalculateValues['temp_table'], $params['contact_id']);
  }

  return civicrm_api3_create_success($returnValues, $params, 'Synopsis', 'Calculate');
}
