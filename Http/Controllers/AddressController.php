<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Http\Requests\ShipToAddressRequest;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Contact;
use Amplify\System\Backend\Models\CustomerAddress;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class AddressController extends Controller
{
    use HasDynamicPage;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->loadPageByType('address');
        if (!customer(true)->can('ship-to-addresses.list')) {
            abort(403);
        }

        return $this->render();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->loadPageByType('address_create');

        if (!customer(true)->can('ship-to-addresses.add')) {
            abort(403);
        }

        return $this->render();
    }

    /**
     * @return RedirectResponse
     *                          The purpose of this function is to store the address from the customer admin panel
     */
    public function store(ShipToAddressRequest $request)
    {
        $validated = $request->validated();

        // STV Client: Auto-generate unique address code from first address line
        // Example: "123 Main Street" → "123", check duplicates → "123-1", "123-2", etc.
        if (config('amplify.client_code') === 'STV') {
            // Step 1: Extract the first address line and trim whitespace
            // Input: "  123 Main Street  " → Output: "123 Main Street"
            $address1 = trim($validated['address_1'] ?? '');

            if (!empty($address1)) {
                // Step 2: Extract the first word/token from the address
                // Input: "123 Main Street" → Output: "123"
                // Input: "BuildingA Suite 200" → Output: "BuildingA"
                $firstToken = preg_split('/\s+/', $address1)[0] ?? $address1;

                // Step 3: Sanitize the token - keep only alphanumeric and hyphens
                // Removes special characters: periods, slashes, commas, @ symbols, etc.
                // Input: "#Suite-201" → Output: "Suite-201"
                // Input: "Apt.#5B" → Output: "Apt5B"
                $baseCode = preg_replace('/[^A-Za-z0-9\-]/', '', $firstToken);

                // Step 4: Fallback - if sanitization removed everything, try removing spaces instead
                // Input: "!@#$%" → After sanitize: "" → Try space removal: "" → Move to next step
                if ($baseCode === '') {
                    $baseCode = preg_replace('/\s+/', '', $firstToken);
                }

                // Step 5: Final fallback - if still empty, use default 'ADDR'
                // This ensures we always have a valid base code
                if ($baseCode === '') {
                    $baseCode = 'ADDR';
                }

                // Step 6: Check for duplicates and generate unique code
                // Query database for existing codes for this customer
                // If "123" exists, try "123-1", "123-2", etc. until unique
                // Example flow:
                //   - Customer has addresses with codes: "123", "123-1"
                //   - New code "123" → exists
                //   - New code "123-1" → exists
                //   - New code "123-2" → doesn't exist ✓
                $candidate = $baseCode;
                $counter = 0;

                while (
                    CustomerAddress::where('customer_id', customer()->getKey())
                        ->where('address_code', $candidate)
                        ->exists()
                ) {
                    $counter++;
                    $candidate = $baseCode . '-' . $counter;
                }

                // Store the generated unique address code in validated data
                $validated['address_code'] = $candidate;
            }
        }

        try {
            $validateAddress = ErpApi::validateCustomerShippingLocation([
                'ship_to_name' => $validated['address_name'] ?? null,
                'ship_to_code' => $validated['address_code'] ?? null,
                'ship_to_address1' => $validated['address_1'],
                'ship_to_address2' => $validated['address_2'] ?? null,
                'ship_to_address3' => $validated['address_3'] ?? null,
                'ship_to_city' => $validated['city'],
                'ship_to_country_code' => $validated['country_code'],
                'ship_to_state' => $validated['state'] ?? null,
                'ship_to_zip_code' => $validated['zip_code'],
            ]);

            if ($validateAddress->Response !== 'Success') {
                Session::flash('error', $validateAddress->Message ?? 'The address value was incomplete.');
                return back();
            }

            $erpAddress = ErpApi::createCustomerShippingLocation([
                'address_code' => $validated['address_code'] ?? null,
                'address_name' => $validated['address_name'] ?? null,
                'address_1' => $validated['address_1'],
                'address_2' => $validated['address_2'] ?? null,
                'address_3' => $validated['address_3'] ?? null,
                'contact' => $validated['shipping_contact1'] ?? null,
                'contact_2' => $validated['shipping_contact2'] ?? null,
                'phone_1' => $validated['shipping_phone1'] ?? null,
                'phone_2' => $validated['shipping_phone2'] ?? null,
                'email_1' => $validated['shipping_email1'] ?? null,
                'email_2' => $validated['shipping_email2'] ?? null,
                'country_code' => $validated['country_code'],
                'state' => $validated['state'] ?? null,
                'city' => $validated['city'],
                'zip_code' => $validated['zip_code'],
            ]);

            if (config('amplify.client_code') != 'ACP' && !empty($erpAddress->ShipToNumber)) {
                CustomerAddress::create([
                    'customer_id' => $request->input('customer_id', customer()->getKey()),
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

            Session::flash('success', 'Address Added Successfully');

            return redirect()->route('frontend.addresses.index');
        } catch (\Throwable $th) {
            Log::error($th);
            Session::flash('error', 'Sorry something went wrong...');

            return back();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomerAddress $address)
    {
        store()->addressModel = $address;

        $this->loadPageByType('address_details');
        if (!customer(true)->can('ship-to-addresses.view')) {
            abort(403);
        }

        return $this->render();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomerAddress $address)
    {
        store()->addressModel = $address;

        $this->loadPageByType('address_edit');

        if (!customer(true)->can('ship-to-addresses.update')) {
            abort(403);
        }

        return $this->render();
    }

    /**
     * @return RedirectResponse
     *                          This method updates a single address stored in the database
     */
    public function update(CustomerAddress $address, ShipToAddressRequest $request)
    {
        try {
            $address->update($request->validated());
            Session::flash('success', 'You have successfully updated the address!');
        } catch (\Throwable $th) {
            Log::error($th);
            Session::flash('error', 'Sorry something went wrong...');
        }

        return redirect()->route('frontend.addresses.index');
    }

    /**
     * @return RedirectResponse
     *                          This method deletes a single address fromt the database
     */
    public function destroy(CustomerAddress $address)
    {
        if (!customer(true)->can('ship-to-addresses.remove')) {
            abort(403);
        }
        try {
            $this->updateContactDefaultAddressIfAddressIsConstrainedToIt($address);

            $address->delete();

            Session::flash('success', 'You have successfully deleted the address!');
        } catch (\Throwable $th) {
            Session::flash('error', 'Sorry something went wrong...');
        }

        return back();
    }

    /**
     * This method sets the default address of a customer
     */
    public function setDefault(CustomerAddress $address): RedirectResponse
    {
        // Get customer object through the belongsTo relation between Contact and Customer then update it's default address
        customer(true)->customer()->update([
            'default_address_id' => $address->id,
        ]);

        Session::flash('success', 'You have successfully set the default address!');

        return back();
    }

    /**
     * @return Collection
     *                    This method checks if the contact default address is constrained to the address. If so it updates the customer_address_id column to null
     */
    public function updateContactDefaultAddressIfAddressIsConstrainedToIt($address)
    {
        $constrainedContact = Contact::where('customer_address_id', $address->id)->first();

        if ($constrainedContact) {
            $constrainedContact->customer_address_id = null; // we set it to null so that there is no hindrance of foreign key checks
            $constrainedContact->save();
        }

        return $constrainedContact;
    }
}
