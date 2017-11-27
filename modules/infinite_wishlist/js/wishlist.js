Drupal.behaviors.infiniteWishlist = {
    getWishlist: function () {
        var wishlist = localStorage.getItem('infinite_wishlist');
        if (null === wishlist) {
            return [];
        } else {
            return JSON.parse(wishlist);
        }
    },

    setCount: function() {
        document.getElementById('wishlist__toggle__count').innerText = this.getWishlist().length;
    },

    storeItem: function (productId) {
        var wishlist = this.getWishlist();

        var alreadyPresent = false;
        for (var i = 0; i < wishlist.length; i++) {
            if (productId === wishlist[i].productId) {
                alreadyPresent = true;
                break;
            }
        }
        if (alreadyPresent) {
            this.growl('item ' + productId + ' is already present in wishlist');
            return;
        }
        if (null === productId) {
            this.growl('Unable to retrieve product id');
            return;
        }
        wishlist.push({
            productId: productId,
            expires: 0,
            markup: ''
        });
        localStorage.setItem('infinite_wishlist', JSON.stringify(wishlist));

        this.fetchProducts(function () {
            Drupal.behaviors.infiniteWishlist.renderList(document.getElementById('wishlist__list'));
            Drupal.behaviors.infiniteWishlist.track('stored', productId);
        });

        this.growl('added item ' + productId + ' to wishlist');
    },

    track: function(type, productId) {
        var items = this.getWishlist();
        var item = null;
        for (var i = 0; i < items.length; i++) {
            if (productId === items[i].productId) {
                item = items[i];
                break;
            }
        }

        if (null === item) {
            throw new Error('product with id ' + productId + ' not found in wishlist storage');
        }
        console.log('DEBUG', item);
        switch (type) {
            case 'stored':
                TrackingManager.ecommerce({
                    'name': item.name,
                    'id': item.productId,
                    'price': item.price,
                    'brand': item.brand,
                    'category': item.category,
                    'quantity': 1,
                    'currencyCode': item.currency
                }, 'addToCart');
                break;
            case 'removed':
                TrackingManager.ecommerce({
                    'name': item.name,
                    'id': item.productId,
                    'price': item.price,
                    'brand': item.brand,
                    'category': item.category,
                    'quantity': 1,
                    'currencyCode': item.currency
                }, 'removeFromCart');
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
                localStorage.setItem('infinite_wishlist', JSON.stringify(storedWishlist));
                callback(storedWishlist);
            };
            worker.postMessage(storedWishlist);
        } else {
            // TODO: do we need a fallback for this? http://caniuse.com/#search=web%20worker
        }
    },

    renderList: function (container) {
        var items = this.getWishlist();
        var currentlyRenderedProductIds = [];
        for (var i = 0; i < container.children.length; i++) {
            currentlyRenderedProductIds.push(container.children[i].getAttribute('data-product-id'));
        }
        // check if all product ids are already rendered, if so do nothing
        var allItemsAlreadyRendered = true;
        if (items.length === currentlyRenderedProductIds.length) {
            for (var i = 0; i < items.length; i++) {
                if (currentlyRenderedProductIds.indexOf(items[i].productId) === -1) {
                    allItemsAlreadyRendered = false;
                    break;
                }
            }
        }
        if (currentlyRenderedProductIds.length > 1 && allItemsAlreadyRendered) {
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
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var li = document.createElement('li');
                li.setAttribute('data-product-id', item.productId);
                li.innerHTML = item.markup;
                container.appendChild(li);
            }

            this.initRemoveButtons(container);
        }
    },

    getStoredProductIds: function () {
        var storedProductIds = [];
        var wishlistItems = this.getWishlist();
        for (var i = 0; i < wishlistItems.length; i++) {
            storedProductIds.push(wishlistItems[i].productId);
        }
        return storedProductIds;
    },

    injectIcons: function () {
        var storedProductIds = this.getStoredProductIds();
        var items = document.getElementsByClassName('item-ecommerce');
        for (var i = 0; i < items.length; i++) {
            var item = items[i];

            var icon = document.createElement('button');
            icon.innerHTML = '<svg width="100%" height="100%" viewBox="-1 -1 23 22" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" >' +
                '    <g transform="matrix(1,0,0,1,-8,-7.99976)">' +
                '        <path d="M28.989,14.042C28.989,14.41 28.961,14.782 28.907,15.157C28.507,17.921 26.713,20.753 23.985,23.579C22.491,25.12 20.868,26.532 19.134,27.798C18.774,28.051 18.299,28.068 17.922,27.841C17.837,27.791 17.688,27.697 17.484,27.564C15.896,26.524 14.407,25.341 13.034,24.03C9.903,21.028 8,17.836 8,14.523C8,7.698 15.499,6.09 18.502,10.547C21.512,6.152 28.989,7.525 28.989,14.042Z"/>' +
                '    </g>' +
                '</svg>';
            icon.productId = item.getAttribute('data-product-id');
            icon.classList.add('wishlist__icon--add');
            if (storedProductIds.indexOf(icon.productId) > -1) {
                icon.classList.add('in-wishlist');
            }
            icon.addEventListener('click', function (e) {
                e.stopPropagation();
                if (e.currentTarget.classList.contains('in-wishlist')) {
                    e.currentTarget.classList.remove('in-wishlist');
                    Drupal.behaviors.infiniteWishlist.removeFromWishlist(e.currentTarget.productId);
                } else {
                    e.currentTarget.classList.add('in-wishlist');
                    Drupal.behaviors.infiniteWishlist.storeItem(e.currentTarget.productId);
                    Drupal.behaviors.infiniteWishlist.animateStore(
                        e.currentTarget.parentNode.querySelector('img')
                    );
                    window.setTimeout(function () {
                        Drupal.behaviors.infiniteWishlist.setCount();
                    }, 1000);
                }
            });

            item.insertBefore(icon, item.firstChild);
        }
    },

    injectHeaderIcon: function () {
        var button = document.getElementById('wishlist__toggle');
        if (button.injectedHeaderIcon) {
            return;
        }

        var wishlist = document.getElementById('wishlist');
        button.addEventListener('click', function () {
            wishlist.classList.toggle('open');
        });
        button.addEventListener('mouseover', function () {
            // only prefetch if overlay is not currently open
            if (false === wishlist.classList.contains('open')) {
                Drupal.behaviors.infiniteWishlist.fetchProducts();
            }
        });
        button.injectedHeaderIcon = true;
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

    removeFromWishlist: function (productId) {
        var wishlist = this.getWishlist();
        for (var i = 0; i < wishlist.length; i++) {
            var item = wishlist[i];
            if (productId === item.productId) {
                wishlist.splice(i, 1);
                break;
            }
        }

        this.growl('Removed item with productId' + productId);

        this.track('removed', productId);
        localStorage.setItem('infinite_wishlist', JSON.stringify(wishlist));


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
        var items = document.querySelectorAll('[data-wishlist-remove="' + productId + '"]');
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var li = firstParentThatMatches('li', item);
            li.parentNode.removeChild(li);
        }

        this.setCount();
    },

    onFocus: function () {
        Drupal.behaviors.infiniteWishlist.fetchProducts();
        Drupal.behaviors.infiniteWishlist.setCount();
        // handle already injected icons
        var injectedIcons = document.querySelectorAll('.wishlist__icon--add');
        var storedProductIds = Drupal.behaviors.infiniteWishlist.getStoredProductIds();
        for (var i = 0; i < injectedIcons.length; i++) {
            var icon = injectedIcons[i];
            if (storedProductIds.indexOf(icon.productId) > -1) {
                icon.classList.add('in-wishlist');
            } else {
                icon.classList.remove('in-wishlist');
            }
        }
    },

    attach: function () {
        try {
            var test = 'local_storage_availability_test';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);

            if (window.Worker) {
                this.injectHeaderIcon();
                this.injectIcons();
                this.setCount();

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
