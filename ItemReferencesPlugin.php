<?php

DEFINE("ITEM_REFERENCES_MAP_HEIGHT_DEFAULT", 300);

/**
* ItemReferences plugin.
*
* @package Omeka\Plugins\ItemReferences
*/
class ItemReferencesPlugin extends Omeka_Plugin_AbstractPlugin
{
  // Define Hooks
  protected $_hooks = array(
    'initialize',
    'install',
    'uninstall',
    'after_save_item',
    'config_form',
    'config',
    'admin_head',
    'public_head',
    'admin_items_show',
    'public_items_show',
  );

  //Define Filters
  // protected $_filters = array('admin_navigation_main');

  protected $_options = array(
    'item_references_select' => "[]",
    'item_references_second_level' => true,
    'item_references_map_height' => ITEM_REFERENCES_MAP_HEIGHT_DEFAULT,
    'item_references_show_maps' => true, // obsolete
    'item_references_show_lines' => false, // obsolete
    'item_references_configuration' => "[]",
  );

  private static $_withGeoLoc; // flag -- is the GeoLocation plugin installed and active
  private static $_withSecondLevel; // flag -- do or do not follow second level references

  private static $_geoLocations = array(); // collecting geolocations of referenced items
  private static $_secondLevelGeoLocations = array(); // collecting geolocations of 2nd level referenced items

  private static $_enhancedGeoLog; // enhanced version of GeoLocations plugin supporting map overlays

  /**
  * Initialize the plugin
  */
  public function hookInitialize() {
    add_translation_source(dirname(__FILE__) . '/languages');
    $db = get_db();

    // Add filters
    $filter_names = array(
        'Display',
        'ElementInput',
    );
    $referenceElements = SELF::_retrieveReferenceElements();

    foreach($referenceElements as $element_id ) {
      $element = $db->getTable('Element')->find($element_id);
      $elementSet = $db->getTable('ElementSet')->find($element->element_set_id);
      foreach ($filter_names as $filter_name) {
        add_filter(
            array($filter_name, 'Item', $elementSet->name, $element->name),
            array($this, "filter$filter_name")
        );
      }
    }

    SELF::$_withGeoLoc = SELF::_withGeoLoc();
    SELF::$_withSecondLevel = !!intval(get_option('item_references_second_level'));
    SELF::$_enhancedGeoLog = ( (SELF::$_withGeoLoc) AND
      $db->fetchOne("SHOW COLUMNS FROM $db->Locations LIKE 'overlay'")
    );
  }

  /**
  * Retrieve the element IDs that are supposed to store references from JSON configuration variable
  */
  protected function _retrieveReferenceElements() {
    $referenceElementsJson=get_option('item_references_select');
    if (!$referenceElementsJson) { $referenceElementsJson="[]"; }
    $referenceElements = json_decode($referenceElementsJson,true);
    return $referenceElements;
  }

  /**
  * Retrieve the element configurations (map style / color) from JSON configuration vafiable
  */
  protected function _retrieveReferenceElementConfiguration() {
    $itemReferencesConfiguration = get_option('item_references_configuration');
    if (!$itemReferencesConfiguration) { $itemReferencesConfiguration="null"; }
    $itemReferencesConfiguration = json_decode($itemReferencesConfiguration,true);
    return $itemReferencesConfiguration;
  }

  /**
  * Determine whether or not the Geolocations plugin is installed
  */
  private function _withGeoLoc() {
    $db = get_db();
    return $db->fetchOne("SELECT active FROM $db->Plugins WHERE name='GeoLocation' LIMIT 1");
  }

  /**
  * Install the plugin.
  */
  public function hookInstall() {
    SELF::_installOptions();
  }

  /**
  * Uninstall the plugin.
  */
  public function hookUninstall() {
    SELF::_uninstallOptions();
  }

  protected function myAddSearchText($item, $enrichedSearchTexts) {
    // http://omeka.org/forums/topic/adding-item-search-text-from-a-plugin
    //look up the existing search text
    $searchText = $this->_db->getTable('SearchText')->findByRecord('Item', $item->id);

    // searchText should already exist, but if something goes wrong, create it
    if (!$searchText) {
      $searchText = new SearchText;
      $searchText->record_type = 'Item';
      $searchText->record_id = $item->id;
      $searchText->public = $item->public;
      $searchText->title = metadata($item, array('Dublin Core', 'Title'));
    }
    $searchText->text .= ' ' . $enrichedSearchTexts;
    $searchText->save();
  }

