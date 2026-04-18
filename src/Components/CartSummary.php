<?php

namespace Amplify\Frontend\Components;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Abstracts\BaseComponent;
use Closure;
use Illuminate\Contracts\View\View;

/**
 * @class CartSummary
 */
class CartSummary extends BaseComponent
{
    public function __construct(public string $backToUrl = 'home',
        public bool $createOrderListFromCart = true,
        public bool $allowChangeShipTo = true,
        public string $orderListLabel = 'Shopping List',
        public string $updateStyle = 'line',
    ) {
        parent::__construct();
    }

    /**
     * Whether the component should be rendered
     */
    public function shouldRender(): bool
    {
        return true;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $templateBrandColor = theme_option(key: 'primary_color', default: '#002767');

        $isCartEmpty = ! getCart()->cartItems()->exists();

        $cartId = getCart()->getKey();

        $shipToAddress = null;

        $shipToNumber = session('ship_to_address.ShipToNumber', session('ship_to_address.address_code', ErpApi::getCustomerDetail()->DefaultShipTo));

        if (! empty($shipToNumber)) {
            $shipToAddress = ErpApi::getCustomerShippingLocationList()->firstWhere('ShipToNumber', '=', $shipToNumber);
        }

        return view('widget::cart-summary', compact('templateBrandColor', 'isCartEmpty', 'cartId', 'shipToAddress'));
    }

    public function createOrderListLabel(): string
    {
        return 'Create '.$this->orderListLabel;
    }

    public function backToShoppingUrl(): string
    {
        if ($this->backToUrl == 'home') {
            return frontendHomeURL();
        }

        return frontendShopURL();
    }
}
