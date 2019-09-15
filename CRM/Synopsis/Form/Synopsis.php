<?php

use CRM_Synopsis_ExtensionUtil as E;
use CRM_Synopsis_BAO_Synopsis as Syn;
use CRM_Synopsis_Config as C;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Synopsis_Form_Synopsis extends CRM_Core_Form {

  public function buildQuickForm() {

    /*
     * Test area
     */
    $pseudovars = C::singleton()->getParams();


    // End of test area
    // Set optiongroups for selector
    $optGroups = [
      'names' => [
        'fundraising',
        'soft',
        'membership',
        'event_standard',
        'event_turnout',
      ],
      'titles' => [
        'Fundraising',
        'Soft Credits',
        'Membership',
        'Events (Standard)',
        'Events (Turnout)',
      ],
    ];
    $dataTypes = [
      'String',
      'Money',
      'Int',
      'Date',
      'Selector'
    ];

    // load configuration
    $settings = Civi::settings()->get('synopsis_config');

    if (is_array($settings) && !empty($settings['field_configuration'])) {
      $this->configuration = $settings['field_configuration'];
    }
    else {
      $this->configuration = '{
        "Label": "",
        "DataType": "String",
        "Query": "",
        "Optgroup":"fundraising",
        "Graph": "",
        "Remove": "",
        "SelectRef": "none",
        "MName": "",
        "CFID": "",
        "Weight": "",
        "column_name":""
      }';
    }

    $json_editor_mode = 'text';
    $this->assign('json_editor_mode', $json_editor_mode);

    // add form elements
    $this->assign('optGroups', json_encode($optGroups));
    $this->assign('dataTypes', json_encode($dataTypes));
    $this->assign('availOptGroups', json_encode(Syn::fetchAvailableOptionGroups()));

    $this->add('hidden', 'configuration', $this->configuration);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());

    // add JSONEditor resources
    $resources = CRM_Core_Resources::singleton();
    // Add the JSON editor package
    $resources->addScriptFile('synopsis', 'resources/jsoneditor/jsoneditor-1.3.5.min.js');
    $resources->addStyleFile('synopsis', 'resources/css/synopsis_jsoneditor.css');

    parent::buildQuickForm();
  }

  public function postProcess() {

    // Basic stuff
    $settings = CRM_Synopsis_Config::singleton()->getParams();
    $synTableID = $settings['table_configuration']['field_table_id'];

    // Get the values from the form
    $values = $this->exportValues();
    // We have to decode the json values
    $decodedValues = json_decode($values['configuration'], TRUE);
    // Variable declaration
    $formvalues = [];

    // Walk within the array
    foreach ($decodedValues as $cfkey => $cfdata) {

      // We need to check the data_type selected fetch the field parameters
      $fieldParams = Syn::getFieldParams($cfdata['DataType']);

      // Setup our variable with the field parameters
      $cfieldParams = [
        'name' => CRM_Utils_String::munge($cfdata['Label'], '_', 64), // We'll get the label and convert it to machine name
        'label' => $cfdata['Label'],
        'is_searchable' => 1,
        'is_view' => 1,
        'custom_group_id' => $synTableID
      ];
      if (is_array($fieldParams)) {
        $cfieldParams = array_merge($cfieldParams, $fieldParams);
      }

      // Set the action
      if (isset($cfdata['CFID']) && $cfdata['CFID'] == 0 && $cfdata['Remove'] == 0) {
        // Custom field addition
        $action = CRM_Core_Action::ADD;
      }
      elseif (isset($cfdata['CFID']) && $cfdata['CFID'] > 0 && $cfdata['Remove'] == 0) {
        // Custom field update
        $cfieldParams['id'] = $cfdata['CFID'];
        $action = CRM_Core_Action::UPDATE;
      }
      elseif (isset($cfdata['CFID']) && $cfdata['CFID'] > 0 && $cfdata['Remove'] == 1) {
        // Custom field deletion
        $action = CRM_Core_Action::DELETE;
        $removeField = TRUE;
      }
      $removeField = FALSE;

      if ($action != CRM_Core_Action::DELETE) {
        try {
          // Create or Update
          $result = civicrm_api3('CustomField', 'create', $cfieldParams);
        }
        catch (Exception $ex) {
          CRM_Core_Session::setStatus(E::ts('Error during field creation: %1', [1 => $ex->getMessage()]), E::ts('Failed'));
        }
      }
      else {
        try {
          // Delete
          $result = civicrm_api3('CustomField', 'delete', ['id' => $cfdata['CFID']]);
          unset($decodedValues[$cfkey]);
        }
        catch (Exception $ex) {
          CRM_Core_Session::setStatus(E::ts('Error during field deletion: %1', [1 => $ex->getMessage()]), E::ts('Failed'));
        }
      }

      if ($result['is_error'] == 0 && $action != CRM_Core_Action::DELETE) {
        $decodedValues[$cfkey]['CFID'] = $result['id'];
        $decodedValues[$cfkey]['MName'] = $result['values'][$result['id']]['name'];
        $decodedValues[$cfkey]['Weight'] = $result['values'][$result['id']]['weight'];
        $decodedValues[$cfkey]['column_name'] = $result['values'][$result['id']]['column_name'];
      }
    }

    // Sort the multidimensional array based on the value of the field `weight`
    // source: https://stackoverflow.com/a/2699159/4329327
    usort($decodedValues, function($a, $b) {
      return $a['Weight'] <=> $b['Weight'];
    });

    // Encode the configuration into a valid json object
    $formvalues['field_configuration'] = json_encode($decodedValues);

    try {
      // Don't use `Civi::settings()->set` directly as it will override the stored settings!
      // We're using a tree like structure to minimize the stored DB variables
      CRM_Synopsis_Config::singleton()->setParams($formvalues);
    }
    catch (Exception $ex) {
      // Throw the error to the status popup
      CRM_Core_Session::setStatus(E::ts('Error saving the configuration: %1', [1 => $ex->getMessage()]), E::ts('Failed'));
    }

    CRM_Core_Session::setStatus(E::ts('Field configuration has been saved', ['domain' => 'synopsis']), 'Synopsis field configuration', 'success');
    parent::postProcess();
    // Redirect so that the same form reloads, otherwise there appears to be a glitch
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/settings/synopsis'));
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
