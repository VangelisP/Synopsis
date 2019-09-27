<?php

use CRM_Synopsis_ExtensionUtil as E;
use CRM_Synopsis_BAO_Synopsis as Syn;
use CRM_Synopsis_Config as C;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Synopsis_Form_Config extends CRM_Core_Form {

  public function buildQuickForm() {

    // Fetch all financial types
    $availableFinTypes = Syn::synopsis_get_types('financial');
    $availableEventTypes = Syn::synopsis_get_types('events');
    $fieldLegend = [];

    // Add extra settings to fieldsets
    if (Syn::check_component_enabled('CiviContribute')) {
      $this->add('select', 'financial_type_ids', E::ts('Financial Types'), $availableFinTypes, TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));
      $fieldLegend['financial_type_ids'] = E::ts('For financial types, the proper token to use in the query is `{financial_types}`.');
    }

    if (Syn::check_component_enabled('CiviMember')) {
      $this->add('select', 'mbr_financial_type_ids', E::ts('Membership Financial Types'), $availableFinTypes, TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));
      $fieldLegend['mbr_financial_type_ids'] = E::ts('For membership financial types, the proper token to use in the query is `{mbr_financial_types}`.');
    }

    // Add extra settings to fieldsets
    if (Syn::check_component_enabled('CiviEvent')) {
      $this->add('select', 'event_type_ids', E::ts('Event Types'), $availableEventTypes, TRUE, array('multiple' => TRUE, 'class' => 'crm-select2 huge'));
      $fieldLegend['mbr_financial_type_ids'] = E::ts('For event types, the proper token to use in the query is `{event_types}`.');
    }

    $this->assign('synopsis_fieldlegend', $fieldLegend);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Save configuration'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    $storeVals = [];
    $values = $this->exportValues();
    if (isset($values['financial_type_ids'])) {
      $storeVals['financial_type_ids'] = $values['financial_type_ids'];
    }
    else {
      $storeVals['financial_type_ids'] = [];
    }
    if (isset($values['mbr_financial_type_ids'])) {
      $storeVals['mbr_financial_type_ids'] = $values['mbr_financial_type_ids'];
    }
    else {
      $storeVals['mbr_financial_type_ids'] = [];
    }    
    if (isset($values['event_type_ids'])) {
      $storeVals['event_type_ids'] = $values['event_type_ids'];
    }
    else {
      $storeVals['event_type_ids'] = [];
    }

    $formvalues['global_configuration'] = $storeVals;

    try {
      // Don't use `Civi::settings()->set` directly as it will override the stored settings!
      // We're using a tree like structure to minimize the stored DB variables
      CRM_Synopsis_Config::singleton()->setParams($formvalues);
    }
    catch (Exception $ex) {
      // Throw the error to the status popup
      CRM_Core_Session::setStatus(E::ts('Error saving the configuration: %1', [1 => $ex->getMessage()]), E::ts('Failed'));
    }

    CRM_Core_Session::setStatus(E::ts('Configuration has been saved', ['domain' => 'synopsis']), 'Synopsis field configuration', 'success');
    parent::postProcess();
  }

  function setDefaultValues() {
    $defaults = parent::setDefaultValues();
    $settings = C::singleton()->getParams();
    $storedDefaults = $settings['global_config'];
    foreach ($storedDefaults as $sdk => $sdv) {
      $defaults[$sdk] = $sdv;
    }

    return $defaults;
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
