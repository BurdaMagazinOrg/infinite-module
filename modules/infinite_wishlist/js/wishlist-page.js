Drupal.behaviors.infiniteWishlistPage = {
    render: function (storedWishlist) {
        var container = document.getElementById('wishlist-page');
        Drupal.behaviors.infiniteWishlist.renderList(container);
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
