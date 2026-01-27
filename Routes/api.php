<?php

use Amplify\System\Backend\Http\Controllers\CustomerOrderController;
use Amplify\System\Backend\Http\Controllers\ECommerceGatewayController;
use Amplify\System\Backend\Http\Controllers\PunchOutController;
use Illuminate\Support\Facades\Route;

/*
 * Customer Api Routes.
 */
Route::group(['prefix' => 'api', 'as' => 'api.'], function () {
    Route::get('/list-types', function (Request $request) {
        try {
            $listTypes = config('amplify.constant.favorite_list_type');
            if (!config('amplify.basic.enable_quick_list', true)) {
                unset($listTypes['quick-list']);
            }
            return response()->json(['data' => $listTypes, 'message' => '']);
        } catch (Exception $e) {
            return response()->json(['data' => [], 'message' => $e->getMessage()]);
        }
    });

    // Route::resource('/contacts', ContactResourceController::class);
    Route::get('/product-details/rbs/{p_code}/{w_code?}', [ECommerceGatewayController::class, 'getPriceAvailability']);
    Route::post('/cenpos-token', [CustomerOrderController::class, 'getCenposToken']);
    Route::get('punchout/login', [PunchOutController::class, 'login'])->name('punchout.login');
});