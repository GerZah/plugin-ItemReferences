jQuery(document).bind("omeka:elementformload", function() {
//jQuery(document).ready(function () {
  var $ = jQuery; // use noConflict version of jQuery as the short $ within this block
  var options = {};

  var updateTimer = null;

  init();

  function init() {
      resetOptions();

      $('new_relation_object_item_type_id_reference').val(-1);
      $('input[name=itemsListsortReference]:checked').val('timestamp_reference');
      $('#partial_object_title_reference').val('');
      $('#id_limit_reference').val('');

      updateChoices();
      updateSelectButton();
  }

  function resetOptions() {
      options = {
          partialReference: '',
          id_limitReference: '',
          item_typeReference: -1,
          sortReference: 'mod_desc_ref',
          pageReference: 0,
          per_pageReference: 15,
          max_pageReference: 0
      };
  }

  function updateChoices() {
    if (updateTimer != null) { clearTimeout(updateTimer); }
    updateTimer = setTimeout(updateChoicesCore, 1000);
  }

  function updateChoicesCore() {
      if (updateTimer != null) { clearTimeout(updateTimer); updateTimer = null; }

      options['partialReference'] = $('#partial_object_title_reference').val();
      options['id_limitReference'] = $('#id_limit_reference').val();
      options['item_typeReference'] = $('#new_relation_object_item_type_id_reference').val();
      if ($('input[name=itemsListsortReference]:checked').val() === 'timestamp_reference') {
          options['sortReference'] = 'mod_desc_ref';
      }
      else{
          options['sortReference'] = 'alpha_asc_ref';
      }
      if (options['pageReference'] < 0) {
          options['pageReference'] = 0;
      }
      if (options['pageReference'] > options['max_pageReference']) {
          options['pageReference'] = options['max_pageReference'];
      }
      $.ajax({
          url: itemReferencesUrl,
          dataType: 'json',
          data: options,
          success: function (data) {
              var i;
              var items = [];

              /* options */
              $('#lookup-results-reference').find('li').remove();
              for (i = 0; i < data['items'].length; ++i) {
                  items.push('<li data-value="' + data['items'][i]['value'] + '">' +
                  '<span class="refListItemId">#' + data['items'][i]['value'] + "</span> " +
                  data['items'][i]['label'] + '</li>');
              }
              $('#lookup-results-reference').append(items.join(''));

              /* pagination */
              options['max_pageReference'] = Math.floor(data['count'] / options['per_pageReference']);

              if (0 < options['pageReference']) {
                  $('#selector-previous-page-reference').removeClass('pg_disabled');
              }
              else {
                  $('#selector-previous-page-reference').addClass('pg_disabled');
              }

              if (options['pageReference'] < options['max_pageReference']) {
                  $('#selector-next-page-reference').removeClass('pg_disabled');
              }
              else {
                  $('#selector-next-page-reference').addClass('pg_disabled');
              }
          }
      });
  }
  $('#lookup-results-reference').on('click', 'li', function () {
      $('#new_reference_object_item_id_reference').val($(this).attr('data-value'));
      $('#object_title_reference').html(
        '<a href="' + $('#object_title_reference').attr('data-base-url') + '/items/show/' + $(this).attr('data-value') + '" target="_blank">' +
        $(this).html() +
        '</a>'
      );
      updateSelectButton();
  });

  function updateSelectButton() {
      var selectButton = $('#select_item');
      if ($('#new_reference_object_item_id_reference').val()) {
          selectButton.removeProp('disabled');
          selectButton.removeAttr('disabled');
      }
      else {
          selectButton.prop('disabled', true);
          selectButton.attr('disabled', true);
      }
  }

  $('#selector-previous-page-reference').click(function (e) {
      e.preventDefault();
      if (0 < options['pageReference']) {
          options['pageReference']--;
          updateChoicesCore();
      }
  });

  $('#selector-next-page-reference').click(function (e) {
      e.preventDefault();
      if (options['pageReference'] < options['max_pageReference']) {
          options['pageReference']++;
          updateChoicesCore();
      }
  });

  $('#new_relation_object_item_type_id_reference').change(function () {
      updateChoices();
  });

  $('#new_selectObjectsort_timestamp_reference').click(function () {
      updateChoicesCore();
  });

  $('#new_selectObjectsort_name_reference').click(function () {
      updateChoicesCore();
  });

  $('#partial_object_title_reference').on('input', function () {
      updateChoices();
  });

  $('#id_limit_reference').on('input', function () {
      updateChoices();
  });

  var lightbox = lity(); // https://www.npmjs.com/package/lity
  //var selectButtonTxt = $(".itemReferencesBtn").first().text();

  $(".itemReferencesField").unbind("click").click(function(e) {
    $(this).next().next().click();
  } );

  $(".itemReferencesBtn").unbind("click").click(function(e) {
    e.preventDefault();

  //  $("#new_relation_property_id").hide().prev().hide().prev().hide();
  //  $("#add-reference").hide();
  //  $("#add-reference").parent().append("<a href='#' id='select_item' class='green button'>"+selectButtonTxt+"</a>");

    var currentTitle = $(this).prev().prev().attr("id"); // for title
    var currentId = $(this).prev().attr("id"); // for id

    $("#select_item").unbind("click").click(function(e) {
      e.preventDefault();

      var currentTitleVerb = $('#object_title_reference').text();
      currentTitleVerb = currentTitleVerb.substr(currentTitleVerb.indexOf(" ")+1);

      $("#"+currentTitle).val(currentTitleVerb);
      $("#"+currentId).val($('#new_reference_object_item_id_reference').val());

      lightbox.close();
    });

    lightbox("#item-reference-selector");
  });

  $(".itemReferencesClearBtn").click(function(e) {
    e.preventDefault();

    var currentTitle = $(this).prev().prev().prev().attr("id"); // for title
    var currentId = $(this).prev().prev().attr("id"); // for id

    $("#"+currentTitle).val("");
    $("#"+currentId).val("");
  } );

  $("#item-reference-selector button").click(function(e) { e.preventDefault(); });

  $(document).on('lity:close', function(event, lightbox) {
  });

} );
