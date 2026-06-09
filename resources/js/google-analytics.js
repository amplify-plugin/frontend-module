/*
|--------------------------------------------------------------------------
| Listen Cart Events
|--------------------------------------------------------------------------
*/

window.addEventListener('add_to_cart', function (event) {
    if (window.hasOwnProperty('dataLayer')) {
        let addToCartTotal = 0;
        let items = [];

        event.detail.items.forEach((item) => {
            addToCartTotal += item.subtotal;
            items.push({
                item_id: item.product_code,
                item_name: item.product_name,
                price: item.unitprice,
                quantity: item.qty
            });
        });

        window.dataLayer.push({
            event: event.type,
            sei_item_loc: event.detail.page,
            ecommerce: {
                currency: AMPLIFY_BASE_CURRENCY,
                value: addToCartTotal,
                items: items
            }
        });
    }
});

window.addEventListener('remove_from_cart', function (event) {
    if (window.hasOwnProperty('dataLayer')) {
        let deletedCartTotal = 0;
        let items = [];

        event.detail.forEach((item) => {
            deletedCartTotal += item.subtotal;
            items.push({
                item_id: item.product_code,
                item_name: item.product_name,
                price: item.unitprice,
                quantity: item.qty
            });
        });

        window.dataLayer.push({
            event: event.type,
            ecommerce: {
                currency: AMPLIFY_BASE_CURRENCY,
                value: deletedCartTotal,
                items: items
            }
        });
    }
});

window.addEventListener('search', function (event) {
    if (window.hasOwnProperty('dataLayer')) {
        window.dataLayer.push({
            event: event.type,
            search_term: event.detail.query
        });
    }
});