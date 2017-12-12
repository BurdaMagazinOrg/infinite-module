Drupal.behaviors.infiniteWishlist = {
    getWishlist: function () {
        var wishlist = localStorage.getItem('infinite__wishlist');
        if (null === wishlist) {
            return [];
        } else {
            return JSON.parse(wishlist);
        }
    },

    setCount: function() {
        var count = this.getWishlist().length;
        document.getElementById('wishlist__toggle__count').innerText = count;
        if (count) {
            document.getElementById('wishlist__toggle').classList.remove('wishlist--empty');
        } else {
            document.getElementById('wishlist__toggle').classList.add('wishlist--empty');
        }
    },

    storeItem: function (uuid) {
        var wishlist = this.getWishlist();

        var alreadyPresent = false;
        for (var i = 0; i < wishlist.length; i++) {
            if (uuid === wishlist[i].uuid) {
                alreadyPresent = true;
                break;
            }
        }
        if (alreadyPresent) {
            this.growl('item ' + uuid + ' is already present in wishlist');
            return;
        }
        if (null === uuid) {
            this.growl('Unable to retrieve product id');
            return;
        }
        wishlist.push({
            uuid: uuid,
            expires: 0,
            markup: '',
            addedToWishlistTimestamp: Date.now()
        });
        localStorage.setItem('infinite__wishlist', JSON.stringify(wishlist));

        this.fetchProducts(function () {
            Drupal.behaviors.infiniteWishlist.renderList(document.getElementById('wishlist__list'));
            Drupal.behaviors.infiniteWishlist.track('stored', uuid);
        });

        this.growl('added item ' + uuid + ' to wishlist');
    },

    track: function(type, uuid) {
        var items = this.getWishlist();
        var item = null;
        for (var i = 0; i < items.length; i++) {
            if (uuid === items[i].uuid) {
                item = items[i];
                break;
            }
        }

        if (null === item) {
            console.error('product with id ' + uuid + ' not found in wishlist storage');
            return;
        }

        switch (type) {
            case 'stored':
                TrackingManager.trackEvent({
                    category: 'click',
                    action: 'wishlist--add-to-wishlist',
                    label: item.name + ' | ' + item.productId,
                    location: window.location.pathname
                });
                break;
            case 'removed':
                TrackingManager.trackEvent({
                    category: 'click',
                    action: 'wishlist--remove-wishlist',
                    label: item.name + ' | ' + item.productId,
                    location: window.location.pathname,
                    productExtraInformation: Drupal.behaviors.infiniteWishlist.getDurationInWishlist(item)
                });
                break;
        }
    },

    animateStore: function (originalImage) {
        var originalRect = originalImage.getBoundingClientRect();
        var button = document.getElementById('wishlist__toggle');
        var buttonRect = button.getBoundingClientRect();
        var buttonTop = buttonRect.top;
        var buttonLeft = buttonRect.left;
        var duplicate = document.createElement('img');
        duplicate.setAttribute('src', originalImage.getAttribute('src'));
        duplicate.classList.add('wishlist__store-duplicate');
        duplicate.style.position = 'fixed';
        duplicate.style.top = originalRect.top + 'px';
        duplicate.style.left = originalRect.left + 'px';
        duplicate.style.width = originalRect.width + 'px';
        duplicate.style.height = originalRect.height + 'px';
        duplicate.style.transformOrigin = 'top left';
        document.body.appendChild(duplicate);
        window.setTimeout(function () {
            var newTop = buttonTop - originalRect.top;
            var newLeft = buttonLeft - originalRect.left;
            duplicate.style.transform = 'translate(' + newLeft + 'px, ' + newTop + 'px) scale(0.3)';
            duplicate.style.opacity = 0;
        }, 0);
    },

    /**
     * Use this for debugging prefetch
     * @param message
     */
    growl: function (message) {
        return;
        // var growl = document.createElement('div');
        // growl.setAttribute('style', 'position: fixed; bottom: 0; right: 0; background: black; color: deeppink; font-weight: bold; padding: 10px 20px');
        // growl.innerText = message;
        // document.body.appendChild(growl);
        // window.setTimeout(function () {
        //     document.body.removeChild(growl);
        // }, 5000);
    },

    fetchProducts: function (callback) {
        if (typeof callback === 'undefined') {
            callback = function (storedWishlist) {
                Drupal.behaviors.infiniteWishlist.renderList(document.getElementById('wishlist__list'));
            }
        }
        var storedWishlist = this.getWishlist();

        // only fetch products is at least one has not been cached or not in the last hour
        var fetch = false;

        // set to true for debugging purposes
        // fetch = true;

        for (var i = 0; i < storedWishlist.length; i++) {
            var item = storedWishlist[i];


            if (item.expires < Date.now()) {
                fetch = true;
                break;
            }
        }
        if (false === fetch) {
            this.growl('everything already fetched');
            callback(this.getWishlist());
            return;
        }

        if (window.Worker) {
            this.growl('prefetch products');
            var worker = new Worker('/modules/contrib/infinite_base/modules/infinite_wishlist/js/wishlist-worker.js');
            worker.onmessage = function (e) {
                storedWishlist = e.data;
                localStorage.setItem('infinite__wishlist', JSON.stringify(storedWishlist));
                callback(storedWishlist);
                Drupal.behaviors.infiniteWishlist.resizeWishlistFlyout();
            };
            worker.postMessage(storedWishlist);
        } else {
            // TODO: do we need a fallback for this? http://caniuse.com/#search=web%20worker
        }
    },

    renderList: function (container) {
        var items = this.getWishlist();
        var currentlyRenderedUuids = [];
        for (var i = 0; i < container.children.length; i++) {
            currentlyRenderedUuids.push(container.children[i].getAttribute('data-uuid'));
        }
        // check if all product ids are already rendered, if so do nothing
        var allItemsAlreadyRendered = false;
        if (items.length === currentlyRenderedUuids.length) {
            allItemsAlreadyRendered = true;
            for (var i = 0; i < items.length; i++) {
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
        if (0 === items.length) {
            var li = document.createElement('li');
            li.classList.add('wishlist__item--empty');
            li.innerHTML = '<h4>Deine Wunschliste ist noch leer</h4>' +
                '<div><img src="/modules/contrib/infinite_base/modules/infinite_wishlist/icons/wishlist--empty.svg" /></div>';
            container.appendChild(li);
        } else {
            for (var i = items.length - 1; i >= 0; i--) {
                var item = items[i];
                var li = document.createElement('li');
                li.setAttribute('data-uuid', item.uuid);
                li.innerHTML = item.markup;
                container.appendChild(li);
                var link = li.querySelector('a');
                link.setAttribute('data-tracking-label', item.name + ' | ' + item.productId);
                link.setAttribute('data-product-extra-information', Drupal.behaviors.infiniteWishlist.getDurationInWishlist(item));
                link.addEventListener('click', function (e) {
                    TrackingManager.trackEvent({
                        category: 'click',
                        action: 'wishlist--click-item-in-wishlist',
                        label: e.currentTarget.getAttribute('data-tracking-label'),
                        location: window.location.pathname,
                        productExtraInformation: e.currentTarget.getAttribute('data-product-extra-information')
                    });
                });
            }

            this.initRemoveButtons(container);
        }
    },

    getDurationInWishlist: function(item) {
        function convertMS(ms) {
            var d, h, m, s;
            s = Math.floor(ms / 1000);
            m = Math.floor(s / 60);
            s = s % 60;
            h = Math.floor(m / 60);
            m = m % 60;
            d = Math.floor(h / 24);
            h = h % 24;
            return { d: d, h: h, m: m, s: s };
        }

        var dateData = convertMS(Date.now() - item.addedToWishlistTimestamp);
        return dateData.d + 'd:' + dateData.h + 'h:' + dateData.m + 'm:' + dateData.s + 's';
    },

    getStoredProductIds: function () {
        var storedProductIds = [];
        var wishlistItems = this.getWishlist();
        for (var i = 0; i < wishlistItems.length; i++) {
            storedProductIds.push(wishlistItems[i].uuid);
        }
        return storedProductIds;
    },

    injectIcons: function () {
        var storedProductIds = this.getStoredProductIds();
        var items = document.getElementsByClassName('item-ecommerce');
        for (var i = 0; i < items.length; i++) {
            var item = items[i];

            // check if icon is already present
            if (item.querySelector('.wishlist__icon--add')) {
                var oldIcon = item.querySelector('.wishlist__icon--add');
                oldIcon.parentNode.removeChild(oldIcon);
            }

            var icon = document.createElement('button');
            icon.innerHTML = '<svg width="100%" height="100%" viewBox="-1 -1 23 22" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" >' +
                '    <g transform="matrix(1,0,0,1,-8,-7.99976)">' +
                '        <path d="M28.989,14.042C28.989,14.41 28.961,14.782 28.907,15.157C28.507,17.921 26.713,20.753 23.985,23.579C22.491,25.12 20.868,26.532 19.134,27.798C18.774,28.051 18.299,28.068 17.922,27.841C17.837,27.791 17.688,27.697 17.484,27.564C15.896,26.524 14.407,25.341 13.034,24.03C9.903,21.028 8,17.836 8,14.523C8,7.698 15.499,6.09 18.502,10.547C21.512,6.152 28.989,7.525 28.989,14.042Z"/>' +
                '    </g>' +
                '</svg>';
            icon.uuid = item.getAttribute('data-uuid');
            icon.classList.add('wishlist__icon--add');
            if (storedProductIds.indexOf(icon.uuid) > -1) {
                icon.classList.add('in-wishlist');
            }
            icon.addEventListener('click', function (e) {
                e.stopPropagation();
                if (e.currentTarget.classList.contains('in-wishlist')) {
                    e.currentTarget.classList.remove('in-wishlist');
                    Drupal.behaviors.infiniteWishlist.removeFromWishlist(e.currentTarget.uuid);
                } else {
                    e.currentTarget.classList.add('in-wishlist');
                    Drupal.behaviors.infiniteWishlist.storeItem(e.currentTarget.uuid);
                    Drupal.behaviors.infiniteWishlist.animateStore(
                        e.currentTarget.parentNode.querySelector('img')
                    );
                    window.setTimeout(function () {
                        Drupal.behaviors.infiniteWishlist.setCount();
                    }, 1000);
                }
            });

            item.appendChild(icon);
        }
    },

    enableHeaderIcon: function () {
        var button = document.getElementById('wishlist__toggle');
        if (button.injectedHeaderIcon) {
            return;
        }

        var wishlist = document.getElementById('wishlist');
        button.addEventListener('click', function () {
            Drupal.behaviors.infiniteWishlist.resizeWishlistFlyout();
            wishlist.classList.toggle('open');
            if (wishlist.classList.contains('open')) {
                TrackingManager.trackEvent({
                    category: 'click',
                    action: 'wishlist--click-wishlist-icon',
                    location: window.location.pathname
                });
            }
        });
        button.addEventListener('mouseover', function () {
            // only prefetch if overlay is not currently open
            if (false === wishlist.classList.contains('open')) {
                Drupal.behaviors.infiniteWishlist.fetchProducts();
            }
        });

        button.injectedHeaderIcon = true;

        // on front page handle movement of icon on scroll
        if (document.body.classList.contains('page-front')) {
            var mainNav = document.getElementById('menu-main-navigation');
            var icon = mainNav
                .querySelector('.flyout--wishlist');
            icon.parentNode.removeChild(icon);

            var moveIcon = function () {
                if (mainNav.classList.contains('stuck')) {
                    if (null === mainNav.querySelector('#wishlist__toggle')) { // button is in social bar
                        mainNav.insertBefore(button.parentNode, mainNav.querySelector('.icon-search'));
                        Drupal.behaviors.infiniteWishlist.resizeWishlistFlyout();
                    }
                } else {
                    if (mainNav.querySelector('#wishlist__toggle')) { // button is in main nav
                        document.querySelector('.socials-bar').appendChild(button.parentNode);
                        Drupal.behaviors.infiniteWishlist.resizeWishlistFlyout();
                    }
                }
            };
            window.addEventListener('scroll', moveIcon);
            window.addEventListener('load', moveIcon);
            window.addEventListener('scroll', function () {
                if(document.querySelector('#wishlist.open')) {
                    Drupal.behaviors.infiniteWishlist.resizeWishlistFlyout();
                }
            });
            window.addEventListener('resize', function () {
                if(document.querySelector('#wishlist.open')) {
                    Drupal.behaviors.infiniteWishlist.resizeWishlistFlyout();
                }
            });
        }
    },

    resizeWishlistFlyout: function () {
        var offsetBottom = 70;

        var wl = document.getElementById('wishlist');
        var list = document.getElementById('wishlist__list');
        var height = 180 + wl.getBoundingClientRect().top;
        for (var i = 0; i < list.children.length; i++) {
            height += jQuery(list.children[i]).outerHeight(true);
        }

        if (height + offsetBottom > window.innerHeight) {
            wl.style.height = String(window.innerHeight - offsetBottom - wl.getBoundingClientRect().top) + 'px';
        } else {
            wl.style.height = 'auto';
        }
    },

    initRemoveButtons: function (container) {
        var buttons = container.querySelectorAll('[data-wishlist-remove]');
        for (var i = 0; i < buttons.length; i++) {
            var button = buttons[i];
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                Drupal.behaviors.infiniteWishlist.removeFromWishlist(
                    e.currentTarget.getAttribute('data-wishlist-remove')
                )
            });
        }
    },

    removeFromWishlist: function (uuid) {
        var wishlist = this.getWishlist();
        for (var i = 0; i < wishlist.length; i++) {
            var item = wishlist[i];
            if (uuid === item.uuid) {
                wishlist.splice(i, 1);
                break;
            }
        }

        this.growl('Removed item with uuid' + uuid);

        this.track('removed', uuid);
        localStorage.setItem('infinite__wishlist', JSON.stringify(wishlist));


        // remove from dom
        function firstParentThatMatches(selector, childElement) {
            var parent = childElement.parentNode;
            while (
                parent &&
                typeof parent.matches === 'function' &&
                false === parent.matches(selector)
                ) {
                parent = parent.parentNode;
            }
            return typeof parent.matches === 'function' &&
            parent.matches(selector) ? parent : null;
        }
        var items = document.querySelectorAll('[data-wishlist-remove="' + uuid + '"]');
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var li = firstParentThatMatches('li', item);
            li.parentNode.removeChild(li);
        }

        this.setCount();
        this.toggleIconsAccordingToWishlistStatus();
        this.fetchProducts();
    },

    toggleIconsAccordingToWishlistStatus: function () {
        var injectedIcons = document.querySelectorAll('.wishlist__icon--add');
        var storedProductIds = Drupal.behaviors.infiniteWishlist.getStoredProductIds();
        for (var i = 0; i < injectedIcons.length; i++) {
            var icon = injectedIcons[i];
            if (storedProductIds.indexOf(icon.uuid) > -1) {
                icon.classList.add('in-wishlist');
            } else {
                icon.classList.remove('in-wishlist');
            }
        }
    },

    onFocus: function () {
        Drupal.behaviors.infiniteWishlist.fetchProducts();
        Drupal.behaviors.infiniteWishlist.setCount();
        // handle already injected icons
        Drupal.behaviors.infiniteWishlist.toggleIconsAccordingToWishlistStatus();
    },

    attach: function () {
        try {
            var test = 'local_storage_availability_test';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);

            if (window.Worker) {
                this.enableHeaderIcon();
                this.injectIcons();
                this.setCount();

                window.removeEventListener('focus', this.onFocus);
                window.addEventListener('focus', this.onFocus);
            } else {
                // TODO: handle
                // window worker is not available
            }
        } catch (e) { // local storage is unavailable
            // TODO: handle
            return false;
        }
    },

    detach: function () {
        window.removeEventListener('focus', this.onFocus);

        var buttons = document.getElementsByClassName('wishlist__icon--add');
        for (var i = 0; i < buttons.length; i++) {
            var button = buttons[i];
            if (button.parentNode) {
                button.parentNode.removeChild(button);
            }
        }

        var headerIcon = document.getElementById('wishlist__toggle');
        var clone = headerIcon.cloneNode();
        // move all child elements from the original to the clone
        while (headerIcon.firstChild) {
            clone.appendChild(headerIcon.lastChild);
        }

        headerIcon.parentNode.replaceChild(clone, headerIcon);
    }
};
