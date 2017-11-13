(function ($, Drupal, window) {
  Drupal.behaviors.imagepinEditable = {
    attach: function (context, settings) {
      if(!$('form.node-look-form').length) {
        $('.imagepin-modal-link').hide();
      }
    },
    detach: function (context, settings) {}
  };
}(jQuery, Drupal, window));