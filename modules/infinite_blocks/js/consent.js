(function() {
  function receiveMessage(event) {
    if (!event || !event.data) return;

    function data(str) {
      return JSON.stringify(event.data).indexOf(str) !== -1;
    }

    (!!data('oil_optin_done') || !!data('oil_has_optedin')) && addScript();
  }

  function addScript() {
    if (!!document.getElementById('outbrain-script')) return;

    var s = document.createElement('script');
    s.id = 'outbrain-script';
    s.src = 'https://widgets.outbrain.com/outbrain.js';
    document.getElementsByTagName('head')[0].appendChild(s);
  }

  window.addEventListener('message', receiveMessage, false);
  document.cookie.indexOf('oil_data=') > -1 && addScript();
})();