  /**
  * Retrieve referenced items' titles and add them to item's search text
  *
  * @param array $args
  */
  // saving relation comments into the search index
  public function hookAfterSaveItem($args) {
    $itemId = intval(@$args["record"]["id"]);
    if ($itemId) {
      $item = get_record_by_id('Item', $itemId);

      $itemReferencesSelect = SELF::_retrieveReferenceElements();

      if ($itemReferencesSelect) {
        $elementIds = implode(",", $itemReferencesSelect);
        $db = get_db();
        $sql = "SELECT text FROM $db->ElementTexts".
                " WHERE record_id = $itemId".
                " AND element_id in ($elementIds)";
        $refItemIds = $db->fetchAll($sql);

        if ($refItemIds) {
          $refItemTitles = array();
          $firstLevelIds = array();
          foreach($refItemIds as $refItemId) {
            $firstLevelIds[] = $refItemId["text"];
            $refItemTitles[] = SELF::getTitleForId($refItemId["text"]);
          }

          if ( ($firstLevelIds) and (SELF::$_withSecondLevel) ) {
            $firstLevelIdsVerb = implode(",", $firstLevelIds);

            $sql = "SELECT text FROM $db->ElementTexts".
                    " WHERE record_id in ($firstLevelIdsVerb)".
                    " AND element_id in ($elementIds)";
            $refTwoItemIds = $db->fetchAll($sql);
            foreach($refTwoItemIds as $refTwoItemId) {
              $refItemTitles[] = SELF::getTitleForId($refTwoItemId["text"]);
            }
          }

          if ($refItemTitles) {
            $enrichedSearchTexts = implode(" ", $refItemTitles);
            SELF::myAddSearchText($item, $enrichedSearchTexts);
          }
        } // if ($refItemIds)
      } // if ($itemReferencesSelect)
    } // if ($itemId)
  }

  /**
  * Display the plugin configuration form.
  */
  public static function hookConfigForm() {
    $sqlDb = get_db();
    $page = intval(@$_GET["page"]);

    $itemReferencesSelect = SELF::_retrieveReferenceElements();
    $itemReferencesSecondLevel = SELF::$_withSecondLevel;

    switch ($page) {
      case 2:
          // $itemReferencesShowMaps = !!get_option('item_references_show_maps');
          // $itemReferencesShowLines = !!get_option('item_references_show_lines');
          $itemReferencesMapHeight = intval(get_option('item_references_map_height'));

          $ids = implode(",", $itemReferencesSelect);
          $sql = "SELECT id, name FROM $sqlDb->Elements WHERE id in ($ids)";
          $itemReferencesArr = $sqlDb->fetchALl($sql);

          $itemReferencesConfiguration = SELF::_retrieveReferenceElementConfiguration();

          require dirname(__FILE__) . '/config_form2.php';
        break;

      default:
          $select = "
            SELECT es.name AS element_set_name, e.id AS element_id,
            e.name AS element_name, it.name AS item_type_name
            FROM {$sqlDb->ElementSet} es
            JOIN {$sqlDb->Element} e ON es.id = e.element_set_id
            LEFT JOIN {$sqlDb->ItemTypesElements} ite ON e.id = ite.element_id
            LEFT JOIN {$sqlDb->ItemType} it ON ite.item_type_id = it.id
            WHERE es.id = 3
            ORDER BY it.name, e.name
          ";
          $records = $sqlDb->fetchAll($select);
          $elements = array();
          foreach ($records as $record) {
              $optGroup = $record['item_type_name']
                        ? __('Item Type') . ': ' . __($record['item_type_name'])
                        : __($record['element_set_name']);
              $value = __($record['element_name']);
              $elements[$optGroup][$record['element_id']] = $value;
          }

          $configPage2Url = url("plugins/config?name=ItemReferences&page=2");

          require dirname(__FILE__) . '/config_form.php';
        break;
    }
  }

  /**
  * Tiny min/max function to ensure a variable to be within a certain range
  */
  protected function minMax($x, $min, $max) {
    $x = ($x < $min ? $min : $x);
    $x = ($x > $max ? $max : $x);
    return $x;
  }

