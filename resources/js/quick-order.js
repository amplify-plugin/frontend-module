export const QuickOrder = {
    initialized: false,
    limit: 0,
    from: 0,
    pendingRequests: 0,
    root: null,

    init() {
        if (this.initialized) {
            return;
        }

        this.root = document.querySelector('.x-quick-order');
        if (!this.root) {
            return;
        }

        this.initialized = true;
        this.limit = 0;
        this.from = 0;
        this.pendingRequests = 0;
        this.addProduct(true);
        this.bindEvents();
    },

    config() {
        const el = this.root || document.querySelector('.x-quick-order');
        const data = el ? el.dataset : {};

        return {
            warehouseCode: data.warehouseCode || '',
            warehouseName: data.warehouseName || '',
            checkQty: parseInt(data.checkQty || '0', 10) === 1,
            uploadUrl: data.uploadUrl || '',
            lookupUrl: data.lookupUrl || '',
            csrf: data.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            addToOrderLabel: data.addToOrderLabel || 'Add to Order',
            uploadLabel: data.uploadLabel || 'Upload',
            filePlaceholder: data.filePlaceholder || 'Choose a CSV, XLS, or XLSX file',
            pressEnterHint: data.pressEnterHint || 'Press Enter to add',
        };
    },

    bindEvents() {
        const tbody = document.getElementById('quick_order_tbody');
        const fileInput = document.getElementById('quick_order_file');
        const uploadBtn = document.getElementById('upload_btn');
        const addToOrderBtn = document.getElementById('add_to_order_btn');

        tbody?.addEventListener('keydown', (event) => {
            if (!event.target.matches('input[name="product_code[]"]')) {
                return;
            }
            if (event.key === 'Enter' || event.keyCode === 13) {
                event.preventDefault();
                this.lookupProductByCode(event.target);
            }
        });

        tbody?.addEventListener('input', (event) => {
            if (event.target.matches('input[name="product_code[]"]')) {
                this.handleProductCodeInput(event.target);
            }
        });

        tbody?.addEventListener('click', (event) => {
            const removeBtn = event.target.closest('[data-quick-order-remove]');
            if (removeBtn) {
                event.preventDefault();
                this.removeProduct(removeBtn);
            }
        });

        fileInput?.addEventListener('change', (event) => {
            this.readQuickOrderFile(event.target);
        });

        uploadBtn?.addEventListener('click', (event) => {
            event.preventDefault();
            this.uploadQuickOrderFile();
        });

        addToOrderBtn?.addEventListener('click', (event) => {
            event.preventDefault();
            this.addToOrder();
        });
    },

    spinnerHtml(label) {
        return '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>' + label;
    },

    refreshActionButtons() {
        const isBusy = this.pendingRequests > 0;
        const addedCount = $('#quick_order_tbody tr.added_products').filter(function () {
            return $(this).find('input[name="product_code[]"]').val().trim() !== '';
        }).length;

        $('#add_to_order_btn').prop('disabled', isBusy || addedCount === 0);
        $('#upload_btn').prop('disabled', isBusy);
        $('#quick_order_file').prop('disabled', isBusy);
    },

    setBusy(isStarting, message) {
        this.pendingRequests += isStarting ? 1 : -1;
        if (this.pendingRequests < 0) {
            this.pendingRequests = 0;
        }

        if (this.pendingRequests > 0 && message) {
            $('#quick_order_status').text(message).removeClass('d-none');
        }

        if (this.pendingRequests === 0) {
            $('#quick_order_status').text('').addClass('d-none');
        }

        this.refreshActionButtons();
    },

    setRowLoading(index, isLoading) {
        const row = $('#added_products_' + index);
        const codeInput = $('#product_code_' + index);

        row.toggleClass('quick-order-row-loading', isLoading);
        row.data('fetching', isLoading);
        codeInput.prop('disabled', isLoading);

        if (isLoading) {
            $('#product_name_' + index).html(
                '<span class="text-muted d-inline-flex align-items-center">' +
                    this.spinnerHtml('Fetching product...') +
                '</span>'
            );
            $('#product_code_error_' + index).text('');
            $('#product_code_hint_' + index).addClass('d-none');
        }
    },

    escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    },

    getRowIndexFromCodeInput(element) {
        return String($(element).attr('id').replace('product_code_', ''));
    },

    normalizeProductCode(code) {
        return String(code || '').trim().toUpperCase();
    },

    getResolvedCode(index) {
        return this.normalizeProductCode($('#product_code_' + index).data('resolvedCode'));
    },

    setResolvedCode(index, code) {
        $('#product_code_' + index).data('resolvedCode', this.normalizeProductCode(code));
        $('#product_code_hint_' + index).remove();
    },

    clearResolvedCode(index) {
        $('#product_code_' + index).removeData('resolvedCode');
        const hasProduct = $('#product_id_' + index).val() !== '';
        if (hasProduct) {
            $('#product_code_hint_' + index).remove();
            return;
        }

        if ($('#product_code_hint_' + index).length === 0) {
            $('#product_code_' + index).after(
                `<small class="form-text text-muted mb-0" id="product_code_hint_${index}">${this.config().pressEnterHint}</small>`
            );
        }
    },

    productCodeFieldHtml(index, value, options) {
        const opts = options || {};
        const errorText = opts.error ? this.escapeHtml(opts.error) : '';
        const hintHtml = opts.alreadyAdded
            ? ''
            : `<small class="form-text text-muted mb-0" id="product_code_hint_${index}">${this.config().pressEnterHint}</small>`;

        return `<input type="text" aria-label="Product code" autocomplete="off"
                    id="product_code_${index}" placeholder="Enter product code"
                    name="product_code[]" class="form-control form-control-sm"
                    value="${this.escapeHtml(value)}">
                ${hintHtml}
                <small class="text-danger" id="product_code_error_${index}">${errorText}</small>`;
    },

    productRowHtml(index, product) {
        const qty = product['qty'] !== undefined && product['qty'] !== '' ? product['qty'] : 1;

        return `<tr class="added_products" id="added_products_${index}">
            <td class="quick-order-code-col">
                <input type="hidden" id="product_id_${index}" value="${this.escapeHtml(product['product_id'])}" name="product_id[]" />
                <input type="hidden" id="product_back_order_${index}" value="${this.escapeHtml(product['product_back_order'])}" name="product_back_order[]" />
                ${this.productCodeFieldHtml(index, product['product_code'], {
                    alreadyAdded: true,
                    error: product['error'],
                })}
            </td>
            <td>
                <span id="product_name_${index}">${this.escapeHtml(product['product_name'] || '-')}</span>
            </td>
            <td class="warehouse text-center align-middle">
                ${this.createWarehouse(product.ERP, index)}
            </td>
            <td class="text-center align-middle">
                <input type="number" aria-label="Quantity" placeholder="Quantity" name="qty[]" value="${this.escapeHtml(qty)}" min="1" max="" id="qty_${index}" class="form-control form-control-sm mx-auto" style="width: 110px;">
                <small class="text-danger" id="qty_error_${index}"></small>
            </td>
            ${this.removeButtonCell(false)}
        </tr>`;
    },

    handleProductCodeInput(element) {
        const index = this.getRowIndexFromCodeInput(element);
        const current = this.normalizeProductCode($(element).val());
        const resolved = this.getResolvedCode(index);

        $('#product_code_error_' + index).text('');

        if (resolved === '' || current === resolved) {
            return;
        }

        $('#product_id_' + index).val('');
        $('#product_back_order_' + index).val('');
        $('#product_name_' + index).text('-');
        $('#qty_error_' + index).text('');
        $(`#added_products_${index} .warehouse`).html(this.createWarehouse([], index));
        this.clearResolvedCode(index);
    },

    focusProductCode(index) {
        const input = $('#product_code_' + index);
        if (input.length) {
            input.trigger('focus');
        }
    },

    readQuickOrderFile(file) {
        const selectedFile = file.files && file.files[0];
        $('#quick_order_file_label').text(selectedFile ? selectedFile.name : this.config().filePlaceholder);
    },

    uploadQuickOrderFile() {
        $('#error_div').empty();
        const file = $('#quick_order_file')[0]?.files[0];
        if (file === undefined) {
            ShowNotification('error', 'Quick Order', 'Please select a file to upload');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', this.config().csrf);

        $.ajax({
            url: this.config().uploadUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: () => {
                $('#upload_btn').html(this.spinnerHtml('Uploading file...'));
                this.setBusy(true, 'Uploading spreadsheet and fetching products...');
            },
            success: (response) => {
                if (!response.success) {
                    ShowNotification('error', 'Quick Order', response.message);
                    return;
                }

                const products = response.data;
                const productsArray = Object.keys(products).map((key) => products[key]);

                if (response.message !== '') {
                    $('#error_div').removeClass('d-none').html(response.message);
                } else {
                    $('#error_div').addClass('d-none').html('');
                }

                if (productsArray.length === 0) {
                    return;
                }

                const lastRow = $('#quick_order_tbody tr.added_products').last();
                const lastIsEntryRow = lastRow.length > 0
                    && lastRow.find('input[name="product_id[]"]').val() === '';
                let addedCount = 0;
                let skippedCount = 0;

                productsArray.forEach((product) => {
                    if (this.findExistingProductRow(product['product_code'])) {
                        skippedCount += 1;
                        return;
                    }

                    const index = this.limit;
                    this.limit += 1;
                    this.from = index;
                    const rowHtml = this.productRowHtml(index, product);

                    if (lastIsEntryRow) {
                        lastRow.before(rowHtml);
                    } else {
                        $('#quick_order_tbody').append(rowHtml);
                    }

                    this.setMaxQty(index);
                    if (product['product_id']) {
                        this.setResolvedCode(index, product['product_code']);
                    }
                    addedCount += 1;
                });

                if (!lastIsEntryRow) {
                    this.addProduct();
                }

                this.updateAddedProductCount();
                $('#quick_order_file').val('');
                $('#quick_order_file_label').text(this.config().filePlaceholder);

                if (addedCount > 0) {
                    ShowNotification('success', 'Quick Order', 'File uploaded successfully');
                }
                if (skippedCount > 0) {
                    ShowNotification(
                        'info',
                        'Quick Order',
                        skippedCount === 1
                            ? '1 product from the file was already in the list and was skipped.'
                            : skippedCount + ' products from the file were already in the list and were skipped.'
                    );
                }
                if (addedCount === 0 && skippedCount === 0) {
                    ShowNotification('info', 'Quick Order', 'No new products were added from the file.');
                }
            },
            error: (xhr) => {
                let message = 'Something went wrong while uploading the file.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                    $('#error_div').html(xhr.responseJSON.message);
                }
                ShowNotification('error', 'Quick Order', message);
            },
            complete: () => {
                $('#upload_btn').html(this.config().uploadLabel);
                this.setBusy(false);
            },
        });
    },

    getCustomerWarehouse(warehouses) {
        const warehouseList = warehouses || [];
        const customerWarehouse = warehouseList.find((warehouse) => {
            return String(warehouse.WarehouseID) === String(this.config().warehouseCode);
        });

        return customerWarehouse || {
            WarehouseID: this.config().warehouseCode,
            WarehouseName: this.config().warehouseName,
            QuantityAvailable: '',
        };
    },

    setMaxQty(index) {
        const quantity = $('#warehouse_' + index).data('quantity');
        const qtyInput = $('#qty_' + index);
        const available = parseFloat(quantity);
        const allowsBackorder = $('#product_back_order_' + index).val() === 'Y';

        if (!allowsBackorder && Number.isFinite(available) && available > 0) {
            qtyInput.attr('max', available);
            return;
        }

        qtyInput.removeAttr('max');
    },

    createWarehouse(warehouses, index) {
        const warehouse = this.getCustomerWarehouse(warehouses);
        const quantityAvailable = warehouse.QuantityAvailable;
        const available = parseFloat(quantityAvailable);
        let availabilityHtml = '';
        if (quantityAvailable !== '' && quantityAvailable !== undefined && quantityAvailable !== null) {
            if (Number.isFinite(available) && available > 0) {
                availabilityHtml = `<div class="text-muted small">${quantityAvailable} available</div>`;
            } else {
                availabilityHtml = `<div class="text-danger small">0 available · Backorder</div>`;
            }
        }

        return `<input type="hidden" name="product_warehouse_code[]" id="warehouse_${index}" value="${warehouse.WarehouseID || ''}" data-quantity="${quantityAvailable}">
            <div class="font-weight-bold">${warehouse.WarehouseName || '—'}</div>
            ${availabilityHtml}
            <small class="text-danger" id="warehouse_error_${index}"></small>`;
    },

    removeButtonCell(hidden) {
        const hiddenClass = hidden ? ' d-none' : '';

        return `<td class="text-center align-middle" style="width: 70px">
            <button type="button" class="btn btn-sm m-0 px-2${hiddenClass}" data-toggle="tooltip" title="Remove" data-quick-order-remove>
                <i class="icon-cross text-danger font-weight-bold"></i>
            </button>
        </td>`;
    },

    findExistingProductRow(productCode, excludeIndex) {
        const normalizedCode = this.normalizeProductCode(productCode);
        let existingRow = null;

        $('#quick_order_tbody tr.added_products').each((_, rowEl) => {
            const row = $(rowEl);
            const rowIndex = String(row.attr('id').split('_').pop());
            if (rowIndex === String(excludeIndex)) {
                return;
            }

            const rowId = row.find('input[name="product_id[]"]').val();
            const rowCode = this.normalizeProductCode(row.find('input[name="product_code[]"]').val());
            if (rowId && rowCode !== '' && rowCode === normalizedCode) {
                existingRow = row;
                return false;
            }
        });

        return existingRow;
    },

    resetProductRow(index) {
        $('#product_id_' + index).val('');
        $('#product_back_order_' + index).val('');
        $('#product_code_' + index).val('');
        $('#product_name_' + index).text('-');
        $('#product_code_error_' + index).text('');
        $('#qty_' + index).val('');
        $('#qty_error_' + index).text('');
        $(`#added_products_${index} .warehouse`).html(this.createWarehouse([], index));
        $(`#added_products_${index} td button`).addClass('d-none');
        this.clearResolvedCode(index);
    },

    notifyProductAlreadyAdded(currentIndex, productCode) {
        const currentRow = $('#added_products_' + currentIndex);
        const isLastRow = currentRow.is($('#quick_order_tbody tr.added_products').last());
        if (isLastRow) {
            this.resetProductRow(currentIndex);
            this.focusProductCode(currentIndex);
        } else {
            currentRow.remove();
        }

        this.updateAddedProductCount();
        ShowNotification(
            'info',
            'Quick Order',
            productCode + ' is already added to the list.'
        );
    },

    updateAddedProductCount() {
        const addedCount = $('#quick_order_tbody tr.added_products').filter(function () {
            return $(this).find('input[name="product_code[]"]').val().trim() !== '';
        }).length;

        $('#added_product_count').text(addedCount > 0 ? addedCount + ' Products added' : '');
        this.refreshActionButtons();
    },

    addProduct(shouldFocus) {
        if ($('#no_product_tr').length > 0) {
            $('#no_product_tr').remove();
        }
        this.from = this.limit;
        this.limit += 1;
        let html = '';
        for (let i = this.from; i < this.limit; i++) {
            html += ` <tr class="added_products" id="added_products_${i}">
                        <td class="quick-order-code-col">
                            <input type="hidden" id="product_id_${i}" value="" name="product_id[]" />
                            <input type="hidden" id="product_back_order_${i}" value="" name="product_back_order[]" />
                            ${this.productCodeFieldHtml(i, '', { alreadyAdded: false })}
                        </td>
                        <td>
                            <span id="product_name_${i}">-</span>
                        </td>
                        <td class="warehouse text-center align-middle">
                            ${this.createWarehouse([], i)}
                        </td>
                        <td class="text-center align-middle">
                            <input type="number" aria-label="Quantity" placeholder="Quantity" name="qty[]" value="" min="0" max=""
                                    onkeypress="return event.charCode >= 48"
                                    id="qty_${i}"
                                    class="form-control form-control-sm mx-auto" style="width: 110px;">
                            <small class="text-danger" id="qty_error_${i}"></small>
                        </td>
                        ${this.removeButtonCell(true)}
                    </tr>`;
        }

        $('#quick_order_tbody').append(html);
        this.updateAddedProductCount();
        if (shouldFocus) {
            this.focusProductCode(this.from);
        }
    },

    promptRemoveButton(index) {
        $(`#added_products_${index} td button`).removeClass('d-none');
    },

    removeProduct(element) {
        $(element).closest('tr').remove();
        ShowNotification('success', 'Quick Order', 'Product removed successfully');
        this.updateAddedProductCount();
        if ($('#quick_order_tbody tr').length === 0) {
            this.showNoProductsFound();
        }
    },

    lookupProductByCode(element) {
        const productCode = $(element).val();
        const index = this.getRowIndexFromCodeInput(element);
        const normalized = this.normalizeProductCode(productCode);

        if (normalized === '') {
            return;
        }

        if (normalized.length < 3) {
            $('#product_code_error_' + index).text('Enter at least 3 characters, then press Enter');
            return;
        }

        if ($('#added_products_' + index).data('fetching')) {
            return;
        }

        if (this.getResolvedCode(index) === normalized && $('#product_id_' + index).val() !== '') {
            return;
        }

        const existingRow = this.findExistingProductRow(productCode, index);
        if (existingRow) {
            this.notifyProductAlreadyAdded(index, productCode);
            return;
        }

        $.ajax({
            url: this.config().lookupUrl,
            type: 'POST',
            data: {
                product_code: productCode,
                _token: this.config().csrf,
            },
            beforeSend: () => {
                this.setRowLoading(index, true);
                this.setBusy(true);
            },
            success: (response) => {
                if (response.success == true) {
                    const duplicateRow = this.findExistingProductRow(productCode, index);
                    if (duplicateRow) {
                        this.notifyProductAlreadyAdded(index, productCode);
                        return;
                    }

                    $('#product_id_' + index).val(response.data.product_id);
                    $('#product_back_order_' + index).val(response.data.product_back_order);
                    $('#product_name_' + index).text(response.data.product_name);
                    $('#product_code_error_' + index).text('');
                    this.setResolvedCode(index, productCode);
                    if (!$('#qty_' + index).val()) {
                        $('#qty_' + index).val(1);
                    }

                    this.selectWarehouseForSingleProduct(response.data, index);

                    if ($('#quick_order_tbody tr').last().find('input[name="product_code[]"]').val() != '') {
                        this.addProduct();
                        this.promptRemoveButton(index);
                    }
                } else {
                    $('#product_name_' + index).text(response.data.product_name || 'N/A');
                    $('#product_code_error_' + index).text(response.data.error);
                    $(`#added_products_${index} .warehouse`).html(this.createWarehouse([], index));
                }
            },
            error: () => {
                $('#product_name_' + index).text('-');
                $('#product_code_error_' + index).text('Unable to fetch this product. Please try again.');
                ShowNotification('error', 'Quick Order', 'Unable to fetch product details.');
            },
            complete: () => {
                this.setRowLoading(index, false);
                this.setBusy(false);
            },
        });
    },

    selectWarehouseForSingleProduct(product, index) {
        $(`#added_products_${index} .warehouse`).html(this.createWarehouse(product?.ERP || [], index));
        this.setMaxQty(index);
    },

    showNoProductsFound() {
        $('#quick_order_tbody').empty();
        this.addProduct();
    },

    addToOrder() {
        const products = [];
        let validationError = false;
        const quickOrderLink = $('#quick-order-link').data('link');

        if ($('#quick_order_tbody tr').length > 0 && $('#quick_order_tbody tr#no_product_tr').length === 0) {
            $('#quick_order_tbody tr').each((_, element) => {
                const productCode = $(element).find('input[name="product_code[]"]').val();
                const productId = $(element).find('input[name="product_id[]"]').val();
                const qty = $(element).find('input[name="qty[]"]').val();
                const warehouse = $(element).find('input[name="product_warehouse_code[]"]').val();
                const id = $(element).attr('id').split('_')[2];
                const maxQty = $(element).find('input[name="product_warehouse_code[]"]').data('quantity');

                if (productCode.trim() !== '' && qty.trim() !== '') {
                    $('#product_code_error_' + id).text('');
                    $('#qty_error_' + id).text('');
                    $('#warehouse_error_' + id).text('');
                    products.push({
                        product_code: productCode,
                        product_id: productId,
                        qty: qty,
                        product_warehouse_code: warehouse,
                    });
                    const allowsBackorder = $(element).find('input[name="product_back_order[]"]').val() === 'Y';
                    const availableQty = parseFloat(maxQty);
                    const orderedQty = parseFloat(qty);
                    const shouldEnforceLimit = this.config().checkQty
                        && !allowsBackorder
                        && Number.isFinite(availableQty)
                        && availableQty > 0
                        && Number.isFinite(orderedQty)
                        && orderedQty > availableQty;

                    if (shouldEnforceLimit) {
                        $('#qty_error_' + id).text('Quantity exceeds available inventory');
                        validationError = true;
                    }
                } else if (productCode.trim() !== '') {
                    if (qty.trim() === '') {
                        $('#qty_error_' + id).text('Quantity is required');
                    } else {
                        $('#qty_error_' + id).text('');
                    }
                    validationError = true;
                } else {
                    $('#product_code_error_' + id).text('');
                }
            });

            if (validationError) {
                ShowNotification('error', 'Quick Order', 'Please fix the highlighted rows before adding to order.');
                return;
            }

            if (products.length === 0) {
                ShowNotification('error', 'Quick Order', 'Please add at least one product');
                return;
            }

            $.ajax({
                url: quickOrderLink,
                type: 'POST',
                data: {
                    products: products,
                    _token: this.config().csrf,
                },
                beforeSend: () => {
                    $('#add_to_order_btn').html(this.spinnerHtml('Adding to order...'));
                    this.setBusy(true, 'Adding products to your order...');
                },
                success: (response) => {
                    if (response.success) {
                        this.showNoProductsFound();
                        this.updateAddedProductCount();
                        ShowNotification('success', 'Quick Order', response.message);
                        setTimeout(() => {
                            Amplify.loadCartDropdown();
                        }, 1000);
                    } else {
                        ShowNotification('error', 'Quick Order', response.message);
                    }
                },
                error: (xhr) => {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Unable to add products to your order. Please try again.';
                    ShowNotification('error', 'Quick Order', message);
                },
                complete: () => {
                    $('#add_to_order_btn').html(this.config().addToOrderLabel);
                    this.setBusy(false);
                },
            });
        } else {
            ShowNotification('error', 'Quick Order', 'Please add at least one product');
        }
    },
};

function bootQuickOrder() {
    window.Amplify?.QuickOrder?.init();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootQuickOrder);
} else {
    bootQuickOrder();
}
