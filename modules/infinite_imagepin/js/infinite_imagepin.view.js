/**
 * @file
 * JS implementation for viewing imagepin items.
 */

(function ($, Drupal, drupalSettings, window) {

  'use strict';

  Drupal.imagepin = Drupal.imagepin || {};

  Drupal.imagepin.attachPin = function (pin) {
    var attach_id = pin.parent().parent().attr('data-imagepin-attach-to');

    var image = $("img[data-imagepin-attach-from='" + attach_id + "']");

    image.before(pin);
    // Ensure the parent is a block,
    // and its positioning isn't influenced by its parents.
    pin.parent().css('display', 'block');
    pin.parent().css('position', 'relative');

    Drupal.imagepin.setPosition(pin, image);
    pin.attr('data-imagepin-attached-to', attach_id);
  };

  Drupal.imagepin.setPosition = function (pin, image) {

    // Set the default positioning, if available.
    var position = pin.attr('data-position-default');
    if (position) {
      position = JSON.parse(position);

      // Try to set the position relatively to the given image size.
      var image_natural_width = image.prop('naturalWidth');
      var image_client_width = image.width();
      if ((image_client_width > 0) && (image_natural_width !== 'undefined')) {
        var image_natural_height = image.prop('naturalHeight');
        var image_client_height = image.height();
        var ratio_image_width = image_natural_width / position.image_width;
        var ratio_image_height = image_natural_height / position.image_height;
        var ratio_client_width = image_client_width / image_natural_width;
        var ratio_client_height = image_client_height / image_natural_height;
        pin.css('top', (position.top * ratio_client_height * ratio_image_height).toString() + 'px');
        pin.css('left', (position.left * ratio_client_width * ratio_image_width).toString() + 'px');
      }
      else {
        pin.css('top', (position.top).toString() + 'px');
        pin.css('left', (position.left).toString() + 'px');
      }
    }
  };

  Drupal.imagepin.adjustPositions = function () {
    $('img[data-imagepin-attach-from]').each(function () {
      var image = $(this);
      var attach_id = image.attr('data-imagepin-attach-from');
      $(".imagepin[data-imagepin-attached-to='" + attach_id + "']").each(function () {
        Drupal.imagepin.setPosition($(this), image);
      });
    });
  };

  Drupal.imagepin.overlay = function (pin, widget) {
    var isTouchDevice = ('ontouchstart' in window || 'onmsgesturechange' in window),
      $visibleOverlay = [];

    if (isTouchDevice && pin.parent().find('.imagepin-overlay').length > 0) {
      $visibleOverlay = pin.parent().find('.imagepin-overlay');
      if (widget.data('imagepin-key') == $visibleOverlay.data('imagepin-key')) {
        Drupal.imagepin.removeOverlays(pin);
        return;
      }
    }

    var overlay = widget,
      $img = pin.parent().find('img').not('.imagepin-widget img'),
      $arrow,
      img_width = $img.width(),
      overlay_width,
      overlay_height,
      top_position,
      left_position,
      img_overlay_diff,
      pin_height = pin.height(),
      horizontal_diff,
      pin_top_position = parseInt(pin.css('top')),
      pin_left_position = parseInt(pin.css('left')),
      direction = 'down';

    /**
     * remove all overlays
     */
    Drupal.imagepin.removeOverlays(pin);

    /**
     * append overlay
     */

    pin.before(overlay);
    pin.trigger('overlay:show', [overlay]);

    if (overlay.children('.imagepin-widget-content').length === 0) {
      overlay.children().wrapAll('<div class="imagepin-widget-content"></div>');
    }
    $arrow = overlay.find('.imagepin-widget-content .arrow');
    if ($arrow.length === 0) {
      $arrow = $('<span class="arrow"></span>').appendTo(overlay.find('.imagepin-widget-content'));
    }

    overlay.attr('class', widget.attr('data-imagepin-overlay-class'));
    overlay.css('position', 'absolute');
    // For default direction = 'down' set margin on TouchDevices and padding on nonTouchDevices
    // This is necessary because clicking on another pin while overlay is opened triggers an URL-Opening from overlay
    if (isTouchDevice) {
      overlay.css('margin-top', '40px');
    }
    else {
      overlay.css('padding-top', '40px');
    }
    //
    overlay_width = overlay.find('.product-widgets').outerWidth();
    overlay_height = overlay.find('.product-widgets').outerHeight();

    /**
     * pos overlay
     * @type {number}
     */
    if ((pin_top_position - overlay_height) < 0) {
      top_position = pin_top_position;
    } else {
      direction = 'up';
      // If direction = 'up' reset margin-bottom on TouchDevices and padding-bottom on nonTouchDevices
      // set margin-bottom on TouchDevices and padding-bottom on nonTouchDevices
      if (isTouchDevice) {
        overlay.css('margin-top', '0');
        overlay.css('margin-bottom', '40px');
      }
      else {
        overlay.css('padding-top', '0');
        overlay.css('padding-bottom', '40px');
      }
      top_position = pin_top_position + pin_height - overlay_height - 40;
    }

    overlay.addClass(direction);
    horizontal_diff = -(($arrow.outerWidth() / 2) + 1);
    img_overlay_diff = ((img_width - overlay_width) / 2) + ($arrow.outerWidth() / 2);

    if (pin_left_position > img_overlay_diff && pin_left_position < img_width - img_overlay_diff) {
      left_position = (img_width / 2) - (overlay_width / 2);
    } else {
      left_position = Math.max(horizontal_diff, pin_left_position - (overlay_width / 2));
      left_position = Math.max(horizontal_diff, Math.min(left_position, pin_left_position - overlay_width - horizontal_diff));
    }

    /**
     * pos stupid arrow
     * @type {number}
     */
    $arrow.css('left', Math.max(0, pin_left_position - left_position));

    overlay.css('top', (top_position).toString() + 'px');
    overlay.css('left', (left_position).toString() + 'px');
    overlay.css('display', 'none');
    overlay.fadeIn('fast');

    pin.addClass('imagepin--active');
    widget.off('click').on('click', function () {
        var link = document.createElement('a');
        link.href = widget.find('[data-external-url]').data('external-url');
        link.setAttribute('target', '_blank');
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
    });

    if (!isTouchDevice) {
      overlay.mouseleave(function () {
        Drupal.imagepin.removeOverlays(pin);
      });
    }

    // handle overflow in parent container
    overlay.parents('.item-content__row-col').each(function () {
      $(this).data('previous-overflow-value', $(this).css('overflow'));
      this.style.overflow = 'visible';
    });

    Drupal.behaviors.infiniteWishlist.toggleIconsAccordingToWishlistStatus();
  };

  Drupal.imagepin.removeOverlays = function ($element) {
    var $parent = $element.parent();

    $.each($parent.find('.imagepin-widget'), function () {
        $(this).detach();
    });

    $parent.find('.imagepin--active').trigger('overlay:hide').removeClass('imagepin--active');

    // reset overflow in parent container
      $element.parents('.item-content__row-col').each(function () {
      if (typeof $(this).data('previous-overflow-value') !== 'undefined') {
        this.style.overflow = $(this).data('previous-overflow-value');
        this.removeAttribute('previous-overflow-value');
      }
    });
  }

  $(window).resize(function () {
    Drupal.imagepin.adjustPositions();
  });

  $(window).load(function () {
    Drupal.imagepin.adjustPositions();
  });

  /**
   * Initialize / Uninitialize the view for imagepin items.
   */
  Drupal.behaviors.imagepinView = {
    attach: function (context, settings) {

      $('.imagepin-widgets', context).each(function () {
        var widgets = $(this);
        var attach_id = widgets.attr('data-imagepin-attach-to');
        var image = $("img[data-imagepin-attach-from='" + attach_id + "']");

        image.load(function () {
          widgets.css('max-width', image.width());
          image.parent().addClass('initialized');
          Drupal.imagepin.adjustPositions();
        }).each(function () {
          if (this.complete) $(this).load();
        });

        image.on('click', function () {
          Drupal.imagepin.removeOverlays(image);
        });

        widgets.find('.imagepin').each(function () {
          var pin = $(this);
          Drupal.imagepin.attachPin(pin);

          var key = pin.attr('data-imagepin-key');
          var widget = $("[data-imagepin-attach-to='" + attach_id + "'] .imagepin-widget[data-imagepin-key='" + key + "']");

          //click touchstart
          pin.on('touchstart', function (e) {
            e.preventDefault();
            Drupal.imagepin.overlay(pin, widget);
          });

          pin.on('mouseover', function (e) {
            Drupal.imagepin.overlay(pin, widget);
          });


        });
      });

      if ('extensions' in Drupal.imagepin) {
        for (var extension in Drupal.imagepin.extensions) {
          if (Drupal.imagepin.extensions.hasOwnProperty(extension)) {
            Drupal.imagepin.extensions[extension].attach(context, settings);
          }
        }
      }

    },

    detach: function (context, settings) {

      if ('extensions' in Drupal.imagepin) {
        for (var extension in Drupal.imagepin.extensions) {
          if (Drupal.imagepin.extensions.hasOwnProperty(extension)) {
            Drupal.imagepin.extensions[extension].detach(context, settings);
          }
        }
      }

    }
  };

}(jQuery, Drupal, drupalSettings, window));