(function($, Drupal, window) {
  Drupal.behaviors.imagepinEditable = {
    attach: function(context, settings) {
      if (!$('form.node-look-form, form.node-look-edit-form').length) {
        $('.imagepin-modal-link').hide();
      }
    },
    detach: function(context, settings) {},
  };
})(jQuery, Drupal, window);
