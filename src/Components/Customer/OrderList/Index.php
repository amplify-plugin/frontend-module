<?php

namespace Amplify\Frontend\Components\Customer\OrderList;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Backend\Models\OrderList;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class Index
 */
class Index extends BaseComponent
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $nameLabel = '',
        public string $listTypeLabel = '',
        public string $descriptionLabel = '',
        public string $productCountLabel = '',
        public string $widgetTitle = 'Order List'
    ) {
        parent::__construct();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $request = request();

        $orderLists = OrderList::query()

            // Type filter from request
            ->when($request->filled('type'), function ($q) use ($request) {
                $q->where('list_type', $request->input('type'));
            })

            // Permission based filtering
            ->where(function ($query) {

                // Global lists
                if (customer(true)->canAny([
                    'favorites.use-global-list',
                    'favorites.manage-global-list'
                ])) {

                    $query->orWhere('list_type', 'global');
                }

                // Personal lists (only own data)
                if (customer(true)->can('favorites.manage-personal-list')) {

                    $query->orWhere(function ($personalQuery) {

                        $personalQuery
                            ->where('list_type', 'personal')
                            ->where('contact_id', auth('customer')->user()->id);
                    });
                }
            })

            // Search
            ->when($request->filled('search'), function ($q) use ($request) {

                $search = strtolower($request->search);

                $q->where(function ($searchQuery) use ($search) {

                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })

            // Exclude quick list
            ->where('list_type', '!=', 'quick-list')

            // Sorting
            ->orderBy(
                $request->input('sort', 'id'),
                $request->input('dir', 'desc')
            )

            // Pagination
            ->paginate(
                $request->input('per_page', getPaginationLengths()[0])
            );

        $columns = [
            'name' => strlen($this->nameLabel) != 0,
            'list_type' => strlen($this->listTypeLabel) != 0,
            'description' => strlen($this->descriptionLabel) != 0,
            'product_count' => strlen($this->productCountLabel) != 0,
        ];

        $listTypes = config('amplify.constant.favorite_list_type');

        if (!config('amplify.basic.enable_quick_list', true)) {
            unset($listTypes['quick-list']);
        }

        $singleType = count($listTypes) == 1;

        return view('widget::customer.order-list.index', compact('orderLists', 'columns', 'listTypes', 'singleType'));
    }
}
