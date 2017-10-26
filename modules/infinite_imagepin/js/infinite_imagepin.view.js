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
    var overlay = widget.clone(),
      $img = pin.parent().find('img').not('.imagepin-widget img'),
      $arrow = [],
      $widget_content = [],
      img_width = $img.width(),
      img_height = $img.height(),
      overlay_width,
      overlay_height,
      top_position,
      left_position,
      horizontal_diff = 0,
      pin_width = pin.width(),
      pin_height = pin.height(),
      pin_top_position = parseInt(pin.css('top')),
      pin_left_position = parseInt(pin.css('left')),
      direction = 'down',
      isTouchDevice = ('ontouchstart' in window || 'onmsgesturechange' in window);

    /**
     * remove all overlays
     */
    Drupal.imagepin.removeOverlays(pin);

    /**
     * append overlay
     */
    $widget_content = $('<div class="imagepin-widget-content"></div>').appendTo(overlay);
    $arrow = $('<span class="arrow"></span>').appendTo($widget_content);

    pin.before(overlay);

    overlay.attr('class', widget.attr('data-imagepin-overlay-class'));
    overlay.css('position', 'absolute');

    overlay_width = overlay.outerWidth();
    overlay_height = overlay.outerHeight();

    /**
     * pos overlay
     * @type {number}
     */
    if ((pin_top_position + overlay_height) > img_height) {
      direction = 'up';
      top_position = pin_top_position + pin_height - overlay_height;
    } else {
      top_position = pin_top_position;
    }

    overlay.addClass(direction);

    left_position = Math.max(horizontal_diff, pin_left_position - (overlay_width / 2));
    // left_position = Math.max(padding_left, Math.min(left_position, pin_left_position - overlay_width));
    left_position = Math.max(horizontal_diff, Math.min(left_position, img_width - overlay_width - horizontal_diff));

    $arrow.css('left', pin_left_position - left_position);

    overlay.css('top', (top_position).toString() + 'px');
    overlay.css('left', (left_position).toString() + 'px');
    overlay.css('z-index', '9');
    overlay.css('display', 'none');
    overlay.fadeIn('fast');

    /**
     * pos stupid arrow
     * @type {number}
     */
    pin.addClass('imagepin--active');
    widget.addClass('imagepin--active');

    if (!isTouchDevice) {
      overlay.mouseleave(function () {
        Drupal.imagepin.removeOverlays(pin, true);
      });
    }
  };

  Drupal.imagepin.removeOverlays = function (pin) {
    var $parent = pin.parent();

    $.each($parent.find('.imagepin-widget'), function (pIndex, pItem) {
      $(pItem).fadeOut('slow', function () {
        $(this).remove();
      });
    });

    $parent.find('.imagepin').removeClass('imagepin--active');

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
        });
        // Prepare widgets container display.

        widgets.find('.imagepin').each(function () {
          var pin = $(this);
          Drupal.imagepin.attachPin(pin);

          var key = pin.attr('data-imagepin-key');
          var widget = $("[data-imagepin-attach-to='" + attach_id + "'] .imagepin-widget[data-imagepin-key='" + key + "']");

          pin.on('mouseover click touchstart', function () {
            Drupal.imagepin.overlay(pin, widget);
          });

          // pin.on('mouseout touchend', function () {
          //   Drupal.imagepin.removeOverlays($(this));
          // });

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