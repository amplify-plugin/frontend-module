<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Http\Requests\QuickListRequest;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\OrderList;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class QuickListController extends Controller
{
    use HasDynamicPage;

    public function __construct()
    {
        if (! app()->runningInConsole()) {
            if (! config('amplify.basic.enable_quick_list', true)) {
                abort(401, 'Quick List Feature is disabled. Contact Administrator.');
            }
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @throws \ErrorException
     */
    public function index(Request $request): string
    {

        if (! customer(true)->can('favorites.manage-personal-list')) {
            abort(403);
        }

        $this->loadPageByType('quicklist');

        return $this->render();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): string
    {
        if (! customer(true)->can('favorites.manage-personal-list')) {
            abort(403);
        }

        store()->favouriteModel = new OrderList;

        $this->loadPageByType('quicklist_create');

        push_css('https://unpkg.com/vue-multiselect@2.1.6/dist/vue-multiselect.min.css', 'custom-style');

        return $this->render();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(QuickListRequest $request): JsonResponse
    {
        if (! customer(true)->can('favorites.manage-personal-list')) {
            abort(403);
        }

        try {
            $inputs = $request->all();

            $orderList = new OrderList([
                'name' => $inputs['name'] ?? null,
                'list_type' => 'quick-list',
                'description' => $inputs['description'] ?? null,
                'contact_id' => $inputs['contact_id'] ?? null,
                'customer_id' => customer(true)->customer_id,
            ]);

            $orderListItems = $inputs['order_list_items'];

            $orderList->save();

            $orderList->orderListItems()->createMany($orderListItems);

            return response()->json([
                'status' => true,
                'message' => 'New quick list created successfully.',
                'redirect_url' => route('frontend.quick-lists.index'),
            ]);

        } catch (\Exception $exception) {
            return response()->json([
                'redirect_url' => route('frontend.quick-lists.index'),
                'status' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderList $quick_list): string
    {

        $this->loadPageByType('quicklist_detail');

        $quick_list->load('orderListItems');

        store()->favouriteModel = $quick_list;

        return $this->render();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @throws \ErrorException
     */
    public function edit(OrderList $quick_list): string
    {
        if (! customer(true)->can('favorites.manage-personal-list')) {
            abort(403);
        }

        $quick_list->load('orderListItems');

        store()->favouriteModel = $quick_list;

        $this->loadPageByType('quicklist_edit');

        push_css('https://unpkg.com/vue-multiselect@2.1.6/dist/vue-multiselect.min.css', 'custom-style');

        return $this->render();
    }

    /**
     * This method updates a single address stored in the database
     */
    public function update(OrderList $quick_list, QuickListRequest $request): JsonResponse
    {
        $jsonResponse = [
            'status' => false,
            'message' => 'Something went wrong. please try again later.',
            'redirect_url' => route('frontend.quick-lists.index'),
        ];

        try {

            $quick_list->name = $request->input('name', $quick_list->name);
            $quick_list->description = $request->input('description', $quick_list->description);

            $quick_list->orderListItems->each(function ($item) {
                $item->delete();
            });

            $orderListItems = $request->input('order_list_items.*');

            if ($quick_list->save() && $quick_list->orderListItems()->createMany($orderListItems)) {
                $jsonResponse['status'] = true;
                $jsonResponse['message'] = 'You have successfully updated the quick list!';
            }

        } catch (\Exception $exception) {
            Log::error($exception);
            $jsonResponse['status'] = false;
            $jsonResponse['message'] = $exception->getMessage();
            $jsonResponse['redirect_url'] = null;
        }

        return response()->json($jsonResponse);

    }

    /**
     * This will delete the saved order list
     */
    public function destroy(OrderList $quicklist): RedirectResponse
    {
        try {

            $quicklist->orderListItems()->each(function ($item) {
                $item->delete();
            });

            $quicklist->delete();

            Session::flash('success', 'You have successfully deleted the Quick List!');
        } catch (\Exception $e) {
            Session::flash('error', 'Sorry! Something went wrong...');
        }

        return back();
    }
}
