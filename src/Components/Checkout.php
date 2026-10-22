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
    public array $data = [];

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

    public function shouldRender()
    {
        $this->prepareData($this->data);

        return true;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('widget::checkout', $this->withData($this->data));
    }
    protected function prepareData(&$data): void
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

        $data['customer'] = $customer;

        $data['allowChooseBilling'] = true;

        if (config('amplify.payment.default') == 'aptean') {
            $data['apteanStyle'] = $this->apteanStyle();
        }
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
        $data['paymentTerms'] = ErpApi::getTermsType();

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


    public function apteanStyle(): array
    {
        return [
            'styles' => [
                'base' => [
                    'border-radius' => '22px',
                    'height' => '44px',
                    'padding' => '0 18px 3px',
                    'border' => '1px solid #e1e7ec',
                    'font-size' => '1rem',
                ]
            ]
        ];
    }

    public function gatewayAssets(): array
    {
        return match (config('amplify.payment.default')) {
            'cenpos' => [
                'https://cdn.jsdelivr.net/npm/jquery-migrate@1.4.1/dist/jquery-migrate.min.js',
                'https://www.cenpos.com/Plugins/porthole.min.js',
                'https://www.cenpos.com/Plugins/jquery.simplewebpay.js',
            ],
            'aptean' => ['https://stg.js.apteansharedservices.com/apteanpay-js/v1'],
            default => []
        };
    }
}
