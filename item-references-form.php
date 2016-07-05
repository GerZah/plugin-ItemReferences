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

  <div class="field">
      <div class="three columns alpha">
          <?php echo $view->formLabel('new_relation_object_item_type_id_reference', __('Item Types')); ?>
      </div>
      <div class="inputs nine columns omega">
          <?php echo $view->formSelect('new_relation_object_item_type_id_reference', null, array('multiple' => false), $itemTypesList); ?>
      </div>
  </div>


  <div class="field">
      <div class="three columns alpha">
          <?php echo $view->formLabel('partial_object_title_reference', __('Partial Object Title')); ?>
      </div>
      <fieldset class="inputs four columns">
              <?php echo $view->formText('partial_object_title_reference', null, array('size' => 10, 'maxlength' => 60)); ?>
      </fieldset>
      <fieldset class="inputs five columns omega">
            <div class="three columns alpha">
              <?php echo $view->formLabel('id_limit_reference', __('Limit Item IDs (“x” or “x-y”)')); ?>
            </div>
            <div class="inputs two columns omega">
              <?php echo $view->formText('id_limit_reference', null, array('size' => 10, 'maxlength' => 60)); ?>
            </div>
      </fieldset>
  </div>

  <div class="field">
      <div class="three columns alpha">
          <?php echo $view->formLabel('new_relation_item_sort', __('Item Sort')); ?>
      </div>
      <fieldset class="inputs nine columns omega">
          <div class="four columns alpha">
              <input type="radio" name="itemsListsortReference" id="new_selectObjectsort_timestamp_reference" value="timestamp_reference" checked>
              <label for="new_selectObjectsort_timestamp_reference"><?php echo __("Most recently updated"); ?></label>
          </div>
          <div class="four columns omega">
              <input type="radio" name="itemsListsortReference" id="new_selectObjectsort_name_reference" value="name_reference">
            <label for="new_selectObjectsort_name_reference"><?php echo __("Alphabetically"); ?></label>
          </div>
      </fieldset>
  </div>

  <div class="field">
      <div class="inputs two columns alpha">
          <?php echo $view->formLabel('object_title', __('Object Title')); ?>
      </div>
      <div class="inputs nine columns omega">
          <span id="object_title_reference" data-base-url="<?php echo CURRENT_BASE_URL; ?>">
            <em><?php echo __('[Search and Select Below]'); ?></em>
          </span>
      </div>
  </div>

  <input id="new_reference_object_item_id_reference" type="hidden">

  <div class="field">
      <ul class="pagination">
          <li id="selector-previous-page-reference" class="pg_disabled pagination_previous"><a href="#">&lt;</a></li>
          <li id="selector-next-page-reference" class="pg_disabled pagination_next"><a href="#">&gt;</a></li>
      </ul>
  </div>

      <br>
      <ul id="lookup-results-reference"></ul>
  <a href="#" id="select_item" class="green button" data-lity-close><?php echo __('Select'); ?></a>
  </div>
