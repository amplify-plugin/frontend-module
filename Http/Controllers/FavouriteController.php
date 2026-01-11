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
use Illuminate\Support\Facades\Session;

class FavouriteController extends Controller
{
    use HasDynamicPage;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!in_array(true, [customer(true)->can('favorites.use-global-list'), customer(true)->can('favorites.manage-personal-list')])) {
            abort(403);
        }
        if ($request->wantsJson()) {
            $lists = [
                config('amplify.constant.favorite_list_type.global') => OrderList::select('id', 'name')->whereCustomerId(customer()->getKey())->whereListType('global')->get(),
                config('amplify.constant.favorite_list_type.personal') => OrderList::select('id', 'name')->whereCustomerId(customer()->getKey())->whereContactId(customer(true)->getKey())->whereListType('personal')->get(),
                config('amplify.constant.favorite_list_type.quick-list') => OrderList::select('id', 'name')->whereCustomerId(customer()->getKey())->whereContactId(customer(true)->getKey())->whereListType('quick-list')->get(),
            ];

            if (!config('amplify.basic.enable_quick_list', true)) {
                unset($lists[config('amplify.constant.favorite_list_type.quick-list')]);
            }

            return response()->json(['data' => $lists, 'create_new_list' => customer(true)->can('favorites.manage-personal-list')]);
        } else {
            $this->loadPageByType('favourite');

            return $this->render();
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FavoriteListRequest $request): JsonResponse
    {
        try {

            $inputs = $request->validated();

            $orderList = OrderList::firstOrCreate(
                [
                    'id' => $inputs['list_id']
                ],
                [
                    'name' => $inputs['list_name'],
                    'list_type' => $inputs['list_type'] ?? 'personal',
                    'description' => $inputs['list_desc'] ?? '',
                    'contact_id' => customer(true)->getKey(),
                    'customer_id' => customer()->getKey(),
                ]
            );

            if ($orderList instanceof OrderList) {
                $orderList->orderListItems()->createMany($this->processOrderListItems($request));
                $orderList->touch();
            }

            $header = $request->input('is_shopping_list', 0) ? 'Shopping List' : 'Favourites List';

            return response()->json([
                'type' => 'success',
                'status' => true,
                'message' => ($inputs['list_id'] != null) ? "New item added to {$header}" : "{$header} created and Added this item(s).",
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
    public function show(string $id)
    {
        if (!in_array(true, [customer(true)->can('favorites.manage-global-list'), customer(true)->can('favorites.manage-personal-list')])) {
            abort(403);
        }
        $this->loadPageByType('favourite_detail');

        return $this->render();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @throws \ErrorException
     */
    public function edit(string $id): string
    {
        if (!in_array(true, [customer(true)->can('favorites.manage-personal-list'), customer(true)->can('favorites.manage-personal-list')])) {
            abort(403);
        }
        $this->loadPageByType('favourite_edit');

        return $this->render();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderListRequest $request, string $id): RedirectResponse
    {
        if (!in_array(true, [customer(true)->can('favorites.manage-personal-list'), customer(true)->can('favorites.manage-personal-list')])) {
            abort(403);
        }
        $orderList = OrderList::findOrFail($id);

        $orderList->fill($request->validated());

        if (!$orderList->save()) {
            session()->flash('error', 'Favorite List updated field.');

            return redirect()->back();
        }
        session()->flash('success', 'Favorite List updated successfully.');

        return redirect()->route('frontend.favourites.index');
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
     * @param OrderList $favourite
     */
    public function destroy(Request $request, $favouriteItem): RedirectResponse
    {
        if (!in_array(true, [customer(true)->can('favorites.manage-global-list'), customer(true)->can('favorites.manage-personal-list')])) {
            abort(403);
        }
        try {
            $item = OrderList::find($favouriteItem);
            $item->delete();
            Session::flash('success', 'You have successfully deleted the Favorite List!');
        } catch (\Exception $e) {
            Session::flash('error', 'Sorry! Something went wrong...');
        }

        return back();
    }

    /**
     * This will delete the saved order list item
     *
     * @param OrderListItem $favourite
     */
    public function destroyOrderListItem(Request $request, $favouriteItem): RedirectResponse
    {
        if (!in_array(true, [customer(true)->can('favorites.manage-global-list'), customer(true)->can('favorites.manage-personal-list')])) {
            abort(403);
        }

        try {
            $item = OrderListItem::find($favouriteItem);
            $item->delete();
            Session::flash('success', 'You have successfully deleted the Favorite List Item!');
        } catch (\Exception $e) {
            Session::flash('error', 'Sorry! Something went wrong...');
        }

        return back();
    }
}
