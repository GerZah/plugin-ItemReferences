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

   $db = get_db();

   if (!$this->_hasParam('partialReference')) {
       $this->_setParam('partialReference', '');
   }
   if (!$this->_hasParam('item_typeReference')) {
       $this->_setParam('item_typeReference', -1);
   }
   if (!$this->_hasParam('sortReference')) {
       $this->_setParam('sortReference', 'mod_desc_ref');
   }
   if (!$this->_hasParam('pageReference')) {
       $this->_setParam('pageReference', 0);
   }
   if (!$this->_hasParam('per_pageReference')) {
       $this->_setParam('per_pageReference', 15);
   }

   $partial = preg_replace('/[^ \.,\!\?\p{L}\p{N}\p{Mc}]/ui', '', $this->_getParam('partialReference'));
}
