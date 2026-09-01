<div {!! $htmlAttributes !!}>
    <div class="card">
        <div class="card-body">
            <div class="form-group mb-0">
                <div class="quick-order-import border rounded p-3 mb-3">
                    <label class="font-weight-bold mb-1 d-block" for="quick_order_file">{{ __('Import from spreadsheet') }}</label>
                    <p class="text-muted small mb-2 mb-md-3">
                        {{ __('Add multiple products at once by uploading a CSV, XLS, or XLSX file.') }}
                    </p>
                    <div class="quick-order-import-controls">
                        <div class="custom-file">
                            <input class="custom-file-input" type="file" id="quick_order_file"
                                   aria-describedby="quick_order_file_help"
                                   accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                                   onchange="readQuickOrderFile(this)" name="quick_order_file">
                            <label class="custom-file-label" for="quick_order_file" id="quick_order_file_label">
                                {{ __('Choose a CSV, XLS, or XLSX file') }}
                            </label>
                        </div>
                        <button type="button" id="upload_btn" class="btn btn-primary my-0"
                                onclick="UploadQuickOrderFile()">
                            {{ __('Upload') }}
                        </button>
                    </div>
                    <div class="mt-2">
                        <a class="small" href="{{ asset('assets/samples/QUICK-ORDER-SAMPLE.csv') }}" download>
                            {{ __('Download sample template') }}
                        </a>
                    </div>
                    <span id="error_div" class="d-block text-danger mt-2" role="alert"></span>
                    <div id="quick_order_file_help" class="small text-muted mt-2 mb-0">
                        {{ __('Spreadsheet requirements:') }}
                        <ul class="mb-0 pl-3">
                            <li>{{ __('Supported file types: CSV, XLS, XLSX.') }}</li>
                            <li>{{ __('Headings must be on row 1. Heading names are ignored.') }}</li>
                            <li>{{ __('Column A = SKU / item number.') }}</li>
                            <li>{{ __('Column B = quantity ordered.') }}</li>
                            <li>{{ __('Maximum of 100 line items.') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
            <style>
                #quickOrderTable td {
                    vertical-align: middle;
                }
                #quickOrderTable .quick-order-code-col {
                    min-width: 260px;
                    width: 30%;
                }
                #quickOrderTable input[name="product_code[]"] {
                    width: 100%;
                    min-width: 220px;
                }
                #quickOrderTable tr.quick-order-row-loading {
                    opacity: 0.72;
                }
                .quick-order-import-controls {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }
                .quick-order-import-controls .custom-file {
                    position: relative;
                    flex: 1 1 auto;
                    height: 44px;
                    margin: 0;
                }
                .quick-order-import .custom-file-input,
                .quick-order-import .custom-file-label {
                    height: 44px;
                    margin: 0;
                }
                .quick-order-import .custom-file-label {
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                    padding: 0 12px;
                    padding-right: 108px;
                    color: #6c757d;
                    font-weight: 400;
                    line-height: 42px;
                    border-radius: 8px;
                }
                .quick-order-import .custom-file-label::after {
                    content: "{{ __('Browse') }}";
                    top: 1px;
                    right: 1px;
                    bottom: 1px;
                    height: auto;
                    padding: 0 16px;
                    line-height: 40px;
                    color: #212529;
                    border-radius: 0 7px 7px 0 !important;
                }
                .quick-order-import-controls #upload_btn {
                    flex: 0 0 auto;
                    width: auto !important;
                    min-width: 110px;
                    margin: 0 !important;
                    height: 44px !important;
                    padding: 0 18px !important;
                    line-height: 1 !important;
                    display: inline-flex !important;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                    border-radius: 6px;
                    font-size: 14px;
                }
                @media (max-width: 768px) {
                    .quick-order-import-controls {
                        flex-wrap: wrap;
                    }
                    .quick-order-import-controls .custom-file,
                    .quick-order-import #upload_btn {
                        width: 100%;
                    }
                }
            </style>
            <p class="text-muted small mb-2" id="quick_order_code_help">
                {{ __('Type or paste a product code, then press Enter to add it.') }}
            </p>
            <div class="tableFixHead table-responsive pb-4 pb-md-0">
                <table class="table table-striped table-hover" id="quickOrderTable">
                    <thead>
                    <tr>
                        <th scope="col" class="quick-order-code-col">{{ __('Product Code') }}</th>
                        <th scope="col" style="min-width: 180px">{{ __('Product Name') }}</th>
                        @erp
                        <th scope="col" class="text-center" style="min-width: 140px">{{ __('Warehouse') }}</th>
                        <th scope="col" class="text-center" style="width: 120px">{{ __('Quantity') }}</th>
                        @enderp
                        <th scope="col" class="text-center" style="width: 70px">{{ __('Remove') }}</th>
                    </tr>
                    </thead>
                    <tbody id="quick_order_tbody"></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center pb-md-0">
                <div>
                    <div class="text-success font-weight-bold" id="added_product_count"></div>
                    <div class="text-muted small d-none" id="quick_order_status" role="status"></div>
                </div>
                <div class="text-center">
                    <button type="button" id="add_to_order_btn" class="btn btn-primary btn-sm" disabled
                            onclick="addToOrder()">
                        {{ __('Add to Order') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var limit = 0;
    var from = 0;

    const USER_ACTIVE_WAREHOUSE_CODE = @json($userActiveWarehouseCode);
    const USER_ACTIVE_WAREHOUSE_NAME = @json($userActiveWarehouseName);
    const WAREHOUSE_QUANTITY_AVAILABILITY_CHECK = parseInt("{{ $checkWarehouseQtyAvailability }}")
    const ADD_TO_ORDER_LABEL = @json(__('Add to Order'));
    const UPLOAD_LABEL = @json(__('Upload'));
    const FILE_INPUT_PLACEHOLDER = @json(__('Choose a CSV, XLS, or XLSX file'));
    const PRESS_ENTER_HINT = @json(__('Press Enter to add'));
    let pendingRequests = 0;

    function spinnerHtml(label) {
        return '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>' + label;
    }

    function refreshActionButtons() {
        const isBusy = pendingRequests > 0;
        const addedCount = $('#quick_order_tbody tr.added_products').filter(function() {
            return $(this).find('input[name="product_code[]"]').val().trim() !== '';
        }).length;

        $('#add_to_order_btn').prop('disabled', isBusy || addedCount === 0);
        $('#upload_btn').prop('disabled', isBusy);
        $('#quick_order_file').prop('disabled', isBusy);
    }

    function setQuickOrderBusy(isStarting, message) {
        pendingRequests += isStarting ? 1 : -1;
        if (pendingRequests < 0) {
            pendingRequests = 0;
        }

        if (pendingRequests > 0 && message) {
            $('#quick_order_status').text(message).removeClass('d-none');
        }

        if (pendingRequests === 0) {
            $('#quick_order_status').text('').addClass('d-none');
        }

        refreshActionButtons();
    }

    function setRowLoading(index, isLoading) {
        const row = $('#added_products_' + index);
        const codeInput = $('#product_code_' + index);

        row.toggleClass('quick-order-row-loading', isLoading);
        row.data('fetching', isLoading);
        codeInput.prop('disabled', isLoading);

        if (isLoading) {
            $('#product_name_' + index).html(
                '<span class="text-muted d-inline-flex align-items-center">' +
                    spinnerHtml('Fetching product...') +
                '</span>'
            );
            $('#product_code_error_' + index).text('');
            $('#product_code_hint_' + index).addClass('d-none');
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function getRowIndexFromCodeInput(element) {
        return String($(element).attr('id').replace('product_code_', ''));
    }

    function getResolvedCode(index) {
        return normalizeProductCode($('#product_code_' + index).data('resolvedCode'));
    }

    function setResolvedCode(index, code) {
        $('#product_code_' + index).data('resolvedCode', normalizeProductCode(code));
        $('#product_code_hint_' + index).remove();
    }

    function clearResolvedCode(index) {
        $('#product_code_' + index).removeData('resolvedCode');
        const hasProduct = $('#product_id_' + index).val() !== '';
        if (hasProduct) {
            $('#product_code_hint_' + index).remove();
            return;
        }

        if ($('#product_code_hint_' + index).length === 0) {
            $('#product_code_' + index).after(
                `<small class="form-text text-muted mb-0" id="product_code_hint_${index}">${PRESS_ENTER_HINT}</small>`
            );
        }
    }

    function productCodeFieldHtml(index, value, options) {
        const opts = options || {};
        const errorText = opts.error ? escapeHtml(opts.error) : '';
        const hintHtml = opts.alreadyAdded
            ? ''
            : `<small class="form-text text-muted mb-0" id="product_code_hint_${index}">${PRESS_ENTER_HINT}</small>`;

        return `<input type="text" aria-label="Product code" autocomplete="off"
                    id="product_code_${index}" placeholder="Enter product code"
                    name="product_code[]" class="form-control form-control-sm"
                    value="${escapeHtml(value)}">
                ${hintHtml}
                <small class="text-danger" id="product_code_error_${index}">${errorText}</small>`;
    }

    function productRowHtml(index, product) {
        const qty = product['qty'] !== undefined && product['qty'] !== '' ? product['qty'] : 1;

        return `<tr class="added_products" id="added_products_${index}">
            <td class="quick-order-code-col">
                <input type="hidden" id="product_id_${index}" value="${escapeHtml(product['product_id'])}" name="product_id[]" />
                <input type="hidden" id="product_back_order_${index}" value="${escapeHtml(product['product_back_order'])}" name="product_back_order[]" />
                ${productCodeFieldHtml(index, product['product_code'], {
                    alreadyAdded: true,
                    error: product['error'],
                })}
            </td>
            <td>
                <span id="product_name_${index}">${escapeHtml(product['product_name'] || '-')}</span>
            </td>
            <td class="warehouse text-center align-middle">
                ${createWarehouse(product.ERP, index)}
            </td>
            <td class="text-center align-middle">
                <input type="number" aria-label="Quantity" placeholder="Quantity" name="qty[]" value="${escapeHtml(qty)}" min="1" max="" id="qty_${index}" class="form-control form-control-sm mx-auto" style="width: 110px;">
                <small class="text-danger" id="qty_error_${index}"></small>
            </td>
            ${removeButtonCell(false)}
        </tr>`;
    }

    function handleProductCodeInput(element) {
        const index = getRowIndexFromCodeInput(element);
        const current = normalizeProductCode($(element).val());
        const resolved = getResolvedCode(index);

        $('#product_code_error_' + index).text('');

        if (resolved === '' || current === resolved) {
            return;
        }

        $('#product_id_' + index).val('');
        $('#product_back_order_' + index).val('');
        $('#product_name_' + index).text('-');
        $('#qty_error_' + index).text('');
        $(`#added_products_${index} .warehouse`).html(createWarehouse([], index));
        clearResolvedCode(index);
    }

    function focusProductCode(index) {
        const input = $('#product_code_' + index);
        if (input.length) {
            input.trigger('focus');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        addProduct(true);
        bindProductCodeEvents();
    });

    function bindProductCodeEvents() {
        const tbody = document.getElementById('quick_order_tbody');
        tbody.addEventListener('keydown', function(event) {
            if (!event.target.matches('input[name="product_code[]"]')) {
                return;
            }
            if (event.key === 'Enter' || event.keyCode === 13) {
                event.preventDefault();
                lookupProductByCode(event.target);
            }
        });
        tbody.addEventListener('input', function(event) {
            if (event.target.matches('input[name="product_code[]"]')) {
                handleProductCodeInput(event.target);
            }
        });
    }

    function readQuickOrderFile(file) {
        const selectedFile = file.files && file.files[0];
        $('#quick_order_file_label').text(selectedFile ? selectedFile.name : FILE_INPUT_PLACEHOLDER);
    }

    function UploadQuickOrderFile() {
        $('#error_div').empty();
        var file = $('#quick_order_file')[0].files[0];
        if (file === undefined) {
            ShowNotification('error', 'Quick Order', 'Please select a file to upload');
            return;
        }

        var formData = new FormData();
        formData.append('file', file);
        formData.append('_token', '{{ csrf_token() }}');
        $.ajax({
            url: '{{ route('frontend.order.quick-order-file-upload') }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#upload_btn').html(spinnerHtml('Uploading file...'));
                setQuickOrderBusy(true, 'Uploading spreadsheet and fetching products...');
            },
            success: function(response) {
                    if (response.success) {
                        let products = response.data;
                        // let products = items.filter(i => !i.error);

                        let products_array = Object.keys(products).map(function(key) {
                            return products[key];
                        });
                        if (response.message !== '') {
                            $('#error_div').removeClass('d-none');
                            $('#error_div').html(response.message);
                        } else {
                            $('#error_div').addClass('d-none');
                            $('#error_div').html('');
                        }
                        if (products_array.length > 0) {
                            const lastRow = $('#quick_order_tbody tr.added_products').last();
                            const lastIsEntryRow = lastRow.length > 0
                                && lastRow.find('input[name="product_id[]"]').val() === '';
                            let addedCount = 0;
                            let skippedCount = 0;

                            products_array.forEach(function(product) {
                                if (findExistingProductRow(product['product_code'])) {
                                    skippedCount += 1;
                                    return;
                                }

                                const index = limit;
                                limit += 1;
                                from = index;
                                const rowHtml = productRowHtml(index, product);

                                if (lastIsEntryRow) {
                                    lastRow.before(rowHtml);
                                } else {
                                    $('#quick_order_tbody').append(rowHtml);
                                }

                                setMaxQty(index);
                                if (product['product_id']) {
                                    setResolvedCode(index, product['product_code']);
                                }
                                addedCount += 1;
                            });

                            if (!lastIsEntryRow) {
                                addProduct();
                            }

                            updateAddedProductCount();
                            $('#quick_order_file').val('');
                            $('#quick_order_file_label').text(FILE_INPUT_PLACEHOLDER);

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
                        }
                    } else {
                        ShowNotification('error', 'Quick Order', response.message);
                    }
                },
                error: function(xhr) {
                    let message = 'Something went wrong while uploading the file.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    if (xhr.status == 422 && xhr.responseJSON && xhr.responseJSON.message) {
                        $('#error_div').html(xhr.responseJSON.message);
                    }
                    ShowNotification('error', 'Quick Order', message);
                },
                complete: function() {
                    $('#upload_btn').html(UPLOAD_LABEL);
                    setQuickOrderBusy(false);
                },
            });
    }

    function getCustomerWarehouse(warehouses) {
        const warehouseList = warehouses || [];
        const customerWarehouse = warehouseList.find(function (warehouse) {
            return String(warehouse.WarehouseID) === String(USER_ACTIVE_WAREHOUSE_CODE);
        });

        return customerWarehouse || {
            WarehouseID: USER_ACTIVE_WAREHOUSE_CODE,
            WarehouseName: USER_ACTIVE_WAREHOUSE_NAME,
            QuantityAvailable: '',
        };
    }

    function setMaxQty(index) {
        const quantity = $('#warehouse_' + index).data('quantity');
        const qtyInput = $('#qty_' + index);
        const available = parseFloat(quantity);
        const allowsBackorder = $('#product_back_order_' + index).val() === 'Y';

        if (!allowsBackorder && Number.isFinite(available) && available > 0) {
            qtyInput.attr('max', available);
            return;
        }

        qtyInput.removeAttr('max');
    }

    function createWarehouse(warehouses, index) {
        const warehouse = getCustomerWarehouse(warehouses);
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
    }

    function removeButtonCell(hidden) {
        const hiddenClass = hidden ? ' d-none' : '';

        return `<td class="text-center align-middle" style="width: 70px">
            <button type="button" class="btn btn-sm m-0 px-2${hiddenClass}" data-toggle="tooltip" title="Remove" onclick="removeProduct(this)">
                <i class="icon-cross text-danger font-weight-bold"></i>
            </button>
        </td>`;
    }

    function normalizeProductCode(code) {
        return String(code || '').trim().toUpperCase();
    }

    function findExistingProductRow(productCode, excludeIndex) {
        const normalizedCode = normalizeProductCode(productCode);
        let existingRow = null;

        $('#quick_order_tbody tr.added_products').each(function() {
            const row = $(this);
            const rowIndex = String(row.attr('id').split('_').pop());
            if (rowIndex === String(excludeIndex)) {
                return;
            }

            const rowId = row.find('input[name="product_id[]"]').val();
            const rowCode = normalizeProductCode(row.find('input[name="product_code[]"]').val());
            if (rowId && rowCode !== '' && rowCode === normalizedCode) {
                existingRow = row;
                return false;
            }
        });

        return existingRow;
    }

    function resetProductRow(index) {
        $('#product_id_' + index).val('');
        $('#product_back_order_' + index).val('');
        $('#product_code_' + index).val('');
        $('#product_name_' + index).text('-');
        $('#product_code_error_' + index).text('');
        $('#qty_' + index).val('');
        $('#qty_error_' + index).text('');
        $(`#added_products_${index} .warehouse`).html(createWarehouse([], index));
        $(`#added_products_${index} td button`).addClass('d-none');
        clearResolvedCode(index);
    }

    function notifyProductAlreadyAdded(currentIndex, productCode) {
        const currentRow = $('#added_products_' + currentIndex);
        const isLastRow = currentRow.is($('#quick_order_tbody tr.added_products').last());
        if (isLastRow) {
            resetProductRow(currentIndex);
            focusProductCode(currentIndex);
        } else {
            currentRow.remove();
        }

        updateAddedProductCount();
        ShowNotification(
            'info',
            'Quick Order',
            productCode + ' is already added to the list.'
        );
    }

    function updateAddedProductCount() {
        const addedCount = $('#quick_order_tbody tr.added_products').filter(function() {
            return $(this).find('input[name="product_code[]"]').val().trim() !== '';
        }).length;

        $('#added_product_count').text(addedCount > 0 ? addedCount + ' Products added' : '');
        refreshActionButtons();
    }

    function addProduct(shouldFocus) {
        if ($('#no_product_tr').length > 0) {
            $('#no_product_tr').remove();
        }
        from = limit;
        limit += 1;
        let html = '';
        for (var i = from; i < limit; i++) {
            html += ` <tr class="added_products" id="added_products_${i}">
                        <td class="quick-order-code-col">
                            <input type="hidden" id="product_id_${i}" value="" name="product_id[]" />
                            <input type="hidden" id="product_back_order_${i}" value="" name="product_back_order[]" />
                            ${productCodeFieldHtml(i, '', { alreadyAdded: false })}
                        </td>
                        <td>
                            <span id="product_name_${i}">-</span>
                        </td>
                        <td class="warehouse text-center align-middle">
                            ${createWarehouse([], i)}
                        </td>
                        <td class="text-center align-middle">
                            <input type="number" aria-label="Quantity" placeholder="Quantity" name="qty[]" value="" min="0" max=""
                                    onkeypress="return event.charCode >= 48"
                                    id="qty_${i}"
                                    class="form-control form-control-sm mx-auto" style="width: 110px;">
                            <small class="text-danger" id="qty_error_${i}"></small>
                        </td>
                        ${removeButtonCell(true)}
                    </tr>`;
        }

        $('#quick_order_tbody').append(html);
        updateAddedProductCount();
        if (shouldFocus) {
            focusProductCode(from);
        }
    }

    function promptRemoveButton(index) {
        $(`#added_products_${index} td button`).removeClass('d-none');
    }

    function removeProduct(element) {
        $(element).closest('tr').remove();
        ShowNotification('success', 'Quick Order', 'Product removed successfully');
        updateAddedProductCount();
        if ($('#quick_order_tbody tr').length === 0) {
            showNoProductsFound();
        }
    }

    function lookupProductByCode(element) {
        const product_code = $(element).val();
        const index = getRowIndexFromCodeInput(element);
        const normalized = normalizeProductCode(product_code);

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

        if (getResolvedCode(index) === normalized && $('#product_id_' + index).val() !== '') {
            return;
        }

        const existingRow = findExistingProductRow(product_code, index);
        if (existingRow) {
            notifyProductAlreadyAdded(index, product_code);
            return;
        }

        $.ajax({
            url: '{{ route('frontend.order.get-product-name-by-code') }}',
            type: 'POST',
            data: {
                product_code: product_code,
                _token: '{{ csrf_token() }}',
            },
            beforeSend: function() {
                setRowLoading(index, true);
                setQuickOrderBusy(true);
            },
            success: function(response) {
                if (response.success == true) {
                    const duplicateRow = findExistingProductRow(product_code, index);
                    if (duplicateRow) {
                        notifyProductAlreadyAdded(index, product_code);
                        return;
                    }

                    $('#product_id_' + index).val(response.data.product_id);
                    $('#product_back_order_' + index).val(response.data.product_back_order);
                    $('#product_name_' + index).text(response.data.product_name);
                    $('#product_code_error_' + index).text('');
                    setResolvedCode(index, product_code);
                    if (!$('#qty_' + index).val()) {
                        $('#qty_' + index).val(1);
                    }

                    selectWarehouseForSingleProduct(response.data, index);

                    if ($('#quick_order_tbody tr').last().find('input[name="product_code[]"]').val() != '') {
                        addProduct();
                        promptRemoveButton(index);
                    }

                } else {
                    $('#product_name_' + index).text(response.data.product_name || 'N/A');
                    $('#product_code_error_' + index).text(response.data.error);
                    $(`#added_products_${index} .warehouse`).html(createWarehouse([], index));
                }
            },
            error: function() {
                $('#product_name_' + index).text('-');
                $('#product_code_error_' + index).text('Unable to fetch this product. Please try again.');
                ShowNotification('error', 'Quick Order', 'Unable to fetch product details.');
            },
            complete: function() {
                setRowLoading(index, false);
                setQuickOrderBusy(false);
            },
        });
    }

    function selectWarehouseForSingleProduct(product, index) {
        $(`#added_products_${index} .warehouse`).html(createWarehouse(product?.ERP || [], index));
        setMaxQty(index);
    }

    function showNoProductsFound() {
        $('#quick_order_tbody').empty();
        addProduct();

    }

    function addToOrder() {
        let products = [];
        let validation_error = false;
        let quick_order_link = $('#quick-order-link').data('link');

        if ($('#quick_order_tbody tr').length > 0 && $('#quick_order_tbody tr#no_product_tr').length === 0) {
            $('#quick_order_tbody tr').each(function(index, element) {
                let product_code = $(element).find('input[name="product_code[]"]').val();
                let product_id = $(element).find('input[name="product_id[]"]').val();
                let qty = $(element).find('input[name="qty[]"]').val();
                let warehouse = $(element).find('input[name="product_warehouse_code[]"]').val();
                let id = $(element).attr('id').split('_')[2];
                let maxQty = $(element).find('input[name="product_warehouse_code[]"]').data('quantity');

                if (product_code.trim() !== '' && qty.trim() !== '') {
                    $('#product_code_error_' + id).text('');
                    $('#qty_error_' + id).text('');
                    $('#warehouse_error_' + id).text('');
                    products.push({
                        product_code: product_code,
                        product_id: product_id,
                        qty: qty,
                        product_warehouse_code: warehouse,
                    });
                    const allowsBackorder = $(element).find('input[name="product_back_order[]"]').val() === 'Y';
                    const availableQty = parseFloat(maxQty);
                    const orderedQty = parseFloat(qty);
                    const shouldEnforceLimit = WAREHOUSE_QUANTITY_AVAILABILITY_CHECK
                        && !allowsBackorder
                        && Number.isFinite(availableQty)
                        && availableQty > 0
                        && Number.isFinite(orderedQty)
                        && orderedQty > availableQty;

                    if (shouldEnforceLimit) {
                        $('#qty_error_' + id).text('Quantity exceeds available inventory');
                        validation_error = true;
                    }
                } else {
                    if (product_code.trim() !== '') {
                        if (qty.trim() === '') {
                            $('#qty_error_' + id).text('Quantity is required');
                        } else {
                            $('#qty_error_' + id).text('');
                        }
                        validation_error = true;
                    } else {
                        $('#product_code_error_' + id).text('');
                    }
                }
            });

            if (validation_error) {
                ShowNotification('error', 'Quick Order', 'Please fix the highlighted rows before adding to order.');
                return;
            }

            if (products.length === 0) {
                ShowNotification('error', 'Quick Order', 'Please add at least one product');
                return;
            }

            $.ajax({
                url: quick_order_link,
                type: 'POST',
                data: {
                    products: products,
                    _token: '{{ csrf_token() }}',
                },
                beforeSend: function() {
                    $('#add_to_order_btn').html(spinnerHtml('Adding to order...'));
                    setQuickOrderBusy(true, 'Adding products to your order...');
                },
                success: function(response) {
                    if (response.success) {
                        showNoProductsFound();
                        updateAddedProductCount();
                        ShowNotification('success', 'Quick Order', response.message);
                        setTimeout(function() {
                            Amplify.loadCartDropdown();
                        }, 1000);
                    } else {
                        ShowNotification('error', 'Quick Order', response.message);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Unable to add products to your order. Please try again.';
                    ShowNotification('error', 'Quick Order', message);
                },
                complete: function() {
                    $('#add_to_order_btn').html(ADD_TO_ORDER_LABEL);
                    setQuickOrderBusy(false);
                },
            });
        } else {
            ShowNotification('error', 'Quick Order', 'Please add at least one product');
        }
    }
</script>
