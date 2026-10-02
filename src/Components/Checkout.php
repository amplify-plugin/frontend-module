<?php

namespace Amplify\Frontend\Components;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Abstracts\BaseComponent;
use Amplify\System\Backend\Models\Cart;
use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\Country;
use Amplify\System\Backend\Models\State;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
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
                                public string $orderListTitle = 'Shopping List',
                                public string $assetUrl = '',
    )
    {
        parent::__construct();
    }

    /**
     * Get the view / contents that represent the component.
     * @throws \ErrorException
     */
    public function render(): View|Closure|string
    {
        $cart = getCart();
        $data['cartId'] = $cart->getKey();
        $data['cartItemCount'] = $cart instanceof Cart ? $cart->cartItems()->count() : 0;
        $customer = customer_check() ? ErpApi::getCustomerDetail() : ErpApi::adapter()->getCustomerDetail();
        $data['addresses'] = customer_check() ? ErpApi::getCustomerShippingLocationList() : ErpApi::adapter()->getCustomerShippingLocationList();
        $contact = customer_check() ? customer(true) : new Contact;

        $data['contact'] = Arr::only($contact->toArray(), ['id', 'name', 'phone', 'email']);

        $data['guestCheckout'] = customer_check() ? false : config('amplify.frontend.guest_checkout');
        $data['templateBrandColor'] = theme_option('primary_color');
        $data['allowChooseShipping'] = false; //havePermissions(['checkout.choose-ship-to']);
        $data['editable'] = true;
        $data['allowCreateShipping'] = config('amplify.erp.auto_create_ship_to');
        $data['shipTo'] = session('ship_to_address.ShipToNumber', $customer->DefaultShipTo);

        $data['steps'] = [
            ['id' => 'account', 'label' => 'Account', 'active' => false, 'component' => 'account'],
            ['id' => 'shipping', 'label' => 'Shipping', 'active' => false, 'component' => 'shipping'],
        ];
//        if ($customer->CreditCardOnly == 'Y' && havePermissions(['checkout.allow-credit-card-payment'])) {
        if (config('amplify.payment.default') != 'default') {
            $data['steps'][] = ['id' => 'payment', 'label' => 'Payment', 'active' => false, 'component' => 'payment'];
        }
//        }

        $data['steps'][] = ['id' => 'review', 'label' => 'Review', 'active' => true, 'component' => 'review'];

        $countryIds = array_map(fn($country) => $country['id'], config('amplify.basic.countries'));

        $data['countries'] = Country::enabled()->select('id', 'name', 'iso2')->whereIn('id', $countryIds)->get();

        $data['states'] = State::select('iso2', 'country_id', 'name')->whereIn('country_id', $countryIds)->get();

        $data['verifyPoNumber'] = false;

        $data['hasShipInstruction'] = false;

        $data['paymentTerms'] = ErpApi::getTermsType();

        $data['customer'] = $customer;

        $data['allowChooseBilling'] = true;

        return view('widget::checkout', $this->withData($data));
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

    protected function withData($data): array
    {
        return $data;
    }

    public function props(array $variables = [])
    {
        $reserved = ['__path', '__data', 'app', 'errors', '__env',
            '__laravel_slots', 'props', 'slot', 'ignoredParameterNames',
            'htmlAttributes', 'options', 'componentName', 'attributes',
            'itemRow', 'backToUrl', 'assetUrl', 'gatewayAssets'
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

    public function gatewayAssets(): array
    {
        return match (config('amplify.payment.default')) {
            'cenpos' => [
                'https://cdn.jsdelivr.net/npm/jquery-migrate@1.4.1/dist/jquery-migrate.min.js',
                'https://www.cenpos.com/Plugins/porthole.min.js',
                'https://www.cenpos.com/Plugins/jquery.simplewebpay.js',
            ],
            'aptean' => ['https://stg.js.apteansharedservices.com/apteanpay-js/v1']
        };
    }
}
