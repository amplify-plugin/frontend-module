<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Http\Requests\ShippingOptionRequest;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CustomerAddress;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'shipping_number' => 'nullable',
            'shipping_name' => 'nullable',
            'shipping_address1' => 'required',
            'shipping_address2' => 'nullable',
            'shipping_contact1' => 'nullable',
            'shipping_contact2' => 'nullable',
            'shipping_phone1' => 'required',
            'shipping_phone2' => 'nullable',
            'shipping_email1' => 'required',
            'shipping_email2' => 'nullable',
            'shipping_country' => 'required',
            'shipping_state' => 'required',
            'shipping_city' => 'required',
            'shipping_zip' => 'required',
        ]);

        try {
            $validateAddress = ErpApi::validateCustomerShippingLocation($validatedData);

            if ($validateAddress->Response === 'Success') {
                $erpAddress = ErpApi::createCustomerShippingLocation([
                    'shipping_number' => $validatedData['shipping_number'],
                    'shipping_name' => $validateAddress->Name,
                    'shipping_address1' => $validateAddress->Address1,
                    'shipping_address2' => $validateAddress->Address2,
                    'shipping_contact1' => $validatedData['shipping_contact1'],
                    'shipping_contact2' => $validatedData['shipping_contact2'],
                    'shipping_phone1' => $validatedData['shipping_phone1'],
                    'shipping_phone2' => $validatedData['shipping_phone2'],
                    'shipping_email1' => $validatedData['shipping_email1'],
                    'shipping_email2' => $validatedData['shipping_email2'],
                    'shipping_country' => $validatedData['shipping_country'],
                    'shipping_state' => $validateAddress->State,
                    'shipping_city' => $validateAddress->City,
                    'shipping_zip' => $validateAddress->ZipCode,
                ]);

                if (config('amplify.basic.client_code') != 'ACP' && ! empty($erpAddress->ShipToNumber)) {
                    CustomerAddress::create([
                        'customer_id' => customer()->id,
                        'address_code' => $erpAddress->ShipToNumber,
                        'address_name' => $erpAddress->ShipToName,
                        'address_1' => $erpAddress->ShipToAddress1,
                        'address_2' => $erpAddress->ShipToAddress2,
                        'country_code' => $erpAddress->ShipToCountryCode,
                        'state' => $erpAddress->ShipToState,
                        'city' => $erpAddress->ShipToCity,
                        'zip_code' => $erpAddress->ShipToZipCode,
                    ]);
                }

                return $erpAddress;
            }

            return response()->json([
                'message' => $validateAddress->Details ?? 'Something went wrong.',
            ], 500);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function validateAddress(Request $request)
    {
        $validatedData = $request->validate([
            'shipping_name' => 'nullable',
            'shipping_address1' => 'required',
            'shipping_address2' => 'nullable',
            'shipping_state' => 'required',
            'shipping_city' => 'required',
            'shipping_zip' => 'required',
        ]);

        return ErpApi::validateCustomerShippingLocation($validatedData);
    }

    public function options(ShippingOptionRequest $request)
    {
        $orderInfo = [
            'customer_number' => customer_check() ? customer()->customer_erp_id : config('amplify.frontend.guest_default'),
            'customer_default_warehouse' => customer_check()
                ? ($request->input('customer_default_warehouse') ?: customer()->warehouse_seq_code)
                : config('amplify.frontend.guest_checkout_warehouse'),
            'shipping_method' => $request->input('shipping_method', 'UPS'),
            'customer_order_ref' => $request->input('customer_order_ref', ''),
            'ship_to_number' => $request->input('ship_to_number', ''),
            'ship_to_address1' => $request->input('customer_address_one', ''),
            'ship_to_address2' => $request->input('customer_address_two', ''),
            'ship_to_address3' => $request->input('customer_address_three', ''),
            'ship_to_city' => $request->input('customer_city', ''),
            'ship_to_country_code' => $request->input('customer_country_code', 'US'),
            'ship_to_state' => $request->input('customer_state', ''),
            'ship_to_zip_code' => $request->input('customer_zipcode', ''),
            'phone_number' => $request->input('customer_phone', ''),
            'shipping_name' => $request->input('shipping_name', ''),
            'items' => $this->getCartItemsForFactsErp(),
        ];

        $orderTotal = ErpApi::getOrderTotal($orderInfo);

        return response()->json($orderTotal);
    }

    /**
     * @return array|mixed [type]
     */
    private function getCartItemsForFactsErp(): mixed
    {
        $cart = getCart();

        if ($cart instanceof Cart) {

            return $cart->cartItems->map(function ($item) {
                return [
                    'ItemNumber' => $item->product_code,
                    'WarehouseID' => $item->product_warehouse_code,
                    'OrderQty' => $item->quantity,
                    'UnitOfMeasure' => $item->uom,
                ];
            })->toArray();
        } else {
            return [];
        }
    }

    /**
     * @return array|mixed
     */
    private function getWillCallMethods(mixed $carts, mixed $shippingMethods): mixed
    {
        $products = ErpApi::getProductPriceAvailability([
            'items' => $carts->map(fn ($item): array => [
                'item' => $item['ItemNumber'],
                'qty' => $item['OrderQty'],
            ]),
            'warehouse' => $carts->pluck('WarehouseID')->unique()->implode(','),
        ]);

        $warehouses = Warehouse::whereIn('code', $carts->pluck('WarehouseID')->unique()->toArray())->wherePickupLocation(true)
            ->get()
            ->groupBy('name')
            ->mapWithKeys(function ($items, $key): array {
                $item = $items->first();

                return [
                    strtoupper($key) => [
                        'shipvia' => 'WILL CALL',
                        'fullday' => '',
                        'date' => '',
                        'nrates' => '',
                        'amount' => '0.00',
                        'address1' => $item['address'],
                        'address2' => '',
                        'city' => '',
                        'state' => '',
                        'zip' => $item['zip_code'],
                        'email' => $item['email'],
                        'telephone' => $item['telephone'],
                    ],
                ];
            })
            ->toArray();

        $quantityAvailable = $products->pluck('QuantityAvailable')->toArray();

        $isBackOrder = $carts
            ->zip($quantityAvailable)
            ->contains(function ($pair): bool {
                [$cart, $quantity] = $pair;

                return $quantity < $cart['OrderQty'];
            });

        if (count($warehouses) > 0 && ! $isBackOrder) {
            $shippingMethods['FreightRate']['WILL CALL'][0] = $warehouses;
        } else {
            unset($shippingMethods['FreightRate']['WILL CALL']);
        }

        return $shippingMethods;
    }

    /**
     * Persist the selected Ship-To into session.
     */
    public function saveShipToAddress(Request $request)
    {
        // validate that they submitted a ShipToNumber
        $data = $request->validate([
            'ship_to_number' => 'required|string',
        ]);

        // re-load the full list from the ERP API
        $all = ErpApi::getCustomerShippingLocationList();

        // find the matching wrapper by ShipToNumber
        $selected = $all->firstWhere('ShipToNumber', $data['ship_to_number']);

        if (! $selected) {
            return back()->withErrors([
                'ship_to_number' => 'Selected address is invalid.',
            ]);
        }

        // only save if nothing’s been set yet
        $current = session('ship_to_address');
        if (empty($current)) {
            session(['ship_to_address' => $selected->toArray()]);
        }

        // now allow the user through
        return redirect()->intended('/');
    }
}