  /**
  * Handle the plugin configuration form.
  */
  public static function hookConfig() {
    $page = intval(@$_POST["configPage"]);

    switch ($page) {
      case 2:
          // $itemReferencesShowMaps = !!$_POST["item_references_show_maps"];
          // set_option('item_references_show_maps', intval($itemReferencesShowMaps) );

          // $itemReferencesShowLines = !!$_POST["item_references_show_lines"];
          // set_option('item_references_show_lines', intval($itemReferencesShowLines) );

          $itemReferencesMapHeight = 0;
          if (isset($_POST["item_references_map_height"])) {
            $itemReferencesMapHeight = intval($_POST["item_references_map_height"]);
          }
          set_option('item_references_map_height', $itemReferencesMapHeight );

          $itemReferencesElements = SELF::_retrieveReferenceElements();
          $elementConfigurations = array();

          foreach($itemReferencesElements as $itemReferencesElement) {
            $elementConfigurationType = intval(@$_POST["item_reference_type_$itemReferencesElement"]);
            $elementConfigurationType = SELF::minMax($elementConfigurationType, 0, 2);
            $elementConfigurationColor = intval(@$_POST["item_reference_color_$itemReferencesElement"]);
            $elementConfigurationColor = SELF::minMax($elementConfigurationColor, 0, 7);
            $elementConfigurations[$itemReferencesElement] = array(
              $elementConfigurationType,
              $elementConfigurationColor,
            );
          }

          $itemReferencesConfiguration = json_encode($elementConfigurations);
          set_option('item_references_configuration', $itemReferencesConfiguration);
        break;

      default:
          $itemReferencesSelect = array();
          $postIds=false;
          if (isset($_POST["item_references_select"])) { $postIds = $_POST["item_references_select"]; }
          if (is_array($postIds)) {
      			foreach($postIds as $postId) {
      				$postId = intval($postId);
      				if ($postId) { $itemReferencesSelect[] = $postId; }
      			}
      		}
          $itemReferencesSelect = array_unique($itemReferencesSelect);
      		$itemReferencesSelect = json_encode($itemReferencesSelect);
          set_option('item_references_select', $itemReferencesSelect);

          $itemReferencesSecondLevel = !!intval(@$_POST["item_references_second_level"]);
          set_option('item_references_second_level', $itemReferencesSecondLevel);

        break;
    }

  }

  /**
  * Add item references select JavaScript code to editor / reference map code to item show
  */
  public function hookAdminHead() {
    $request = Zend_Controller_Front::getInstance()->getRequest();
    $module = $request->getModuleName();
    if (is_null($module)) { $module = 'default'; }
    $controller = $request->getControllerName();
    $action = $request->getActionName();

    if ($module === 'default' && $controller === 'items' && in_array($action, array('add', 'edit'))) {
      $itemTypesList = array(
          '-1' => '- ' . __('All') . ' -',
      );
      $itemTypesList += SELF::_getUsedItemTypes();
      require dirname(__FILE__) . '/item-references-form.php';
    }

    if ($module === 'default' && $controller === 'items' && $action === 'show') {
      queue_js_file('referencemap');
    }

  }
  /**
   * Get the list of used item types for select form.
   *
   * @return array
   */
  protected function _getUsedItemTypes()
  {
      $db = get_db();

      $itemTypesTable = $db->getTable('ItemType');
      $itemTypesAlias = $itemTypesTable->getTableAlias();

      $select = $itemTypesTable->getSelect()
          ->reset(Zend_Db_Select::COLUMNS)
          ->from(array(), array($itemTypesAlias . '.id', $itemTypesAlias . '.name'))
          ->joinInner(array('items' => $db->Item), "items.item_type_id = $itemTypesAlias.id", array())
          ->group($itemTypesAlias . '.id')
          ->order($itemTypesAlias . '.name ASC');

      $permissions = new Omeka_Db_Select_PublicPermissions('Items');
      $permissions->apply($select, 'items');

      $itemTypes = $db->fetchPairs($select);

      return $itemTypes;
  }

  /**
  * Same as hookAdminHead -- but for public item show (edit code obsolete in this scenario)
  */
  public function hookPublicHead() { SELF::hookAdminHead(); }

  public function getTitleForId($itemId) {
    $data = SELF::getDataForId($itemId);
    return ($data ? $data["title"] : false);
  }

