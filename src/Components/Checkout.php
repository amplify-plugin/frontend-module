<?php

namespace Amplify\Frontend\Components;

use Amplify\ErpApi\Collections\ShippingLocationCollection;
use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Backend\Models\Cart;
use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Country;
use Amplify\System\Backend\Models\State;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\InvokableComponentVariable;

/**
 * @class Checkout
 */
class Checkout extends BaseComponent
{
    public function __construct(public bool   $createFavouriteFromCart = true,
                                public bool   $allowRequestQuote = true,
                                public bool   $allowDraftOrder = false,
                                public string $backToUrl = 'home',
                                public string $assetUrl = ''
    )
    {
        parent::__construct();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $cart = getCart();
        $cartId = $cart->getKey();
        $cartItemCount = $cart instanceof Cart ? $cart->cartItems()->count() : 0;
        $customer = customer_check() ? ErpApi::getCustomerDetail() : ErpApi::adapter()->getCustomerDetail();
        $addresses = customer_check() ? ErpApi::getCustomerShippingLocationList() : ErpApi::adapter()->getCustomerShippingLocationList();
        $contact = customer_check() ? customer(true) : new Contact([]);
        $guestCheckout = config('amplify.frontend.guest_checkout');
        $templateBrandColor = theme_option('primary_color');
        $allowChooseShipping = havePermissions(['checkout.choose-ship-to']);
        $editable = true;
        $allowCreateShipping = config('amplify.erp.auto_create_ship_to');

        $steps = [
            ['index' => 1, 'id' => 'customer', 'label' => 'Account', 'active' => false, 'component' => 'account'],
            ['index' => 2, 'id' => 'shipping', 'label' => 'Shipping', 'active' => false, 'component' => 'shipping'],
            ['index' => 3, 'id' => 'review', 'label' => 'Review', 'active' => true, 'component' => 'review'],
            ['index' => 4, 'id' => 'review', 'label' => 'Payment', 'active' => true, 'component' => 'payment'],
        ];

        if ($customer->CreditCardOnly == 'Y' && havePermissions(['checkout.allow-credit-card-payment'])) {

            $steps[] = match (config('amplify.payment.default')) {
                'cenpos' => ['index' => 4, 'id' => 'billing', 'label' => 'Payment', 'active' => false, 'component' => 'payment'],
                default => ['index' => 4, 'id' => 'billing', 'label' => 'Payment', 'active' => false, 'component' => 'payment']
            };
        }

//        $addresses->push($this->loadEmptyShippingLocation());

        $countryIds = array_map(fn($country) => $country['id'], config('amplify.basic.countries'));

        $countries = Country::enabled()->select('id', 'name', 'iso2')->whereIn('id', $countryIds)->get();

        $states = State::select('iso2', 'country_id', 'name')->whereIn('country_id', $countryIds)->get();

        $shipOptions = ErpApi::getShippingOption();

        $steps = array_reverse($steps);

        return view('widget::checkout', compact(
            'allowCreateShipping',
            'editable',
            'guestCheckout',
            'cart',
            'cartId',
            'cartItemCount',
            'templateBrandColor',
            'steps',
            'customer',
            'addresses',
            'countries',
            'states',
            'shipOptions',
            'allowChooseShipping',
            'contact',
        ));
    }

    private function getOrderPricing($order)
    {
        try {
            $customerDetails = ErpApi::getCustomerDetail();
            $products = $order->orderLines->map(function ($item) {
                return [
                    'ItemNumber' => $item->product_code,
                    'WarehouseID' => $item->warehouse->warehouse->code ?? (customer()->warehouse->code ?? null),
                    'OrderQty' => $item->qty,
                ];
            });

            $order_infos = [
                'customer_number' => $customerDetails->CustomerNumber,
                'ship_to_number' => $customerDetails->DefaultShipTo,
                'payment_type' => $customerDetails->CreditCardOnly === 'Y' ? 'CreditCard' : 'Standard',
                'order_type' => 'T',
                'return_type' => 'D',
            ];

            $quote = ErpApi::createQuotation([
                'order' => $order_infos, 'items' => $products->toArray(),
            ])->first();

            return [
                'order_subtotal' => $quote->TotalOrderValue,
                'order_tax' => $quote->SalesTaxAmount,
                'order_ship' => $quote->FreightAmount,
                'order_total' => $quote->TotalOrderValue + $quote->SalesTaxAmount + $quote->FreightAmount,
                'threshold_limit' => $customerDetails->FreightOptionAmount ?? config('amplify.marketing.free_ship_threshold'),
                'threshold_message' => config('amplify.marketing.checkout_threshold_replace'),

            ];

        } catch (\Exception $exception) {
            return [
                'order_subtotal' => null,
                'order_tax' => null,
                'order_ship' => null,
                'order_total' => null,
                'threshold_limit' => config('amplify.marketing.free_ship_threshold'),
                'threshold_message' => customer()->free_shipment_amount ?? config('amplify.marketing.checkout_threshold_replace'),
            ];
        }
    }

    private function loadEmptyShippingLocation()
    {
        return ErpApi::adapter()->renderSingleCustomerShippingLocation([
            'ShipToNumber' => 'TEMP',
            'ShipToName' => strtoupper('Temporary address'),
        ]);
    }

    public function backToShoppingUrl(): string
    {
        if ($this->backToUrl == 'home') {
            return frontendHomeURL();
        }

        return frontendShopURL();
    }

    public function props(array $variables = [])
    {
        $reserved = ['__path', '__data', 'app', 'errors', '__env',
            '__laravel_slots', 'props', 'slot', 'ignoredParameterNames',
            'htmlAttributes', 'options', 'componentName', 'attributes',
            'itemRow', 'backToUrl', 'assetUrl'
        ];

        foreach ($variables as $key => $value) {
            if ($value instanceof InvokableComponentVariable) {
                $variables[$key] = $value();
            }

            if ($value instanceof ComponentAttributeBag) {
                foreach ($value->getAttributes() as $attrKey => $attrValue) {
                    $variables[$attrKey] = json_encode($attrValue);
                }
            }

            if (in_array($key, $reserved)) {
                unset($variables[$key]);
            }
        }

        return $variables;
    }
}
