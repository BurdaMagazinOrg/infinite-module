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
                    category: 'wishlist',
                    action: 'wishlist--add-to-wishlist',
                    label: item.name + ' | ' + item.productId,
                    location: window.location.pathname
                });
                break;
            case 'removed':
                TrackingManager.trackEvent({
                    category: 'wishlist',
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
                '<div>' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">\n' +
                '    <g fill="none" fill-rule="evenodd">\n' +
                '        <path fill="#fff" d="M25.738 25.862c.48-1.964 1.694-5.415 2.926-9.404l.806-2.662.37-1.25.159-.535.002-.03-.034.006-.645.005-1.198.002a39.43 39.43 0 0 1-1.957-.049c-1.088-.062-1.716-.194-1.716-.452 0-.292.628-.422 1.716-.468a37.89 37.89 0 0 1 1.957-.014l3.119.053-.98 2.977-.846 2.65c-1.73 5.602-2.812 10.334-3.547 10.856l.022.293-18.61-.057H5.482l.017-.222c-.747-.397-1.926-5.167-3.694-10.859L0 11.03l3.176-.013a38.6 38.6 0 0 1 1.964.048c.808.046 1.363.13 1.598.275l.105-1.38 7.295-.004c2.042.007 3.822.024 5.288.057 2.931.066 4.607.195 4.607.432 0 .27-1.676.398-4.607.45a271.07 271.07 0 0 1-5.288.03c-1.912-.013-4.074-.027-6.44-.04L6.323 27.006l.958.001 17.621-.083c-.199-2.369-.38-4.535-.543-6.453a310.477 310.477 0 0 1-.39-5.346c-.184-2.967-.197-4.672.052-4.692.25-.02.515 1.665.815 4.624.15 1.48.308 3.276.476 5.338l.426 5.466zM6.708 11.733c-.25.146-.793.22-1.568.253-.546.022-1.208.024-1.964.014l-1.203-.022-.649-.015-.081-.017.006.071.153.542c.117.41.238.827.36 1.25l.797 2.66c1.278 4.118 2.58 7.65 3.057 9.571l1.092-14.307zM22.35 9.652c-.41.106-.631-.891-1.519-2.141-.455-.614-1.086-1.307-1.97-1.828-.867-.539-1.983-.884-3.171-.896-1.189.012-2.305.354-3.174.892-.886.52-1.52 1.21-1.977 1.825-.894 1.251-1.116 2.251-1.51 2.148-.195-.062-.267-.326-.173-.821.091-.486.352-1.177.88-1.897a7.022 7.022 0 0 1 2.336-2.022c1.019-.567 2.286-.901 3.618-.912 1.332.011 2.598.35 3.613.92a6.99 6.99 0 0 1 2.32 2.028c.523.72.781 1.408.875 1.892.095.492.032.755-.148.812z"/>\n' +
                '    </g>\n' +
                '</svg>' +
                '</div>';
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
                        category: 'wishlist',
                        action: 'wishlist--click-item-in-wishlist',
                        label: e.currentTarget.getAttribute('data-tracking-label'),
                        location: window.location.pathname,
                        productExtraInformation: e.currentTarget.getAttribute('data-product-extra-information')
                    });
                });
            }

            this.initRemoveButtons(container);
        }

        this.resizeWishlistFlyout();
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
        var storedProductIds = Drupal.behaviors.infiniteWishlist.getStoredProductIds();
        var items = document.getElementsByClassName('item-ecommerce');
        //var items = document.querySelectorAll('.item-ecommerce .img-container');
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var itemImgContainer = item.querySelector('.img-container');

            // check if icon is already present
            if (item.querySelector('.wishlist__icon--add')) {
                var oldIcon = item.querySelector('.wishlist__icon--add');
                oldIcon.parentNode.removeChild(oldIcon);
            }

            var icon = document.createElement('button');
            icon.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" >' +
                '    <g>' +
                '<path id="a" d="M22.978 9.115a6.32 6.32 0 0 0-1.776-4.471c-1.175-1.241-2.952-1.976-4.765-1.896a6.218 6.218 0 0 0-3.79 1.495c-.462.401-.524.516-.62.617-.096-.1-.195-.247-.611-.608a6.243 6.243 0 0 0-3.792-1.504c-1.815-.082-3.595.65-4.774 1.89a6.32 6.32 0 0 0-1.785 4.475c.013 1.364.319 2.674 1.237 3.642 0 0 9.378 9.442 9.733 9.158.347.264 9.766-9.248 9.766-9.248.826-.98 1.163-2.226 1.177-3.55zM12.03 21.742c-.3-.784-8.634-9.154-8.634-9.154a5.379 5.379 0 0 1-1.421-3.492 5.432 5.432 0 0 1 1.49-3.864C4.472 4.146 6.006 3.497 7.59 3.555a5.39 5.39 0 0 1 3.311 1.296c.157.137.369.413.52.574l.612.679.354-.449.326-.385a5.419 5.419 0 0 1 1.457-1.107 5.382 5.382 0 0 1 2.299-.605c1.582-.055 3.112.598 4.115 1.684a5.418 5.418 0 0 1 1.477 3.857 5.367 5.367 0 0 1-1.421 3.478c.001-.001-8.346 8.378-8.61 9.165z"/>' +
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

            itemImgContainer.appendChild(icon);
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
                    category: 'wishlist',
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
        var height = 110 + wl.getBoundingClientRect().top;
        height = height - Number(document.body.style.paddingTop.replace('px', '')); // make up for logged in menu bar
        for (var i = 0; i < list.children.length; i++) {
            height += jQuery(list.children[i]).outerHeight(true);
        }

        if (height + offsetBottom > window.innerHeight) {
            wl.style.height = String(window.innerHeight - offsetBottom - wl.getBoundingClientRect().top) + 'px';
        } else {
            wl.style.height = height + 'px';
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
                window.addEventListener('infinite-wishlist--update-icons', this.injectIcons)
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
