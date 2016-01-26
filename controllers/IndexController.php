<?php
/**
* Controller for ItemReferences admin pages.
*
* @package ItemReferences
*/
class ItemReferences_IndexController extends Omeka_Controller_AbstractActionController
{
  /**
  * Front admin page.
  */
  public function indexAction() {
    $this->_helper->db->setDefaultModelName('ItemReferences');
  
  }

}