  /**
  * Determine title for an item ID, numerical value if not present, or false if not accessible (public context)
  */
  public function getDataForId($itemId) {
    $itemId = intval($itemId);
    $result = false;
    if ($itemId) {
      $item = get_record_by_id('Item', $itemId);
      if ($item) {
        $title = metadata($item, array('Dublin Core', 'Title'), array('no_filter' => true));
        $title = ($title ? $title : "#$itemId" );
        $details = metadata($item, array('Dublin Core', 'Description'), array('no_filter' => true));
        $result = array( "title" => $title, "details" => $details );
      }
    }
    return $result;
  }

  /**
  * Filter to modify reference fields in item editor -- to show the item select popup, etc.
  */
  public function filterElementInput($components, $args) {
    $view = get_view();

    $itemId = intval($args['value']);
    $itemDetails = SELF::getDataForId($itemId);
    $itemTitle = "";
    if ($itemDetails !== false) { $itemTitle = $itemDetails["title"]; }

    $components['input'] = "";
    $components['input'] .= $view->formText(
                              $args['input_name_stem'] . '[text]'.'-title',
                              $itemTitle,
                              array('readonly' => 'true', 'style' => 'width: auto;', 'class' => 'itemReferencesField'),
                              null
                            );
    $components['input'] .= $view->formHidden(
                              $args['input_name_stem'].'[text]',
                              $itemId,
                              array('readonly' => 'true', 'style' => 'width: auto;'),
                              null
                            );
    $components['input'] .= " <button class='itemReferencesBtn'>".__("Select")."</button>";
    $components['input'] .= "<button class='itemReferencesClearBtn'>".__("Clear")."</button>";
    $components['html_checkbox'] = false;
    return $components;
  }

  /**
  * Check is wanton array is still undefined -- and, if not, prepare an empty one
  */
  protected function _prepareArray(&$potentialArray) {
    if (!isset($potentialArray)) { $potentialArray = array(); }
  }

