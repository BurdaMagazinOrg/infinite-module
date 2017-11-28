
onmessage = function (e) {
    var storedWishlist = e.data;
    var data;
    try {
        data = new FormData();
    } catch (e) {
        importScripts('form-data-builder.js');
        data = new FormDataBuilder();
    }
    data.append('wishlist', JSON.stringify(storedWishlist));

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/wishlist/fetch-products');

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var result = JSON.parse(xhr.responseText);
            for (var i = 0; i < Object.keys(storedWishlist).length; i++) {
                var storedItem = storedWishlist[Object.keys(storedWishlist)[i]];
                var itemUuid = storedItem.uuid;
                for (var j = 0; j < result.products.length; j++) {
                    var resultItem = result.products[j];
                    if (resultItem.uuid === itemUuid) {
                        storedWishlist[i] = resultItem;
                        storedWishlist[i].expires = Date.now() + (60 * 60 * 1000);
                    }
                }
            }

            postMessage(storedWishlist);
        }
    };

    if (data.type) {
        xhr.setRequestHeader('Content-Type', data.type);
    }
    xhr.send(data.getBlob ? data.getBlob() : data);
};
