Drupal.behaviors.infiniteWishlist = {
  getWishlist() {
    const wishlist = localStorage.getItem('infinite__wishlist');
    if (wishlist === null) {
      return [];
    }
    return JSON.parse(wishlist);
  },

  setCount() {
    const count = this.getWishlist().length;
    document.getElementById('wishlist__toggle__count').innerText = count;
    if (count) {
      document.getElementById('wishlist__toggle').classList.remove('wishlist--empty');
    }
    else {
      document.getElementById('wishlist__toggle').classList.add('wishlist--empty');
    }
  },

  storeItem(uuid) {
    const wishlist = this.getWishlist();

    let alreadyPresent = false;
    for (let i = 0; i < wishlist.length; i++) {
      if (uuid === wishlist[i].uuid) {
        alreadyPresent = true;
        break;
      }
    }
    if (alreadyPresent) {
      return;
    }
    if (uuid === null) {
      return;
    }
    wishlist.push({
      uuid,
      expires: 0,
      markup: '',
      addedToWishlistTimestamp: Date.now(),
    });
    localStorage.setItem('infinite__wishlist', JSON.stringify(wishlist));

    this.fetchProducts(() => {
      Drupal.behaviors.infiniteWishlist.renderList(document.getElementById('wishlist__list'));
      Drupal.behaviors.infiniteWishlist.track('stored', uuid);
    });
  },

  track(type, uuid) {
    /* global TrackingManager */
    const items = this.getWishlist();
    let item = null;
    for (let i = 0; i < items.length; i++) {
      if (uuid === items[i].uuid) {
        item = items[i];
        break;
      }
    }

    if (item === null) {
      console.error(`product with id ${uuid} not found in wishlist storage`);
      return;
    }

    switch (type) {
      case 'stored':
        TrackingManager.trackEvent({
          category: 'wishlist',
          action: 'wishlist--add-to-wishlist',
          label: `${item.name} | ${item.productId}`,
          location: window.location.pathname,
          eventNonInteraction: false,
        });
        break;
      case 'removed':
        TrackingManager.trackEvent({
          category: 'wishlist',
          action: 'wishlist--remove-wishlist',
          label: `${item.name} | ${item.productId}`,
          location: window.location.pathname,
          productExtraInformation: Drupal.behaviors.infiniteWishlist.getDurationInWishlist(item),
          eventNonInteraction: false,
        });
        break;
      default: break;
    }
  },

  animateStore(originalImage) {
    const originalRect = originalImage.getBoundingClientRect();
    const button = document.getElementById('wishlist__toggle');
    const buttonRect = button.getBoundingClientRect();
    const buttonTop = buttonRect.top;
    const buttonLeft = buttonRect.left;
    const duplicate = document.createElement('img');
    duplicate.setAttribute('src', originalImage.getAttribute('src'));
    duplicate.classList.add('wishlist__store-duplicate');
    duplicate.style.position = 'fixed';
    duplicate.style.top = `${originalRect.top}px`;
    duplicate.style.left = `${originalRect.left}px`;
    duplicate.style.width = `${originalRect.width}px`;
    duplicate.style.height = `${originalRect.height}px`;
    duplicate.style.transformOrigin = 'top left';
    document.body.appendChild(duplicate);
    window.setTimeout(() => {
      const newTop = buttonTop - originalRect.top;
      const newLeft = buttonLeft - originalRect.left;
      duplicate.style.transform = `translate(${newLeft}px, ${newTop}px) scale(0.3)`;
      duplicate.style.opacity = 0;
    }, 0);
  },

  fetchProducts(callback) {
    if (typeof callback === 'undefined') {
      callback = () => {
        Drupal.behaviors.infiniteWishlist.renderList(document.getElementById('wishlist__list'));
      };
    }
    let storedWishlist = this.getWishlist();

    // only fetch products is at least one has not been cached or not in the last hour
    let fetch = false;

    // set to true for debugging purposes
    // fetch = true;

    for (let i = 0; i < storedWishlist.length; i++) {
      const item = storedWishlist[i];


      if (item.expires < Date.now()) {
        fetch = true;
        break;
      }
    }
    if (fetch === false) {
      callback(this.getWishlist());
      return;
    }

    if (window.Worker) {
      const worker = new Worker('/modules/contrib/infinite_base/modules/infinite_wishlist/js/wishlist-worker.js');
      worker.onmessage = (e) => {
        storedWishlist = e.data;
        localStorage.setItem('infinite__wishlist', JSON.stringify(storedWishlist));
        callback(storedWishlist);
        Drupal.behaviors.infiniteWishlist.resizeWishlistFlyout();
      };
      worker.postMessage(storedWishlist);
    }
    else {
      // TODO: do we need a fallback for this? http://caniuse.com/#search=web%20worker
    }
  },

  renderList(container) {
    const items = this.getWishlist();
    const currentlyRenderedUuids = [];
    for (let i = 0; i < container.children.length; i++) {
      currentlyRenderedUuids.push(container.children[i].getAttribute('data-uuid'));
    }
    // check if all product ids are already rendered, if so do nothing
    let allItemsAlreadyRendered = false;
    if (items.length === currentlyRenderedUuids.length) {
      allItemsAlreadyRendered = true;
      for (let i = 0; i < items.length; i++) {
        if (currentlyRenderedUuids.indexOf(items[i].uuid) === -1) {
          allItemsAlreadyRendered = false;
          break;
        }
      }
    }
    if (currentlyRenderedUuids.length > 1 && allItemsAlreadyRendered) {
      return;
    }

    container.innerHTML = '';
    if (items.length === 0) {
      const li = document.createElement('li');
      li.classList.add('wishlist__item--empty');
      li.innerHTML = '<h4>Deine Wunschliste ist noch leer</h4>'
                + '<div>'
                + '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">\n'
                + '    <g fill="none" fill-rule="evenodd">\n'
                + '        <path fill="#fff" d="M25.738 25.862c.48-1.964 1.694-5.415 2.926-9.404l.806-2.662.37-1.25.159-.535.002-.03-.034.006-.645.005-1.198.002a39.43 39.43 0 0 1-1.957-.049c-1.088-.062-1.716-.194-1.716-.452 0-.292.628-.422 1.716-.468a37.89 37.89 0 0 1 1.957-.014l3.119.053-.98 2.977-.846 2.65c-1.73 5.602-2.812 10.334-3.547 10.856l.022.293-18.61-.057H5.482l.017-.222c-.747-.397-1.926-5.167-3.694-10.859L0 11.03l3.176-.013a38.6 38.6 0 0 1 1.964.048c.808.046 1.363.13 1.598.275l.105-1.38 7.295-.004c2.042.007 3.822.024 5.288.057 2.931.066 4.607.195 4.607.432 0 .27-1.676.398-4.607.45a271.07 271.07 0 0 1-5.288.03c-1.912-.013-4.074-.027-6.44-.04L6.323 27.006l.958.001 17.621-.083c-.199-2.369-.38-4.535-.543-6.453a310.477 310.477 0 0 1-.39-5.346c-.184-2.967-.197-4.672.052-4.692.25-.02.515 1.665.815 4.624.15 1.48.308 3.276.476 5.338l.426 5.466zM6.708 11.733c-.25.146-.793.22-1.568.253-.546.022-1.208.024-1.964.014l-1.203-.022-.649-.015-.081-.017.006.071.153.542c.117.41.238.827.36 1.25l.797 2.66c1.278 4.118 2.58 7.65 3.057 9.571l1.092-14.307zM22.35 9.652c-.41.106-.631-.891-1.519-2.141-.455-.614-1.086-1.307-1.97-1.828-.867-.539-1.983-.884-3.171-.896-1.189.012-2.305.354-3.174.892-.886.52-1.52 1.21-1.977 1.825-.894 1.251-1.116 2.251-1.51 2.148-.195-.062-.267-.326-.173-.821.091-.486.352-1.177.88-1.897a7.022 7.022 0 0 1 2.336-2.022c1.019-.567 2.286-.901 3.618-.912 1.332.011 2.598.35 3.613.92a6.99 6.99 0 0 1 2.32 2.028c.523.72.781 1.408.875 1.892.095.492.032.755-.148.812z"/>\n'
                + '    </g>\n'
                + '</svg>'
                + '</div>';
      container.appendChild(li);
    }
    else {
      for (let i = items.length - 1; i >= 0; i--) {
        const item = items[i];
        const li = document.createElement('li');
        li.setAttribute('data-uuid', item.uuid);
        li.innerHTML = item.markup;
        container.appendChild(li);
        const link = li.querySelector('a');
        if (link.getAttribute('data-provider') === 'tipser') {
          link.addEventListener('click', (e) => {
            e.preventDefault();

            const wishlist = document.getElementById('wishlist');
            wishlist.classList.remove('open');

            const productId = e.currentTarget.getAttribute('data-product-id');
            Drupal.behaviors.instyleInfiniteTipser.loadTipser(() => {
              Drupal.behaviors.instyleInfiniteTipser.hideTipserIcons();
              Drupal.behaviors.instyleInfiniteTipser.openTipserProductDetailPage(productId);
            });
          });
        }
        link.setAttribute('data-tracking-label', `${item.name} | ${item.productId}`);
        link.setAttribute('data-product-extra-information', Drupal.behaviors.infiniteWishlist.getDurationInWishlist(item));
        link.addEventListener('click', (e) => {
          TrackingManager.trackEvent({
            category: 'wishlist',
            action: 'wishlist--click-item-in-wishlist',
            label: e.currentTarget.getAttribute('data-tracking-label'),
            location: window.location.pathname,
            productExtraInformation: e.currentTarget.getAttribute('data-product-extra-information'),
            eventNonInteraction: false,
          });
        });
      }

      this.initRemoveButtons(container);
    }

    this.resizeWishlistFlyout();
  },

  getDurationInWishlist(item) {
    function convertMS(ms) {
      let h;
      const d = Math.floor(h / 24);
      let m;
      let s;
      s = Math.floor(ms / 1000);
      m = Math.floor(s / 60);
      s %= 60;
      h = Math.floor(m / 60);
      m %= 60;
      h %= 24;
      return {
        d, h, m, s,
      };
    }

    const dateData = convertMS(Date.now() - item.addedToWishlistTimestamp);
    return `${dateData.d}d:${dateData.h}h:${dateData.m}m:${dateData.s}s`;
  },

  getStoredProductIds() {
    const storedProductIds = [];
    const wishlistItems = this.getWishlist();
    for (let i = 0; i < wishlistItems.length; i++) {
      storedProductIds.push(wishlistItems[i].uuid);
    }
    return storedProductIds;
  },

  injectIcons() {
    const storedProductIds = Drupal.behaviors.infiniteWishlist.getStoredProductIds();
    const items = document.getElementsByClassName('item-ecommerce');
    // var items = document.querySelectorAll('.item-ecommerce .img-container');
    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      const itemImgContainer = item.querySelector('.img-container');

      // check if icon is already present
      if (item.querySelector('.wishlist__icon--add')) {
        const oldIcon = item.querySelector('.wishlist__icon--add');
        oldIcon.parentNode.removeChild(oldIcon);
      }

      const icon = document.createElement('BUTTON');
      icon.innerHTML = ' <svg id="svg__wishlist__icon--add" data-name="Wishlist Icon Add" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28.02 24.66">'
                + '<g id="Symbols">'
                    + '<g id="icons_32_wishlist" data-name="icons/32/wishlist">'
                        + '<g id="Group">'
                            + '<g id="Mask">'
                                + '<path id="svg__wishlist__icon--add__fill" d="M30,12.2a8.35,8.35,0,0,0-2.2-5.7,7.77,7.77,0,0,0-6-2.4,7.85,7.85,0,0,0-5.7,2.8,7.85,7.85,0,0,0-5.7-2.8A8.45,8.45,0,0,0,4.3,6.6,8,8,0,0,0,2,12.2a6,6,0,0,0,1.7,4.7S15.7,29,16,28.7c.4.2,12.4-11.9,12.4-11.9A6.34,6.34,0,0,0,30,12.2Z" transform="translate(-2 -4.09)"/>'
                            + '</g>'
                        + '</g>'
                    + '</g>'
                    + '<g id="Symbols-2" data-name="Symbols">'
                        + '<g id="icons_32_add_to_wishlist" data-name="icons/32/add_to_wishlist">'
                            + '<g id="Mask-2" data-name="Mask">'
                                + '<path id="svg__wishlist__icon--add__ouline" data-name="path-1" d="M30,12.24a8,8,0,0,0-8.29-8.08,8,8,0,0,0-3.35.91A7.83,7.83,0,0,0,16,7,7.82,7.82,0,0,0,13.7,5.07,8,8,0,0,0,2,12.24,6.37,6.37,0,0,0,3.71,17S15.7,29,16.05,28.74c.36.25,12.36-11.85,12.36-11.85A6.67,6.67,0,0,0,30,12.24ZM16,28.6c-.33-.85-4.53-5.09-10.86-11.36h0a7,7,0,0,1-2.24-5A7.1,7.1,0,0,1,13.33,5.77,8.24,8.24,0,0,1,16,8.19a7.79,7.79,0,0,1,2.71-2.42,7,7,0,0,1,3-.8,7.09,7.09,0,0,1,7.34,7.26,7.49,7.49,0,0,1-2.34,5.09h0C20.5,23.55,16.34,27.75,16,28.6Z" transform="translate(-2 -4.09)"/>'
                            + '</g>'
                        + '</g>'
                    + '</g>'
                + '</g>'
            + '</svg>';
      icon.uuid = item.getAttribute('data-uuid');

      icon.classList.add('wishlist__icon--add');
      if (storedProductIds.indexOf(icon.uuid) > -1) {
        icon.classList.add('in-wishlist');
      }
      icon.addEventListener('click', (e) => {
        e.stopPropagation();
        if (e.currentTarget.classList.contains('in-wishlist')) {
          e.currentTarget.classList.remove('in-wishlist');
          Drupal.behaviors.infiniteWishlist.removeFromWishlist(e.currentTarget.uuid);
        }
        else {
          e.currentTarget.classList.add('in-wishlist');
          Drupal.behaviors.infiniteWishlist.storeItem(e.currentTarget.uuid);
          Drupal.behaviors.infiniteWishlist.animateStore(
            e.currentTarget.parentNode.querySelector('img'),
          );
          window.setTimeout(() => {
            Drupal.behaviors.infiniteWishlist.setCount();
          }, 1000);
        }
      });

      itemImgContainer.appendChild(icon);
    }
  },

  dispatchToggleEvent() {
    const toggleStatus = document.getElementById('wishlist').classList.contains('open');
    const event = new CustomEvent('wishlist-overlay', { detail: { isLayerVisible: toggleStatus } });
    window.dispatchEvent(event);
  },

  handleClick(e) {
    const wishlist = document.getElementById('wishlist');
    const closest = BurdaInfinite.utils.BaseUtils.closest;
    const isWishlistOpen = wishlist.classList.contains('open');
    const clickedWishlistIcon = !!closest(e.target, '#wishlist__toggle');
    const clickedOutsideOverlay = !closest(e.target, '#wishlist') && isWishlistOpen;
    if (clickedWishlistIcon || clickedOutsideOverlay) {
      Drupal.behaviors.infiniteWishlist.resizeWishlistFlyout();
      wishlist.classList.toggle('open');
      if (wishlist.classList.contains('open')) {
        TrackingManager.trackEvent({
          category: 'wishlist',
          action: 'wishlist--click-wishlist-icon',
          location: window.location.pathname,
          eventNonInteraction: false,
        });
      }
      this.dispatchToggleEvent();
    }
  },

  enableHeaderIcon() {
    const touchOrClickEvent = 'ontouchstart' in document.documentElement ? 'touchstart' : 'click';

    const button = document.getElementById('wishlist__toggle');
    if (button.injectedHeaderIcon) {
      return;
    }

    const wishlist = document.getElementById('wishlist');
    const closeButton = document.querySelector('.wishlist__close-button');
    window.addEventListener(touchOrClickEvent, this.handleClick.bind(this));
    button.addEventListener('mouseover', () => {
      // only prefetch if overlay is not currently open
      if (wishlist.classList.contains('open') === false) {
        Drupal.behaviors.infiniteWishlist.fetchProducts();
      }
    });
    closeButton.addEventListener(touchOrClickEvent, this.handleClick.bind(this));
    window.addEventListener('tipser-overlay', (e) => {
      if (e.detail.isLayerVisible) {
        wishlist.classList.remove('open');
      }
    });

    button.injectedHeaderIcon = true;

    // on front page handle movement of icon on scroll
    if (document.body.classList.contains('page-front')) {
      const mainNav = document.getElementById('menu-main-navigation');
      const userMenuHeader = document.getElementById('user-navigation--header');
      const userMenumainNav = document.getElementById('user-navigation--main-navigation');
      const icon = mainNav.querySelector('.flyout--wishlist');
      icon.parentNode.removeChild(icon);

      const moveIcon = () => {
        if (mainNav.classList.contains('stuck')) {
          if (mainNav.querySelector('#wishlist__toggle') === null) { // button is in social bar
            userMenumainNav.append(button.parentNode);
            Drupal.behaviors.infiniteWishlist.resizeWishlistFlyout();
          }
        }
        else if (mainNav.querySelector('#wishlist__toggle')) { // button is in main nav
          userMenuHeader.appendChild(button.parentNode);
          Drupal.behaviors.infiniteWishlist.resizeWishlistFlyout();
        }
      };
      window.addEventListener('scroll', moveIcon);
      window.addEventListener('load', moveIcon);
      window.addEventListener('scroll', () => {
        if (document.querySelector('#wishlist.open')) {
          Drupal.behaviors.infiniteWishlist.resizeWishlistFlyout();
        }
      });
      window.addEventListener('resize', () => {
        if (document.querySelector('#wishlist.open')) {
          Drupal.behaviors.infiniteWishlist.resizeWishlistFlyout();
        }
      });
    }
  },

  resizeWishlistFlyout() {
    const offsetBottom = 70;

    const wl = document.getElementById('wishlist');
    const list = document.getElementById('wishlist__list');
    let height = 110 + wl.getBoundingClientRect().top;
    height -= Number(document.body.style.paddingTop.replace('px', '')); // make up for logged in menu bar
    for (let i = 0; i < list.children.length; i++) {
      height += jQuery(list.children[i]).outerHeight(true);
    }

    if (height + offsetBottom > window.innerHeight) {
      wl.style.height = `${String(window.innerHeight - offsetBottom - wl.getBoundingClientRect().top)}px`;
    }
    else {
      wl.style.height = `${height}px`;
    }
  },

  initRemoveButtons(container) {
    const buttons = container.querySelectorAll('[data-wishlist-remove]');
    for (let i = 0; i < buttons.length; i++) {
      const button = buttons[i];
      button.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        Drupal.behaviors.infiniteWishlist.removeFromWishlist(
          e.currentTarget.getAttribute('data-wishlist-remove'),
        );
      });
    }
  },

  removeFromWishlist(uuid) {
    const wishlist = this.getWishlist();
    for (let i = 0; i < wishlist.length; i++) {
      const item = wishlist[i];
      if (uuid === item.uuid) {
        wishlist.splice(i, 1);
        break;
      }
    }

    this.track('removed', uuid);
    localStorage.setItem('infinite__wishlist', JSON.stringify(wishlist));


    // remove from dom
    function firstParentThatMatches(selector, childElement) {
      let parent = childElement.parentNode;
      while (
        parent
                && typeof parent.matches === 'function'
                && parent.matches(selector) === false
      ) {
        parent = parent.parentNode;
      }
      return typeof parent.matches === 'function'
            && parent.matches(selector) ? parent : null;
    }
    const items = document.querySelectorAll(`[data-wishlist-remove="${uuid}"]`);
    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      const li = firstParentThatMatches('li', item);
      li.parentNode.removeChild(li);
    }

    this.setCount();
    this.toggleIconsAccordingToWishlistStatus();
    this.fetchProducts();
  },

  toggleIconsAccordingToWishlistStatus() {
    const injectedIcons = document.querySelectorAll('.wishlist__icon--add');
    const storedProductIds = Drupal.behaviors.infiniteWishlist.getStoredProductIds();
    for (let i = 0; i < injectedIcons.length; i++) {
      const icon = injectedIcons[i];
      if (storedProductIds.indexOf(icon.uuid) > -1) {
        icon.classList.add('in-wishlist');
      }
      else {
        icon.classList.remove('in-wishlist');
      }
    }
  },

  onFocus() {
    Drupal.behaviors.infiniteWishlist.fetchProducts();
    Drupal.behaviors.infiniteWishlist.setCount();
    // handle already injected icons
    Drupal.behaviors.infiniteWishlist.toggleIconsAccordingToWishlistStatus();
  },

  attach() {
    try {
      const test = 'local_storage_availability_test';
      localStorage.setItem(test, test);
      localStorage.removeItem(test);

      if (window.Worker) {
        this.enableHeaderIcon();
        this.injectIcons();
        this.setCount();

        window.removeEventListener('focus', this.onFocus);
        window.addEventListener('focus', this.onFocus);
        window.addEventListener('infinite-wishlist--update-icons', this.injectIcons);
      }
      else {
        // TODO: handle
        // window worker is not available
      }
    }
    catch (e) { // local storage is unavailable
      // TODO: handle
      return false;
    }
  },

  detach() {
    window.removeEventListener('focus', this.onFocus);

    const buttons = document.getElementsByClassName('wishlist__icon--add');
    for (let i = 0; i < buttons.length; i++) {
      const button = buttons[i];
      if (button.parentNode) {
        button.parentNode.removeChild(button);
      }
    }

    const headerIcon = document.getElementById('wishlist__toggle');
    const clone = headerIcon.cloneNode();
    // move all child elements from the original to the clone
    while (headerIcon.firstChild) {
      clone.appendChild(headerIcon.lastChild);
    }

    headerIcon.parentNode.replaceChild(clone, headerIcon);
  },
};
