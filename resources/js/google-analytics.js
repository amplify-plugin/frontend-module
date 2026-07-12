/*
|--------------------------------------------------------------------------
| Listen Cart Events
|--------------------------------------------------------------------------
*/

window.addEventListener('add_to_cart', function (event) {
    if (window.hasOwnProperty('dataLayer')) {

        window.dataLayer.push({
            ecommerce: null
        });

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
                value: addToCartTotal.toFixed(2),
                items: items
            }
        });
    }
});

window.addEventListener('remove_from_cart', function (event) {
    if (window.hasOwnProperty('dataLayer')) {

        window.dataLayer.push({
            ecommerce: null
        });

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
                value: deletedCartTotal.toFixed(2),
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

window.addEventListener('add_shipping_info', function (event) {

    if (window.hasOwnProperty('dataLayer')) {

        window.dataLayer.push({
            ecommerce: null
        });


        let shippingCartTotal = 0;
        let items = [];

        event.detail.items.forEach((item) => {
                shippingCartTotal += item.subtotal;
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
                value: shippingCartTotal.toFixed(2),
                shipping_tier: event.detail.method.shipvia,
                items: items
            }
        });
    }
});

window.addEventListener('add_payment_info', function (event) {

    if (window.hasOwnProperty('dataLayer')) {

        window.dataLayer.push({
            ecommerce: null
        });


        let paidCartTotal = 0;
        let items = [];

        event.detail.items.forEach((item) => {
            paidCartTotal += parseFloat(item.subtotal);
            items.push({
                item_id: item.product_code,
                item_name: item.product_name,
                price: parseFloat(item.unitprice),
                quantity: item.qty
            });
        });

        window.dataLayer.push({
            event: event.type,
            payment_type: event.detail.payment_method,
            ecommerce: {
                currency: AMPLIFY_BASE_CURRENCY,
                value: paidCartTotal,
                items: items
            }
        });
    }
});

window.addEventListener('request_quote', function (event) {

    if (window.hasOwnProperty('dataLayer')) {

        window.dataLayer.push({
            ecommerce: null
        });


        let quotedCartTotal = 0;
        let items = [];

        event.detail.items.forEach((item) => {
            quotedCartTotal += parseFloat(item.subtotal);
            items.push({
                item_id: item.product_code,
                item_name: item.product_name,
                price: parseFloat(item.unitprice),
                quantity: item.quantity ?? item.qty ?? null
            });
        });

        window.dataLayer.push({
            event: event.type,
            ecommerce: {
                currency: AMPLIFY_BASE_CURRENCY,
                transaction_id: event.detail.quote_number,
                value: quotedCartTotal.toFixed(2),
                items: items
            }
        });
    }
});

window.addEventListener('purchase', function (event) {

    if (window.hasOwnProperty('dataLayer')) {

        window.dataLayer.push({
            ecommerce: null
        });


        let purchasedCartTotal = 0;
        let items = [];

        event.detail.items.forEach((item) => {
            purchasedCartTotal += item.subtotal;
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
                affiliation: 'B2B Website',
                currency: AMPLIFY_BASE_CURRENCY,
                transaction_id: event.detail.order_number,
                value: purchasedCartTotal.toFixed(2),
                tax: parseFloat(event.detail.tax || 0).toFixed(2),
                shipping: parseFloat(event.detail.shipping || 0).toFixed(2),
                shipping_tier: event.detail.shipping_method.shipvia,
                payment_type: event.detail.payment_method,
                items: items
            }
        });
    }
});
