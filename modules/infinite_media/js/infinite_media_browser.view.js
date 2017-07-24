/**
 * @file
 * Defines the behavior of the media entity browser view.
 */

(function ($) {

  'use strict';

  /**
   * Attaches the behavior of the media entity browser view.
   */
  Drupal.behaviors.infiniteMediaBrowserView = {
    attach: function (context, settings) {
      drupalSettings.entity_browser_widget = drupalSettings.entity_browser_widget || {};
    }
  };

}(jQuery, Drupal));
