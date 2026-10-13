<?php

namespace Amplify\Frontend\Http\Controllers\Checkout;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Events\CartUpdated;
use Amplify\Frontend\Http\Requests\CheckoutRequest;
use Amplify\Frontend\Services\RecentlyViewedProductService;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\CustomerOrder;
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
            /**
             * @var CheckoutContext $context
             */
            $context = app(Pipeline::class)
                ->send(new CheckoutContext($request->validated()))
                ->through(config('amplify.checkout_pipeline', []))
                ->then(function (CheckoutContext $context) {
                    return $context;
                });

            $order = $context->resolved['order']->fresh();

            $order->load(['orderLines', 'orderLines.warehouse', 'orderLines.product']);

            $orderInfo = $order->toArray();

            $orderInfo['credentials'] = $context->payload['payment']['credentials'];

            if ($order->order_type == CustomerOrder::IS_ORDER_TYPE) {
                $response = ErpApi::createOrder($orderInfo);
            }

            if ($order->order_type == CustomerOrder::IS_RFQ_TYPE) {
                $response = ErpApi::createQuotation($orderInfo);
            }

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
