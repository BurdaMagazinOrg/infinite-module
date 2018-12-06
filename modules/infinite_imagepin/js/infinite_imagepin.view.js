/**
 * @file
 * JS implementation for viewing imagepin items.
 */

(function($, Drupal, drupalSettings, window) {
  Drupal.imagepin = Drupal.imagepin || {};

  Drupal.imagepin.attachPin = function(pin) {
    const attach_id = pin
      .parent()
      .parent()
      .attr('data-imagepin-attach-to');

    const image = $(`img[data-imagepin-attach-from='${attach_id}']`);

    image.before(pin);
    // Ensure the parent is a block,
    // and its positioning isn't influenced by its parents.
    pin.parent().css('display', 'block');
    pin.parent().css('position', 'relative');

    Drupal.imagepin.setPosition(pin, image);
    pin.attr('data-imagepin-attached-to', attach_id);
  };

  Drupal.imagepin.setPosition = function(pin, image) {
    // Set the default positioning, if available.
    let position = pin.attr('data-position-default');
    if (position) {
      position = JSON.parse(position);

      // Try to set the position relatively to the given image size.
      const image_natural_width = image.prop('naturalWidth');
      const image_client_width = image.width();
      if (image_client_width > 0 && image_natural_width !== 'undefined') {
        const image_natural_height = image.prop('naturalHeight');
        const image_client_height = image.height();
        const ratio_image_width = image_natural_width / position.image_width;
        const ratio_image_height = image_natural_height / position.image_height;
        const ratio_client_width = image_client_width / image_natural_width;
        const ratio_client_height = image_client_height / image_natural_height;
        pin.css(
          'top',
          `${(
            position.top *
            ratio_client_height *
            ratio_image_height
          ).toString()}px`
        );
        pin.css(
          'left',
          `${(
            position.left *
            ratio_client_width *
            ratio_image_width
          ).toString()}px`
        );
      } else {
        pin.css('top', `${position.top.toString()}px`);
        pin.css('left', `${position.left.toString()}px`);
      }
    }
  };

  Drupal.imagepin.adjustPositions = function() {
    $('img[data-imagepin-attach-from]').each(function() {
      const image = $(this);
      const attach_id = image.attr('data-imagepin-attach-from');
      $(`.imagepin[data-imagepin-attached-to='${attach_id}']`).each(function() {
        Drupal.imagepin.setPosition($(this), image);
      });
    });
  };

  Drupal.imagepin.overlay = function(pin, widget) {
    const isTouchDevice =
      'ontouchstart' in window || 'onmsgesturechange' in window;

    let $visibleOverlay = [];

    if (isTouchDevice && pin.parent().find('.imagepin-overlay').length > 0) {
      $visibleOverlay = pin.parent().find('.imagepin-overlay');
      if (widget.data('imagepin-key') == $visibleOverlay.data('imagepin-key')) {
        Drupal.imagepin.removeOverlays(pin);
        return;
      }
    }

    const overlay = widget;

    const $img = pin
      .parent()
      .find('img')
      .not('.imagepin-widget img');

    let $arrow;

    const img_width = $img.width();

    let overlay_width;

    let overlay_height;

    let top_position;

    let left_position;

    let img_overlay_diff;

    const pin_height = pin.height();

    let horizontal_diff;

    const pin_top_position = parseInt(pin.css('top'));

    const pin_left_position = parseInt(pin.css('left'));

    let direction = 'down';

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
      $arrow = $('<span class="arrow"></span>').appendTo(
        overlay.find('.imagepin-widget-content')
      );
    }

    overlay.attr('class', widget.attr('data-imagepin-overlay-class'));
    overlay.css('position', 'absolute');
    // For default direction = 'down' set margin on TouchDevices and padding on nonTouchDevices
    // This is necessary because clicking on another pin while overlay is opened triggers an URL-Opening from overlay
    if (isTouchDevice) {
      overlay.css('margin-top', '40px');
    } else {
      overlay.css('padding-top', '40px');
    }
    //
    overlay_width = overlay.find('.product-widgets').outerWidth();
    overlay_height = overlay.find('.product-widgets').outerHeight();

    /**
     * pos overlay
     * @type {number}
     */
    if (pin_top_position - overlay_height < 0) {
      top_position = pin_top_position;
    } else {
      direction = 'up';
      // If direction = 'up' reset margin-bottom on TouchDevices and padding-bottom on nonTouchDevices
      // set margin-bottom on TouchDevices and padding-bottom on nonTouchDevices
      if (isTouchDevice) {
        overlay.css('margin-top', '0');
        overlay.css('margin-bottom', '40px');
      } else {
        overlay.css('padding-top', '0');
        overlay.css('padding-bottom', '40px');
      }
      top_position = pin_top_position + pin_height - overlay_height - 40;
    }

    overlay.addClass(direction);
    horizontal_diff = -($arrow.outerWidth() / 2 + 1);
    img_overlay_diff =
      (img_width - overlay_width) / 2 + $arrow.outerWidth() / 2;

    if (
      pin_left_position > img_overlay_diff &&
      pin_left_position < img_width - img_overlay_diff
    ) {
      left_position = img_width / 2 - overlay_width / 2;
    } else {
      left_position = Math.max(
        horizontal_diff,
        pin_left_position - overlay_width / 2
      );
      left_position = Math.max(
        horizontal_diff,
        Math.min(
          left_position,
          pin_left_position - overlay_width - horizontal_diff
        )
      );
    }

    /**
     * pos stupid arrow
     * @type {number}
     */
    $arrow.css('left', Math.max(0, pin_left_position - left_position));

    overlay.css('top', `${top_position.toString()}px`);
    overlay.css('left', `${left_position.toString()}px`);
    overlay.css('display', 'none');
    overlay.fadeIn('fast');

    pin.addClass('imagepin--active');

    if (!isTouchDevice) {
      overlay.mouseleave(() => {
        Drupal.imagepin.removeOverlays(pin);
      });
    }

    Drupal.behaviors.infiniteWishlist.toggleIconsAccordingToWishlistStatus();
  };

  Drupal.imagepin.removeOverlays = function($element) {
    const $parent = $element.parent();

    $.each($parent.find('.imagepin-widget'), function() {
      $(this).detach();
    });

    $parent
      .find('.imagepin--active')
      .trigger('overlay:hide')
      .removeClass('imagepin--active');
  };

  $(window).resize(() => {
    Drupal.imagepin.adjustPositions();
  });

  $(window).load(() => {
    Drupal.imagepin.adjustPositions();
  });

  /**
   * Initialize / Uninitialize the view for imagepin items.
   */
  Drupal.behaviors.imagepinView = {
    attach: function(context, settings) {
      $('.imagepin-widgets', context).each(function() {
        const widgets = $(this);
        const attach_id = widgets.attr('data-imagepin-attach-to');
        const image = $(`img[data-imagepin-attach-from='${attach_id}']`);

        image
          .load(() => {
            widgets.css('max-width', image.width());
            image.parent().addClass('initialized');
            Drupal.imagepin.adjustPositions();
          })
          .each(function() {
            if (this.complete) $(this).load();
          });

        image.on('click', () => {
          Drupal.imagepin.removeOverlays(image);
        });

        widgets.find('.imagepin').each(function() {
          const pin = $(this);
          Drupal.imagepin.attachPin(pin);

          const key = pin.attr('data-imagepin-key');
          const widget = $(
            `[data-imagepin-attach-to='${attach_id}'] .imagepin-widget[data-imagepin-key='${key}']`
          );

          // click touchstart
          pin.on('touchstart', e => {
            e.preventDefault();
            Drupal.imagepin.overlay(pin, widget);
          });

          pin.on('mouseover', e => {
            Drupal.imagepin.overlay(pin, widget);
          });
        });
      });

      if ('extensions' in Drupal.imagepin) {
        for (const extension in Drupal.imagepin.extensions) {
          if (Drupal.imagepin.extensions.hasOwnProperty(extension)) {
            Drupal.imagepin.extensions[extension].attach(context, settings);
          }
        }
      }
    },

    detach: function(context, settings) {
      if ('extensions' in Drupal.imagepin) {
        for (const extension in Drupal.imagepin.extensions) {
          if (Drupal.imagepin.extensions.hasOwnProperty(extension)) {
            Drupal.imagepin.extensions[extension].detach(context, settings);
          }
        }
      }
    },
  };
})(jQuery, Drupal, drupalSettings, window);
