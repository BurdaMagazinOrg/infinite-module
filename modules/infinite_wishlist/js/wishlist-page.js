Drupal.behaviors.infiniteWishlistPage = {
    attach: function () {
        var container = document.getElementById('wishlist-page');
        if (container) {
            Drupal.behaviors.infiniteWishlist.fetchProducts(function (storedWishlist) {
                for(var i = 0; i < storedWishlist.length; i++) {
                    var item = storedWishlist[i];
                    var li = document.createElement('li');
                    li.innerHTML = item.markup;
                    container.appendChild(li);
                }
            });
        }
    }
};