  /**
  * Filter to modify reference fields during rendering -- to display references item titles instead of IDs.
  * PLUS: Collect geolocation data for combined reference maps / 2nd level refrence maps.
  */
  public function filterDisplay($text, $args) {
    $result = $text;

    $itemId = intval($text); // this is our referenced item -- as stored in the element text
    if ($itemId) {
      $itemData = SELF::getDataForId($text);
      if ($itemData === false) { return; } // if we can't access its title, it's probably not public

      $itemTitle = $itemData["title"];
      $itemDetails = $itemData["details"];
      $referenceUrl = url('items/show/' . $text);
      $result = __("Reference").": ".
                "<a href='$referenceUrl'>$itemTitle</a>".
                ( !$itemDetails ? "" :
                  " <span class='itemRefDetailsLink'>(".__("Details").")</span>".
                  "<div class='itemRefDetailsText'>$itemDetails</div>"
                );

      $element_id = $args["element_text"]->element_id;

      $db = get_db();

      // collect the referenced item's geolocation only if the GeoLocation plugin is active
      if (SELF::$_withGeoLoc) {
        $sql = "SELECT * FROM $db->Locations WHERE item_id = $itemId";
        $geoLoc = $db->fetchAll($sql); // just one result, i.e. one geoloc -- but fetchAll for "SELECT *"
        if ($geoLoc) {
          $geoLoc[0]["url"] = $referenceUrl;
          $geoLoc[0]["geo_title"] = $itemTitle;
          $geoLoc[0]["geo_title"] .= ( $geoLoc[0]["address"] ? " - " . $geoLoc[0]["address"] : "" );
          if (!isset($geoLoc[0]["overlay"])) { $geoLoc[0]["overlay"] = -1; }
          SELF::_prepareArray(SELF::$_geoLocations[$element_id]);
          SELF::$_geoLocations[$element_id][$itemId] = $geoLoc[0];
          /* * /
          $lat = $geoLoc[0]["latitude"];
          $lng = $geoLoc[0]["longitude"];
          $zoom = $geoLoc[0]["zoom_level"];
          $title = $geoLoc[0]["geo_title"];
          $result .= "<br>(".__("Geolocation").": ";
          $result .= "<a href='https://www.google.de/maps".
                      "/place/$lat+$lng".
                      "/@$lat,$lng,$zoom"."z' target='_blank'>";
          $result .= $title;
          $result .= "</a>";
          $result .= ")";
          /* */
        }
      }

      if (SELF::$_withSecondLevel) {
        // Let's find 2nd order references
        $sql = "SELECT text FROM $db->element_texts".
                " WHERE element_id = $element_id AND record_id = $itemId";
        $secondaryItems = $db->fetchAll($sql);

        $secondaryList = "";

        foreach($secondaryItems as $secondaryItem) {
          $secondaryItemId = intval($secondaryItem["text"]);
          if ($secondaryItemId) {
            $secondaryItemData = SELF::getDataForId($secondaryItemId);
            if ($secondaryItemData !== false) {
              $secondaryItemTitle = $secondaryItemData["title"];
              $secondaryItemDetails = $secondaryItemData["details"];
              $secondaryReferenceUrl = url('items/show/' . $secondaryItemId);

              // $gMapsLink = "";
              if (SELF::$_withGeoLoc) {
                $sql = "SELECT * FROM $db->Locations WHERE item_id = $secondaryItemId";
                $geoLoc = $db->fetchAll($sql); // just one result, i.e. one geoloc -- but fetchAll for "SELECT *"
                if ($geoLoc) {
                  $geoLoc[0]["url"] = $secondaryReferenceUrl;
                  $geoLoc[0]["geo_title"] = $secondaryItemTitle;
                  $geoLoc[0]["geo_title"] .= ( $geoLoc[0]["address"] ? " - " . $geoLoc[0]["address"] : "" );
                  if (!isset($geoLoc[0]["overlay"])) { $geoLoc[0]["overlay"] = -1; }
                  SELF::_prepareArray(SELF::$_secondLevelGeoLocations[$element_id]);
                  SELF::_prepareArray(SELF::$_secondLevelGeoLocations[$element_id][$itemId]);
                  SELF::$_secondLevelGeoLocations[$element_id][$itemId][$secondaryItemId] = $geoLoc[0];
                  /* * /
                  $lat = $geoLoc[0]["latitude"];
                  $lng = $geoLoc[0]["longitude"];
                  $zoom = $geoLoc[0]["zoom_level"];
                  $title = $geoLoc[0]["geo_title"];
                  $gMapsLink .= "<br>(".__("Geolocation").": ";
                  $gMapsLink .= "<a href='https://www.google.de/maps".
                                "/place/$lat+$lng".
                                "/@$lat,$lng,$zoom"."z' target='_blank'>";
                  $gMapsLink .= $title;
                  $gMapsLink .= "</a>";
                  $gMapsLink .= ")";
                  /* */
                }
              }

              $secondaryList .= "<li>".
                                __("Reference").": ".
                                "<a href='$secondaryReferenceUrl'>$secondaryItemTitle</a>".
                                ( !$secondaryItemDetails ? "" :
                                  " <span class='itemRefDetailsLink'>(".__("Details").")</span>".
                                  "<div class='itemRefDetailsText'>$secondaryItemDetails</div>"
                                ).
                                // $gMapsLink.
                                "</li>";

            }
          }
        }

        if ($secondaryList) { $result .= "<ul>$secondaryList</ul>\n"; }
      }

    }
    return $result;
  }

  /**
  * Additional item display: display reverse references and reference maps / 2nd level reference maps
  */
  public function hookAdminItemsShow($args) {
    echo '<link href="' . css_src('item-references-maps') . '" rel="stylesheet">';
    if (SELF::$_enhancedGeoLog) {
      $overlays = GeolocationPlugin::GeolocationConvertOverlayJsonForUse();
    }
    else { $overlays = null; }
    SELF::_displaySelfReferences($args);
    $js = "";
    $js .= SELF::_displayReferenceMaps($args, $overlays);
    $js .= SELF::_displaySecondLevelReferenceMaps($args, $overlays);
    if ($js) {
      if ($overlays) {
        $js .= "var mapOverlays = ".$overlays["jsData"].";";
      }
      echo "<script type='text/javascript'>\n" . $js . "\n</script>";
    }
  }

  /**
  * Same as hookAdminItemsShow, but in public context
  */
  public function hookPublicItemsShow($args) { SELF::hookAdminItemsShow($args); }

