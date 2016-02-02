jQuery(document).bind("omeka:elementformload", function() {
  var $ = jQuery; // use noConflict version of jQuery as the short $ within this block

  var lightbox = lity(); // https://www.npmjs.com/package/lity
  var selectButtonTxt = $(".itemReferencesBtn").first().text();

  $(".itemReferencesBtn").unbind("click").click(function(e) {
    e.preventDefault();

    $("#new_relation_property_id").hide().prev().hide().prev().hide();
    $("#relation_comment").parent().hide();
    $("#add-relation").hide();
    $("#add-relation").parent().append("<a href='#' id='select_item' class='green button'>"+selectButtonTxt+"</a>");

    var currentTitle = $(this).prev().prev().attr("id"); // for title
    var currentId = $(this).prev().attr("id"); // for id

    // console.log(currentTitle);
    // console.log(currentId);

    $("#select_item").click(function(e) {
      e.preventDefault();
      $("#"+currentTitle).val($('#object_title').text());
      $("#"+currentId).val($('#new_relation_object_item_id').val());

      lightbox.close();
    });

    lightbox("#item-relation-selector");
  });

  $(".itemReferencesClearBtn").click(function(e) {
    e.preventDefault();

    var currentTitle = $(this).prev().prev().prev().attr("id"); // for title
    var currentId = $(this).prev().prev().attr("id"); // for id

    $("#"+currentTitle).val("");
    $("#"+currentId).val("");
  } );

  $("#item-relation-selector button").click(function(e) { e.preventDefault(); });

  $(document).on('lity:close', function(event, lightbox) {
    $("#new_relation_property_id").show().prev().show().prev().show();
    $("#relation_comment").parent().show();
    $("#add-relation").show();
    $("#select_item").remove();
  });

} );
