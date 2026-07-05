<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ProductCompareController extends Controller
{
    use HasDynamicPage;

    private array $items = [];

    private string $sessionKey = 'compareProducts';

    private int $maxItems = 4;

    public function __invoke(Request $request): JsonResponse
    {

        $this->items = request()->session()->get($this->sessionKey, []);

        try {

            hasAccessOrFail('product-compare.manage');

            $validator = Validator::make($request->all(), [
                'product' => 'nullable|integer|exists:products,id',
                'action' => 'string|in:add,remove,clear',
            ]);

            if ($validator->failed()) {
                return $this->apiResponse(false, $validator->errors()->first(), 500);
            }

            $action = $request->input('action');

            $message = '';

            $data = [];

            switch ($action) {

                case 'clear':
                {

                    $this->items = [];

                    $message = 'Your product comparison list have been cleared.';

                    break;
                }

                case 'remove':
                {

                    $this->items = array_filter($this->items, fn($item) => $item['id'] != $request->input('product'));

                    $message = 'Item removed from product comparison list.';

                    break;
                }

                case 'add':
                {

                    $this->items = array_filter($this->items, fn($item) => $item['id'] != $request->input('product'));

                    if (count($this->items) >= $this->maxItems) {
                        return $this->apiResponse(false, "You can only compare up to <b>{$this->maxItems}</b> products at a time. <br>Please remove an existing product before adding.", 500);
                    }

                    $product = Product::findOrFail($request->input('product'));

                    $this->items[] = [
                        'id' => $product->id,
                        'code' => $product->product_code,
                        'name' => $product->product_name,
                        'image' => $product->productImage?->main ?? config('amplify.frontend.fallback_image_path'),
                        'href' => frontendSingleProductURL($product),
                    ];

                    $message = "<strong>{$product->product_name}</strong> added to your product comparison list.";

                    break;
                }

                default:
                {
                    $message = 'Your product comparison list';

                    $data = [
                        'data' => [
                            'count' => count($this->items),
                            'html' => view('widget::product.comparison.dropdown', [
                                'items' => $this->items,
                            ])->render()
                        ]
                    ];
                }
            }

            $request->session()->put($this->sessionKey, $this->items);

            return $this->apiResponse(true, $message, 200, $data);

        } catch (\Throwable $exception) {
            return $this->apiResponse(false, $exception->getMessage(), 500);
        }
    }
}
