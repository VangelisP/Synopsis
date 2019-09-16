<?php

use CRM_Synopsis_ExtensionUtil as E;

class CRM_Synopsis_BAO_Synopsis {
  /*
   * Will return an array of all optiongroups available
   * (used by datatype of type select)
   */

  public static function fetchAvailableOptionGroups() {
    $config = CRM_Core_Config::singleton();
    $availOptGroups = [];
    try {
      $allOptionGroups = civicrm_api3('OptionGroup', 'get', [
        'sequential' => 1,
      ]);
    }
    catch (Exception $ex) {

    }
    $availOptGroups['titles'][] = '-- None --';
    $availOptGroups['names'][] = 'none';
    foreach ($allOptionGroups['values'] as $opkey => $opdata) {
      $availOptGroups['titles'][] = $opdata['title'];
      $availOptGroups['names'][] = $opdata['name'];
    }

    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    if ($campaignEnabled) {
      $availOptGroups['titles'][] = 'Campaign';
      $availOptGroups['names'][] = 'campaign';
    }

    // Add here any other optiongroups that you need to be shown to the list

    return $availOptGroups;
  }

  /*
   * Will lookup all optionValues of a specific optiongroup based on the name
   *
   * @param   array $og as ['custom_123'] = 'Gender'
   * @return  array array of all option values of that option group.
   *
   * Lookup is based on OptionGroup's machine name (name).
   *
   */

  public static function fetchAvailableOptionValues($og) {
    $value = array_shift($og);
    $availOptValues = [];

    if ($value && $value != 'none') {
      try {
        $optValsAPI = civicrm_api3('OptionValue', 'get', [
          'sequential' => 1,
          'return' => ["value", "label"],
          'option_group_id' => $value,
        ]);
      }
      catch (Exception $ex) {

      }
      if (isset($optValsAPI['values']) && is_array($optValsAPI['values'])) {
        foreach ($optValsAPI['values'] as $ovkey => $ovdata) {
          $availOptValues[$ovdata['value']] = $ovdata['label'];
        }
      }
    }
    return $availOptValues;
  }

  /*
   * Based on the dataType that we send, this function will return the
   * necessary parameters for the CustomField creation.
   *
   * @param string $dataType
   * @return array $parameters
   *
   */

  public static function getFieldParams($dataType) {
    $parameters = [];

    switch ($dataType) {
      case 'String':
        $parameters = [
          'html_type' => 'Text',
          'data_type' => 'String',
          'text_length' => 128,
        ];
        break;
      case 'Money':
        $parameters = [
          'html_type' => 'Text',
          'data_type' => 'Money',
          'text_length' => 32,
        ];
        break;
      case 'Date':
        $parameters = [
          'html_type' => 'Select Date',
          'data_type' => 'Date',
          'text_length' => 32,
          'date_format' => (!empty(Civi::settings()->get('dateInputFormat'))) ? Civi::settings()->get('dateInputFormat') : 'mm/dd/yy',
          'time_format' => Civi::settings()->get('timeInputFormat'),
        ];
        break;
      case 'Selector':
        $parameters = [
          'html_type' => 'Select',
          'data_type' => 'String',
          'text_length' => 255,
        ];
        break;
    }

    return $parameters;
  }

  /*
   * These are the parameters of the main customgroup
   */

  private function SynopsisGroup() {
    $customgroup = [
      'name' => 'Synopsis_Fields',
      'title' => E::ts('Synopsis'),
      'extends' => 'Contact',
      'style' => 'Tab',
      'collapse_display' => '0',
      'help_pre' => '',
      'help_post' => '',
      'weight' => '30',
      'is_active' => '1',
      'is_multiple' => '0',
      'collapse_adv_display' => '0',
      'optgroup' => 'fundraising',
      'version' => 3
    ];
    return $customgroup;
  }

  public static function synopsis_create_custom_group() {
    $params = self::SynopsisGroup();

    try {
      $result = civicrm_api('CustomGroup', 'create', $params);
    }
    catch (Exception $ex) {

    }
    if ($result['is_error'] == 1) {
      // Bail. No point in continuing if we can't get the table built.
      return FALSE;
    }
    else {
      if (isset($result['id'])) {
        return $result['id'];
      }
      return FALSE;
    }
  }

  /*
   * Function to construct the query for calculations and execute it
   *
   */

