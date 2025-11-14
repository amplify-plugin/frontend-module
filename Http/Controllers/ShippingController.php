<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Http\Requests\ShippingOptionRequest;
use Amplify\System\Backend\Models\Cart;
use Amplify\System\Backend\Models\CustomerAddress;
use Amplify\System\Backend\Models\Warehouse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShippingController extends Controller
{
    public function store(Request $request)
    {
        // Define validation rules first (outside of try)
        $rules = [
            'shipping_number' => ['nullable'],
            'shipping_name' => 'required|string|max:255',
            'shipping_address1' => 'required|string|max:255',
            'shipping_address2' => 'nullable|string|max:255',
            'shipping_address3' => 'nullable|string|max:255',
            'shipping_contact1' => 'nullable|string|max:255',
            'shipping_contact2' => 'nullable|string|max:255',
            'shipping_phone1' => 'required|string|min:10|max:255',
            'shipping_phone2' => 'nullable|string|min:10|max:255',
            'shipping_email1' => 'nullable|string|email:dns,rfc|max:255',
            'shipping_email2' => 'nullable|string|email:dns,rfc|max:255',
            'shipping_country' => 'required|string|max:255',
            'shipping_state' => 'required|string|max:255',
            'shipping_city' => 'required|string|max:255',
            'shipping_zip' => 'required|string|max:10',
        ];

        if (config('amplify.client_code') === 'STV') {
            $rules['shipping_number'][] = Rule::unique('customer_addresses', 'address_code')
                ->where(fn($query) => $query->where('customer_id', customer()->getKey()));
        }

        try {
            // Let Laravel throw ValidationException automatically
            $validatedData = $request->validate($rules);

            $validateAddress = ErpApi::validateCustomerShippingLocation([
                'ship_to_name' => $validatedData['shipping_name'] ?? null,
                'ship_to_code' => $validatedData['shipping_number'] ?? null,
                'ship_to_address1' => $validatedData['shipping_address1'],
                'ship_to_address2' => $validatedData['shipping_address2'] ?? null,
                'ship_to_address3' => $validatedData['shipping_address3'] ?? null,
                'ship_to_city' => $validatedData['shipping_city'],
                'ship_to_country_code' => $validatedData['shipping_country'],
                'ship_to_state' => $validatedData['shipping_state'],
                'ship_to_zip_code' => $validatedData['shipping_zip'],
            ]);

            if ($validateAddress->Response !== 'Success') {
                throw new \Exception($validateAddress->Message ?? 'The address value was incomplete.');
            }

            $erpAddress = ErpApi::createCustomerShippingLocation([
                'address_code' => $validateAddress->Reference,
                'address_name' => $validateAddress->Name,
                'address_1' => $validateAddress->Address1,
                'address_2' => $validateAddress->Address2,
                'address_3' => $validateAddress->Address3,
                'contact' => $validatedData['shipping_contact1'] ?? null,
                'contact_2' => $validatedData['shipping_contact2'] ?? null,
                'phone_1' => $validatedData['shipping_phone1'] ?? null,
                'phone_2' => $validatedData['shipping_phone2'] ?? null,
                'email_1' => $validatedData['shipping_email1'] ?? null,
                'email_2' => $validatedData['shipping_email2'] ?? null,
                'country_code' => $validateAddress->CountryCode,
                'state' => $validateAddress->State,
                'city' => $validateAddress->City,
                'zip_code' => $validateAddress->ZipCode,
            ]);

            if (config('amplify.client_code') !== 'ACP' && !empty($erpAddress->ShipToNumber)) {
                CustomerAddress::create([
                    'customer_id' => customer()->getKey(),
                    'address_code' => $erpAddress->ShipToNumber,
                    'address_name' => $erpAddress->ShipToName,
                    'address_1' => $erpAddress->ShipToAddress1,
                    'address_2' => $erpAddress->ShipToAddress2,
                    'address_3' => $erpAddress->ShipToAddress3,
                    'country_code' => $erpAddress->ShipToCountryCode,
                    'state' => $erpAddress->ShipToState,
                    'city' => $erpAddress->ShipToCity,
                    'zip_code' => $erpAddress->ShipToZipCode,
                ]);
            }

            return response()->json(['success' => true, 'address' => $erpAddress]);

        } catch (ValidationException $e) {
            // Return validation errors in JSON
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $th) {
            // Return any other errors
            return response()->json(['message' => $th->getMessage()], 500);
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
            'items' => $carts->map(fn($item): array => [
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

        if (count($warehouses) > 0 && !$isBackOrder) {
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

        if (!$selected) {
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

    public function storeSessionAddress(Request $request, string $addressCode)
    {
        // re-load the full list from the ERP API
        $all = ErpApi::getCustomerShippingLocationList();

        // find the matching wrapper by ShipToNumber
        $selected = $all->firstWhere('ShipToNumber', $addressCode);

        if (!$selected) {
            return back()->withErrors([
                'ship_to_number' => 'Selected address is invalid.',
            ]);
        }
        
        // always overwrite previous session value
        session(['ship_to_address' => $selected->toArray()]);

        // redirct back with flash message
        return redirect()->back()->with('success', 'Shipping address selected successfully.');
    }
}
