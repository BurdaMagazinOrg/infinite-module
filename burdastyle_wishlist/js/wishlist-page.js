Drupal.behaviors.burdastyleWishlistPage = {
    render: function (storedWishlist) {
        var container = document.getElementById('wishlist-page');
        container.innerHTML = '';
        for(var i = 0; i < storedWishlist.length; i++) {
            var item = storedWishlist[i];
            var li = document.createElement('li');
            li.innerHTML = item.markup;
            container.appendChild(li);
        }
        Drupal.behaviors.burdastyleWishlist.initRemoveButtons(container);
    },

    attach: function () {
        var container = document.getElementById('wishlist-page');
        if (container) {
            Drupal.behaviors.burdastyleWishlist.fetchProducts(this.render);

            window.addEventListener('focus', function () {
                Drupal.behaviors.burdastyleWishlist.fetchProducts(
                    Drupal.behaviors.burdastyleWishlistPage.render
                );
            });
        }
    }
};
