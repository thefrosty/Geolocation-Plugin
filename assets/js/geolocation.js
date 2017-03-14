(function ($) {

  "use strict";

  var center = new google.maps.LatLng(0.0, 0.0);
  var geolocation = window.geoObject;
  var myOptions = {
    'zoom': geolocation.zoom,
    center: center,
    mapTypeId: google.maps.MapTypeId.ROADMAP
  };
  var map = new google.maps.Map(document.getElementById("map"), myOptions);
  var image = geolocation.images.pin;
  var shadow = new google.maps.MarkerImage(geolocation.images.pin_shadow,
    new google.maps.Size(39, 23),
    new google.maps.Point(0, 0),
    new google.maps.Point(12, 25));

  var marker_options = {
    position: center,
    map: map,
    title: 'Post Location'
  };
  if (geolocation.has_pin) {
    marker_options.icon = image;
    marker_options.shadow = shadow;
  }
  var marker = new google.maps.Marker(marker_options);

  var allowDisappear = true;
  var cancelDisappear = false;

  $(".geoObject-link").mouseover(function () {
    $("#map").stop(true, true);
    var lat = $(this).attr("name").split(",")[0];
    var lng = $(this).attr("name").split(",")[1];
    var latlng = new google.maps.LatLng(lat, lng);
    placeMarker(latlng);

    var offset = $(this).offset();
    $("#map").fadeTo(250, 1);
    $("#map").css("z-index", "99");
    $("#map").css("visibility", "visible");
    $("#map").css("top", offset.top + 20);
    $("#map").css("left", offset.left);

    allowDisappear = false;
    $("#map").css("visibility", "visible");
  });

  $(".geoObject-link").mouseover(function () {
  });

  $(".geoObject-link").mouseout(function () {
    allowDisappear = true;
    cancelDisappear = false;
    setTimeout(function () {
      if ((allowDisappear) && (!cancelDisappear)) {
        $("#map").fadeTo(500, 0, function () {
          $("#map").css("z-index", "-1");
          allowDisappear = true;
          cancelDisappear = false;
        });
      }
    }, 800);
  });

  $("#map").mouseover(function () {
    allowDisappear = false;
    cancelDisappear = true;
    $("#map").css("visibility", "visible");
  });

  $("#map").mouseout(function () {
    allowDisappear = true;
    cancelDisappear = false;
    $(".geoObject-link").mouseout();
  });

  function placeMarker(location) {
    map.setZoom(' . $zoom . ');
    marker.setPosition(location);
    map.setCenter(location);
  }

  google.maps.event.addListener(map, "click", function () {
    window.location = "https://maps.google.com/maps?q=" + map.center.lat() + ",+" + map.center.lng();
  });

}(jQuery));