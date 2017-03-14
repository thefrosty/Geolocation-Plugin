(function ($, google) {

  "use strict";

  var Geolocation = {

    geolocationObject: window.geolocation_object,
    center: null,
    map: null,
    image: geolocationObject.images.pin,
    shadow: null,
    marker: null,
    allowDisappear: true,
    cancelDisappear: false,

    init: function () {
      Geolocation.center = new google.maps.LatLng(0.0, 0.0);

      var mapOptions = {
        'zoom': Geolocation.geolocationObject.zoom,
        center: Geolocation.center,
        mapTypeId: google.maps.MapTypeId.ROADMAP
      };

      var marker_options = {
        position: Geolocation.center,
        map: map,
        title: 'Post Location'
      };

      Geolocation.map = new google.maps.Map(document.getElementById('map'), mapOptions);
      Geolocation.shadow = new google.maps.MarkerImage(
        Geolocation.geolocationObject.images.pin_shadow,
        new google.maps.Size(39, 23),
        new google.maps.Point(0, 0),
        new google.maps.Point(12, 25)
      );

      if (Geolocation.geolocationObject.has_pin) {
        marker_options.icon = this.image;
        marker_options.shadow = this.shadow;
      }

      Geolocation.marker = new google.maps.Marker(marker_options);

      //
      google.maps.event.addListener(map, 'click', function () {
        window.location = 'https://maps.google.com/maps?q=' + map.center.lat() + ',+' + map.center.lng();
      });
    },

    mouseover: function () {
      var $map = $('#map');

      $('.geolocation-link').on('mouseover', function (e) {
        $map.stop(true, true);
        var lat = $(this).prop('name').split(',')[0];
        var lng = $(this).prop('name').split(',')[1];
        var latlng = new google.maps.LatLng(lat, lng);
        Geolocation.placeMarker(latlng);

        var offset = $(this).offset();
        $map.fadeTo(250, 1);
        $map.css('z-index', '99');
        $map.css('visibility', 'visible');
        $map.css('top', offset.top + 20);
        $map.css('left', offset.left);

        Geolocation.allowDisappear = false;
        $map.css('visibility', 'visible');
      });

      $map.on('mouseover', function () {
        Geolocation.allowDisappear = false;
        Geolocation.cancelDisappear = true;
        $map.css('visibility', 'visible');
      });
    },

    mouseout: function () {
      var $map = $('#map');

      $('.geolocation-link').on('mouseout', function () {
        Geolocation.allowDisappear = true;
        Geolocation.cancelDisappear = false;
        setTimeout(function () {
          if ((Geolocation.allowDisappear) && (!Geolocation.cancelDisappear)) {
            $map.fadeTo(500, 0, function () {
              $map.css('z-index', '-1');
              Geolocation.allowDisappear = true;
              Geolocation.cancelDisappear = false;
            });
          }
        }, 800);
      });

      $map.on('mouseout', function () {
        Geolocation.allowDisappear = true;
        Geolocation.cancelDisappear = false;
        $('.geolocation-link').mouseout();
      });
    },

    placeMarker: function (location) {
      Geolocation.map.setZoom(' . $zoom . ');
      Geolocation.marker.setPosition(location);
      Geolocation.map.setCenter(location);
    }
  };

  $(document).ready(function () {
    Geolocation.init();
  });

}(jQuery, window.google));
