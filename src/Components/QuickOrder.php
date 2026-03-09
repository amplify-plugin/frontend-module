<?php

namespace Amplify\Frontend\Components;

use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class QuickOrder
 */
class QuickOrder extends BaseComponent
{
    public function __construct(public bool   $checkWarehouseQtyAvailability = true,
                                public string $widgetTitle = 'Quick Order',
                                public int    $defaultProductRows = 1)
    {
        parent::__construct();
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        if (customer_check()) {
            return true;
        }

        if (config('amplify.frontend.guest_add_to_cart')) {
            return true;
        }

        return false;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $contact = customer(true);

        $userActiveWarehouseCode = null;
        $userActiveWarehouseName = null;

        if (isset($contact->warehouse)) {
            $userActiveWarehouseCode = $contact->warehouse->code;
            $userActiveWarehouseName = $contact->warehouse->name;
        }

        return view('widget::quick-order', compact('userActiveWarehouseCode', 'userActiveWarehouseName'));
    }
}
