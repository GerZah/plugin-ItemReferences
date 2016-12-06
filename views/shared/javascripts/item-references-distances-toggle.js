jQuery(document).ready(function () {
  var $ = jQuery;
  // console.log(refDistancesShowHide,refDistancesShowHideAll);

  $(".refDistanceHead").each(function(element) {
      var curDistance = $(this).data("block");
      var blockClass = "refDistanceBlock_"+curDistance;
      $("th", this).append(
          " <a href='#' class='refDistanceHideBtn' data-block='"+curDistance+"'>"+
          "["+refDistancesShowHide +"]"+
          "</a>"
      );
  });

  $(".refDistanceHideBtn").click(function(e) {
      e.preventDefault();
      var curDistance = $(this).data("block");
      var blockClass = "refDistanceBlock_"+curDistance;
      $("."+blockClass).toggle();
  });

  $(".refDistanceRow").hide();

  var colspan = $(".refDistanceHead th").first().attr('colSpan');

  $(".refDistanceTable").each(function(element) {
    var curElement = $(this).data("element");
    $("th", this).first().parent().parent().prepend(
        "<tr><th colspan='"+colspan+"'>"+
        "<a href='#' class='refDistancesHideAllBtn'"+
        " data-showhide='0'"+
        " data-element='"+curElement+"'>["+refDistancesShowHideAll+"]</a>"+
        "</th></tr>"
    );
  });

  $(".refDistancesHideAllBtn").click(function(e){
      e.preventDefault();
      var curElement = $(this).data("element");
      var showHide = !$(this).data("showhide");
      $(this).data("showhide", showHide);
      var selectClass = ".refDistanceElement_" +  curElement + " .refDistanceRow";
      if (showHide) { $(selectClass).show() } else { $(selectClass).hide(); }
  });

});
