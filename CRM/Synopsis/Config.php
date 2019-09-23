<?php

use CRM_Synopsis_ExtensionUtil as E;

class CRM_Synopsis_Config {

  private static $_singleton = NULL;
  private $params = [];

  public static function &singleton() {
    if (self::$_singleton === NULL) {
      // first, attempt to get configuration object from cache
      $cache = CRM_Utils_Cache::singleton();
      self::$_singleton = $cache->get('CRM_Synopsis_Config');
      // if not in cache, fire off config construction
      if (!self::$_singleton) {
        self::$_singleton = new CRM_Synopsis_Config();
        self::$_singleton->_initialize();
        $cache->set('CRM_Synopsis_Config', self::$_singleton);
      }
      else {
        self::$_singleton->_initialize();
      }
    }
    return self::$_singleton;
  }

  private function _initialize() {
    $settings = Civi::settings()->get('synopsis_config');

    $this->params['field_config'] = self::getStoredConfig('field_configuration', $settings);
    $this->params['field_selectors'] = self::getSelectorFields($settings);
    $this->params['global_config'] = self::getStoredConfig('global_config', $settings);
    $this->params['table_configuration'] = self::getStoredConfig('table_id', $settings);
  }

  /*
   * Depending on the options, it will return back an array of values or a string
   * that can be used for further settings
   */

  private function getStoredConfig($options = NULL, $settings) {
    $StoredConfig = '';

    if (!is_null($options)) {
      switch ($options) {
        case 'field_configuration':
          if (is_array($settings) && !empty($settings['field_configuration'])) {
            $StoredConfig = json_decode($settings['field_configuration'], TRUE);
          }
          break;
        case 'field_settings':
          if (is_array($settings) && !empty($settings['field_settings'])) {
            $StoredConfig = json_decode($settings['field_settings'], TRUE);
          }
          break;
        case 'global_config':
          if (is_array($settings) && !empty($settings['global_configuration'])) {
            if (is_array($settings['global_configuration'])) {
              $StoredConfig = $settings['global_configuration'];
            }
            else {
              $StoredConfig = json_decode($settings['global_configuration'], TRUE);
            }
          }
          break;
        case 'table_id':
          if (is_array($settings) && !empty($settings['table_configuration']['field_table_id']) && is_array($settings) && !empty($settings['table_configuration']['field_table_name'])) {
            $StoredConfig = [
              'field_table_name' => $settings['table_configuration']['field_table_name'],
              'field_table_id' => $settings['table_configuration']['field_table_id'],
            ];
          }
          break;
      }
    }
    return $StoredConfig;
  }

  /*
   * Reads the stored configuration and brings back the customfields that have been set as 'Selectors'
   *
   */

  private function getSelectorFields($settings) {
    $allFields = json_decode($settings['field_configuration'], TRUE);
    $selectorFields = [];
    if (is_array($allFields) && count($allFields) > 0) {
      foreach ($allFields as $fkey => $fdata) {
        if (isset($fdata['DataType']) && $fdata['DataType'] == 'Selector') {
          $selectorFields['custom_' . $fdata['CFID']] = $fdata['SelectRef'];
        }
      }
    }
    return $selectorFields;
  }

  public function getParams($param = '') {
    if (!empty($param)) {
      return isset($this->params[$param]) ? $this->params[$param] : NULL;
    }
    else {
      return $this->params;
    }
  }

  public function setParams($params = []) {
    // We need to make sure that we don't override other parameter
    $storedSettings = Civi::settings()->get('synopsis_config');
    if (is_array($storedSettings)) {
      Civi::settings()->set('synopsis_config', array_merge($storedSettings, $params));
    }
    else {
      Civi::settings()->set('synopsis_config', array_merge($params));
    }
  }

  private function isJSON($string) {
    return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
  }

}
