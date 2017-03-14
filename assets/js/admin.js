(function ($) {

  "use strict";

  var Geolocation_Post = {

    hasLocation: false,
    center: new google.maps.LatLng(0.0, 0.0),
    geoObject: window.geoObject,
    $attr: {
      public: $('#geoObject-public'),
      latitude: $('#geoObject-latitude'),
      longitude: $('#geoObject-longitude'),
      address: $('#geoObject-address'),
      load: $('#geoObject-load'),
      enabled: $('#geoObject-enabled'),
      disabled: $('#geoObject-disabled'),
      map: $('#geoObject-map')
    },

    init: function () {
      if (!this.geoObject.is_public) {
        this.$attr.public.attr('checked', false);
      } else {
        this.$attr.public.attr('checked', true);
      }

      if (!this.geoObject.is_enabled) {
        this.disableGeo();
      } else {
        this.enableGeo();
      }

      if ((this.geoObject.latitude !== '') && (this.geoObject.longitude !== '')) {
        this.hasLocation = true;
        this.center = new google.maps.LatLng(
          this.geoObject.latitude, this.geoObject.longitude
        );
        this.$attr.latitude.val(this.center.lat());
        this.$attr.latitude.val(this.center.lng());
        this.reverseGeocode(this.center);
      }
      //

      var mapOptions = {
          'zoom': this.geoObject.zoom,
          'center': this.center,
          'mapTypeId': google.maps.MapTypeId.ROADMAP
        },
        image = this.geoObject.images.pin,
        shadow = new google.maps.MarkerImage(
          this.geoObject.images.pin_shadow,
          new google.maps.Size(39, 23),
          new google.maps.Point(0, 0),
          new google.maps.Point(12, 25)
        );

      var map = new google.maps.Map(document.getElementById('geoObject-map'), mapOptions);
      var marker_options = {
        position: this.center,
        map: map,
        title: 'Post Location'
      };
      if (this.geoObject.has_pin) {
        marker_options.icon = image;
        marker_options.shadow = shadow;
      }
      var marker = new google.maps.Marker(marker_options);

      if ((!this.hasLocation) && (google.loader.ClientLocation)) {
        this.center = new google.maps.LatLng(
          google.loader.ClientLocation.latitude, google.loader.ClientLocation.longitude
        );
        this.reverseGeocode(this.center, marker);
      } else if (!this.hasLocation) {
        map.setZoom(1);
      }

      google.maps.event.addListener(map, 'click', function (event) {
        this.placeMarker(event.latLng, marker);
      });

      var currentAddress;
      var customAddress = false;

      this.$attr.address.on('click', function () {
        currentAddress = $(this).val();
        if (currentAddress != '')
          Geolocation_Post.$attr.address.val('');
      });

      this.$attr.load.on('click', function () {
        if (Geolocation_Post.$attr.address.val() != '') {
          customAddress = true;
          currentAddress = Geolocation_Post.$attr.address.val();
          this.geocode(currentAddress, marker);
        }
      });

      this.$attr.address.keyup(function (e) {
        if (e.keyCode == 13)
          Geolocation_Post.$attr.load.trigger('click');
      });

      this.$attr.enabled.on('click', function () {
        Geolocation_Post.enableGeo();
      });

      this.$attr.disabled.on('click', function () {
        Geolocation_Post.disableGeo();
      });
    },

    /**
     *
     * @param {string} location
     * @param {object} marker
     */
    placeMarker: function (location, marker) {
      marker.setPosition(location);
      map.setCenter(location);
      if ((location.lat() != '') && (location.lng() != '')) {
        this.$attr.latitude.val(location.lat());
        this.$attr.longitude.val(location.lng());
      }

      //if (!customAddress) {
      //  this.reverseGeocode(location, marker);
      //}
    },

    /**
     *
     * @param {string} address
     * @param {object} marker
     */
    geocode: function (address, marker) {
      var geocoder = new google.maps.Geocoder();
      if (geocoder) {
        geocoder.geocode({"address": address}, function (results, status) {
          if (status == google.maps.GeocoderStatus.OK) {
            this.placeMarker(results[0].geometry.location, marker);
            if (!this.hasLocation) {
              map.setZoom(16);
              this.hasLocation = true;
            }
          }
        });
      }
      //$('#geodata').html(latitude + ', ' + longitude);
    },

    /**
     *
     * @param {string} location
     * @param {object} marker
     */
    reverseGeocode: function (location, marker) {
      var geocoder = new google.maps.Geocoder();
      if (geocoder) {
        geocoder.geocode({"latLng": location}, function (results, status) {
          if (status == google.maps.GeocoderStatus.OK) {
            if (results[1]) {
              var address = results[1].formatted_address;
              if (address == '') {
                address = results[7].formatted_address;
              }

              this.$attr.address.val(address);
              this.placeMarker(location, marker);
            }
          }
        });
      }
    },

    enableGeo: function () {
      this.$attr.address.removeAttr('disabled');
      this.$attr.load.removeAttr('disabled');
      this.$attr.map.css('filter', '');
      this.$attr.map.css('opacity', '');
      this.$attr.map.css('-moz-opacity', '');
      this.$attr.public.removeAttr('disabled');
      this.$attr.map.removeAttr('readonly');
      this.$attr.disabled.removeAttr('checked');
      this.$attr.enabled.attr('checked', 'checked');

      if (this.geoObject.is_public) {
        this.$attr.public.prop('checked', true);
      }
    },

    disableGeo: function () {
      this.$attr.address.attr('disabled', 'disabled');
      this.$attr.load.attr('disabled', 'disabled');
      this.$attr.map.css('filter', 'alpha(opacity=50)');
      this.$attr.map.css('opacity', '0.5');
      this.$attr.map.css('-moz-opacity', '0.5');
      this.$attr.map.attr('readonly', 'readonly');
      this.$attr.public.attr('disabled', 'disabled');

      this.$attr.enabled.removeAttr('checked');
      this.$attr.enabled.removeProp('checked');
      this.$attr.disabled.prop('checked', true);

      if (this.geoObject.is_public) {
        this.$attr.public.prop('checked', true);
      }
    }

  };

  $(document).ready(function () {
    Geolocation_Post.init();
  });

}(jQuery));