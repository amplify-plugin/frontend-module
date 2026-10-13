<?php

namespace Amplify\Frontend\Http\Controllers\Checkout;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Http\Requests\CheckoutRequest;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\CustomerOrder;
use Amplify\System\Contexts\CheckoutContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controller;
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
            $order->load(['orderLines', 'orderLines.warehouse', 'orderLines.product', 'orderNotes']);
            $orderInfo = $order->toArray();
            $orderInfo['credentials'] = $context->payload['payment']['credentials'];

            $response = null;

            if ($order->order_type == CustomerOrder::IS_ORDER_TYPE) {
                $response = ErpApi::createOrder($orderInfo);
            }

            if ($order->order_type == CustomerOrder::IS_RFQ_TYPE) {
                $response = ErpApi::createQuotation($orderInfo);
            }

            $order->refresh();

            $success = empty($response->Message);

            $erpLog = $order->erp_log ?? [];

            if (!$success) {
                $erpLog['error'] = $response->Message;
            }

            CustomerOrder::query()
                ->where('id', $order->id)
                ->update([
                    'order_status' => $success ? 'Approved' : 'Rejected',
                    'erp_order_id' => $response->OrderNumber,
                    'erp_log' => $erpLog,
                ]);

            $orderType = $context->payload['checkout']['type'];

            if (!$success) {
                return $this->apiResponse(false, "Your {$orderType} submission failed. Please review given errors", 400, [
                    'redirect_to' => '',
                    'errors' => explode(" | ", $response->Message),
                    'items' => array_values($context->payload['items']),
                    'order_number' => null,
                ]);
            }

            return $this->apiResponse(true, "Thank you for your {$orderType}. Your {$orderType} number is {$response->OrderNumber}.", 200, [
                'redirect_to' => match ($orderType) {
                    'order' => route('frontend.orders.show', [$response->OrderNumber, 'suffix' => $response->OrderSuffix]),
                    'quotation' => route('frontend.quotations.show', ['quotation' => $response->OrderNumber, 'suffix' => $response->OrderSuffix]),
                    'draft' => route('frontend.drafts.show', ['draft' => $order->id]),
                    default => route('frontend.orders.index'),
                },
                'items' => array_values($context->payload['items']),
                'order_number' => $response->OrderNumber ?? null,
            ]);

        } catch (\Exception $exception) {

            Log::debug($exception);

            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }
}
