<?php
use CRM_Synopsis_ExtensionUtil as E;

class CRM_Synopsis_Page_Dashlet extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('Dashlet'));
    if (isset($_REQUEST['cid'])) {
          $contact_id = (int) $_REQUEST['cid'];
    } else {
      $contact_id = 0;
    }
    $this->assign('contactID',$contact_id);
    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    parent::run();
  }

}