  public static function executeCalculations($contact_id = NULL) {
    $settings = CRM_Synopsis_Config::singleton()->getParams();
    $fieldConfig = $settings['field_config'];
    $extconfig = $settings['global_config'];
    $finTypes = [];
    $SelectClause = [];
    $failed = false;
    $tmpTable = 'civicrm_synopsis_tmp_' . uniqid();


    foreach ($fieldConfig as $fieldKey => $fieldData) {
      // Do a simple check to see if we have valid information
      if (isset($fieldData['Query']) &&
        !empty($fieldData['Query']) &&
        isset($fieldData['column_name']) &&
        !empty($fieldData['column_name'])) {
        $SelectClause[] = "(" . $fieldData['Query'] . ") AS " . $fieldData['column_name'];
      }
    }

    // Construct our query
    // Try to use a more civicrm oriented way to build a tmp table
    $baseSql = "
      CREATE TEMPORARY TABLE {$tmpTable}
      (INDEX cmpd_key (entity_id))
      SELECT
        c.id as entity_id,";
    $baseSql .= implode(', ', $SelectClause);
    if (!empty($contact_id)) {
      $whereClause = " WHERE c.id = {$contact_id} ";
    }
    else {
      $whereClause = '';
    }

    $postSQL = "
      FROM civicrm_contact c
      {$whereClause}
      GROUP BY c.id";

    // Final SQL query
    $structuredSQL = $baseSql . $postSQL;

    // Do some token replacement
    $structuredSQL = self::synopsis_replace_tokens($structuredSQL, $extconfig); 
    
    try {
      $dao = CRM_Core_DAO::executeQuery($structuredSQL);
    }
    catch (Exception $ex) {
      $failed = true;
    }

    $sqlData = [
      'temp_table' => $tmpTable,
      'SQL' => $structuredSQL,
      'failed' => $failed
    ];

    // Calculation has finished, return
    return $sqlData;
  }

  /*
   * Call to store the previously executed calculation(s)
   */

  public static function StoreCalculations($tmpTable, $contact_id = NULL) {
    $updated = FALSE;
    if (!$tmpTable) {
      return;
    }
    $settings = CRM_Synopsis_Config::singleton()->getParams();
    $fieldConfig = $settings['field_config'];
    $table_name = $settings['table_configuration']['field_table_name'];

    $tblColumns = [];
    // Get a clean array of column_names
    foreach ($fieldConfig as $fkey => $fdata) {
      if (isset($fdata['column_name']) && !empty($fdata['column_name'])) {
        $tblColumns[] = $fdata['column_name'];
      }
    }

    // Move temp data into custom field table
    if (is_null($contact_id)) {
      // Truncate the table first
      $truncateSynopsisSQL = "TRUNCATE TABLE `{$table_name}`";
      $dao = CRM_Core_DAO::executeQuery($truncateSynopsisSQL);
      // And replace everything
      $query = "INSERT INTO `$table_name` "
        . "(entity_id, " . implode(',', $tblColumns) . ") "
        . "SELECT entity_id, " . implode(',', $tblColumns) . " FROM `{$tmpTable}` t1 ";
      $query = rtrim($query, ',');

      try {
        $dao = CRM_Core_DAO::executeQuery($query);
      }
      catch (Exception $ex) {

      }
    }
    else {
      // Update a specific contact ID only
      $query = "INSERT INTO `$table_name` "
        . "(entity_id, " . implode(',', $tblColumns) . ") "
        . "SELECT entity_id, " . implode(',', $tblColumns) . " FROM `{$tmpTable}` t1 "
        . " ON DUPLICATE KEY UPDATE ";
      foreach ($tblColumns as $tblKey) {
        $query .= "{$tblKey} = t1.{$tblKey},";
      }

      $query = rtrim($query, ',');
      try {
        $dao = CRM_Core_DAO::executeQuery($query);
      }
      catch (Exception $ex) {

      }
    }
    if (isset($dao->N)) {
      $updated = TRUE;
    }
    return $updated;
  }

  public static function synopsis_get_types($type = NULL) {
    if (!empty($type)) {
      switch ($type) {
        case 'financial':
          $values = [];
          CRM_Core_PseudoConstant::populate($values, 'CRM_Financial_DAO_FinancialType', $all = TRUE);
          break;
        case 'events':
          $values = CRM_Core_OptionGroup::values('event_type', FALSE, FALSE, FALSE, NULL, 'label', $onlyActive = FALSE);
          break;
        case 'participants':
          $values = [];
          CRM_Core_PseudoConstant::populate($values, 'CRM_Event_DAO_ParticipantStatusType', $all = TRUE);
          break;
      }
    }
    return $values;
  }

  /**
   * Helper script - report if Component is enabled
   * */
  public static function check_component_enabled($component) {
    static $config;
    if (is_null($config)) {
      $config = CRM_Core_Config::singleton();
    }
    return in_array($component, $config->enableComponents);
  }

  /*
   * Helper function to do the replacement of tokens
   */

