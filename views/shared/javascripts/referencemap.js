jQuery( document ).ready(function() {
  var $ = jQuery; // use noConflict version of jQuery as the short $ within this block

  if (typeof mapsData != 'undefined') {

    var numMaps = mapsData.length;
    for (var i = 0; i < numMaps; i++) {

      mapsData[i].map = new google.maps.Map(document.getElementById(mapsData[i].mapId), {
        center: {lat: 0, lng: 0},
        zoom: 0
      });
      var thismap = mapsData[i].map;

      var numCoords = mapsData[i].coords.length;
      var latLngBounds = new google.maps.LatLngBounds();
      var polyLineCoordinates = [ ];
      var itemReferencesShowLines = mapsData[i].line;

      var gCol = googleColors(mapsData[i].color);
      var pinVerbColor = gCol.verb;
      var pinRgbColor = gCol.rgb;

      var curLat = 0;
      var curLng = 0;
      var curZl = 0;

      for (var j = 0; j < numCoords; j++) {
        var curTitle = mapsData[i].coords[j].title;
        curLat = mapsData[i].coords[j].lat;
        curLng = mapsData[i].coords[j].lng;
        var curUrl = mapsData[i].coords[j].url;
        curZl = mapsData[i].coords[j].zl;

        latLngBounds.extend(new google.maps.LatLng(curLat, curLng));

        var contentString = "<a href='"+curUrl+"' class='refMapLink'>"+curTitle+"</a>";
        var infoWindow = new google.maps.InfoWindow({ content: contentString });

        mapsData[i].coords[j].marker = new google.maps.Marker({
          icon: 'http://maps.google.com/mapfiles/ms/icons/'+pinVerbColor+'-dot.png',
          title: curTitle,
          position: {lat: curLat, lng: curLng},
          map: thismap,
          infoWindow: infoWindow
        });

        google.maps.event.addListener(mapsData[i].coords[j].marker, 'click', function() {
          this.infoWindow.open(this.map, this);
        });

        if (itemReferencesShowLines) {
          polyLineCoordinates.push( { lat: curLat, lng: curLng } );
        }
      }

      if (numCoords==1) {
        thismap.setCenter( new google.maps.LatLng(curLat, curLng) );
        thismap.setZoom( curZl );
      }
      else {
        thismap.fitBounds(latLngBounds);
      }

      if (itemReferencesShowLines) {
        var polyLine = new google.maps.Polyline({
            path: polyLineCoordinates,
            geodesic: true,
            strokeColor: pinRgbColor,
            strokeOpacity: 1.0,
            strokeWeight: 2
          });
        polyLine.setMap(thismap);
      }
    }
  }

  if (typeof mapsTwoData != 'undefined') {

    var numTwoMaps = mapsTwoData.length;
    for (var i = 0; i < numTwoMaps; i++) {

      var mapTwoId = mapsTwoData[i].mapId;
      mapsTwoData[i].map = new google.maps.Map(document.getElementById(mapTwoId), {
        center: {lat: 0, lng: 0},
        zoom: 0
      });
      var thismap = mapsTwoData[i].map;
      var mapTwoLegend = mapTwoId + "_legend";
      $("#"+mapTwoLegend).empty();

      var numTwoCoordsAll = 0;
      var latLngTwoBounds = new google.maps.LatLngBounds();
      var itemReferencesShowTwoLines = mapsTwoData[i].line;

      var refMaps = mapsTwoData[i].refMaps;

      var refMapsIds = Object.keys(refMaps);
      var numRefMaps = refMapsIds.length;

      var curLat = 0;
      var curLng = 0;
      var curZl = 0;

      var curCol = -1;

      for (var j = 0; j < numRefMaps; j++) {
        var coords = refMaps[refMapsIds[j]].coords;
        var numTwoCoords = coords.length;
        numTwoCoordsAll += numTwoCoords;

        curCol++;
        var gCol = googleColors(curCol);
        var pinVerbName = gCol.name;
        var pinRgbColor = gCol.rgb;

        var refMapTitle = refMaps[refMapsIds[j]].title;
        var refMapUrl = refMaps[refMapsIds[j]].url;
        var iconUrl = 'http://maps.google.com/mapfiles/ms/icons/'+pinVerbName+'.png';
        $("#"+mapTwoLegend).append(
            "<p><a href='"+refMapUrl+"' style='color:"+pinRgbColor+"'>"+
            "<img src='"+iconUrl+"'> "+refMapTitle+
            "</a></p>"
          );

        var polyTwoLineCoordinates = [ ];

        for (var k = 0; k < numTwoCoords; k++) {
          var curTitle = coords[k].title;
          curLat = coords[k].lat;
          curLng = coords[k].lng;
          var curUrl = coords[k].url;
          curZl = coords[k].zl;

          latLngTwoBounds.extend(new google.maps.LatLng(curLat, curLng));

          var contentString = "<a href='"+curUrl+"' class='refMapLink'>"+curTitle+"</a>";
          var infoWindow = new google.maps.InfoWindow({ content: contentString });

          coords[k].marker = new google.maps.Marker({
            icon: iconUrl,
            title: curTitle,
            position: {lat: curLat, lng: curLng},
            map: thismap,
            infoWindow: infoWindow
          });

          google.maps.event.addListener(coords[k].marker, 'click', function() {
            this.infoWindow.open(this.map, this);
          });

          if (itemReferencesShowTwoLines) {
            polyTwoLineCoordinates.push( { lat: curLat, lng: curLng } );
          }
        }

        if (itemReferencesShowTwoLines) {
          var polyTwoLine = new google.maps.Polyline({
              path: polyTwoLineCoordinates,
              geodesic: true,
              strokeColor: pinRgbColor,
              strokeOpacity: 1.0,
              strokeWeight: 2
            });
          polyTwoLine.setMap(thismap);
        }

      }

      if (numTwoCoordsAll==1) {
        thismap.setCenter( new google.maps.LatLng(curLat, curLng) );
        thismap.setZoom( curZl );
      }
      else {
        thismap.fitBounds(latLngTwoBounds);
      }

      thismap.controls[google.maps.ControlPosition.TOP_CENTER].push(
        document.getElementById(mapTwoLegend));
      $("#"+mapTwoLegend).css("display", "block");
    }
  }

  $(".refMapOvlSel").change( function() {
    var overlayIdx = this.value;
    var mapArr;
    var map;
    if (typeof $(this).data("map-arr") != "undefined") {
      mapArr = $(this).data("map-arr");
      map = mapsData[mapArr].map;
    }
    else {
      mapArr = $(this).data("map-two-arr");
      map = mapsTwoData[mapArr].map;
    }
    mapSelOverlay(overlayIdx, map)
  } );
  $(".refMapOvlSel").change();

  function mapSelOverlay(overlayIdx, map) {
    if (typeof map.mapOverlay != 'undefined') { map.mapOverlay.setMap(null); }
    if ( (overlayIdx>=0) && (typeof mapOverlays[overlayIdx] != 'undefined') ) {
      var overlayData = mapOverlays[overlayIdx];
      var imageBounds = {
        north: parseFloat(overlayData["latNorth"]),
        south: parseFloat(overlayData["latSouth"]),
        west:  parseFloat(overlayData["lngWest"]),
        east:  parseFloat(overlayData["lngEast"])
      };
      map.mapOverlay = new google.maps.GroundOverlay( overlayData["imgUrl"], imageBounds);
      map.mapOverlay.setMap(map);
    }
  }

  function minMax(x, min, max) {
    x = (x < min ? min : x);
    x = x % (max+1);
    x = (x > max ? max : x);
    return x;
  }

  // https://groups.google.com/d/msg/google-maps-api/2k3T5hwI5Ck/RRso0D2jB1oJ
  // http://www.lass.it/Web/viewer.aspx?id=4
  // yellow, green, ltblue, blue, red, purple, pink, orange
  // *.png *-dot.png *-pushpin.png
  function googleColors(colId) {
    var colIdExt = minMax(parseInt(colId), 0, 15);
    colId = minMax(colIdExt, 0, 7);
    var pinVerbColor = "red";
    var pinRgbColor = "#ff4d4f";
    switch (parseInt(colId)) {
      case 1:
        pinVerbColor = "orange";
        pinRgbColor = "#fc8707";
      break;
      case 2:
        pinVerbColor = "yellow";
        pinRgbColor = "#fff44c";
      break;
      case 3:
        pinVerbColor = "green";
        pinRgbColor = "#00ec37";
      break;
      case 4:
        pinVerbColor = "ltblue";
        pinRgbColor = "#00ddd6";
      break;
      case 5:
        pinVerbColor = "blue";
        pinRgbColor = "#387dff";
      break;
      case 6:
        pinVerbColor = "purple";
        pinRgbColor = "#8749ff";
      break;
      case 7:
        pinVerbColor = "pink";
        pinRgbColor = "#ff359c";
      break;
    }
    return {
      verb: pinVerbColor,
      name: pinVerbColor + ( colIdExt < 8 ? "-dot" : "" ),
      rgb: pinRgbColor
    };
  }

  $(".itemRefDetailsLink").click( function(){
    var thisDiv;
    thisDiv = $(this).next();
    if (!thisDiv.hasClass('itemRefDetailsText')) {
      thisDiv = $(this).parent().next();
    }
    var curVis = thisDiv.data("visible");
    thisDiv.data("visible", !curVis);
    if (curVis) { thisDiv.slideUp('fast'); } else { thisDiv.slideDown('fast'); }
  } );

} );
