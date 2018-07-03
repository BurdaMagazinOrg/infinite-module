(function($, Drupal, drupalSettings) {
  "use strict";

  var API_TOKEN = drupalSettings.infinite_fashwell.API_TOKEN;
  var API_URL = drupalSettings.infinite_fashwell.API_URL;

  Drupal.behaviors.infiniteFashwell = {
    attach: function(context, settings) {
      $("input.fashwell")
        .once("fashwell-init")
        .parent()
        .find(".fashwell-alt")
        .one("click.fashwell", this.clickHandler);
    },
    clickHandler(e) {
      var $this = $(this);
      var input = $this
        .closest(".form-item__field-wrapper")
        .find("input.fashwell");
      var imageUrl = input.data("product-image");

      var throbber = $(
        '<div class="ajax-progress-throbber"><div class="throbber">&nbsp;</div></div>'
      );

      $this.append(throbber);

      $.ajax(API_URL, {
        method: "POST",
        dataType: "json",
        headers: {
          Authorization: "Token " + API_TOKEN
        },
        data: {
          url: imageUrl
        }
      })
        .success(function(data) {
          setTimeout(function() {
            $this.attr("href", data.annotator_url);
            var newWindow = window.open(data.annotator_url);
            newWindow.focus();
            $this.find(".ajax-progress-throbber").remove();
          }, 2500);
        })
        .error(function(jqXHR, textStatus, errorThrown) {
          console.error(textStatus, errorThrown);
          $this.one(
            "click.fashwell",
            Drupal.behaviors.infiniteFashwell.clickHandler
          );
          $this.find(".ajax-progress-throbber").remove();
        });
    }
  };
})(jQuery, Drupal, drupalSettings);
