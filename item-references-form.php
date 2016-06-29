<?php
  $view = get_view();

  if (!defined("LITYLOADED")) {
    queue_css_file("lity.min");
    queue_js_file('lity.min');
    DEFINE("LITYLOADED", 1);
  }

  queue_css_file('item-references');
  queue_js_string("
    var itemReferencesUrl = ".json_encode(url('item-references/lookup/')).";
  ");
  queue_js_file('itemreferences');

?>
<div id="item-reference-selector" style="overflow: auto; padding: 20px; border-radius: 6px; background: #fff" class="lity-hide">
      <p><label for="new_relation_object_item_type_id_reference"><?php echo __('Item Types'); ?>: </label>
      <?php echo $view->formSelect('new_relation_object_item_type_id_reference', null, array('multiple' => false), $itemTypesList); ?></p>

      <p><?php echo __('Item Sort'); ?>:
          <fieldset>
              <input type="radio" name="itemsListsortReference" id="new_selectObjectsort_timestamp_reference" value="timestamp_reference" checked>
              <label for="selectObjectSortTimeStamp"><?php echo __("Most recently updated"); ?></label>
              <input type="radio" name="itemsListsortReference" id="new_selectObjectsort_name_reference" value="name_reference">
              <label for="selectObjectSortName"><?php echo __("Alphabetically"); ?></label>
          </fieldset>
      </p>

      <p><?php echo __('Object Title'); ?>: <span id="object_title_reference"></span></p>
      <input id="new_reference_object_item_id_reference" type="hidden">
      <label for="partial_object_title_reference"><?php echo __('Partial Object Title'); ?>: </label>
      <input id="partial_object_title_reference">

      <br>
      <ul class="pagination">
          <li id="selector-previous-page-reference" class="pg_disabled pagination_previous"><a href="#">&lt;</a></li>
          <li id="selector-next-page-reference" class="pg_disabled pagination_next"><a href="#">&gt;</a></li>
      </ul>

      <br>
      <ul id="lookup-results-reference"></ul>
  <a href="#" id="select_item" class="green button" data-lity-close><?php echo __('Select'); ?></a>
  </div>
