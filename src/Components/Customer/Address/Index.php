<?php

namespace Amplify\Frontend\Components\Customer\Address;

use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Backend\Models\CustomerAddress;
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
        public bool $allowCreate = true,
        public bool $allowUpdate = true,
        public bool $allowDelete = true,
        public string $widgetTitle = 'Address',
    ) {
        parent::__construct();

    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return customer(true)->can('address.list');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $perPage = request('per_page', getPaginationLengths()[0]);

        $search = request('search');

        $addresses = CustomerAddress::whereCustomerId(customer()->getKey())
            ->whereNotNull('address_code')
            ->where(function ($query) use ($search) {
                $query->where('address_name', 'like', "%{$search}%")
                    ->orWhere('address_code', 'like', "%{$search}%")
                    ->orWhere('address_1', 'like', "%{$search}%")
                    ->orWhere('address_2', 'like', "%{$search}%")
                    ->orWhere('address_3', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('country_code', 'like', "%{$search}%")
                    ->orWhere('zip_code', 'like', "%{$search}%");
            })
            ->latest()->paginate($perPage)->withQueryString();

        $useAddress = session('ship_to_address');

        $defaultAddressId = customer()->default_address_id;

        return view('widget::customer.address.index',
            compact('addresses', 'perPage', 'search', 'useAddress', 'defaultAddressId'));
    }
}
