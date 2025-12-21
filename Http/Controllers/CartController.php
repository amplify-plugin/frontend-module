<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Events\CartUpdated;
use Amplify\Frontend\Http\Requests\AddToCartRequest;
use Amplify\Frontend\Http\Resources\CartResource;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Cart;
use Amplify\System\Backend\Models\CartItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    use HasDynamicPage;

    public function __construct()
    {
        if (!config('amplify.frontend.guest_add_to_cart')) {
            $this->middleware('customers');
        }
    }

    /**
     * @throws \ErrorException|\Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {

            $cart = \getCart();

            if ($cart instanceof Cart) {

                $cart->load(['cartItems', 'cartItems.product.manufacturerRelation']);

                return new CartResource($cart);
            }

            return new CartResource(null);
        }

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

            \event(new CartUpdated($cart));

            return $this->apiResponse(true, __('Product(s) added to cart successfully.'));

        } catch (\Exception $exception) {

            DB::rollBack();

            Log::debug($exception);

            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }

    public function remove(string $cartItemId)
    {
        try {

            $cart = getCart();

            $products = $cartItemId;

            if ($cart->cartItems()->whereIn('id', Arr::wrap($products))->delete()) {

                \event(new CartUpdated($cart));

                return response()->json(['message' => __('Your current cart items are removed'), 'success' => true], 200);
            }

            return response()->json(['message' => __('Failed to clear the current cart items.'), 'success' => false], 500);
        } catch (\Exception $exception) {

            Log::error($exception);

            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }

    public function update(CartItem $cartItem, Request $request)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();

        try {

            $product = $cartItem->toArray();

            $product['qty'] = $request->input('quantity');

            $data = app(Pipeline::class)
                ->send(['meta' => [], 'items' => [$product]])
                ->through(config('amplify.add_to_cart_pipeline', []))
                ->thenReturn();

            $cartItem->update(array_shift($data['items']));

            DB::commit();

            \event(new CartUpdated($cartItem->cart));

            return $this->apiResponse(true, __('Cart updated successfully.'));

        } catch (\Exception $exception) {

            DB::rollBack();

            Log::error($exception);

            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }

    public function destroy(Cart $cart)
    {
        try {
            if ($cart->cartItems()->delete()) {

                \event(new CartUpdated($cart));

                return $this->apiResponse(true, __('Your current cart items are removed.'));

            }

            return $this->apiResponse(false, __('Failed to clear the current cart items.'), 500);

        } catch (\Exception $exception) {

            Log::error($exception);

            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }
}
