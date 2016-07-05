<?php
/**
 * Item References
 * LookupController
 */

class ItemReferences_LookupController extends Omeka_Controller_AbstractActionController
{
    public function indexAction()
    {
      $db = get_db();

      if (!$this->_hasParam('partialReference')) {
          $this->_setParam('partialReference', '');
      }
        if (!$this->_hasParam('id_limitReference')) {
            $this->_setParam('id_limitReference', '');
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
      $where_text = '';
      if (strlen($partial) > 0) {
          $where_text = 'AND text RLIKE ' . $db->quote($partial);
      }

        $where_id_limitReference = '';
        if (preg_match("/\s*(\d+)(?:-(\d+))?\s*/", $this->_getParam('id_limitReference'), $matches)) {
          $fromId = $matches[1];
          $toId = @$matches[2];
          if (!$toId) { $toId = $fromId; }
          if ($fromId > $toId) {
            $tmpId = $toId;
            $toId = $fromId;
            $fromId = $tmpId;
          }
          $where_id_limitReference = "AND items.id BETWEEN $fromId AND $toId";
        }

      $item_type = intval($this->_getParam('item_typeReference'));
      $where_item_type = '';
      if ($item_type > 0) {
          $where_item_type = "AND items.item_type_id = $item_type";
      }

      $per_page = intval($this->_getParam('per_pageReference'));
      $page = intval($this->_getParam('pageReference'));
      $offset = $page * $per_page;

      $order_clause = 'ORDER BY items.item_type_id ASC, text ASC';
      switch ($this->_getParam('sortReference')) {
      case 'mod_desc_ref':
          $order_clause = 'ORDER BY UNIX_TIMESTAMP(modified) DESC, items.item_type_id ASC, text ASC';
          break;
      case 'mod_asc_ref':
          $order_clause = 'ORDER BY UNIX_TIMESTAMP(modified) ASC, items.item_type_id ASC, text ASC';
          break;
      case 'alpha_desc_ref':
          $order_clause = 'ORDER BY items.item_type_id ASC, text DESC';
          break;
      case 'alpha_asc_ref':
          $order_clause = 'ORDER BY items.item_type_id ASC, text ASC';
          break;
      default:
          /* do nothing */
          break;
      }

      $titleId = 50;
      $query = <<<QCOUNT
SELECT count(*) AS count
FROM {$db->Item} items
LEFT JOIN {$db->Element_Texts} elementtexts
ON (items.id = elementtexts.record_id) AND (elementtexts.record_type = 'Item')
WHERE elementtexts.element_id = $titleId
$where_item_type
$where_text
$where_id_limitReference
GROUP BY elementtexts.record_id
QCOUNT;
      $m_count = count($db->fetchAll($query));

      $max_page = floor($m_count / $per_page);
      if ($page > $max_page) {
          $page = $max_page;
          $offset = $page * $per_page;
      }

      $query = <<<QUERY
SELECT items.id AS id, text
FROM {$db->Item} items
LEFT JOIN {$db->Element_Texts} elementtexts
ON (items.id = elementtexts.record_id) AND (elementtexts.record_type = 'Item')
WHERE elementtexts.element_id = $titleId
$where_item_type
$where_text
$where_id_limitReference
GROUP BY elementtexts.record_id
$order_clause
LIMIT $per_page
OFFSET $offset
QUERY;
      $items = $db->fetchAll($query);
      $m_items = array();

      foreach ($items as $item) {
          $m_items[] = array(
              'value' => $item['id'],
              'label' => $item['text'],
          );
      }

      $metadata = array(
          'count' => $m_count,
          'items' => $m_items,
      );

      $this->_helper->json($metadata);


}
}