  /**
  * Display list of reverse references (1st level only)
  */
  protected function _displaySelfReferences($args) {

    $referenceElements = SELF::_retrieveReferenceElements();
    if (!$referenceElements) { return; }
    $referenceElementsStr = implode(",", $referenceElements);

    $item = $args['item'];
    $itemId = $item->id;

    $db = get_db();
    $sql = "SELECT DISTINCT record_id FROM `$db->ElementTexts`".
          " WHERE element_id in ($referenceElementsStr)".
          " AND text = '$itemId'";
    $referencers = $db->fetchAll($sql);

    if ($referencers) {
      echo "<h2>".__("Items Referencing this Item")."</h2>\n";

      echo "<ul>\n";
      foreach($referencers as $referencer) {
        $referencerId = $referencer["record_id"];
        $referencerUrl = url('items/show/' . $referencerId);
        $data = SELF::getDataForId($referencerId);
        if ($data !== false) {
          $title = $data["title"];
          $details = $data["details"];
          echo "<li><a href='" . $referencerUrl . "'>" .
                $title .
                "</a>".
                ( !$details ? "" :
                  " <span class='itemRefDetailsLink'>(".__("Details").")</span>".
                  "<div class='itemRefDetailsText'>$details</div>"
                ).
                "</li>\n";
        }
      }
      echo "</ul>\n";
    }

  }

  /**
  * Determine whether this item contains reference element and thus potentially draws reference maps
  */
  protected function _needsMaps($itemReferencesConfiguration) {
    $result = false;
    foreach($itemReferencesConfiguration as $itemReference) {
      $result = ($itemReference[0] != 0);
      if ($result) { break; }
    }
    return $result;
  }

  /**
  * Display 1st level reference maps -- i.e. create HTML tags, push data into JavaScript to create Google Maps dynamically
  */
  protected function _displayReferenceMaps($args, $overlays) {
    $js = "";

    $itemReferencesConfiguration = SELF::_retrieveReferenceElementConfiguration();
    if (!SELF::_needsMaps($itemReferencesConfiguration)) { return; }

    if ( (SELF::$_withGeoLoc) AND (SELF::$_geoLocations) ) {

      $output = "<h2>".__("Geolocations of References Items")."</h2>\n";

      $itemReferencesMapHeight = intval(get_option('item_references_map_height'));
      if (!$itemReferencesMapHeight) { $itemReferencesMapHeight = ITEM_REFERENCES_MAP_HEIGHT_DEFAULT; }

      $mapsData = array();

      $db = get_db();

      foreach(SELF::$_geoLocations as $elementId => $referenceMap) {
        if ( ($referenceMap) and ($itemReferencesConfiguration[$elementId][0]>0) ) {
          $sql = "SELECT name FROM $db->Elements WHERE id = $elementId";
          $elementName = $db->fetchOne($sql);
          $output .= "<h4>$elementName</h4>\n";

          $data = array(
            "mapId" => "map".$elementId,
            "coords" => array(),
            "line" => intval($itemReferencesConfiguration[$elementId][0]==2),
            "color" => intval($itemReferencesConfiguration[$elementId][1]),
          );

          $reqOverlays = array();

          foreach($referenceMap as $pin) {
            if ($pin) {
              $data["coords"][] = array( // see below (*)
                "title" => $pin["geo_title"],
                "lat" => $pin["latitude"],
                "lng" => $pin["longitude"],
                "url" => $pin["url"],
                "ovl" => $pin["overlay"],
                "zl" => $pin["zoom_level"],
              );
              if (isset($reqOverlays[$pin["overlay"]])) {
                $reqOverlays[$pin["overlay"]]++;
              }
              else {
                $reqOverlays[$pin["overlay"]] = 1;
              }
            }
          }

          $ovlDefault = -1;
          if (count($reqOverlays) == 1) { $ovlDefault = array_keys($reqOverlays)[0]; }

          $output .= "<div id='".$data["mapId"]."' style='height:".$itemReferencesMapHeight."px; width:100%;'></div>\n";
          $curCount = count($mapsData);
          if ($overlays) {
            $output .= '<div class="reference_ovl_options"><strong>' . __("Select Map Overlay:") . '</strong> '.
              get_view()->formSelect(
                $data["mapId"]."_ovl",
                $ovlDefault,
                array(
                  "class" => "refMapOvlSel",
                  "data-map-arr" => $curCount, // latest added IDX - see above (*)
                ),
                $overlays["jsSelect"]
              ).
              "<span class='refMapOvlSlider' id='".$data["mapId"]."_slider' data-map-arr='".$curCount."'></span>".
              "</div>";
          }

          $mapsData[] = $data;

        }

      }

      if ($mapsData) {
        echo $output;
        $js .= "var mapsData=".json_encode($mapsData).";\n";
      }

    }

    return $js;
  }

