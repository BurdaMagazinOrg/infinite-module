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
            this.growl('Unalbe to retrieve product id');
            return;
        }
        wishlist.push({
            productId: productId,
            expires: 0,
            markup: ''
        });
        localStorage.setItem('infinite_wishlist', JSON.stringify(wishlist));

        this.fetchProducts();

        this.growl('added item ' + productId + ' to wishlist');
    },

    animateStore: function (originalImage) {
        var originalRect = originalImage.getBoundingClientRect();
        var button = document.getElementById('wishlist__toggle');
        var buttonRect = button.getBoundingClientRect();
        var buttonTop = buttonRect.y;
        var buttonLeft = buttonRect.x;
        var duplicate = document.createElement('img');
        duplicate.setAttribute('src', originalImage.getAttribute('src'));
        duplicate.classList.add('wishlist__store-duplicate');
        duplicate.style.position = 'fixed';
        duplicate.style.top = originalRect.y + 'px';
        duplicate.style.left = originalRect.x + 'px';
        duplicate.style.width = originalRect.width + 'px';
        duplicate.style.height = originalRect.height + 'px';
        duplicate.style.transformOrigin = 'top left';
        document.body.appendChild(duplicate);
        window.setTimeout(function () {
            var newLeft = buttonLeft - originalRect.x;
            var newTop = buttonTop - originalRect.y;
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
        container.innerHTML = '';
        var items = this.getWishlist();
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
            icon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 22.87 21.79">\n' +
                '    <path class="cls-1"\n' +
                '          d="M22.87,6.58a8.52,8.52,0,0,1-.09,1.21c-.44,3-2.39,6.1-5.36,9.18a41.26,41.26,0,0,1-5.29,4.6,1.21,1.21,0,0,1-1.32,0l-.48-.3a33.94,33.94,0,0,1-4.85-3.85C2.07,14.2,0,10.72,0,7.11,0-.33,8.17-2.08,11.44,2.78A6.23,6.23,0,0,1,22.87,6.58Z"\n' +
                '          transform="translate(0 0)"/>\n' +
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

    attach: function () {
        try {
            var test = 'local_storage_availability_test';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);

            this.injectHeaderIcon();
            this.injectIcons();
            this.setCount();

            window.addEventListener('focus', function () {
                Drupal.behaviors.infiniteWishlist.fetchProducts();
                Drupal.behaviors.infiniteWishlist.setCount();
                // handle already injected icons
                var injectedIcons = document.querySelectorAll('.wishlist__icon--add');
                var storedProductIds = Drupal.behaviors.infiniteWishlist.getStoredProductIds();
                for (var i = 0; i < injectedIcons.length; i++) {
                    var icon = injectedIcons[i];
                    icon.classList.toggle('in-wishlist', storedProductIds.indexOf(icon.productId) > -1);
                }
            });
        } catch (e) { // local storage is unavailable
            // TODO: handle
            return false;
        }
    }
};
