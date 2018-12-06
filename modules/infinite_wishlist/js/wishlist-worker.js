onmessage = function(e) {
  const storedWishlist = e.data;
  let data;
  try {
    data = new FormData();
  } catch (e) {
    importScripts('form-data-builder.js');
    data = new FormDataBuilder();
  }
  data.append('wishlist', JSON.stringify(storedWishlist));

  const xhr = new XMLHttpRequest();
  xhr.open('POST', '/wishlist/fetch-products');

  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4 && xhr.status === 200) {
      const result = JSON.parse(xhr.responseText);
      for (let i = 0; i < Object.keys(storedWishlist).length; i++) {
        const storedItem = storedWishlist[Object.keys(storedWishlist)[i]];
        const itemUuid = storedItem.uuid;
        for (let j = 0; j < result.products.length; j++) {
          const resultItem = result.products[j];
          if (resultItem.uuid === itemUuid) {
            storedWishlist[i] = resultItem;
            storedWishlist[i].addedToWishlistTimestamp =
              storedItem.addedToWishlistTimestamp;
            storedWishlist[i].expires = Date.now() + 60 * 60 * 1000;
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