  /**
  * Display 2nd level reference maps -- i.e. create HTML tags, push data into JavaScript to create Google Maps dynamically
  */
  protected function _displaySecondLevelReferenceMaps($args, $overlays) {
    $js = "";

    $itemReferencesConfiguration = SELF::_retrieveReferenceElementConfiguration();
    if (!SELF::_needsMaps($itemReferencesConfiguration)) { return; }

    if ( (SELF::$_withGeoLoc) AND (SELF::$_secondLevelGeoLocations) ) {

      $output = "<h2>".__("Geolocations of Second Level References Items")."</h2>\n";

      $itemReferencesMapHeight = intval(get_option('item_references_map_height'));
      if (!$itemReferencesMapHeight) { $itemReferencesMapHeight = ITEM_REFERENCES_MAP_HEIGHT_DEFAULT; }

      $secondLevelMapsData = array();

      $db = get_db();

      foreach(SELF::$_secondLevelGeoLocations as $elementId => $firstLevelRef) {
        if ( ($firstLevelRef) and ($itemReferencesConfiguration[$elementId][0]>0) ) {
          $sql = "SELECT name FROM $db->Elements WHERE id = $elementId";
          $elementName = $db->fetchOne($sql);
          $output .= "<h4>$elementName</h4>\n";

          $data = array(
            "mapId" => "mapTwo".$elementId,
            "line" => intval($itemReferencesConfiguration[$elementId][0]==2),
            "refMaps" => array(),
          );

          $reqOverlays = array();

          foreach($firstLevelRef as $firstLevelRefId => $referenceMap) {

            $firstLevelRefData = SELF::getDataForId($firstLevelRefId);

            if ($firstLevelRefData !== false) {

              $firstLevelRefTitle = $firstLevelRefData["title"];
              $firstLevelRefUrl = url('items/show/' . $firstLevelRefId);

              $data["refMaps"][$firstLevelRefId] = array(
                "title" => $firstLevelRefTitle,
                "url" => $firstLevelRefUrl,
                "coords" => array(),
              );

              foreach($referenceMap as $secondLevelRefId => $pin) {
                if ($pin) {
                  $data["refMaps"][$firstLevelRefId]["coords"][] = array( // see below (*)
                    "title" => $pin["geo_title"],
                    "lat" => $pin["latitude"],
                    "lng" => $pin["longitude"],
                    "url" => $pin["url"],
                    "ovl" => $pin["overlay"],
                    "zl" => $pin["zoom_level"],
                  );
                  if (isset($reqOverlays[$pin["overlay"]])) {
                    $reqOverlays[$pin["overlay"]]++;
                  }
                  else {
                    $reqOverlays[$pin["overlay"]] = 1;
                  }
                } # if ($pin)
              } # foreach($referenceMap

            } # if ($firstLevelRefTitle

          } # foreach($firstLevelRef

          $ovlDefault = -1;
          if (count($reqOverlays) == 1) { $ovlDefault = array_keys($reqOverlays)[0]; }

          $output .= "<div id='".$data["mapId"]."' style='height:".$itemReferencesMapHeight."px; width:100%;'></div>\n";
          $curSecondCount = count($secondLevelMapsData);
          if ($overlays) {
            $output .= '<div class="reference_ovl_options"><strong>'.__("Select Map Overlay:").'</strong> '.
              get_view()->formSelect(
                $data["mapId"]."_ovl",
                $ovlDefault,
                array(
                  "class" => "refMapOvlSel",
                  "data-map-two-arr" => $curSecondCount, // latest added IDX - see above (*)
                ),
                $overlays["jsSelect"]
              ).
              "<span class='refMapOvlSlider' id='".$data["mapId"]."_slider' data-map-two-arr='".$curSecondCount."'></span>".
              "</div>";
          }
          $output .= "<div id='".$data["mapId"]."_legend' class='itemRefTwoMapLegend'></div>";

          $secondLevelMapsData[] = $data;

        } # if ( ($firstLevelRef)

      } # foreach(SELF::$_secondLevelGeoLocations

      if ($secondLevelMapsData) {
        echo $output;
        $js .= "var mapsTwoData=".json_encode($secondLevelMapsData).";\n";
      }

    } # if ( (SELF::$_withGeoLoc) AND (SELF::$_secondLevelGeoLocations) )

    return $js;
  } # function

}
