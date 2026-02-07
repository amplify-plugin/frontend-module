<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Amplify\Frontend\Helpers\CustomerHelper;
use Amplify\Frontend\Http\Controllers\AddressController;
use Amplify\Frontend\Http\Controllers\Auth\AuthenticatedSessionController;
use Amplify\Frontend\Http\Controllers\Auth\ConfirmablePasswordController;
use Amplify\Frontend\Http\Controllers\Auth\CustomerVerificationController;
use Amplify\Frontend\Http\Controllers\Auth\EmailVerificationNotificationController;
use Amplify\Frontend\Http\Controllers\Auth\EmailVerificationPromptController;
use Amplify\Frontend\Http\Controllers\Auth\ForceResetPasswordController;
use Amplify\Frontend\Http\Controllers\Auth\ForgotPasswordController;
use Amplify\Frontend\Http\Controllers\Auth\NewPasswordController;
use Amplify\Frontend\Http\Controllers\Auth\PasswordController;
use Amplify\Frontend\Http\Controllers\Auth\RegisteredUserController;
use Amplify\Frontend\Http\Controllers\Auth\VerifyEmailController;
use Amplify\Frontend\Http\Controllers\BrandIndexController;
use Amplify\Frontend\Http\Controllers\CampaignController;
use Amplify\Frontend\Http\Controllers\CheckoutController;
use Amplify\Frontend\Http\Controllers\ContactLoginController;
use Amplify\Frontend\Http\Controllers\CustomerPartNumberController;
use Amplify\Frontend\Http\Controllers\DashboardController;
use Amplify\Frontend\Http\Controllers\DraftController;
use Amplify\Frontend\Http\Controllers\EventController;
use Amplify\Frontend\Http\Controllers\FaqController;
use Amplify\Frontend\Http\Controllers\FavouriteController;
use Amplify\Frontend\Http\Controllers\FormResponseAcceptController;
use Amplify\Frontend\Http\Controllers\HomeController;
use Amplify\Frontend\Http\Controllers\InvoiceController;
use Amplify\Frontend\Http\Controllers\LocalizationController;
use Amplify\Frontend\Http\Controllers\MessageController;
use Amplify\Frontend\Http\Controllers\MyProfileController;
use Amplify\Frontend\Http\Controllers\NewsletterSubscriptionController;
use Amplify\Frontend\Http\Controllers\OrderController;
use Amplify\Frontend\Http\Controllers\OrderListController;
use Amplify\Frontend\Http\Controllers\OrderStatusController;
use Amplify\Frontend\Http\Controllers\PasswordResetController;
use Amplify\Frontend\Http\Controllers\PastItemsController;
use Amplify\Frontend\Http\Controllers\ProductDetailController;
use Amplify\Frontend\Http\Controllers\ProductSearchController;
use Amplify\Frontend\Http\Controllers\QuickListController;
use Amplify\Frontend\Http\Controllers\QuotationController;
use Amplify\Frontend\Http\Controllers\ShippingController;
use Amplify\Frontend\Http\Middlewares\CustomerDefaultValues;
use Amplify\System\Backend\Http\Controllers\CartController;
use Amplify\System\Backend\Http\Controllers\CenPosPaymentController;
use Amplify\System\Backend\Http\Controllers\CustomerOrderController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;

Route::get('admin', function () {
    return Redirect::to('admin/login');
});

