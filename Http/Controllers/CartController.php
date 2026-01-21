<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\ErpApi\Wrappers\ProductPriceAvailability;
use Amplify\Frontend\Events\CartUpdated;
use Amplify\Frontend\Http\Requests\AddToCartRequest;
use Amplify\Frontend\Http\Resources\CartResource;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Enums\ProductAvailabilityEnum;
use Amplify\System\Backend\Http\Requests\OrderFileRequest;
use Amplify\System\Backend\Models\Cart;
use Amplify\System\Backend\Models\CartItem;
use Amplify\System\Backend\Models\DocumentType;
use Amplify\System\Backend\Models\Product;
use Amplify\System\Sayt\Classes\ItemRow;
use App\Http\Controllers\Controller;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

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
        if ($request->expectsJson()) {

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
        $cart = getCart();

        $payload = [
            'meta' => [],
            'errors' => [],
        ];

        $requestItems = $request->input('products');

        $productCodes = [];

        foreach ($requestItems as $i) {
            $productCodes[] = $i['product_code'];
        }

        $cartItems = $cart->cartItems->toArray();

        $cartItems = array_filter($cartItems, fn($item) => !in_array($item['product_code'], $productCodes));

        $payload['items'] = array_merge($requestItems, $cartItems);

        $data = app(Pipeline::class)
            ->send($payload)
            ->through(config('amplify.add_to_cart_pipeline', []))
            ->then(function ($data) {
                foreach ($data['items'] as $index => $item) {
                    $data['items'][$index]['error'] = isset($data['errors'][$index]) ? implode("\n", $data['errors'][$index]) : null;
                }
                return $data;
            });

        if (!empty($data['errors'])) {
            return $this->apiResponse(false, count($data['errors']) == 1
                ? Arr::first(Arr::flatten($data['errors']))
                : __('There are issue(s) appeared on your order (marked in red). Please correct before adding to the Cart.')
                , 400,
                ['errors' => $data['errors']]
            );
        }

        try {

            $cart->cartItems()
                ->whereIn('product_id', collect($data['items'])->pluck('product_id')->toArray())
                ->delete();

            $cart->cartItems()->createMany($data['items']);

            \event(new CartUpdated($cart));

            return $this->apiResponse(true, __('Product(s) added to cart successfully.'));

        } catch (\Exception $exception) {

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

        $payload = [
            'meta' => [],
            'errors' => [],
        ];

        $quantity = $request->input('quantity');

        $payload['items'] = CartItem::where('cart_id', '=', $cartItem->cart_id)
            ->get()
            ->map(function ($entry) use ($cartItem, $quantity) {
                $entry->quantity = $entry->getKey() == $cartItem->getKey()
                    ? $quantity
                    : $entry->quantity;

                return $entry;
            })
            ->toArray();

        $data = app(Pipeline::class)
            ->send($payload)
            ->through(config('amplify.add_to_cart_pipeline', []))
            ->then(function ($data) {
                foreach ($data['items'] as $index => $item) {
                    $data['items'][$index]['error'] = isset($data['errors'][$index]) ? implode("\n", $data['errors'][$index]) : null;
                }
                return $data;
            });


        if (!empty($data['errors'])) {
            $this->apiResponse(false, Arr::first(Arr::flatten($data['errors'])), 500, ['errors' => $data['errors']]);
        }

        try {

            $cart = $cartItem->cart;

            $cart->cartItems()->delete();

            $cart->cartItems()->createMany($data['items']);

            \event(new CartUpdated($cart));

            return $this->apiResponse(true, __('Cart updated successfully.'));

        } catch (\Exception $exception) {

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

    public function orderFile(OrderFileRequest $request): JsonResponse
    {
        $file = request()->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('public/quick_order/', $fileName);

        $fileExtension = strtoupper($file->getClientOriginalExtension());

        $readerType = match ($fileExtension) {
            'CSV' => \Maatwebsite\Excel\Excel::CSV,
            'XLS' => \Maatwebsite\Excel\Excel::XLS,
            'XLSX' => \Maatwebsite\Excel\Excel::XLSX,
            default => null
        };

        $fileData = Excel::toArray((object)[], $filePath, 'local', $readerType);

        $firstSheet = array_shift($fileData);

        if (empty($firstSheet) || count($firstSheet) == 1) {
            throw ValidationException::withMessages(['file' => 'The file is empty or only have headers.']);
        }

        // Remove Header
        if (isset($firstSheet[0][0])) {
            unset($firstSheet[0]);
        }

        if (count($firstSheet) > 100) {
            return $this->apiResponse(false, __('The file has more than 100 items.'), 500);
        }

        // Merge Quantity for Duplicate Product
        $items = [];
        $codes = [];

        foreach ($firstSheet as $row) {
            $code = strval($row[0]);
            if (empty($code)) {
                continue;
            }
            $codes[$code] = true;
            $items[] = ['code' => $code, 'quantity' => empty($row[1]) ? null : floatval($row[1])];
        }

        if (empty($items)) {
            return $this->apiResponse(false, __('There are no products in the file.'), 500);
        }

        $products = Product::whereIn('product_code', array_keys($codes))->get();

        array_walk($items, function (&$item) use (&$products) {
            $item['product'] = $products->where('product_code', $item['code'])->first() ?? new \stdClass();
        });


        if (customer_check() || config('amplify.basic.enable_guest_pricing')) {

            $warehouses = ErpApi::getWarehouses([['enabled', '=', true]]);

            $warehouseString = $warehouses->pluck('WarehouseNumber')->implode(',');

            $erpCustomer = ErpApi::getCustomerDetail();

            if (!Str::contains($warehouseString, $erpCustomer->DefaultWarehouse)) {
                $warehouseString = "$warehouseString,{$erpCustomer->DefaultWarehouse}";
            }

            $erpProductCodes = [];

            foreach ($products as $product) {
                $erpProductCodes[] = [
                    'item' => $product->product_code,
                    'uom' => $product->uom,
                    'qty' => !empty($items[$product->product_code]['quantity']) ? $items[$product->product_code]['quantity'] : $product->min_order_qty,
                ];
            }

            $erpProductDetails = ErpApi::getProductPriceAvailability([
                'items' => $erpProductCodes,
                'warehouse' => $warehouseString,
            ]);

            unset($erpProductCodes);

            if ($erpProductDetails->isEmpty()) {
                return $this->apiResponse(false, __(product_not_avail_message() . ' for all products'), 500);
            }

            $warehouse_codes = array_unique([$erpCustomer->DefaultWarehouse, customer()?->warehouse?->code, config('amplify.frontend.guest_checkout_warehouse')]);

            array_walk($items, function (&$item) use ($erpProductDetails, $warehouse_codes, $products) {

                $product = $item['product'];

                if ($product instanceof Product) {
                    $filteredPriceAvailability = $erpProductDetails
                        ->where('ItemNumber', $product->product_code)
                        ->whereIn('WarehouseID', $warehouse_codes);

                    $product->ERP = $filteredPriceAvailability->isNotEmpty()
                        ? $filteredPriceAvailability->first()
                        : $erpProductDetails->where('ItemNumber', $product->product_code)
                            ->first();

                    $product->avaliable = $erpProductDetails
                        ->where('ItemNumber', $product->product_code)
                        ->where('QuantityAvailable', '>=', 1)
                        ->count();

                    $product->total_quantity_available = $erpProductDetails->where('ItemNumber', $product->product_code)->sum('QuantityAvailable');

                } else {
                    $product->ERP = new ProductPriceAvailability([]);
                    $product->avaliable = 0;
                    $product->total_quantity_available = 0;
                }

                $product->min_order_qty = $product?->ERP?->MinOrderQuantity ?? $product?->min_order_qty ?? 1;
                $product->qty_interval = $product?->ERP?->QuantityInterval ?? $product?->qty_interval ?? 1;
                $product->allow_back_order = $product?->ERP?->AllowBackOrder ?? $product?->allow_back_order ?? false;
                $product->availability = $product?->availability ?? ProductAvailabilityEnum::Actual;
                $product->pricing = true;
                $product->quantity = $item['quantity'] ?? $product->min_order_qty;
                $product->assembled = ($product?->vendornum ?? '' == 3160);

                $item['product'] = $product;
            });
        }

        Storage::delete($filePath);

        return $this->apiResponse(true, 'Total ' . count($products) . ' items added', 200, [
            'html' => view('widget::quick-order.items', compact('items'))->render()
        ]);
    }

    public function removeCarts()
    {
        $cart = getCart();
        if ($cart instanceof Cart) {
            $cart->cartItems()->delete();

            return $this->apiResponse(true, __('Cart removed successfully.'));
        }

        return $this->apiResponse(false, __('Your current cart is empty.'));
    }
}
