<?php

use CRM_Synopsis_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Synopsis_Upgrader extends CRM_Synopsis_Upgrader_Base {

  public function install() {
    $this->setdefaultvalues();
    return true;
  }

  private function setdefaultvalues() {

    $availableFinTypes = $availableEventTypes = [];
    // Get available financial types
    CRM_Core_PseudoConstant::populate($availableFinTypes, 'CRM_Financial_DAO_FinancialType', $all = TRUE);
    // Get available event types
    $availableEventTypes = CRM_Core_OptionGroup::values('event_type', FALSE, FALSE, FALSE, NULL, 'label', $onlyActive = FALSE);

    $defaults = [
      'financial_type_ids' => array_keys($availableFinTypes),
      'mbr_financial_type_ids' => array_keys($availableFinTypes),
      'event_type_ids' => array_keys($availableEventTypes),
    ];
    $formvalues['global_configuration'] = $defaults;
    // Store the settings
    Civi::settings()->set('synopsis_config', $formvalues);
  }

}
