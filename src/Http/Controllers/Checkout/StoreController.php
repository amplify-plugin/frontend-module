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
use Illuminate\Support\Facades\DB;
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
        try {

            $context = app(Pipeline::class)
                ->send(new CheckoutContext($request->validated()))
                ->through(config('amplify.checkout_pipeline', []))
                ->then(function (CheckoutContext $context) {
                    $response = DB::transaction(function () use (&$context) {
                        $order = $context->resolved['order'];
                        $lines = $context->resolved['items'] ?? [];
                        $notes = $context->resolved['note'] ?? [];

                        if ($order->save()) {
                            if ($order->orderLines()->saveMany($lines) && $order->orderNotes()->saveMany($notes)) {
                                $context->resolved['order'] = $order->fresh();
                                return $context;
                            }
                        }

                        return null;
                    });
                    return $context;
                });


            return $this->apiResponse(true, __('Product(s) added to cart successfully.'), 200, [
                'data' => [
                    'total' => count($context->payload['items']),
                    'items' => array_values($context->payload['items'])
                ]
            ]);

        } catch (\Exception $exception) {

            Log::debug($exception);

            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }
}
