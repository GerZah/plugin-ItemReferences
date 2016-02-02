<?php
  $view = get_view();
  echo $view->formHidden('configPage', 2, null, null, null);
?>
<h2><?php echo __('Reference Element Configuration'); ?></h2>
<div class="field">
  <div class="two columns alpha">
    <label for="item_references_map_height"><?php echo __('Height for Reference Map'); ?></label>
  </div>
  <div class="inputs five columns omega">
    <p class="explanation"><?php echo __('The height of the map displayed on your items/show page. If left blank, the default height of 300px will be used.'); ?></p>
    <?php echo $view->formText('item_references_map_height', $itemReferencesMapHeight); ?>
  </div>
  <div class="two columns alpha">
    <label for="item_references_representations"><?php echo __('References Representation'); ?></label>
  </div>
  <div class="inputs five columns omega">
    <p class="explanation"><?php echo __('Please select for each reference element how it should be represented. It will always be displayed in the form of a list of clickable item titles. But in addition to that, you may decide whether or not you would like to display the referenced items\' geolocations together in a map, and if the markers should be connected with a multi-segment line or not. You may also select the color in which the element\'s map markers and lines will be drawn.'); ?></p>
    <?php if ($itemReferencesSecondLevel) { ?>
    <p class="explanation"><?php echo __('<em>Please note:</em> In second level reference maps showing multiple first level reference groups of one element, the colors will be assigned automatically per refernce group; in that case, the specific color configuration for that particular element will be overridden.'); ?></p>
    <?php } ?>
  </div>
  <?php
    foreach($itemReferencesArr as $itemReference) {
      $elementId = $itemReference["id"];
      $elementName = $itemReference["name"];
      echo '<div class="two columns alpha">';
      echo "$elementName";
      echo "</div>";
      echo '<div class="inputs five columns omega">';
      echo '<div style="float:left; padding-right:1em; width:45%; border-right:thin dotted black;">';
      $defaultType = @$itemReferencesConfiguration[$elementId][0];
      $defaultType = ( $defaultType ? $defaultType : 0);
      echo $view->formRadio(
        "item_reference_type_$elementId",
        $defaultType,
        array(),
        array(
          0 => __("Reference List only"),
          1 => __("Reference Map"),
          2 => __("Reference Map with Line")
        )
      );
      echo "</div>";
      echo '<div style="float:left; padding-left:1em; width:45%;">';
      echo __("Color in Reference maps (both markers and lines):");
      // echo get_view()->formSelect('date_search_limit_fields', $LimitFields, array('multiple' => true, 'size' => 10), $searchElements);
      $defaultColor = @$itemReferencesConfiguration[$elementId][1];
      $defaultColor = ( $defaultColor ? $defaultColor : 0);
      echo $view->formSelect(
        "item_reference_color_$elementId",
        $defaultColor,
        array(), # option
        array(
          0 => __("red"),
          1 => __("orange"),
          2 => __("yellow"),
          3 => __("green"),
          4 => __("light blue"), # ltblue
          5 => __("blue"),
          6 => __("purple"),
          7 => __("pink"),
        )
      );
      echo '</div>';
      echo "</div>";
    }
  ?>
</div>
