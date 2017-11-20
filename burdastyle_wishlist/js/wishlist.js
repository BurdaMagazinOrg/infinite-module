Drupal.behaviors.burdastyleWishlist = {
    getWishlist: function () {
        var wishlist = localStorage.getItem('burdastyle_wishlist');
        if (null === wishlist) {
            return [];
        } else {
            var wishlistItems = JSON.parse(wishlist);

            this.setCount(wishlistItems.length);

            return wishlistItems;
        }
    },

    setCount: function(count) {
        document.querySelector('#wishlist__toggle__count > span').innerText = count;
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
        localStorage.setItem('burdastyle_wishlist', JSON.stringify(wishlist));

        this.fetchProducts();

        this.growl('added item ' + productId + ' to wishlist');
    },

    growl: function (message) {
        var growl = document.createElement('div');
        growl.setAttribute('style', 'position: fixed; bottom: 0; right: 0; background: black; color: deeppink; font-weight: bold; padding: 10px 20px');
        growl.innerText = message;
        document.body.appendChild(growl);
        window.setTimeout(function () {
            document.body.removeChild(growl);
        }, 5000);
    },

    fetchProducts: function (callback) {
        if (typeof callback === 'undefined') {
            callback = function (storedWishlist) {
                Drupal.behaviors.burdastyleWishlist.renderList();
            }
        }
        var storedWishlist = this.getWishlist();

        // only fetch products is at least one has not been cached or not in the last hour
        var fetch = false;
// TODO: remove - for debug only
        fetch = true;
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
            var worker = new Worker('/modules/contrib/burdastyle_base/modules/burdastyle_wishlist/js/wishlist-worker.js');
            worker.onmessage = function (e) {
                storedWishlist = e.data;
                localStorage.setItem('burdastyle_wishlist', JSON.stringify(storedWishlist));

                callback(storedWishlist);
            };
            worker.postMessage(storedWishlist);
        } else {
            // TODO: do we need a fallback for this? http://caniuse.com/#search=web%20worker
        }
    },

    renderList: function () {
        var list = document.getElementById('wishlist__list');
        list.innerHTML = '';
        var items = this.getWishlist();
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var li = document.createElement('li');
            li.innerHTML = item.markup;
            list.appendChild(li);
        }

        this.initRemoveButtons(list);
    },

    injectIcons: function () {
        var items = document.getElementsByClassName('item-ecommerce');
        for (var i = 0; i < items.length; i++) {
            var item = items[i];

            var icon = document.createElement('button');
            icon.innerText = '❤';
            icon.className = 'wishlist__icon--add';
            icon.productId = item.getAttribute('data-product-id');
            icon.addEventListener('click', function (e) {
                e.stopPropagation();
                Drupal.behaviors.burdastyleWishlist.storeItem(e.currentTarget.productId);
            });

            item.insertBefore(icon, item.firstChild);
        }
    },

    injectHeaderIcon: function () {
        // return;
        // var container = document.createElement('div');
        // var list = document.createElement('ul');
        // var button = document.createElement('button');
        //
        // list.id = 'wishlist';
        // container.id = 'wishlist__container';
        //
        // button.innerText = '❤';
        // button.id = 'wishlist__toggle';
        var button = document.getElementById('wishlist__toggle');
        var wishlist = document.getElementById('wishlist');
        button.addEventListener('click', function () {
            wishlist.classList.toggle('open');
        });
        button.addEventListener('mouseover', function () {
            // only prefetch if overlay is not currently open
            if (false === wishlist.classList.contains('open')) {
                Drupal.behaviors.burdastyleWishlist.fetchProducts();
            }
        });
        //
        //
        // document.getElementById('menu-main-navigation').insertBefore(container, document.getElementById('search-open-btn'));
        // container.appendChild(button);
        // container.appendChild(list);
    },

    initRemoveButtons: function (container) {
        var buttons = container.querySelectorAll('[data-wishlist-remove]');
        for (var i = 0; i < buttons.length; i++) {
            var button = buttons[i];
            button.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                Drupal.behaviors.burdastyleWishlist.removeFromWishlist(
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

        localStorage.setItem('burdastyle_wishlist', JSON.stringify(wishlist));

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
    },

    attach: function () {
        try {
            var test = 'local_storage_availability_test';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);

            this.injectHeaderIcon();
            this.injectIcons();

            window.addEventListener('focus', function () {
                Drupal.behaviors.burdastyleWishlist.fetchProducts();
            });
        } catch (e) { // local storage is unavailable
            // TODO: handle
            return false;
        }
    }
};