  public static function synopsis_replace_tokens($query, $config) {
    // Replace contact_id
    $replacedSQL = str_replace('{contact_id}', 'c.id', $query);
    // Check to see if we have financial types
    if (is_array($config['financial_type_ids'])) {
      $replacedSQL = str_replace('{financial_types}', implode(',', $config['financial_type_ids']), $replacedSQL);
    }
    else {
      // If none selected, include all financial types
      $finTypes = self::synopsis_get_types('financial');
      $replacedSQL = str_replace('{financial_types}', implode(',', $finTypes), $replacedSQL);
    }
    $fiscal_dates = self::synopsis_get_fiscal_dates();
    // Do fiscal date replacement
    foreach ($fiscal_dates as $dkey => $dval) {
      $replacedSQL = str_replace($dkey, $dval, $replacedSQL);
    }

    return $replacedSQL;
  }

  /**
   * Based on the civicrm fiscal date setting, determine the dates for the
   * various begin and end fiscal year dates needed by the rewrite function.
   * Borrowed from https://github.com/progressivetech/net.ourpowerbase.sumfields/blob/master/sumfields.php#L233
   * All credits goes to Jamie McClelland
   * */
  private function synopsis_get_fiscal_dates() {

    $ret = array(
      '{current_fiscal_year_begin}' => NULL,
      '{current_fiscal_year_end}' => NULL,
      '{last_fiscal_year_begin}' => NULL,
      '{last_fiscal_year_end}' => NULL,
      '{year_before_last_fiscal_year_begin}' => NULL,
      '{year_before_last_fiscal_year_end}' => NULL,
    );
    $config = CRM_Core_Config::singleton();

    // These are returned as not zero-padded numbers,
    // e.g. 1 and 1 or 9 and 1
    $fiscal_month = self::synopsis_zero_pad($config->fiscalYearStart['M']);
    $fiscal_day = self::synopsis_zero_pad($config->fiscalYearStart['d']);

    $this_calendar_year_fiscal_year_begin_ts = strtotime(date('Y') . '-' . $fiscal_month . '-' . $fiscal_day);
    $now = time();
    if ($now < $this_calendar_year_fiscal_year_begin_ts) {
      // We need to adjust the current fiscal year back one year. For example, it's Feb 3
      // and the fiscal year begins Sep 1, the current fiscal year started Sep 1 of the
      // last calendar year.
      $current_fiscal_year_begin_ts = strtotime('-1 year', $this_calendar_year_fiscal_year_begin_ts);
      $current_fiscal_year_end_ts = strtotime('-1 day', $this_calendar_year_fiscal_year_begin_ts);
      $last_fiscal_year_begin_ts = strtotime('-2 year', $this_calendar_year_fiscal_year_begin_ts);
      $last_fiscal_year_end_ts = strtotime('-1 year -1 day', $this_calendar_year_fiscal_year_begin_ts);
      $year_before_last_fiscal_year_begin_ts = strtotime('-3 year', $this_calendar_year_fiscal_year_begin_ts);
      $year_before_last_fiscal_year_end_ts = strtotime('-2 year -1 day', $this_calendar_year_fiscal_year_begin_ts);
    }
    else {
      $current_fiscal_year_begin_ts = $this_calendar_year_fiscal_year_begin_ts;
      $current_fiscal_year_end_ts = strtotime('+1 year -1 day', $this_calendar_year_fiscal_year_begin_ts);
      $last_fiscal_year_begin_ts = strtotime('-1 year', $this_calendar_year_fiscal_year_begin_ts);
      $last_fiscal_year_end_ts = strtotime('-1 day', $this_calendar_year_fiscal_year_begin_ts);
      $year_before_last_fiscal_year_begin_ts = strtotime('-2 year', $this_calendar_year_fiscal_year_begin_ts);
      $year_before_last_fiscal_year_end_ts = strtotime('-1 year -1 day', $this_calendar_year_fiscal_year_begin_ts);
    }
    return array(
      '{current_fiscal_year_begin}' => date('Y-m-d', $current_fiscal_year_begin_ts),
      '{current_fiscal_year_end}' => date('Y-m-d', $current_fiscal_year_end_ts),
      '{last_fiscal_year_begin}' => date('Y-m-d', $last_fiscal_year_begin_ts),
      '{last_fiscal_year_end}' => date('Y-m-d', $last_fiscal_year_end_ts),
      '{year_before_last_fiscal_year_begin}' => date('Y-m-d', $year_before_last_fiscal_year_begin_ts),
      '{year_before_last_fiscal_year_end}' => date('Y-m-d', $year_before_last_fiscal_year_end_ts),
    );
  }

  /**
   * Utility function for calculating fiscal years
   * */
  private function synopsis_zero_pad($num) {
    if (strlen($num) == 1)
      return '0' . $num;
    return $num;
  }

}
