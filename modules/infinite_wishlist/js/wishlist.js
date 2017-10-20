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

    growl: function (message) {
        var growl = document.createElement('div');
        growl.setAttribute('style', 'position: fixed; bottom: 0; right: 0; background: black; color: deeppink; font-weight: bold; padding: 10px 20px');
        growl.innerText = message;
        document.body.appendChild(growl);
        window.setTimeout(function () {
            document.body.removeChild(growl);
        }, 5000);
    },

    attach: function () {
        try {
            var test = 'test';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            this.injectIcons();
        } catch (e) { // local storage is unavailable
            return false;
        }
    }
};


// var container = document.createElement('div');
// container.id = 'wishlist__container';
//
// var button = document.createElement('button');
// button.innerText = 'add to wishlist';
// button.setAttribute('style', 'color: darkred; background: black;');
// button.addEventListener('click', function() {
//
// });
//
// var list = document.createElement('ul');
// list.id = 'wishlist';
//
// document.body.insertBefore(container, document.body.firstChild);
// container.appendChild(button);
// container.appendChild(list);
//
// var items = [{
//     'title': 'Hello',
//     'url': 'http://instyle.local/test',
// }];
// for(var i = 0; i < items.length; i++) {
//     var item = items[i];
//     var li = document.createElement('li');
//     li.innerHTML = '<a href="' + item.url + '">' + item.title + '</a>';
//     list.appendChild(li);
// }
