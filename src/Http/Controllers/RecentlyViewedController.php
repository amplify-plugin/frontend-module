<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Http\Requests\RecentProductRequest;
use Amplify\Frontend\Services\RecentlyViewedProductService;
use Amplify\Frontend\Traits\HasDynamicPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class RecentlyViewedController extends Controller
{
    use HasDynamicPage;

    public function __construct(
        protected RecentlyViewedProductService $service,
    ) {}

    public function index(Request $request): string
    {
        abort_unless($this->service->isEnabled(), 404);

        if (customer_check()) {
            abort_unless(customer(true)->can('shop.browse'), 403);
        }

        $this->loadPageByType('recently_viewed');

        return $this->render();
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($this->service->isEnabled(), 404);

        try {
            if (customer_check()) {
                $validator = Validator::make($request->all(), [
                    'product_id' => 'required|integer|exists:products,id',
                ]);

                if ($validator->fails()) {
                    return $this->apiResponse(false, $validator->errors()->first(), 422);
                }

                $this->service->record((int) $request->input('product_id'), customer(true));

                return $this->apiResponse(true, __('Product added to recently viewed.'));
            }

            return $this->apiResponse(true, __('Guest recently viewed is stored in the browser.'));
        } catch (\Throwable $exception) {
            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }

    public function products(RecentProductRequest $request): JsonResponse
    {
        abort_unless($this->service->isEnabled(), 404);

        try {
            $productIds = customer_check()
                ? $this->service->getProductIds(customer(true), (int) ($request->input('products_limit') ?: $this->service->maxItems()))
                : $this->service->sanitizeProductIds($request->input('product_ids', []));

            $products = $this->service->loadProducts(
                array_slice($productIds, 0, (int) ($request->input('products_limit') ?: $this->service->maxItems())),
            );

            if ($products->isEmpty()) {
                return $this->apiResponse(true, '', 200, [
                    'count' => 0,
                    'html' => '',
                ]);
            }

            $layout = $request->input('layout', 'card');

            return $this->apiResponse(true, '', 200, [
                'count' => $products->count(),
                'html' => view('widget::product.recently-viewed.items', [
                    'products' => $products,
                    'layout' => $layout,
                    'showCartBtn' => $request->boolean('show_cart_btn', true),
                    'cartButtonLabel' => $request->input('cart_button_label', 'Add To Cart'),
                    'detailButtonLabel' => $request->input('detail_button_label', 'View Details'),
                    'showPrice' => $request->boolean('show_price', true),
                    'showGuestPrice' => $request->boolean('show_guest_price', false),
                    'showTopDiscountBadge' => $request->boolean('show_top_discount_badge', false),
                    'showOrderList' => $request->boolean('show_order_list', false),
                    'orderListLabel' => $request->input('order_list_label', 'Order List'),
                    'displayProductCode' => $request->boolean('display_product_code', true),
                    'displayShortDescription' => $request->boolean('display_short_description', false),
                    'displayManufacturer' => $request->boolean('display_manufacturer', false),
                    'allowRemove' => $request->boolean('allow_remove', false),
                ])->render(),
            ]);
        } catch (\Throwable $exception) {
            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }

    public function merge(Request $request): JsonResponse
    {
        abort_unless($this->service->isEnabled(), 404);
        abort_unless(customer_check(), 401);

        try {
            $validator = Validator::make($request->all(), [
                'product_ids' => 'required|array|max:' . $this->service->maxItems(),
                'product_ids.*' => 'integer',
            ]);

            if ($validator->fails()) {
                return $this->apiResponse(false, $validator->errors()->first(), 422);
            }

            $this->service->mergeGuestHistory(
                customer(true),
                $request->input('product_ids', []),
            );

            return $this->apiResponse(true, __('Recently viewed history merged successfully.'));
        } catch (\Throwable $exception) {
            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }

    public function destroy(int $product): JsonResponse
    {
        abort_unless($this->service->isEnabled(), 404);

        try {
            if (customer_check()) {
                $removed = $this->service->remove($product, customer(true));

                if (! $removed) {
                    return $this->apiResponse(false, __('Recently viewed item not found.'), 404);
                }

                return $this->apiResponse(true, __('Product removed from recently viewed.'));
            }

            return $this->apiResponse(true, __('Guest recently viewed is stored in the browser.'));
        } catch (\Throwable $exception) {
            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }

    public function clear(Request $request): JsonResponse
    {
        abort_unless($this->service->isEnabled(), 404);

        try {
            if (customer_check()) {
                $this->service->clear(customer(true));

                return $this->apiResponse(true, __('Recently viewed history cleared.'));
            }

            return $this->apiResponse(true, __('Guest recently viewed is stored in the browser.'));
        } catch (\Throwable $exception) {
            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }
}
