<div {!! $htmlAttributes !!}
     data-warehouse-code="{{ $userActiveWarehouseCode }}"
     data-warehouse-name="{{ $userActiveWarehouseName }}"
     data-check-qty="{{ $checkWarehouseQtyAvailability ? 1 : 0 }}"
     data-upload-url="{{ route('frontend.order.quick-order-file-upload') }}"
     data-lookup-url="{{ route('frontend.order.get-product-name-by-code') }}"
     data-csrf="{{ csrf_token() }}"
     data-add-to-order-label="{{ __('Add to Order') }}"
     data-upload-label="{{ __('Upload') }}"
     data-file-placeholder="{{ __('Choose a CSV, XLS, or XLSX file') }}"
     data-press-enter-hint="{{ __('Press Enter to add') }}">
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
                                   name="quick_order_file">
                            <label class="custom-file-label" for="quick_order_file" id="quick_order_file_label"
                                   data-browse="{{ __('Browse') }}">
                                {{ __('Choose a CSV, XLS, or XLSX file') }}
                            </label>
                        </div>
                        <button type="button" id="upload_btn" class="btn btn-primary my-0">
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
                            <li>{{ __('Supported file types: CSV, XLS, or XLSX.') }}</li>
                            <li>{{ __('Headings must be on row 1. Heading names are ignored.') }}</li>
                            <li>{{ __('Column A = SKU / item number.') }}</li>
                            <li>{{ __('Column B = quantity ordered.') }}</li>
                            <li>{{ __('Maximum of 100 line items.') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
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
                    <button type="button" id="add_to_order_btn" class="btn btn-primary btn-sm" disabled>
                        {{ __('Add to Order') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
