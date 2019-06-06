(function() {
  if (document.cookie.indexOf('oil_data=') > -1) {
    var s = document.createElement('script');
    s.src = 'https://widgets.outbrain.com/outbrain.js';
    document.getElementsByTagName('head')[0].appendChild(s);
  }
})();
