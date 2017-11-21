onmessage = function (e) {
    var storedWishlist = e.data;
    var data = new FormData();
    data.append('wishlist', JSON.stringify(storedWishlist));

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/wishlist/fetch-products');

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var result = JSON.parse(xhr.responseText);
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

            postMessage(storedWishlist);
        }
    };

    xhr.send(data);
};
