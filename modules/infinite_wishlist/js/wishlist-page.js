Drupal.behaviors.infiniteWishlistPage = {
    render: function (storedWishlist) {
        var container = document.getElementById('wishlist-page');
        container.innerHTML = '';
        for(var i = 0; i < storedWishlist.length; i++) {
            var item = storedWishlist[i];
            var li = document.createElement('li');
            li.innerHTML = item.markup;
            container.appendChild(li);
        }
    },

    attach: function () {
        var container = document.getElementById('wishlist-page');
        if (container) {
            Drupal.behaviors.infiniteWishlist.fetchProducts(this.render);

            window.addEventListener('focus', function () {
                Drupal.behaviors.infiniteWishlist.fetchProducts(
                    Drupal.behaviors.infiniteWishlistPage.render
                );
            });
        }
    }
};
