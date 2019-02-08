(function($, Drupal, drupalSettings) {
  const API_TOKEN = drupalSettings.infinite_fashwell.API_TOKEN;
  const API_URL = drupalSettings.infinite_fashwell.API_URL;

  Drupal.behaviors.infiniteFashwell = {
    attach: function(context, settings) {
      $('input.fashwell')
        .once('fashwell-init')
        .parent()
        .find('.fashwell-alt')
        .one('click.fashwell', this.clickHandler);
    },
    clickHandler: function(e) {
      const $this = $(this);
      const input = $this
        .closest('.form-item__field-wrapper')
        .find('input.fashwell');
      const imageUrl = input.data('product-image');

      const throbber = $(
        '<div class="ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>'
      );

      $this.append(throbber);

      $.ajax(API_URL, {
        method: 'POST',
        dataType: 'json',
        headers: {
          Authorization: 'Token ' + API_TOKEN,
        },
        data: {
          url: imageUrl,
        },
      })
        .success(function(data) {
          setTimeout(function() {
            $this.attr('href', data.annotator_url);
            $this.find('.ajax-progress-throbber').remove();
            const newWindow = window.open(data.annotator_url);
            newWindow.focus();
          }, 2500);
        })
        .error(function(jqXHR, textStatus, errorThrown) {
          console.error(textStatus, errorThrown);
          $this.one(
            'click.fashwell',
            Drupal.behaviors.infiniteFashwell.clickHandler
          );
          $this.find('.ajax-progress-throbber').remove();
        });
    },
  };
})(jQuery, Drupal, drupalSettings);
