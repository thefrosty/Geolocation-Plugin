var Geolocation_Settings = {

  geoObject: window.geoObject,
  path: geolocation.img_path,

  swap_zoom_sample: function (id) {
    var zoomlevel = document.getElementById(id).value;
    this.pin_click(zoomlevel);
  },

  pin_click: function (zoomlevel) {
    var div = document.getElementById('Geo__zoom_level_sample');
    var file = this.path + zoomlevel + '.png';
    if (document.getElementById('geolocation_wp_pin').checked) {
      file = this.path + 'wp_' + zoomlevel + '.png';
    }
    div.style.background = 'url(' + file + ')';
  }

};
