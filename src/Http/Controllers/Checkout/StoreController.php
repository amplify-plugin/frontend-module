<?php

namespace Amplify\Frontend\Http\Controllers\Checkout;

use Amplify\Frontend\Events\CartUpdated;
use Amplify\Frontend\Http\Requests\CheckoutRequest;
use Amplify\Frontend\Services\RecentlyViewedProductService;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Contexts\CheckoutContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    use HasDynamicPage;

    public function __construct()
    {
        if (!config('amplify.frontend.guest_checkout')) {

            $this->middleware('customers');
        }
    }

    public function __invoke(CheckoutRequest $request): JsonResponse
    {
        $context = app(Pipeline::class)
            ->send(new CheckoutContext($request->validated()))
            ->through(config('amplify.checkout_pipeline', []))
            ->then(function (CheckoutContext $context) {
//                foreach ($data['items'] as $index => $item) {
//                    $data['items'][$index]['error'] = isset($data['errors'][$index]) ? implode("\n", $data['errors'][$index]) : null;
//                }

                return $context;
            });

        dd($context);

        if (!empty($context['errors'])) {
            return $this->apiResponse(false, count($context['errors']) == 1
                ? Arr::first(Arr::flatten($context['errors']))
                : __('There are issue(s) appeared on your order (marked in red). Please correct before adding to the Cart.'), 400,
                ['errors' => $context['errors']]
            );
        }

        try {

//            $cart->cartItems()
//                ->whereIn('product_code', collect($data['items'])->pluck('product_code')->toArray())
//                ->delete();
//
//            $cart->cartItems()->createMany($data['items']);
//
//            \event(new CartUpdated($cart));
//
//            if (customer_check()) {
//                $productIds = collect($data['items'])->pluck('product_id')->filter()->all();
//                app(RecentlyViewedProductService::class)->markAddedToCart(customer(true), $productIds);
//            }

            return $this->apiResponse(true, __('Product(s) added to cart successfully.'), 200, [
                'data' => [
                    'total' => cart_count_badge($cart),
                    'items' => array_values($context['items'])
                ]
            ]);

        } catch (\Exception $exception) {

            Log::debug($exception);

            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }
}
