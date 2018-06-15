(function ($, Drupal, drupalSettings, Backbone, BurdaInfinite) {

  "use strict";

  window.OdoscopeArticleModel = BurdaInfinite.models.base.BaseModel.extend({
    defaults: {
      loadingIndex: 0,
      currentURL: null,
    },
    initialize: function (pAttributes, pOptions) {
      BurdaInfinite.models.base.BaseModel.prototype.initialize.call(this, pAttributes, pOptions);
      this.set('list', []);
    },
    create: function (pData) {
      console.log("%codoscopeArticleModel | create", "color: blue; font-weight: bold;", pData, this);
      this.set('list', pData);
      this.set('restoredList', _.clone(pData, true));

      this.trigger('set:articleModel', this);
    },
    getNextURL: function () {
      var tmpURL,
        tmpModel;

      /**
       * Check if an Element in Array exists
       */
      if (!this.has('list') || this.get('list').length == 0) {
        return null;
      }

      /**
       * Set latest Model
       */
      tmpModel = this.get('list').shift();

      /**
       * Check if properties available
       */
      if (!tmpModel.hasOwnProperty('variantID')) {
        return null;
      }

      this.set('loadingIndex', this.get('loadingIndex') + 1);
      this.set('currentURL', '/lazyloading/node/' + tmpModel.variantID + '/nojs?page=' + this.get('loadingIndex'));
      tmpURL = this.get('currentURL');
      console.log("%codoscopeArticleModel | getNextURL", "color: blue; font-weight: bold;", tmpURL, this);
      return tmpURL;
    }
  });

})(jQuery, Drupal, drupalSettings, Backbone, BurdaInfinite);