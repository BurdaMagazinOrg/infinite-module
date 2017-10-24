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
        var overlay = widget.clone();
        var top_position = parseInt(pin.css('top')) - (parseInt(pin.css('height')) / 2) - 10;
        var left_position = parseInt(pin.css('left')) - (parseInt(widget.css('width')) / 2);
        pin.before(overlay);
        overlay.attr('class', widget.attr('data-imagepin-overlay-class'));
        overlay.css('position', 'absolute');
        overlay.css('top', (top_position).toString() + 'px');
        overlay.css('left', (left_position).toString() + 'px');
        overlay.css('z-index', '9');
        overlay.css('display', 'none');
        overlay.fadeIn('fast');
        overlay.mouseleave(function () {
            overlay.fadeOut('slow', function () {
                overlay.remove();
            });
        });
    };

    Drupal.imagepin.onDesktop = function (widgets) {
        return true;
        // HACK!

        var attach_id = widgets.attr('data-imagepin-attach-to');
        var breakpoint = drupalSettings.imagepin[attach_id].breakpoint;
        if (typeof (breakpoint) === 'undefined') {
            breakpoint = 1024;
        }
        if (breakpoint === '') {
            return false;
        }
        breakpoint = parseInt(breakpoint);
        if ($(window).width() < breakpoint) {
            return false;
        }

    };

    $(window).resize(function () {
        Drupal.imagepin.adjustPositions();
        $('.imagepin-widgets').each(function () {
            var widgets = $(this);
            if (Drupal.imagepin.onDesktop(widgets)) {
                widgets.css('display', 'none');
            }
            else {
                widgets.css('display', 'block');
            }
        });
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
                    Drupal.imagepin.adjustPositions();
                });
                // Prepare widgets container display.
                if (Drupal.imagepin.onDesktop(widgets)) {
                    widgets.css('display', 'none');
                }
                else {
                    widgets.css('max-width', image.width());
                }
                widgets.find('.imagepin').each(function () {
                    var pin = $(this);
                    Drupal.imagepin.attachPin(pin);

                    var key = pin.attr('data-imagepin-key');
                    var widget = $("[data-imagepin-attach-to='" + attach_id + "'] .imagepin-widget[data-imagepin-key='" + key + "']");
                    pin.on('mouseover click touchstart', function () {
                        // Mark the selected pin.
                        $('.imagepin').removeClass('imagepin-selected');
                        pin.addClass('imagepin-selected');
                        // Mark the corresponding widget.
                        $('.imagepin-widget').removeClass('imagepin-selected');
                        widget.addClass('imagepin-selected');

                        if (Drupal.imagepin.onDesktop(widgets)) {
                            Drupal.imagepin.overlay(pin, widget);
                        }
                    });
                    widget.on('click touchend', function () {
                        // Mark the selected pin.
                        $('.imagepin').removeClass('imagepin-selected');
                        pin.addClass('imagepin-selected');
                        // Mark the corresponding widget.
                        $('.imagepin-widget').removeClass('imagepin-selected');
                        widget.addClass('imagepin-selected');
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