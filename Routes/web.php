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
use Amplify\Frontend\Http\Controllers\ARSummaryController;
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
use Amplify\Frontend\Http\Controllers\CartController;
use Amplify\Frontend\Http\Controllers\CategoryIndexController;
use Amplify\Frontend\Http\Controllers\CheckoutController;
use Amplify\Frontend\Http\Controllers\ContactController;
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
use Amplify\Frontend\Http\Controllers\MessageController;
use Amplify\Frontend\Http\Controllers\MyProfileController;
use Amplify\Frontend\Http\Controllers\NewsletterSubscriptionController;
use Amplify\Frontend\Http\Controllers\OrderController;
use Amplify\Frontend\Http\Controllers\OrderStatusController;
use Amplify\Frontend\Http\Controllers\PastItemsController;
use Amplify\Frontend\Http\Controllers\ProductDetailController;
use Amplify\Frontend\Http\Controllers\ProductSearchController;
use Amplify\Frontend\Http\Controllers\QuickListController;
use Amplify\Frontend\Http\Controllers\QuotationController;
use Amplify\Frontend\Http\Controllers\RoleController;
use Amplify\Frontend\Http\Controllers\ShippingController;
use Amplify\Frontend\Http\Controllers\ShopSearchController;
use Amplify\System\Backend\Http\Controllers\SwitchAccountController;
use App\Http\Middleware\ContactForceShippingAddressSelection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

