<?php $view = get_view(); ?>
<div class="field">
  <div class="two columns alpha">
    <?php echo $view->formLabel('item_references_select', __('Reference Elements')); ?>
  </div>
  <div class="inputs five columns omega">
    <p class="explanation"><?php echo __('Select elements to transform into item references, i.e. that should represent references to other items.'); ?></p>
    <?php
      echo $view->formSelect('item_references_select',
        $itemReferencesSelect,
        array('multiple' => true, 'size' => 10),
        $elements
      );
    ?>
  </div>
  <div class="two columns alpha">
    <?php echo $view->formLabel('item_references_second_level', __('2nd Level References')); ?>
  </div>
  <div class="inputs five columns omega">
    <p class="explanation">
      <?php echo __("Check this to follow second level references, i.e. check if referenced items reference other items."); ?><br>
      <?php echo __("<em>Please note:</em> The second level is the last level of references to be taken into account. Higher level references are not supported."); ?><br>
      <?php
        echo $view->formCheckbox('item_references_second_level',
          1,
          array('checked' => $itemReferencesSecondLevel)
        );
      ?>
    </p>
  </div>
<?php if ($itemReferencesSelect) { ?>
  <div class="two columns alpha">
    <?php echo $view->formLabel('item_references_configure', __('Reference Element Configuration')); ?>
  </div>
  <div class="inputs five columns omega">
    <p class="explanation">
      <?php
        echo __('Click button below to configure the reference representation (e.g. to display reference geolocations together in a map).');
      ?>
    </p>
    <p>
      <?php
        echo "<a href='$configPage2Url' id='item_references_configure' class='green button'>" . __("Configure") . "</a>";
      ?>
    </p>
  </div>
<?php } ?>
</div>