Route::name('frontend.')->middleware(['web', \Spatie\Honeypot\ProtectAgainstSpam::class])->group(function () {

    Route::middleware([
        \Amplify\Frontend\Http\Middlewares\ContactForceShippingAddressSelection::class,
        \Amplify\Frontend\Http\Middlewares\CaptureIntendedUrl::class
    ])->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Public Routes
        |--------------------------------------------------------------------------
        */
        Route::get('/', HomeController::class)->name('index');

        Route::get('/get-states-by-country-code/{countryCode}', function ($countryCode) {
            return response()->json(['states' => CustomerHelper::fetchStates($countryCode)]);
        });
        /*
        |--------------------------------------------------------------------------
        | Shop Routes
        |--------------------------------------------------------------------------
        */

        $productRoutePrefix = config('amplify.frontend.product_page_prefix');

        Route::get("{$productRoutePrefix}/{identifier}/{slug}/{query?}", ProductDetailController::class)->where(['identifier' => '([a-zA-Z0-9\-]+)', 'query' => '(.*)'])->name('shop.show');

        Route::controller(\Amplify\Frontend\Http\Controllers\ShopSearchController::class)->group(function () {
            $shopRoutePrefix = config('amplify.frontend.shop_page_prefix');
            Route::get("{$shopRoutePrefix}/{query?}", '__invoke')->where(['query' => '(.*)'])->name('index');
            Route::get('quick-view/{id}/{seo_path?}', 'getQuickView')->name('quickView');
            Route::get('warehouse-selection-view/{code}', 'getWarehouseSelectionView')->name('warehouseSelectionView');
        })->name('shop.');

        Route::apiResource('carts', \Amplify\Frontend\Http\Controllers\CartController::class)
            ->where(['cart' => '[0-9]+'])->except('update');
        Route::delete('carts/remove/{cartItem}', [\Amplify\Frontend\Http\Controllers\CartController::class, 'remove'])
            ->name('carts.remove-item');
        Route::patch('carts/update/{cartItem}', [\Amplify\Frontend\Http\Controllers\CartController::class, 'update'])
            ->name('carts.update-item');
        Route::post('carts/order-file', [\Amplify\Frontend\Http\Controllers\CartController::class, 'orderFile'])->name('carts.order-file');
        Route::post('/remove/carts', [\Amplify\Frontend\Http\Controllers\CartController::class, 'removeCarts'])->name('frontend.remove-carts');
        Route::get('checkout', CheckoutController::class)->name('checkout');
        Route::post('subscribe', NewsletterSubscriptionController::class)->name('subscribe');

        Route::prefix('faq')->controller(FaqController::class)->group(function () {
            Route::get('faq/{faq-category-slug?}', '__invoke')->name('faqs.show');
            Route::get('faq/{faq}/stats-count/{value}', 'statsCount')->name('faqs.stats-count');
        });

        Route::post('form-response/{form_code}', FormResponseAcceptController::class)->name('form-submit');
        Route::resource('campaigns', CampaignController::class)->only('index', 'show');
        Route::resource('events', EventController::class)->only('index', 'show');
        Route::get('brands/{query?}', BrandIndexController::class)->where(['query' => '(.*)'])->name('brands');
        Route::get('categories/{query?}', \Amplify\Frontend\Http\Controllers\CategoryIndexController::class)->where(['query' => '(.*)'])->name('categories');
        Route::get('order-completed/{order}', [OrderController::class, 'completed'])->name('orders.completed');
        Route::post('validate/shipping-address', [ShippingController::class, 'validateAddress']);
        Route::post('/get/shipping/option', [ShippingController::class, 'options'])->name('shipping-options');
        Route::get('related-products/{product}', [ProductDetailController::class, 'relatedProducts'])->name('shop.relatedProducts');
        Route::post('/order/quick-order-file-upload',
            [CustomerOrderController::class, 'quickOrderFileUpload'])->name('order.quick-order-file-upload');

        Route::post('/cart/summary', [CartController::class, 'getCartSummary'])->name('cart.summary');

        Route::post('/order/check-order-list-name',
            [CustomerOrderController::class, 'checkOrderListName'])->name('order.order-list.check-name-availability');

        Route::post('/order/get-product-name-by-code',
            [CustomerOrderController::class, 'getProductNameByCode'])->name('order.get-product-name-by-code');
        Route::post('/order/submit-order',
            [CustomerOrderController::class, 'submitOrder'])->name('order.submit-order');
        Route::post('/order/submit-pending-order/{order_id}',
            [CustomerOrderController::class, 'submitPendingOrder'])->name('order.submit-pending-order');
        Route::post('/order/calculate-price',
            [CustomerOrderController::class, 'getOrderPricing'])->name('order.calculate-price');
        Route::post('/order/summary', [CustomerOrderController::class, 'getOrderSummary'])->name('order.summary');
        Route::post('/notices', \Amplify\Frontend\Http\Controllers\NoticeIndexController::class)->name('notices.index');

        Route::controller(LocalizationController::class)->group(function () {
            Route::get('/locale-lang.js', 'exportLocaleLang');
            Route::get('/languages/{locale}', 'switchLanguage');
        });

        /*
        |--------------------------------------------------------------------------
        | Without Authentication
        |--------------------------------------------------------------------------
        */

        Route::middleware('guest:customer')->group(function () {

            Route::controller(AuthenticatedSessionController::class)->group(function () {
                Route::get('login', 'login')->name('login');
                Route::post('login', 'attempt');
                Route::post('logout', 'logout')->name('logout')
                    ->withoutMiddleware('guest:customer');
            });

            Route::controller(RegisteredUserController::class)->prefix('registration')->group(function () {
                Route::get('/', '__invoke')->name('registration');
                Route::post('request-account', 'requestAccount')
                    ->name('registration.request-account');
                Route::post('create-cash-customer', 'newRetailCustomer')
                    ->name('registration.create-cash-customer');
            });

            Route::controller(PasswordResetController::class)->group(function () {
                Route::post('/password-reset-otp', 'sendOtp')->name('password_reset_otp');
                Route::post('/otp-check', 'otpCheck')->name('otp_check');
                Route::post('/reset-password', 'resetPassword')->name('reset_password');
            });

            /*
            |--------------------------------------------------------------------------
            | Forgot Password& Reset Password
            |--------------------------------------------------------------------------
            */
            Route::get('forgot-password', ForgotPasswordController::class)
                ->name('password.request');
            Route::post('forgot-password', [ForgotPasswordController::class, 'store'])
                ->name('password.email');
            Route::get('reset-password/{token}', NewPasswordController::class)
                ->name('password.reset');
            Route::post('reset-password', [NewPasswordController::class, 'attempt'])
                ->name('password.store');
            Route::post('customer-verification', CustomerVerificationController::class)
                ->name('contact-validation');
        });

        /*
        |--------------------------------------------------------------------------
        | Only Authenticated
        |--------------------------------------------------------------------------
        */
        Route::group(['middleware' => ['customers', CustomerDefaultValues::class]], function () {

            /*
            |--------------------------------------------------------------------------
            | Force Reset Password
            |--------------------------------------------------------------------------
            */
            Route::get('force-reset-password', ForceResetPasswordController::class)
                ->name('force-reset-password');

            Route::post('force-reset-password', [ForceResetPasswordController::class, 'attempt'])
                ->name('force-reset-password-attempt');

            Route::get('verify-email', EmailVerificationPromptController::class)
                ->name('verification.notice');

            Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

            Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

            Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

            Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

            Route::put('password', [PasswordController::class, 'update'])->name('password.update');

            /*
            |--------------------------------------------------------------------------
            | Customer Routes
            |--------------------------------------------------------------------------
            */
            Route::get('order-status/{order}', OrderStatusController::class)->name('order-status');

            Route::get('dashboard', DashboardController::class)
                ->name('dashboard');

            Route::get('account-summary', \Amplify\Frontend\Http\Controllers\ARSummaryController::class)->name('account-summary');

            Route::post('my-profile/photo-update', [MyProfileController::class, 'photoUpdate'])->name('profile.photo-update');

            Route::resource('my-profile', MyProfileController::class)->only(['show', 'update'])->names('profile');

            Route::prefix('switch-account')->controller(\Amplify\Frontend\Http\Controllers\SwitchAccountController::class)->group(function () {
                Route::get('/', 'index')->name('switch-account.index');
                Route::put('/', 'update')->name('switch-account.update');
            });

            Route::resource('contacts', \Amplify\Frontend\Http\Controllers\ContactController::class);

            Route::resource('roles', \Amplify\Frontend\Http\Controllers\RoleController::class);

            Route::resource('addresses', AddressController::class);
            Route::get('/addresses/default-address/{address}', [AddressController::class, 'setDefault'])
                ->name('addresses.default-address');

            Route::resource('invoices', InvoiceController::class)
                ->only('index', 'show');
            Route::get('document/{type}/{id}/download', [InvoiceController::class, 'download'])
                ->name('invoices.document.download');
            Route::get('tracking/{invoice}', [InvoiceController::class, 'trackInvoice'])
                ->name('invoices.tracking.invoice');

            Route::resource('orders', OrderController::class)->only('index', 'show');
            Route::post('orders/{id}/approve', [OrderController::class, 'approve'])->name('orders.approve');
            Route::resource('drafts', DraftController::class)->only('index', 'show');

            Route::resource('quotations', QuotationController::class)
                ->only('index', 'show');

            Route::resource('favourites', FavouriteController::class)->where(['favourite' => '[\d]+']);
            Route::delete('favourites/{product}/item', [FavouriteController::class, 'destroyOrderListItem'])->name('favourites.destroy-item');
            Route::post('favourites/{favourite}/sync-product', [FavouriteController::class, 'syncProduct']);

            Route::apiResource('order-lists', OrderListController::class)->where(['order_list' => '[\d]+']);
            Route::delete('order-lists/{product}/item', [OrderListController::class, 'destroyOrderListItem'])->name('order-lists.destroy-item');
            Route::post('order-lists/{order_list}/sync-product', [OrderListController::class, 'syncProduct']);

            Route::get('fetch-products', ProductSearchController::class);

            Route::resource('quick-lists', QuickListController::class)
                ->where(['quick_list' => '[\d]+']);

            Route::post('shipping-address/create', [ShippingController::class, 'store']);
            Route::post('/ship-to-address-save', [ShippingController::class, 'saveShipToAddress'])
                ->name('ship-to-address.store');
            Route::get('/session/shipping-address/{code}', [
                ShippingController::class,
                'storeSessionAddress'
            ])->name('session.shipping-address.store');
            Route::resource('messages', MessageController::class)
                ->names('messages')
                ->where(['message' => '[\d]+']);

            // custom product route

            Route::controller(ContactLoginController::class)->prefix('contact-logins')
                ->name('contact-logins.')->group(function () {
                    Route::get('{contact}/impersonate', 'impersonate')->where(['contact' => '[0-9]+'])->name('impersonate');
                    Route::post('fetch-assignable-customer', 'fetchAssignableCustomer');
                    Route::post('verify-assignable-contact', 'verifyAssignableContact');
                    Route::post('get-roles', 'getRoles');
                    Route::get('/', 'index')->name('index');
                    Route::get('{contact}/edit', 'edit')->name('edit');
                    Route::post('{contact}/update', 'update')->name('update');
                });

            Route::get('past-items', [PastItemsController::class, 'index'])->name('past.items');
            Route::get('past-items-history', [PastItemsController::class, 'history'])->name('past.items.history');
            Route::post('customer-part-numbers', [CustomerPartNumberController::class, 'store'])->name('customer-part-numbers');
            Route::delete('customer-part-numbers', [CustomerPartNumberController::class, 'destroy']);

            Route::prefix('customer')->group(function () {
                Route::get('/cenpos-invoices-payment',
                    [CenPosPaymentController::class, 'invoicePay'])->name('customer.cenpos-invoices-payment');
                Route::post('/cenpos-invoices-pay',
                    [CenPosPaymentController::class, 'invoiceProcessPayment'])->name('customer.cenpos-invoice-pay.submit');

                Route::get('/cenpos-order-credit-card-payment/{customer_order}',
                    [CenPosPaymentController::class, 'orderCreditCardPay'])->name('customer.cenpos-order-credit-card-payment');
                Route::post('/cenpos-order-credit-card-pay-process/{customer_order}', [
                    CenPosPaymentController::class, 'orderCreditCardProcessPayment',
                ])->name('customer.cenpos-order-credit-card-pay.submit');

                Route::get('/cenpos-invoices',
                    [CenPosPaymentController::class, 'index'])->name('customer.cenpos-invoices-pay.index');
            });
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Public System Routes
    |--------------------------------------------------------------------------
    */
    Route::post('/udpate-order-note', [CustomerOrderController::class, 'updateOrderNote'])->name('update.order-note');
    Route::post('/update-draft-note', [CustomerOrderController::class, 'updateDraftNote'])->name('update.draft-note');

    Route::post('/order/submit-quote-as-order',
        [CustomerOrderController::class, 'submitQuoteAsOrder'])->name('submit.quote-to-order');
    Route::post('/order/submit-list-as-order/{list}',
        [CustomerOrderController::class, 'submitListAsOrder'])->name('submit.saved-list-to-order');
    Route::post('/order/add-items-to-order/{list}',
        [CustomerOrderController::class, 'addAllListItemsToLatestOrder'])->name('submit.add-items-to-order');
    Route::post('/order/add-single-item-to-order/{item}',
        [CustomerOrderController::class, 'addSingleListItemsToLatestOrder'])->name('order-list-item.add-to-order');
    Route::post('/order/draft-to-order/{order}',
        [CustomerOrderController::class, 'submitDraftAsOrder'])->name('draft.submit-as-order');
    Route::post('/approve-order/{order}', [CustomerOrderController::class, 'approveOrder'])->name('approve-order');
// Route::delete('/saved-order-list/delete/{list}', [CustomerOrderController::class, 'deleteSavedOrder'])->name('order-list.delete');
    Route::delete('/quote/delete/{quote}', [CustomerOrderController::class, 'deleteQuote'])->name('order.delete');
    Route::delete('/saved-order-list/delete/{item}/item',
        [CustomerOrderController::class, 'deleteSavedOrderItem'])->name('order-list-item.delete');
    Route::delete('/customer-profile-quotation-list-item/delete/{item}/item',
        [CustomerOrderController::class, 'deleteQuotationItem'])->name('customer-profile-quotation-list-item.delete');
});