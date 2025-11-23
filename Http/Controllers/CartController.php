<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Http\Requests\AddToCartRequest;
use Amplify\Frontend\Http\Resources\CartResource;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Cart;
use Amplify\System\Backend\Models\CartItem;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Jobs\CartPricingSyncJob;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    use HasDynamicPage;

    public function __construct()
    {
        if (! config('amplify.frontend.guest_add_to_cart')) {
            $this->middleware('customers');
        }
    }

    /**
     * @throws \ErrorException
     */
    public function index()
    {
        $this->loadPageByType('cart');

        return $this->render();
    }

    public function store(AddToCartRequest $request)
    {
        DB::beginTransaction();

        try {

            $data = app(Pipeline::class)
                ->send(['meta' => [], 'items' => $request->input('products')])
                ->through(config('amplify.add_to_cart_pipeline', []))
                ->thenReturn();

            $cart = getOrCreateCart();

            if (!$cart->wasRecentlyCreated) {
                $cart->cartItems()->whereIn('product_id', collect($data['items'])->pluck('product_id')->toArray())->delete();
            }

            $cart->cartItems()->createMany($data['items']);

            DB::commit();

            CartPricingSyncJob::dispatch($cart->getkey());

            return $this->apiResponse(true, __('Product(s) added to cart successfully.'));

        } catch (\Exception $exception) {

            DB::rollBack();

            Log::debug($exception);

            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }

    public function quickOrderAddToOrder(AddToCartRequest $request)
    {
        try {
            if (! ErpApi::enabled()) {
                return response()->json(['success' => false, 'message' => 'ERP service not enabled.'], 500);
            }

            $isAddedToCart = false;
            $cart = Cart::firstOrCreate(
                ['contact_id' => customer(true)->id, 'status' => 1]
            );

            foreach ($request->products as $key => $product) {
                $product_id = $product['product_id'];
                $product_code = $product['product_code'];
                $warehouse_code = $product['product_warehouse_code'] ?? ErpApi::getCustomerDetail()->DefaultWarehouse;
                $product_qty = $product['qty'];
                $product_back_order = $product['product_back_order'] ?? null;
                $customer_back_order_code = ErpApi::getCustomerDetail()->BackorderCode ?? 'N';
                $is_back_order_enabled = $customer_back_order_code == 'Y' && $product_back_order == 'Y';
                $dbProduct = Product::with('productImage')->where('product_code', $product_code)->first();
                $ERPInfo = $this->getERPInfo($product_code, $warehouse_code)->first();

                if (
                    (bool) $ERPInfo &&
                    (bool) $dbProduct &&
                    $dbProduct->exists() &&
                    $product_qty > 0 &&
                    ($is_back_order_enabled || $ERPInfo->QuantityAvailable >= $product_qty)
                ) {
                    if (! $isAddedToCart) {
                        $isAddedToCart = true;
                    }

                    $product_price = \ErpApi::enabled() ? $ERPInfo->Price : $dbProduct->selling_price;
                    $cartItem = $cart->cartItems()->firstWhere([
                        'product_id' => $product_id,
                        'product_code' => $product_code,
                        'product_warehouse_code' => $warehouse_code,
                    ]);

                    if ((bool) $cartItem) {
                        if ($is_back_order_enabled || $ERPInfo->QuantityAvailable >= $cartItem->quantity + $product_qty) {
                            $cartItem->increment('quantity', $product_qty);
                        }
                    } else {
                        $cart->cartItems()->create([
                            'product_id' => $product_id,
                            'product_code' => $product_code,
                            'product_warehouse_code' => $warehouse_code,
                            'quantity' => $product_qty,
                            'unitprice' => $product_price,
                            'address_id' => customer(true)->customer_address_id,
                            'product_name' => $dbProduct->product_name,
                            'product_back_order' => $product_back_order,
                            'product_image' => $dbProduct->productImage->main ?? null,
                        ]);
                    }
                }
            }

            if ($isAddedToCart) {
                $isSuccess = true;
                $message = 'Added to the order successfully';
                $status = 200;
            } else {
                $isSuccess = false;
                $message = 'Product not available, Try again later.';
                $status = 500;
            }

            return response()->json([
                'success' => $isSuccess,
                'message' => $message,
            ], $status);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function getCarts()
    {
        if (customer_check()) {

            $cart = getCart();

            if ($cart instanceof Cart) {

                $cart->load('cartItems');

                return new CartResource($cart);
            }
        }

        return ['data' => ['products' => [], 'total_price' => 0]];
    }

    public function removeCart(CartItem $cartItem)
    {
        if (customer_check()) {
            $isOwnCart = $cartItem->whereHas('cart', function ($query) {
                $query->where('contact_id', customer(true)->id);
            })->exists();

            if ($isOwnCart) {
                $cartItem->delete();

                return response()->json([
                    'success' => true,
                    'total_price' => getCart()->total,
                    'message' => 'Successfully deleted cart item.',
                ], 200);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
        ], 200);
    }

    public function updateCart(CartItem $cartItem, Request $request)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:1',
            'product_warehouse_code' => 'required',
        ]);

        if (customer_check()) {
            $ERPInfo = $this->getERPInfo($cartItem->product_code, $request->product_warehouse_code)->first();
            $isOwnCart = $cartItem->whereHas('cart', function ($query) {
                $query->where('contact_id', customer(true)->id);
            })->exists();

            if ($isOwnCart && $ERPInfo->QuantityAvailable >= $request->quantity) {
                $cartItem->update([
                    'quantity' => $request->quantity,
                    'product_warehouse_code' => $request->product_warehouse_code,
                ]);

                return response()->json([
                    'success' => true,
                    'total_price' => getCart()->total,
                    'message' => 'Successfully updated cart item.',
                ], 200);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
        ], 200);
    }

    public function removeCarts()
    {
        if (customer_check()) {
            $cart = getCart();
            if ($cart instanceof Cart) {
                $cart->cartItems()->delete();
                $cart->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Cart removed successfully.',
                    'redirect' => url('/shop'),
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Cart is empty.',
                    'redirect' => url('/shop'),
                ], 200);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Couldn\'t removed cart successfully.',
        ], 200);
    }

    private function getERPInfo(string $productCode, string $warehouseString)
    {
        return \ErpApi::getProductPriceAvailability([
            'items' => [
                ['item' => $productCode],
            ],
            'warehouse' => $warehouseString,
        ]);
    }
}
