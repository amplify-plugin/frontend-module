import Swal from 'sweetalert2';

window.swal = Swal.mixin({
    theme: 'bootstrap-4-light',
    showCloseButton: true,
    // buttonsStyling: false,
    customClass: {
        confirmButton: 'btn btn-danger',
        cancelButton: 'btn btn-outline-secondary',
    }
});

window.Amplify = {
    cartUrl: () => '/carts',
    cartItemRemoveUrl: () => '/carts/remove/cart_item_id',
    maxCartItemQuantity: () => 9999999999,
    favouritesCreateUrl: () => '/favourites',
    orderListUrl: () => '/order-lists',

    isHtml(text) {
        return new DOMParser()
            .parseFromString(text, 'text/html')
            .body.children.length > 0;
    },

    /**
     * The function validate if the customer is logged in
     * return bool
     */
    authenticated() {
        const meta = document.querySelector('meta[name="authenticated"]');
        return meta ? meta.content === 'true' : false;
    },

    /**
     * This function fires a popup notification on top right corner
     * @param type
     * @param message
     * @param title
     * @param options
     */
    notify(type = 'info', message = 'Your message here!', title = 'Notification', options = {}) {
        let allowedTypes = ['success', 'error', 'warning', 'info', 'question'];
        if (!allowedTypes.includes(type)) {
            alert(`The ${type} type is not allowed. Allowed types: ${allowedTypes}`);
        }
        const toast = window.swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            didOpen: (t) => {
                t.onmouseenter = Swal.stopTimer;
                t.onmouseleave = Swal.resumeTimer;
            },
            padding: '0.25em 0.75em',
        });

        toast.fire({
            icon: type,
            title: title,
            html: message,
            ...options
        });
    },

    /**
     * The function fires a confirmation popup that ask for confirmation
     * and trigger a action when confirmed.
     * @param question
     * @param title
     * @param confirmBtnText
     * @param options
     * @returns {Promise<SweetAlertResult<Awaited<any>>>}
     */
    confirm(question = 'You won\'t be able to revert this!', title = 'Are you sure?', confirmBtnText = '', options = {}) {
        const confirmAlert = window.swal.mixin({
            icon: 'warning',
            showCancelButton: true,
            showConfirmButton: true,
            showLoaderOnConfirm: true,
            allowOutsideClick: false,
            padding: '1em',
        });

        return confirmAlert.fire({
            title: title,
            html: question,
            confirmButtonText: confirmBtnText,
            ...options
        });
    },

    alert(message = 'This action is not allowed', title = 'Alert', options = {}) {
        return this.confirm(message, title, '', {
            icon: 'warning',
            showConfirmButton: true,
            showLoaderOnConfirm: false,
            showCancelButton: false,
            confirmButtonText: 'Okay',
            customClass: {
                confirmButton: 'btn btn-outline-secondary'
            },
            willOpen: () => document.querySelector('.swal2-actions').style.justifyContent = 'center',
            ...options
        })
    },

    /**
     * This function handle the custom part number add, update and remove operation.
     *
     * @param element
     * @param inputId
     */
    syncCustomPartNumber(element, inputId) {
        const input = document.getElementById(inputId);
        const button = element;
        const actionLink = button.dataset.route;
        const productId = button.dataset.productId;
        const oldValue = input.dataset.current.toString();
        const uom = input.dataset.uom.toString();
        const newValue = input.value.toString();

        if (!this.authenticated()) {
            this.notify('warning', 'You need to be logged in to access this feature.', 'Customer Part Number');
            return;
        }

        if (!oldValue && !newValue) {
            this.notify('warning', 'This field is required', 'Customer Part Number');
            return;
        }

        //current exist and new empty
        if (oldValue) {
            this.confirm('Are you sure you want to remove this part number?',
                'Customer Part Number', 'Remove', {
                    preConfirm: async () => {
                        return await $.ajax({
                            url: actionLink,
                            type: 'DELETE',
                            data: {
                                product_id: productId,
                                customer_product_uom: uom,
                                customer_product_code: oldValue,
                            },
                            success: function (result) {
                                input.dataset.current = false;
                                return result;
                            },
                            error: function (xhr) {
                                Swal.showValidationMessage(`<p>${xhr.responseJSON.message ?? xhr.statusText}</p>`);
                                Swal.hideLoading();
                            },
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                })
                .then(function (result) {
                    if (result.isConfirmed) {
                        Amplify.notify('warning', result.value.message, 'Customer Part Number');
                        input.value = '';
                        input.dataset.current = '';
                        button.innerHTML = 'Add';
                    }
                }).catch((error) => console.error(error));
        } else {
            $.ajax({
                url: actionLink,
                type: 'POST',
                data: {
                    product_id: productId,
                    customer_product_uom: uom,
                    customer_product_code: newValue,
                },
                success: function (response) {
                    Amplify.notify('success', response.message, 'Customer Part Number');
                    input.dataset.current = newValue;
                    button.innerHTML = 'Delete';
                },
                error: function (xhr) {
                    Amplify.alert(xhr.responseJSON.message ?? xhr.statusText, 'Customer Part Number');
                }
            });
        }
    },

    /**
     * This function handle the clear cart option.
     * @param element
     */
    clearCart(element) {
        this.confirm('Are you sure to remove all items from shopping cart?',
            'Cart', 'Confirm', {
                preConfirm: async () => {
                    return await $.ajax({
                        url: this.cartUrl(),
                        type: 'DELETE',
                        data: {},
                        header: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        success: function (result) {
                            return result;
                        },
                        error: function (xhr) {
                            Swal.showValidationMessage(`<p>${xhr.responseJSON.message ?? xhr.statusText}</p>`);
                            Swal.hideLoading();
                        }
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            })
            .then(function (result) {
                if (result.isConfirmed) {
                    Amplify.notify('success', result.value.message, 'Cart');
                    setTimeout(() => {
                        const {origin, pathname} = window.location;
                        window.location.replace(origin + pathname);
                    }, 2000);
                }
            })
            .catch((error) => console.error(error));
    },

    discardQtyChange(event, selector = 'input[data-quantity]') {
        document.querySelectorAll(selector)
            .forEach(el => el.value = el.dataset.quantity);

        this.handleQuantityChange(selector, 'input');
    },

    removeCartItem(cartItemId, redirect = true) {
        const actionLink = this.cartItemRemoveUrl().replace('cart_item_id', cartItemId);
        return this.confirm('Are you sure to remove this item from cart?',
            'Cart', 'Remove', {
                preConfirm: async () => {
                    return await $.ajax({
                        url: actionLink,
                        type: 'DELETE',
                        data: {},
                        header: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        success: function (result) {
                            return result;
                        },
                        error: function (xhr) {
                            Swal.showValidationMessage(`<p>${xhr.responseJSON.message ?? xhr.statusText}</p>`);
                            Swal.hideLoading();
                        },
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            })
            .then(function (result) {
                if (result.isConfirmed) {
                    Amplify.notify('success', result.value.message, 'Cart');
                    if (redirect) {
                        setTimeout(() => {
                            const {origin, pathname} = window.location;
                            window.location.replace(origin + pathname);
                        }, 2000);
                        return true;
                    } else {
                        Amplify.loadCartDropdown();
                        return result;
                    }
                }
            })
            .catch((error) => console.error(error));
    },

    updateCartItem(e, target, cartItemId) {
        e.preventDefault();
        e.stopPropagation();

        if (!this.handleQuantityChange(target, 'input')) {
            return;
        }

        const targetElement = document.querySelector(target);
        const quantity = targetElement.value;

        const actionLink = this.cartUrl();
        const warehouseCode = targetElement.dataset.warehouseCode;
        const productCode = targetElement.dataset.productCode;

        swal.fire({
            title: 'Cart',
            icon: 'warning',
            backdrop: true,
            showCancelButton: false,
            text: `Updating ${productCode}'s information in cart...`,
            confirmButtonText: 'Okay',
            customClass: {
                confirmButton: 'btn btn-outline-secondary'
            },
            willOpen: () => document.querySelector('.swal2-actions').style.justifyContent = 'center',
            didOpen: () => {
                return $.ajax({
                    beforeSend: () => Swal.showLoading(),
                    url: actionLink,
                    type: 'PATCH',
                    data: {
                        products: [
                            {
                                id: cartItemId,
                                qty: Number(quantity),
                                product_code: productCode,
                                product_warehouse_code: warehouseCode,
                            }
                        ]
                    },
                    header: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    success: function (result) {
                        if (result.success) {
                            Swal.close();
                            Amplify.notify('success', result.message, 'Cart');
                            setTimeout(() => {
                                const {origin, pathname} = window.location;
                                window.location.replace(origin + pathname);
                            }, 2000);
                        }
                    },
                    error: function (xhr) {
                        Swal.showValidationMessage(`<p>${xhr.responseJSON.message ?? xhr.statusText}</p>`);
                        Swal.hideLoading();
                    },
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        });
    },

    updateCart(e) {
        e.preventDefault();
        e.stopPropagation();

        let itemChanged = [];

        document.querySelectorAll('#cart-summary input[data-quantity]')
            .forEach(function (input) {
                let currentValue = input.value;
                let originalValue = input.dataset.quantity;

                if (currentValue !== originalValue) {
                    itemChanged.push({
                        id: Number(input.id.replace('cart-item-', '')),
                        qty: Number(currentValue),
                        product_code: input.dataset.productCode,
                        product_warehouse_code: input.dataset.warehouseCode,
                    })
                }
            });

        if (itemChanged.length === 0) {
            return;
        }

        swal.fire({
            title: 'Cart',
            icon: 'warning',
            backdrop: true,
            showCancelButton: false,
            text: `Updating product information in cart...`,
            confirmButtonText: 'Okay',
            customClass: {
                confirmButton: 'btn btn-outline-secondary'
            },
            willOpen: () => document.querySelector('.swal2-actions').style.justifyContent = 'center',
            didOpen: () => {
                return $.ajax({
                    beforeSend: () => Swal.showLoading(),
                    url: this.cartUrl(),
                    type: 'PATCH',
                    data: {
                        products: itemChanged,
                    },
                    header: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    success: function (result) {
                        if (result.success) {
                            Swal.close();
                            Amplify.notify('success', result.message, 'Cart');
                            setTimeout(() => {
                                const {origin, pathname} = window.location;
                                window.location.replace(origin + pathname);
                            }, 2000);
                        }
                    },
                    error: function (xhr) {
                        Swal.showValidationMessage(`<p>${xhr.responseJSON.message ?? xhr.statusText}</p>`);
                        Swal.hideLoading();
                    },
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        });
    },

    /**
     * This function load the current items and display in cart summary
     * table
     */
    async loadCartSummary() {
        await $.ajax({
            beforeSend: function () {
                $('#cart-item-summary').html(
                    `<tr>
                        <td colspan='50' style="display: flex; width: 100%; justify-content: center; padding-top: 1.5rem; padding-bottom: 1.5rem; min-height: 100px">
                                <img src='/vendor/widget/img/loading.gif' alt="preloader" style="max-width: 52px; height: auto; object-fit: contain" class="img-fluid"/>
                         </td>
                  </tr>`,
                );
            },
            url: this.cartUrl(),
            method: 'GET',
            dataType: 'json',
            headers: {
                Accept: 'application/json'
            },
            success: function (response) {
                $('#cart-item-summary').empty();
                if (response.data.products.length > 0) {
                    $(response.data.products).each(function (index, item) {
                        let layout = Amplify.renderCartSummaryItemRow(item, index);
                        $('#cart-item-summary').append(layout);
                    });
                }
                $('#order-subtotal').text(response.data.sub_total);
            },
            error: function (xhr) {
                Amplify.alert(xhr.responseJSON.message ?? xhr.statusText, 'Cart');
            },
        }).done(() => Amplify.attachQuantityInputEvents());
    },

    renderCartSummaryItemRow(product, index) {
        let template = $('#cart-single-item-template').html();

        let mapper = {
            '{serial}': index + 1,
            '{cart_item_id}': product.id,
            '{code}': product.product_code,
            '{warehouse}': product.warehouse_name,
            '{warehouse_code}': product.product_warehouse_code,
            '{name}': product.product_name,
            '{description}': product.short_description,
            '{manufacturer}': product.manufacturer_name,
            '{quantity}': product.qty,
            '{unit_price}': product.price,
            '{subtotal}': product.subtotal,
            '{image}': product.product_image,
            '{uom}': product.uom,
            '{actions}': JSON.stringify(product),
            '{url}': product.url,
            '{min_qty}': product?.additional_info?.minimum_quantity ?? 1,
            '{qty_interval}': product?.additional_info?.quantity_interval ?? 1,
            '{note}': product.note,
            '{ncnr_msg}': product.ncnr_msg,
            '{ship_restriction}': product.ship_restriction,
            '{error}': product.error,
        };

        return stringReplaceArray(Object.keys(mapper), Object.values(mapper), template);
    },

    /**
     * This function load the current cart items from backend
     * @returns {Promise<void>}
     */
    async loadCartDropdown() {
        await $.ajax(this.cartUrl(), {
            beforeSend: () => Amplify.renderEmptyCart('/vendor/widget/img/loading.gif', true),
            method: 'GET',
            dataType: 'json',
            headers: {
                Accept: 'application/json'
            },
            success: function (res) {
                $('.cart-dropdown').empty();
                if (res.data.products.length > 0) {
                    $("#cart-menu-subtotal").show();
                    $('.total_cart_items').text(res.data.item_count);
                    $('.total_cart_items').removeClass('d-none');
                    res.data.products.forEach((product, index) => {
                        $('.cart-dropdown').append(`
                        <div class="dropdown-product-item" id="cart_products_${index}">
                        <span class="dropdown-product-remove" onclick="Amplify.removeCartItem(${product.id})">
                            <i class="icon-cross"></i>
                        </span>
                        <a class="dropdown-product-thumb"
                           href="${product.url}">
                            <img src="${product.product_image}"
                                onerror="this.onerror=null; this.src=FALLBACK_IMG_SRC;"
                                alt="Product">
                        </a>
                        <div class="dropdown-product-info">
                            <a class="dropdown-product-title" href="${product.url}">
                                ${product.product_name}
                            </a>
                            <span class="dropdown-product-details">${product.qty} x ${product.price} = ${product.subtotal}</span>
                        </div>
                    </div>`);
                    });
                    $('.total_cart_amount').text(res.data.sub_total);
                } else {
                    Amplify.renderEmptyCart();
                }
            },
            error: function (xhr, status) {
                Amplify.renderEmptyCart();
            }
        });
    },

    renderEmptyCart(imageUrl = '/assets/img/empty_cart.png', loading = false) {
        $('.cart-dropdown').empty();
        $("#cart-menu-subtotal").hide();
        $('.total_cart_items').text(0);
        $('.total_cart_amount').text('$0.00');
        $('.total_cart_items').addClass('d-none');

        let className = loading ? 'loading' : 'loaded';

        $('.cart-dropdown').append(`
        <div id="cart_items" class="${className}">
            <img src="${imageUrl}" class="img-fluid" alt="No items in cart"/>
        </div>
    `);
    },

    attachQuantityInputEvents() {
        document.querySelectorAll('input.item-quantity')
            .forEach((input) => {

                input.addEventListener('input', function () {
                    let value = this.value;

                    // Remove invalid characters
                    value = value.replace(/[^0-9.]/g, '');

                    // Allow only one dot
                    const parts = value.split('.');
                    if (parts.length > 2) {
                        value = parts[0] + '.' + parts.slice(1).join('');
                    }

                    this.value = value;

                    Amplify.handleQuantityChange('#' + this.id, 'input');
                });

                input.addEventListener('keydown', function (e) {
                    const allowedKeys = [
                        'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'
                    ];

                    if (e.key === 'ArrowUp') {
                        Amplify.handleQuantityChange('#' + e.target.id, 'increment');
                    }

                    if (e.key === 'ArrowDown') {
                        Amplify.handleQuantityChange('#' + e.target.id, 'decrement');
                    }

                    if (allowedKeys.includes(e.key)
                        || (e.key >= '0' && e.key <= '9')
                        || (e.key === '.' && !this.value.includes('.'))) {

                        return;
                    }
                    e.preventDefault();
                });
            });
    },

    /**
     * The function handle quantity update and quantity value validation
     *
     * @param target
     * @param action
     */
    handleQuantityChange(target, action) {

        const targetElement = document.querySelector(target);

        const productCode = targetElement.dataset.productCode;

        if (!['decrement', 'input', 'increment'].includes(action)) {
            this.alert(`Invalid Quantity Change action [${action}].`, 'Cart');
        }

        if (!targetElement) {
            this.alert(`Target input element not found in ${target}`, 'Cart');
            return false;
        }

        targetElement.max = this.maxCartItemQuantity();

        const minOrderQty = parseFloat(targetElement.dataset.minOrderQty);

        if (!minOrderQty) {
            this.alert(`Target input element [${target}] doesn't have "data-min-order-qty" attribute set or is empty.`, 'Cart');
            return false;
        }

        targetElement.min = minOrderQty;

        const qtyInterval = parseFloat(targetElement.dataset.qtyInterval);

        if (!qtyInterval) {
            this.alert(`Target input element [${target}] doesn't have "data-qty-interval" attribute set or is empty.`, 'Cart');
            return false;
        }

        targetElement.step = qtyInterval;

        let quantity = Number(targetElement.value);

        if (quantity <= 0 || quantity >= this.maxCartItemQuantity()) {
            this.alert(`You entered an invalid quantity. Product ${productCode} requires a quantity between ${minOrderQty} and ${this.maxCartItemQuantity()}.`, 'Cart');
            targetElement.value = minOrderQty;
            return false;
        }

        switch (action) {

            case 'decrement' : {
                let newValue = quantity - qtyInterval;
                if (newValue < minOrderQty) {
                    this.alert(
                        `Product ${productCode} requires a minimum order of ${minOrderQty} quantity. You entered ${newValue}.`,
                        'Cart');
                    return false;
                }
                targetElement.value = newValue;
                break;
            }

            case 'increment' : {
                let newValue = quantity + qtyInterval;
                if (newValue > this.maxCartItemQuantity()) {
                    this.alert(
                        `Product ${productCode} requires a maximum order quantity of ${this.maxCartItemQuantity()}. You entered ${newValue}.`,
                        'Cart');
                    return false;
                }
                targetElement.value = newValue;
                break;
            }

            default : {
                if (quantity < minOrderQty) {
                    this.alert(
                        `Product ${productCode} requires a minimum order quantity of ${minOrderQty}. You entered ${quantity}.`,
                        'Cart');
                    targetElement.value = minOrderQty;
                    return false;
                }

                if (quantity > this.maxCartItemQuantity()) {
                    this.alert(
                        `Product ${productCode} requires a maximum order quantity of ${this.maxCartItemQuantity()}. You entered ${quantity}.`,
                        'Cart');
                    targetElement.value = minOrderQty;
                    return false;
                }
                break;
            }
        }

        if (targetElement.value.length > 0 && this.verifyQuantityInterval(targetElement.value, qtyInterval) === false) {
            this.alert(
                `Product ${productCode} requires a order pack(s) of ${qtyInterval}. You entered ${targetElement.value}.`,
                'Cart');
            targetElement.value = this.toNearestQtyInterval(targetElement.value, qtyInterval);
            return false;
        }

        window.dispatchEvent(new CustomEvent('qty-change', {
            detail: {
                target: targetElement,
                quantity: Number(targetElement.value),
                id: targetElement.id,
                changed: targetElement.value !== targetElement.dataset.quantity,
            }
        }));

        return true;
    },

    verifyQuantityInterval(value, interval) {
        if (interval <= 0) return false;
        return Math.abs(value % interval) < 1e-10;
    },

    toNearestQtyInterval(value, interval) {
        if (interval === 0) return value;
        return Math.ceil(value / interval) * interval;
    },

    listenQtyChangeOnCartSummary(event, updateStyle = 'bulk') {
        const {changed, id} = event.detail;

        if (updateStyle === 'line') {
            const cartId = id.replace('cart-item-', '');
            if (changed) {
                $(`#update-button-${cartId}`).show();
                $(`#discard-button-${cartId}`).show();
            } else {
                $(`#update-button-${cartId}`).hide();
                $(`#discard-button-${cartId}`).hide();
            }
        }

        let hasAnyChange = false;

        document.querySelectorAll('#cart-summary input[data-quantity]').forEach(function (input) {
            if (input.value !== input.dataset.quantity) {
                hasAnyChange = true;
            }
        });

        (hasAnyChange)
            ? $(`#checkout-btn`).hide()
            : $(`#checkout-btn`).show();

        if (updateStyle === 'bulk') {
            (hasAnyChange)
                ? $('#quantity-update-actions').removeClass('d-none')
                : $('#quantity-update-actions').addClass('d-none');
        }
    },

    /**
     * The function create a shopping list from product and cart and order and invoice, etc.
     * @param sourceId
     * @param source
     * @param title
     */
    addToNewOrderList(sourceId, source = 'product', title = 'Order List') {

        if (!this.authenticated()) {
            this.alert('You need to be logged in to access this feature.', title);
            return;
        }

        this.confirm('Create a new ' + title.toLowerCase() + ' & add item on it',
            title, 'Save', {
                icon: undefined,
                input: "text",
                inputPlaceholder: 'Enter name',
                inputAttributes: {
                    required: true,
                    max: "255",
                    maxlength: "255",
                    autocorrect: "on"
                },
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-outline-secondary'
                },
                preConfirm: async () => {
                    try {
                        let payload = {
                            type: source,
                            list_id: null,
                            list_name: $('#swal2-input').val(),
                            list_type: $('#swal2-select').val(),
                            list_desc: $('#swal2-textarea').val(),
                            title: title
                        };

                        payload[source + '_id'] = sourceId;

                        return await $.ajax({
                            url: Amplify.orderListUrl(),
                            type: 'POST',
                            data: JSON.stringify(payload),
                            contentType: 'application/json; charset=UTF-8',
                            headers: {
                                Accept: 'application/json'
                            },
                            success: function (result) {
                                return result;
                            },
                            error: function (xhr) {
                                Swal.showValidationMessage(`<p>${xhr.responseJSON.message ?? xhr.statusText}</p>`);
                                Swal.hideLoading();
                            },
                        });
                    } catch (error) {
                        return false;
                    }
                },
                allowOutsideClick: () => !Swal.isLoading(),
                willOpen: () => {
                    $.get('/api/list-types', (result) => {
                        $.each(result.data, function (index, item) {
                            $('#swal2-select').append('<option value="' + index + '">' + item + '</option>');
                        });
                        if (result.count >= 2) {
                            $('#swal2-select').css({
                                'display': 'flex',
                            }).addClass('swal2-input');
                        } else {
                            $('#swal2-select').css({
                                'display': 'none',
                            }).addClass('swal2-input');
                            let firstValue = Object.keys(result.data)[0];
                            $('#swal2-select').val(firstValue).trigger('change');
                        }
                    })
                },
                didOpen: function () {
                    $('#swal2-textarea').css('display', 'flex').attr('placeholder', 'Enter description');
                }
            })
            .then(function (result) {
                if (result.isConfirmed) {
                    Amplify.notify('success', result.value.message, title);
                }
            }).catch((error) => console.error(error));

        return true;
    },

    /**
     *
     * The function add a item(product/cart/order/invoice) to an exists order list
     * @param listId
     * @param sourceId
     * @param source
     * @param title
     */
    addToExistingOrderList(listId, sourceId, source = 'product', title = 'Order List') {

        if (!this.authenticated()) {
            this.alert('You need to be logged in to access this feature.', title);
            return;
        }

        this.confirm('Add item to existing ' + title.toLowerCase(),
            title, 'Save', {
                icon: undefined,
                input: "text",
                inputPlaceholder: 'Enter Quantity',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-outline-secondary'
                },
                inputValidator: (value) => {
                    if (!value) {
                        return "The quantity is required";
                    }

                    if (isNaN(value)) {
                        return "The quantity is not a number";
                    }

                    if (Number(value) <= 0) {
                        return "The quantity cannot to be less than 0";
                    }

                    if (Number(value) >= this.maxCartItemQuantity()) {
                        return "The quantity cannot to be greater than " + this.maxCartItemQuantity();
                    }
                },
                preConfirm: async function (value) {
                    try {
                        let payload = {
                            type: source,
                            list_id: listId,
                            is_shopping_list: 1,
                            list_type: null,
                            title: title
                        };

                        payload[source + '_id'] = sourceId;

                        if (source === 'product') {
                            payload['product_qty'] = value;
                        }

                        return await $.ajax({
                            url: Amplify.orderListUrl(),
                            type: 'POST',
                            data: JSON.stringify(payload),
                            contentType: 'application/json; charset=UTF-8',
                            headers: {
                                Accept: 'application/json'
                            },
                            success: function (result) {
                                return result;
                            },
                            error: function (xhr) {
                                Swal.showValidationMessage(`<p>${xhr.responseJSON.message ?? xhr.statusText}</p>`);
                                Swal.hideLoading();
                            },
                        });
                    } catch (error) {
                        return false;
                    }
                },
                allowOutsideClick: () => !Swal.isLoading(),
            })
            .then(function (result) {
                if (result.isConfirmed) {
                    Amplify.notify('success', result.value.message, 'Shopping List');
                }
            }).catch((error) => console.error(error));

        return true;
    },

    manageOrderList(element, title, id = null) {
        this.confirm(id == null ? 'Create a new ' : 'Update existing ' + title.toLowerCase(),
            title, 'Save', {
                icon: undefined,
                input: "text",
                inputPlaceholder: 'Enter name',
                inputAttributes: {
                    required: true,
                    max: "255",
                    maxlength: "255",
                    autocorrect: "on"
                },
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-outline-secondary'
                },
                preConfirm: async () => {
                    try {
                        return await $.ajax(element.dataset.action, {
                            type: 'PATCH',
                            data: JSON.stringify({
                                name: $('#swal2-input').val(),
                                list_type: $('#swal2-select').val(),
                                description: $('#swal2-textarea').val(),
                                title: title
                            }),
                            contentType: 'application/json; charset=UTF-8',
                            headers: {
                                Accept: 'application/json'
                            },
                            success: (result) => result,
                            error: function (xhr) {
                                Swal.showValidationMessage(`<p>${xhr.responseJSON?.message ?? xhr.statusText}</p>`);
                                Swal.hideLoading();
                            },
                        });
                    } catch (err) {
                        return false;
                    }
                },

                allowOutsideClick: () => !Swal.isLoading(),

                willOpen: function () {
                    $.get('/api/list-types', (result) => {
                        $.each(result.data, function (index, item) {
                            $('#swal2-select').append('<option value="' + index + '">' + item + '</option>');
                        });
                    }).then(() => {
                        $.ajax(element.dataset.action, {
                            type: 'GET',
                            contentType: 'application/json; charset=UTF-8',
                            headers: {
                                Accept: 'application/json'
                            },
                            success: function (result) {
                                let orderList = result.data;
                                $('#swal2-textarea').val(orderList.description).trigger('change');
                                $('#swal2-input').val(orderList.name).trigger('change');
                                $('#swal2-select').val(orderList.list_type).trigger('change');
                            }
                        });
                    });
                },

                didOpen: function () {
                    $('#swal2-select').css({
                        'display': 'flex',
                    }).addClass('swal2-input');

                    $('#swal2-textarea').css('display', 'flex').attr('placeholder', 'Enter description');
                }
            })
            .then(function (result) {
                if (result.isConfirmed) {
                    Amplify.notify('success', result.value.message, title);
                }
                setTimeout(() => {
                    const {origin, pathname} = window.location;
                    window.location.replace(origin + pathname);
                }, 2000);
            });
    },

    async addSingleItemToCart(cartElement, quantityTarget, extras = {}) {

        let defaultContent = cartElement.innerHTML;
        let quantityElement = document.querySelector(quantityTarget);

        cartElement.disabled = true;
        cartElement.innerHTML = '<i class="icon-loader spinner"></i> Processing...';

        if (this.handleQuantityChange(quantityTarget, 'input')) {

            let warehouse = cartElement.dataset.warehouse;
            let options = JSON.parse(cartElement.dataset.options);

            let cartItem = {
                product_code: options.code,
                product_warehouse_code: warehouse,
                qty: quantityElement.value,
            }

            // if (typeof options.source_type != 'undefined') {
            //     cartItem.source_type = options.source_type;
            // }
            //
            // if (typeof options.source_type != 'undefined') {
            //     cartItem.source_type = options.source_type;
            // }

            await $.ajax(this.cartUrl(), {
                beforeSend: () => Amplify.renderEmptyCart('/assets/img/preloader.gif', true),
                method: 'POST',
                dataType: 'json',
                data: {
                    products: [cartItem]
                },
                headers: {
                    Accept: 'application/json',
                    ContentType: 'application/json',

                },
                success: function (res) {
                    Amplify.notify('success', res.message, 'Cart');
                    Amplify.loadCartDropdown();
                },
                error: function (xhr) {
                    Amplify.alert(xhr.responseJSON?.message ?? 'Something Went Wrong. PLease try again later.', 'Cart');
                }
            }).always(function () {
                cartElement.innerHTML = defaultContent;
                cartElement.disabled = false;
            });
        }

        cartElement.innerHTML = defaultContent;
        cartElement.disabled = false;
    },

    addMultipleItemToCart(cartElement, formTarget, extras = {}) {

        let defaultContent = cartElement.innerHTML;

        cartElement.disabled = true;
        cartElement.innerHTML = '<i class="icon-loader spinner"></i> Processing...';

        swal.fire({
            title: 'Cart',
            icon: 'info',
            backdrop: true,
            showCancelButton: false,
            text: `Adding Items to Cart...`,
            confirmButtonText: 'Okay',
            customClass: {
                confirmButton: 'btn btn-outline-secondary'
            },
            willOpen: () => document.querySelector('.swal2-actions').style.justifyContent = 'center',
            didOpen: () => {
                try {
                    return $.ajax(this.cartUrl(), {
                        beforeSend: () => {
                            Swal.showLoading();
                            $('span[id^="product-"][id$="-error"]').each(function (index, element) {
                                const match = this.id.match(/product-(\d+)-error/);
                                if (match) {
                                    const key = match[1];
                                    $(`input#product-code-${key}`).removeClass('is-invalid');
                                }
                                $(element).empty();
                            });
                        },
                        method: 'POST',
                        data: $(`form${formTarget}`).serialize(),
                        processData: false,
                        contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                        success: function (response) {
                            if (response.success) {
                                Amplify.notify('success', response.message, 'Cart');
                                setTimeout(() => {
                                    const {origin, pathname} = window.location;
                                    window.location.replace(origin + pathname);
                                }, 2000);
                            }
                        },
                        error: function (xhr) {
                            let response = xhr.responseJSON ?? {};
                            if (xhr.status === 400) {
                                $.each(response.errors, function (key, messages) {
                                    let message = messages.join('<br>');
                                    $(`input#product-code-${key}`).addClass('is-invalid');
                                    $(`#product-${key}-error`).html(message);
                                })
                            }
                            Swal.showValidationMessage(`<p>${response.message}</p>`);
                            Swal.hideLoading();
                        }
                    });
                } catch (err) {
                    console.error(err);
                    return false;
                }
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then(function () {
            cartElement.innerHTML = defaultContent;
            cartElement.disabled = false;
        });

        cartElement.innerHTML = defaultContent;
        cartElement.disabled = false;
    },

    /**
     * This confirmation modal only sent request on DELETE method
     * also follow the standard api response.
     *
     * @param target
     * @param title
     * @param payload
     * @param redirect
     * @param question
     */
    deleteConfirmation(target, title, payload = {}, redirect = true, question = 'Are you sure to delete this item?') {
        let actionLink = target.dataset.action;

        if (!actionLink) {
            this.alert('There is no action link in the target element.<br>Please add `data-action` attribute to the target element.');
            return;
        }

        this.confirm(question,
            title, 'Delete', {
                preConfirm: async () => {
                    return await $.ajax({
                        url: actionLink,
                        type: 'DELETE',
                        dataType: 'json',
                        data: payload,
                        header: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        success: function (result) {
                            return result;
                        },
                        error: function (xhr) {
                            Swal.showValidationMessage(`<p>${xhr.responseJSON.message ?? xhr.statusText}</p>`);
                            Swal.hideLoading();
                        },
                    });
                },
                allowOutsideClick: () => !window.swal.isLoading()
            })
            .then(function (result) {
                if (result.isConfirmed) {
                    Amplify.notify('success', result.value.message, title);
                    if (redirect) {
                        setTimeout(() => {
                            const {origin, pathname} = window.location;
                            window.location.replace(origin + pathname);
                        }, 2000);
                    }
                }
            })
            .catch((error) => console.error(error));
    },

    filterFaqList(trigger) {
        trigger.each(function () {
            var self = $(this),
                target = self.data('filter-list'),
                search = self.find('input[type=text]'),
                filters = self.find('input[type=radio]'),
                list = $(target).find('.list-group-item');

            // Search
            search.keyup(function () {
                var searchQuery = search.val();
                list.each(function () {
                    var text = $(this).text().toLowerCase();
                    (text.indexOf(searchQuery.toLowerCase()) === 0) ? $(this).show() : $(this).hide();
                });
            });

            // Filters
            filters.on('click', function (e) {
                var targetItem = $(this).val();
                if (targetItem !== 'all') {
                    list.hide();
                    $('[data-filter-item=' + targetItem + ']').show();
                } else {
                    list.show();
                }

            });
        });
    },

    shopCategories() {
        var categoryToggle = $('.widget-categories .has-children > a');

        function closeCategorySubmenu() {
            categoryToggle.parent().removeClass('expanded');
        }

        categoryToggle.on('click', function (e) {
            if ($(e.target).parent().is('.expanded')) {
                closeCategorySubmenu();
            } else {
                closeCategorySubmenu();
                $(this).parent().addClass('expanded');
            }
        });
    },

    // Gallery (Photoswipe)
    //------------------------------------------------------------------------------
    initPhotoSwipeFromDOM(gallerySelector = '.gallery-wrapper') {
        // parse slide data (url, title, size ...) from DOM elements
        // (children of gallerySelector)
        var parseThumbnailElements = function (el) {
            var thumbElements = $(el).find('.gallery-item:not(.isotope-hidden)').get(),
                numNodes = thumbElements.length,
                items = [],
                figureEl,
                linkEl,
                size,
                item;

            for (var i = 0; i < numNodes; i++) {

                figureEl = thumbElements[i]; // <figure> element

                // include only element nodes
                if (figureEl.nodeType !== 1) {
                    continue;
                }

                linkEl = figureEl.children[0]; // <a> element

                // create slide object
                if ($(linkEl).data('type') === 'video') {
                    item = {
                        html: $(linkEl).data('video')
                    };
                } else {
                    size = linkEl.getAttribute('data-size').split('x');
                    item = {
                        src: linkEl.getAttribute('href'),
                        w: parseInt(size[0], 10),
                        h: parseInt(size[1], 10)
                    };
                }

                if (figureEl.children.length > 1) {
                    item.title = $(figureEl).find('.caption').html();
                }

                if (linkEl.children.length > 0) {
                    item.msrc = linkEl.children[0].getAttribute('src');
                }

                item.el = figureEl; // save link to element for getThumbBoundsFn
                items.push(item);
            }

            return items;
        };

        // find nearest parent element
        var closest = function closest(el, fn) {
            return el && (fn(el) ? el : closest(el.parentNode, fn));
        };

        function hasClass(element, cls) {
            return (' ' + element.className + ' ').indexOf(' ' + cls + ' ') > -1;
        }

        // triggers when user clicks on thumbnail
        var onThumbnailsClick = function (e) {
            e = e || window.event;
            e.preventDefault ? e.preventDefault() : e.returnValue = false;

            var eTarget = e.target || e.srcElement;

            // find root element of slide
            var clickedListItem = closest(eTarget, function (el) {
                return (hasClass(el, 'gallery-item'));
            });

            if (!clickedListItem) {
                return;
            }

            // find index of clicked item by looping through all child nodes
            // alternatively, you may define index via data- attribute
            var clickedGallery = clickedListItem.closest('.gallery-wrapper'),
                childNodes = $(clickedListItem.closest('.gallery-wrapper')).find('.gallery-item:not(.isotope-hidden)').get(),
                numChildNodes = childNodes.length,
                nodeIndex = 0,
                index;

            for (var i = 0; i < numChildNodes; i++) {
                if (childNodes[i].nodeType !== 1) {
                    continue;
                }

                if (childNodes[i] === clickedListItem) {
                    index = nodeIndex;
                    break;
                }
                nodeIndex++;
            }

            if (index >= 0) {
                // open PhotoSwipe if valid index found
                openPhotoSwipe(index, clickedGallery);
            }
            return false;
        };

        // parse picture index and gallery index from URL (#&pid=1&gid=2)
        var photoswipeParseHash = function () {
            var hash = window.location.hash.substring(1),
                params = {};

            if (hash.length < 5) {
                return params;
            }

            var vars = hash.split('&');
            for (var i = 0; i < vars.length; i++) {
                if (!vars[i]) {
                    continue;
                }
                var pair = vars[i].split('=');
                if (pair.length < 2) {
                    continue;
                }
                params[pair[0]] = pair[1];
            }

            if (params.gid) {
                params.gid = parseInt(params.gid, 10);
            }

            return params;
        };

        var openPhotoSwipe = function (index, galleryElement, disableAnimation, fromURL) {
            var pswpElement = document.querySelectorAll('.pswp')[0],
                gallery,
                options,
                items;

            items = parseThumbnailElements(galleryElement);

            // define options (if needed)
            options = {

                closeOnScroll: false,

                // define gallery index (for URL)
                galleryUID: galleryElement.getAttribute('data-pswp-uid'),

                getThumbBoundsFn: function (index) {
                    // See Options -> getThumbBoundsFn section of documentation for more info
                    var thumbnail = items[index].el.getElementsByTagName('img')[0]; // find thumbnail
                    if ($(thumbnail).length > 0) {
                        var pageYScroll = window.pageYOffset || document.documentElement.scrollTop,
                            rect = thumbnail.getBoundingClientRect();

                        return {
                            x: rect.left,
                            y: rect.top + pageYScroll,
                            w: rect.width
                        };
                    }
                }

            };

            // PhotoSwipe opened from URL
            if (fromURL) {
                if (options.galleryPIDs) {
                    // parse real index when custom PIDs are used
                    // http://photoswipe.com/documentation/faq.html#custom-pid-in-url
                    for (var j = 0; j < items.length; j++) {
                        if (items[j].pid == index) {
                            options.index = j;
                            break;
                        }
                    }
                } else {
                    // in URL indexes start from 1
                    options.index = parseInt(index, 10) - 1;
                }
            } else {
                options.index = parseInt(index, 10);
            }

            // exit if index not found
            if (isNaN(options.index)) {
                return;
            }

            if (disableAnimation) {
                options.showAnimationDuration = 0;
            }

            // Pass data to PhotoSwipe and initialize it
            gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);
            gallery.init();

            gallery.listen('beforeChange', function () {
                var currItem = $(gallery.currItem.container);
                $('.pswp__video').removeClass('active');
                var currItemIframe = currItem.find('.pswp__video').addClass('active');
                $('.pswp__video').each(function () {
                    if (!$(this).hasClass('active')) {
                        $(this).attr('src', $(this).attr('src'));
                    }
                });
            });

            gallery.listen('close', function () {
                $('.pswp__video').each(function () {
                    $(this).attr('src', $(this).attr('src'));
                });
            });

        };

        // loop through all gallery elements and bind events
        var galleryElements = document.querySelectorAll(gallerySelector);

        for (var i = 0, l = galleryElements.length; i < l; i++) {
            galleryElements[i].setAttribute('data-pswp-uid', i + 1);
            galleryElements[i].onclick = onThumbnailsClick;
        }

        // Parse URL and open gallery if it contains #&pid=3&gid=1
        var hashData = photoswipeParseHash();
        if (hashData.pid && hashData.gid) {
            openPhotoSwipe(hashData.pid, galleryElements[hashData.gid - 1], true, true);
        }
    },

    productSlider: function (target) {
        let $productCarousel = $(target);
        // Carousel init
        $productCarousel.owlCarousel({
            items: 1,
            loop: false,
            dots: false,
            URLhashListener: true,
            startPosition: 'URLHash',
            onTranslate: function(e) {
                var i = e.item.index;
                var $activeHash = $('.owl-item').eq(i).find('[data-hash]').attr('data-hash');
                $('.product-thumbnails li').removeClass('active');
                $('[href="#' + $activeHash + '"]').parent().addClass('active');
                $('.gallery-wrapper .gallery-item').removeClass('active');
                $('[data-hash="' + $activeHash + '"]').parent().addClass('active');

            }
        });
    },

    // Submit cart as quote
    submitCartAsQuote(submitQuoteBtn) {
        // Prevent multiple submissions
        if (submitQuoteBtn.dataset.submitting === 'true') {
            return;
        }

        const submitUrl = submitQuoteBtn.dataset.url;
        const redirectUrl = submitQuoteBtn.dataset.backtoshop ?? window.location.href;
        const noteLabel = submitQuoteBtn.dataset.noteLabel ?? 'Comments';
        const notePlaceholder = submitQuoteBtn.dataset.notePlaceholder ?? 'Enter comments';

        if (!submitUrl) {
            return;
        }

        const noteFieldHtml = `
            <div class="form-group text-left mb-0">
                <label for="quote-order-notes" class="font-weight-bold">${noteLabel}</label>
                <textarea
                    id="quote-order-notes"
                    class="form-control"
                    rows="3"
                    placeholder="${notePlaceholder}"
                ></textarea>
            </div>
        `;

        Amplify.confirm(
            '',
            'Quotation',
            'Submit',
            {
                html: noteFieldHtml,
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-primary',
                },
                preConfirm: () => {
                    const orderNotes = document.getElementById('quote-order-notes')?.value ?? '';

                    submitQuoteBtn.dataset.submitting = 'true';

                    return $.ajax({
                        url: submitUrl,
                        method: 'POST',
                        data: {
                            order_notes: orderNotes,
                        }
                    }).then((data) => {
                        submitQuoteBtn.dataset.submitting = 'false';

                        if (data?.success) {
                            Amplify.confirm(
                                data.message,
                                'Quotation',
                                'Continue Shopping',
                                {
                                    icon: 'success',
                                    cancelButtonText: 'Review Quotation',
                                    customClass: {
                                        actions: 'w-100 d-flex justify-content-center',
                                        confirmButton: 'btn btn-primary',
                                        cancelButton: 'btn btn-secondary',
                                    }
                                }
                            ).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = redirectUrl;
                                    return;
                                }

                                if (result.isDismissed) {
                                    window.location.href = (result.dismiss === 'cancel')
                                        ? (data.redirect_to || redirectUrl)
                                        : redirectUrl;
                                }
                            });
                        }

                        return data;
                    }).catch((err) => {
                        submitQuoteBtn.dataset.submitting = 'false';
                        let response = err.responseJSON || err.response;
                        Amplify.alert(response?.message ?? response?.statusText ?? 'Something went wrong', 'Quotation', {icon: 'error'});
                        throw err;
                    });
                },
            }
        );
    },
}
