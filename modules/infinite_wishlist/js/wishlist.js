Drupal.behaviors.infiniteWishlist = {
    getWishlist: function () {
        var wishlist = localStorage.getItem('infinite_wishlist');
        if (null === wishlist) {
            return [];
        } else {
            return JSON.parse(wishlist);
        }
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
        wishlist.push({
            productId: productId
        });
        localStorage.setItem('infinite_wishlist', JSON.stringify(wishlist));
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

    fetchProducts: function () {
        var storedWishlist = this.getWishlist();
        jQuery.ajax({
            url: '/wishlist/fetch-products',
            method: 'post',
            dataType: 'json',
            data: {
                wishlist: storedWishlist
            },
            success: function (result) {
                for (var i = 0; i < Object.keys(storedWishlist).length; i++) {
                    var storedItem = storedWishlist[Object.keys(storedWishlist)[i]];
                    var itemProductId = storedItem.productId;
                    for (var j = 0; j < result.products.length; j++) {
                        var resultItem = result.products[j];
                        if (resultItem.productId === itemProductId) {
                            storedWishlist[i].markup = resultItem.markup;
                            storedWishlist[i].expires = Date.now() + (60 * 60 * 1000);
                        }
                    }
                }

                localStorage.setItem('infinite_wishlist', JSON.stringify(storedWishlist));
                Drupal.behaviors.infiniteWishlist.renderList();
            },
            error: function () {

            }
        });
    },

    renderList: function () {
        var list = document.getElementById('wishlist');
        list.innerHTML = '';
        var items = this.getWishlist();
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var li = document.createElement('li');
            li.innerHTML = item.markup;
            list.appendChild(li);
        }
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
                Drupal.behaviors.infiniteWishlist.storeItem(e.currentTarget.productId);
            });

            item.insertBefore(icon, item.firstChild);
        }
    },

    injectHeaderIcon: function () {
        var container = document.createElement('div');
        var list = document.createElement('ul');
        var button = document.createElement('button');

        list.id = 'wishlist';
        container.id = 'wishlist__container';

        button.innerText = '❤';
        button.id = 'wishlist__toggle';
        button.addEventListener('click', function () {
            list.classList.toggle('open');
        });
        button.addEventListener('mouseover', function () {
            // only prefetch if overlay is not currently open
            if (false === list.classList.contains('open')) {
                Drupal.behaviors.infiniteWishlist.fetchProducts();
                Drupal.behaviors.infiniteWishlist.growl('prefetch rendered products if not cached');
            }
        });


        document.getElementById('menu-main-navigation').insertBefore(container, document.getElementById('search-open-btn'));
        container.appendChild(button);
        container.appendChild(list);
    },

    attach: function () {
        try {
            var test = 'local_storage_availability_test';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            this.injectHeaderIcon();
            this.injectIcons();
        } catch (e) { // local storage is unavailable
            return false;
        }
    }
};
