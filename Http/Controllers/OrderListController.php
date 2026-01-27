<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Http\Requests\FavoriteListRequest;
use Amplify\Frontend\Http\Requests\UpdateOrderListRequest;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\CartItem;
use Amplify\System\Backend\Models\OrderList;
use Amplify\System\Backend\Models\OrderListItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OrderListController extends Controller
{
    use HasDynamicPage;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!in_array(true, [customer(true)->can('favorites.use-global-list'), customer(true)->can('favorites.manage-personal-list')])) {
            abort(403, 'You don\'t have permission to use this feature');
        }

        if ($request->wantsJson()) {
            try {
                $lists = OrderList::select('id', 'name', 'list_type', 'contact_id', 'customer_id')
                    ->whereCustomerId(customer()->getKey())
                    ->where(function ($query) {
                        $query->whereNull('contact_id')
                            ->orWhere('contact_id', customer(true)->getKey());
                    })
                    ->when(!config('amplify.basic.enable_quick_list', true), function ($query) {
                        $query->where('list_type', '!=', 'quick-list');
                    })
                    ->get()
                    ->map(function ($item) {
                        $item->product_ids = $item->orderListItems?->pluck('product_id')->toArray() ?? [];
                        $item->list_type = $item->list_type_label;
                        unset($item->orderListItems);
                        return $item;
                    })
                    ->toArray();

                return $this->apiResponse(true, '', 200, ['data' => $lists]);
            } catch (\Exception $e) {
                return $this->apiResponse(false, $e->getMessage(), 500);
            }
        } else {
            $this->loadPageByType('order_list');

            return $this->render();
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FavoriteListRequest $request): JsonResponse
    {
        try {

            $inputs = $request->validated();

            $listType = $inputs['list_type'] ?? 'personal';

            $orderList = OrderList::firstOrCreate(
                [
                    'id' => $inputs['list_id']
                ],
                [
                    'name' => $inputs['list_name'] ?? '',
                    'list_type' => $listType,
                    'description' => $inputs['list_desc'] ?? '',
                    'contact_id' => $listType == 'personal' ? customer(true)->getKey() : null,
                    'customer_id' => customer()->getKey(),
                ]
            );

            if ($orderList instanceof OrderList) {
                $orderList->orderListItems()->createMany($this->processOrderListItems($request));
                $orderList->touch();
            }

            $header = $request->input('title', 'Order List');

            return response()->json([
                'type' => 'success',
                'status' => true,
                'message' => ($inputs['list_id'] != null) ? "New item added to " . Str::lower($header) : "{$header} created and Added current item(s).",
            ]);

        } catch (\Exception $exception) {
            return response()->json([
                'type' => 'error',
                'status' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    private function processOrderListItems(FavoriteListRequest $request): array
    {
        switch ($request->input('type')) {
            case 'cart' :
            {
                return CartItem::select('product_id', 'quantity as qty')
                    ->where('cart_id', '=', $request->input('cart_id'))->get()
                    ->map(fn($cartItem) => $cartItem->toArray())
                    ->toArray();
            }
            case 'products':
            {
                return array_map(
                    fn($i) => ['product_id' => $i['product_id'], 'qty' => $i['qty']],
                    $request->input('products', [])
                );
            }
            //waiting for new features
            default :
            {
                return [[
                    'product_id' => $request->input('product_id'),
                    'qty' => $request->input('product_qty', 1),
                ]];
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request)
    {
        if (!in_array(true, [customer(true)->can('favorites.manage-global-list'), customer(true)->can('favorites.manage-personal-list')])) {
            abort(403);
        }

        $orderList = OrderList::find($id);

        if (!$orderList) {
            abort(404, 'Not Found');
        }

        \store()->orderListModel = $orderList;

        if ($request->wantsJson()) {
            return $this->apiResponse(true, '', 200, ['data' => store('orderListModel', [])->makeHidden(['contact_id', 'customer_id', 'created_at', 'updated_at'])]);
        }

        $this->loadPageByType('order_list_detail');

        return $this->render();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderListRequest $request, string $id): JsonResponse
    {
        if (!in_array(true, [customer(true)->can('favorites.manage-personal-list'), customer(true)->can('favorites.manage-personal-list')])) {
            abort(403, 'You don\'t have permission to use this feature');
        }

        $orderList = OrderList::findOrFail($id);

        $title = $request->input('title', 'Order List');

        $orderList->fill($request->validated());

        if (!$orderList->save()) {
            return $this->apiResponse(false, 'Unable to update ' . \Str::lower($title), 500);
        }

        return $this->apiResponse(true, "{$title} updated successfully.");
    }

    /**
     * Update the specified resource in storage.
     */
    public function syncProduct(Request $request, OrderList $orderList)
    {
        try {
            $action = $request->input('action', 'add');

            $products = Arr::wrap($request->input('product'));

            if ($action == 'remove') {
                OrderListItem::whereIn('product_id', $products)->get()->each(function ($item) {
                    if (!$item->delete()) {
                        throw new \Exception('Failed to remove item favorite list');
                    }
                });

                return response()->json([
                    'type' => 'warning',
                    'status' => true,
                    'message' => 'Item removed from favorite list.',
                ]);
            }

            if ($action == 'add') {
                $entries = [];
                foreach ($products as $product) {
                    $entries[] = new OrderListItem([
                        'product_id' => $product,
                        'qty' => 1,
                    ]);
                }

                if ($orderList->orderListItems()->createMany($entries)) {
                    return response()->json([
                        'type' => 'success',
                        'status' => true,
                        'message' => 'Product(s) Added to favorite list.',
                    ]);
                }
            }

        } catch (\Exception $exception) {
            return response()->json(['type' => 'error',
                'status' => false,
                'message' => $exception->getMessage()]);
        }
    }

    /**
     * This will delete the saved order list item
     *
     * @param $favourite
     * @return JsonResponse
     */
    public function destroy($favourite): JsonResponse
    {
        if (!in_array(true, [customer(true)->can('favorites.manage-global-list'), customer(true)->can('favorites.manage-personal-list')])) {
            abort(403, 'Your are not allowed to delete favorite list.');
        }

        try {

            $item = OrderList::find($favourite);

            if ($item->delete()) {
                return $this->apiResponse(true, __('You have successfully deleted the Favorite List.'));
            }

            return $this->apiResponse(false, __('Failed to delete the Favorite List.'), 500);

        } catch (\Exception $e) {
            return $this->apiResponse(false, $e->getMessage(), 500);
        }
    }

    /**
     * This will delete the saved order list item
     *
     * @param $favouriteItem
     * @return JsonResponse
     */
    public function destroyOrderListItem($favouriteItem): JsonResponse
    {
        if (!in_array(true, [customer(true)->can('favorites.manage-global-list'), customer(true)->can('favorites.manage-personal-list')])) {
            abort(403, 'Your are not allowed to delete favorite list item.');
        }

        try {
            $item = OrderListItem::find($favouriteItem);
            if ($item->delete()) {
                return $this->apiResponse(true, 'Item removed from favorite list.');
            }
            return $this->apiResponse(false, 'Failed to remove item from favorite list.');
        } catch (\Exception $e) {
            return $this->apiResponse(false, 'Sorry! Something went wrong...', 500);
        }
    }
}