Route::get('admin', function () {
    return Redirect::to('admin/login');
});
Route::name('frontend.')->middleware(['web', ProtectAgainstSpam::class, ContactForceShippingAddressSelection::class])->group(function () {
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

    $shopRoutePrefix = config('amplify.basic.shop_page_prefix');
    Route::get("{$shopRoutePrefix}/product/{identifier}/{seo_path?}", ProductDetailController::class)->where(['identifier' => '([a-zA-Z0-9\-]+)', 'seo_path' => '(.*)'])->name('shop.show');
    Route::get("{$shopRoutePrefix}/{query?}", ShopSearchController::class)->where(['query' => '(.*)'])->name('shop.index');
    Route::get('quick-view/{id}/{seo_path?}', [ShopSearchController::class, 'getQuickView'])->name('shop.quickView');
    Route::get('warehouse-selection-view/{code}', [ShopSearchController::class, 'getWarehouseSelectionView'])->name('shop.warehouseSelectionView');
    Route::apiResource('carts', CartController::class);
    Route::get('checkout', CheckoutController::class)->name('checkout');
    Route::post('subscribe', NewsletterSubscriptionController::class)->name('subscribe');
    Route::get('faq/{faq-category-slug?}', FaqController::class)->name('faqs.show');
    Route::get('faq/{faq}/stats-count/{value}', [FaqController::class, 'statsCount'])->name('faqs.stats-count');
    Route::post('form-response/{form_code}', FormResponseAcceptController::class)->name('form-submit');
    Route::resource('campaigns', CampaignController::class)->only('index', 'show');
    Route::resource('events', EventController::class)->only('index', 'show');
    Route::get('brands/{query?}', BrandIndexController::class)->where(['query' => '(.*)'])->name('brands');
    Route::get('categories/{query?}', CategoryIndexController::class)->where(['query' => '(.*)'])->name('categories');
    Route::get('order-completed/{order}', [OrderController::class, 'completed'])->name('orders.completed');
    Route::post('validate/shipping-address', [ShippingController::class, 'validateAddress']);
    Route::post('/get/shipping/option', [ShippingController::class, 'options'])->name('shipping-options');

    if (config('amplify.basic.client_code') == 'SPI') {
        Route::get('Items/0/{identifier}/{seopath?}', function ($identifier, $seopath) {
            return redirect()->route('frontend.shop.show', ['identifier' => intval($identifier), 'seo_path' => $seopath]);
        })->where(['identifier' => '([0-9]+)', 'seopath' => '(.*)']);
    }

    /*
    |--------------------------------------------------------------------------
    | Without Authentication
    |--------------------------------------------------------------------------
    */
    Route::middleware('guest:customer')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Login & Logout
        |--------------------------------------------------------------------------
        */
        Route::get('login', [AuthenticatedSessionController::class, 'login'])
            ->name('login');

        Route::post('login', [AuthenticatedSessionController::class, 'attempt']);

        Route::post('logout', [AuthenticatedSessionController::class, 'logout'])
            ->name('logout')
            ->withoutMiddleware('guest:customer');

        /*
        |--------------------------------------------------------------------------
        | Registration
        |--------------------------------------------------------------------------
        */
        Route::get('registration', RegisteredUserController::class)
            ->name('registration');
        Route::post('registration/request-account',
            [RegisteredUserController::class, 'requestAccount'])
            ->name('registration.request-account');
        Route::post('registration/create-cash-customer',
            [RegisteredUserController::class, 'newRetailCustomer'])
            ->name('registration.create-cash-customer');

        /*
        |--------------------------------------------------------------------------
        | Forgot Password& Reset Password
        |--------------------------------------------------------------------------
        */
        Route::get('forgot-password', ForgotPasswordController::class)
            ->name('password.request');
        Route::post('forgot-password', [ForgotPasswordController::class, 'store'])
            ->name('password.email');
        Route::get('reset-password/{token}', [NewPasswordController::class, 'reset'])
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
    Route::group(['middleware' => 'customers'], function () {

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

        Route::get('account-summary', ARSummaryController::class)->name('account-summary');

        Route::post('my-profile/photo-update', [MyProfileController::class, 'photoUpdate'])->name('profile.photo-update');

        Route::resource('my-profile', MyProfileController::class)->only(['show', 'update'])->names('profile');

        Route::get('switch-account', [SwitchAccountController::class, 'index'])->name('switch-account.index');
        Route::put('switch-account', [SwitchAccountController::class, 'update'])->name('switch-account.update');

        Route::resource('contacts', ContactController::class);

        Route::resource('roles', RoleController::class);

        Route::resource('addresses', AddressController::class);
        Route::get('/addresses/default-address/{address}', [AddressController::class, 'setDefault'])
            ->name('addresses.default-address');

        Route::resource('invoices', InvoiceController::class)
            ->only('index', 'show');
        Route::get('document/{type}/{id}/download', [InvoiceController::class, 'download'])
            ->name('invoices.document.download');
        Route::get('tracking/{invoice}', [\Amplify\Frontend\Http\Controllers\InvoiceController::class, 'trackInvoice'])
            ->name('invoices.tracking.invoice');

        Route::resource('orders', OrderController::class)->only('index', 'show');
        Route::post('orders/{id}/approve', [OrderController::class, 'approve'])->name('orders.approve');
        Route::resource('drafts', DraftController::class)->only('index', 'show');

        Route::resource('quotations', QuotationController::class)
            ->only('index', 'show');

        Route::resource('favourites', FavouriteController::class)->where(['favourite' => '[\d]+']);
        Route::delete('favourites/{favourite}/item', [FavouriteController::class, 'destroyOrderListItem'])->name('favourites.destroy-item');
        Route::post('favourites/{favourite}/sync-product', [FavouriteController::class, 'syncProduct']);

        Route::get('fetch-products', ProductSearchController::class);

        Route::resource('quick-lists', QuickListController::class)
            ->where(['quick_list' => '[\d]+']);
        //        Route::post('/quicklist-item/{quicklist}', [QuickListController::class, 'update'])->name('frontend.quicklist.item.update');
        //        Route::delete('/quicklist-item/{quicklist}', [QuickListController::class, 'delete'])->name('frontend.quicklist.item.destroy');

        Route::post('shipping-address/create', [ShippingController::class, 'store']);
        Route::post('/ship-to-address-save', [ShippingController::class, 'saveShipToAddress'])
            ->name('ship-to-address.store');

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
        Route::post('customer-part-number', CustomerPartNumberController::class)
            ->name('customer-part-number.update');
    });

    Route::post('customer-verification', CustomerVerificationController::class)->name('contact-validation');
});
