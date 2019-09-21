<?php

use CRM_Synopsis_ExtensionUtil as E;
use CRM_Synopsis_Config as C;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Synopsis_Form_ConfigImport extends CRM_Core_Form {

  public function buildQuickForm() {
    // this is an import
    CRM_Utils_System::setTitle(E::ts("Configuration importer"));
    $this->assign("is_import", 1);

    $this->addElement(
      'file', 'config_files', E::ts('Select a valid JSON file'));

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Import'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    parent::buildQuickForm();
  }

  public function postProcess() {
    $redirect = $invalid = 0;
    try {
      // Check to see if value is empty
      $value = reset($_FILES['config_files']['name']);
      if (!empty($_FILES['config_files']) && $value) {

        // IMPORT a list of new plugins
        foreach ($_FILES['config_files']['tmp_name'] as $tmp_name) {
          // We expect a valid json format
          $data = file_get_contents($tmp_name);
          // Do a validation test first
          $ob = json_decode($data);
          if ($ob === null) {
            $invalid = 1;
          }
          if (!$invalid) {
            // Setup the proper structure
            $formvalues['field_configuration'] = $data;

            try {
              // Save the configuration settings
              //Civi::settings()->set('rm_cluster_names', $formvalues);
              CRM_Synopsis_Config::singleton()->setParams($formvalues);
              CRM_Core_Session::setStatus(E::ts('New configuration file has been imported'), E::ts('Success'));
              $redirect = 1;
            }
            catch (Exception $ex) {
              CRM_Core_Session::setStatus(E::ts('Import/update failed: %1', [1 => $ex->getMessage()]), E::ts('Failed'));
            }
          }
          else {
            CRM_Core_Session::setStatus(E::ts('File is NOT a valid JSON file, please review the file and/or structure of that file that you try to upload'), E::ts('Error'));
          }
        }
      }
      else {
        CRM_Core_Session::setStatus(E::ts('No configuration file selected'), E::ts('Error'));
      }
    }
    catch (Exception $ex) {
      CRM_Core_Session::setStatus(E::ts('Import failed: %1', [1 => $ex->getMessage()]), E::ts('Failed'));
    }
    if ($redirect) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/settings/synopsis'));
    }
    parent::postProcess();
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
