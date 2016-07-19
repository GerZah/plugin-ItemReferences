jQuery(document).ready(function () {
    var $ = jQuery;

    // var refItemTypeShowHide = "show/hide";
    // var refItemTypeShowHideAll = "show/hide/all"

    $(".refItemTypeHead").each(function(element) {
        var curItemType = $(this).data("item-type");
        var rowClass = "refItemType_"+curItemType;
        var rowCount = $("."+rowClass).size();
        $("th", this).append(
            " <a href='#' class='refItemTypeHideBtn' data-item-type='"+curItemType+"'>"+
            "["+refItemTypeShowHide +" ("+rowCount+")]"+
            "</a>"
        );
        // $("."+rowClass).toggle();
    });

    $(".refItemTypeHideBtn").click(function(e) {
        e.preventDefault();
        var curItemType = $(this).data("item-type");
        var rowClass = "refItemType_"+curItemType;
        $("."+rowClass).toggle();
    });

    var allShowHide = false;
    $(".refItemTypeRow").hide();

    var colspan = $(".refItemTypeHead th").first().attr('colSpan');
    $("#refItemTypeTable tbody").prepend(
        "<tr><th colspan='"+colspan+"'>"+
        "<a href='#' id='refItemTypeShowHideAllBtn'>["+refItemTypeShowHideAll+"]</a>"+
        "</th></tr>"
    );

    $("#refItemTypeShowHideAllBtn").click(function(e){
        e.preventDefault();
        allShowHide = !allShowHide;
        if (allShowHide) { $(".refItemTypeRow").show() } else { $(".refItemTypeRow").hide(); }
    });
});
