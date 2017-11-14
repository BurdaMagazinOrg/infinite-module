(function ($, Drupal, drupalSettings, Backbone, BurdaInfinite) {

  "use strict";

  BurdaInfinite.managers.OdoscopeManager = Backbone.Model.extend({
    instance: null,
    defaults: {
      active: false,
      type: "",
      group: "",
      basehost: window.location.hostname.split(".")[0]
    },
    initialize: function () {
      this.createModels();
    },
    activate: function (pOdoscopeAttr) {
      this.set(pOdoscopeAttr);
      this.set('active', true);

      // this.setGroupCookie(this.get('group'));
      console.log("%codoscopeManager | activate", "color: deepskyblue;", pOdoscopeAttr, this);

      TrackingManager.trackEvent({
        event: AppConfig.gtmEventName,
        category: OdoscopeManager.GTM_EVENT_CATEGORY,
        action: 'pageview',
        label: this.get('group')
      });
    },
    createModels: function () {

      if (typeof OdoscopeArticleModel != "undefined") {
        this.set('articleModel', new OdoscopeArticleModel());
        // console.log("%codoscopeManager | createModels | OdoscopeArticleModel", "color: deepskyblue;");
      }

    },
    isOdoscopeArticleGroup: function () {
      return this.get('group') == OdoscopeManager.GROUP_ODOSCOPE && this.get('type') == OdoscopeManager.ODOSCOPE_TYPE_ARTICLE;
    },
    isOdoscopeGroup: function () {
      return this.getGroup() == OdoscopeManager.GROUP_ODOSCOPE;
    },
    getGroup: function () {
      return this.get('group');
    },
    getType: function () {
      return this.get('type');
    },
    getTrackingObject: function () {
      if (this.getGroup() == undefined) {
        return null;

      }
      return {group: this.getGroup(), type: this.getType()};
    }
  }, {
    ODOSCOPE_TYPE_ARTICLE: 'articlePage',
    ODOSCOPE_TYPE_FEED: 'feedPage',
    GROUP_ODOSCOPE: 'odoscope',
    GROUP_CONTROL: 'control',
    GTM_EVENT_CATEGORY: 'odoscope',
    getInstance: function () {
      if (this.instance == null) {
        this.instance = new OdoscopeManager();
        this.instance.constructor = null;
      }

      return this.instance;
    }
  });

  window.OdoscopeManager = window.OdoscopeManager || BurdaInfinite.managers.OdoscopeManager;

  window.oscSplitTestCallback = function (pPage, pGroup, pData) {

    if (!OdoscopeManager.getInstance().get('active')) {
      var tmpOdoscopeAttr = {type: pPage, group: pGroup};
      OdoscopeManager.getInstance().activate(tmpOdoscopeAttr);
    }

    if (pData != null && typeof TrackingManager != 'undefined') {
      TrackingManager.trackEvent({
        event: AppConfig.gtmEventName,
        category: OdoscopeManager.GTM_EVENT_CATEGORY,
        action: 'oscSaveTracking',
        label: pGroup + '/' + pData
      });
    }

  };

  window.oscInitializeArticlePageRendering = function (pDecisionFunction, pConfig) {
    // console.log("%codoscope | articlePageRendering", "color: deepskyblue;", pDecisionFunction, pConfig);

    pDecisionFunction({}).then(function (pArticles) {
      console.log("%codoscope | articlePageRendering  | decision", "color: deepskyblue;", OdoscopeManager.getInstance().getGroup(), pArticles);

      // if (OdoscopeManager.getInstance().isOdoscopeGroup()) {
      if (pArticles != null) {
        OdoscopeManager.getInstance().get('articleModel').create(pArticles);
      }

    }).catch(function (pError) {
      console.log("%codoscope | articlePageRendering | error", "color: red;", pError);
      // error callback
    });
  };


  window.oscTeaserElementReplaced = function (pElement) {
    // apply social icon code and timeago
  };

  window.oscInfiniteBlockViewUpdated = function (pElement) {
    Drupal.behaviors.blazy.attach(pElement);
    jQuery(pElement).data('infiniteModel').refresh();
    // update waypoints
    // window.Waypoint.refreshAll();

    if (TrackingManager != undefined) {
      TrackingManager.trackEvent({
        event: AppConfig.gtmEventName,
        category: OdoscopeManager.GTM_EVENT_CATEGORY,
        action: 'feedInfiniteBlockRendered'
      });
    }
  };

  if (typeof window.oscCallbackCalls != "undefined") {
    $(document).ready(function () {

      jQuery(window.oscCallbackCalls).each(function (pIndex, pItem) {
        if (pItem.functionName == "oscInfiniteBlockViewUpdated") {

          _.delay(function () {
            window[pItem.functionName].apply(null, pItem.arguments);
          }, 0);
        } else {
          window[pItem.functionName].apply(null, pItem.arguments);
        }

      });

    });
  }

})(jQuery, Drupal, drupalSettings, Backbone, BurdaInfinite);
