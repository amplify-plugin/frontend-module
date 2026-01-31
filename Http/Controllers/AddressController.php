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
        $validatedAddress = ErpApi::validateCustomerShippingLocation([
            'ship_to_name' => $request->input('address_name'),
            'ship_to_code' => $request->input('address_code'),
            'ship_to_address1' => $request->input('address_1'),
            'ship_to_address2' => $request->input('address_2'),
            'ship_to_address3' => $request->input('address_3'),
            'ship_to_city' => $request->input('city'),
            'ship_to_country_code' => $request->input('country_code'),
            'ship_to_state' => $request->input('state'),
            'ship_to_zip_code' => $request->input('zip_code'),
        ]);

        if ($validatedAddress->Response !== 'Success') {
            Session::flash('error', $validatedAddress->Message ?? 'The address value was incomplete.');
            return back();
        }

        try {

            $erpAddress = ErpApi::createCustomerShippingLocation([
                'address_code' => $validatedAddress->Reference,
                'address_name' => $validatedAddress->Name,
                'address_1' => $validatedAddress->Address1 ?? $request->input('address_1'),
                'address_2' => $validatedAddress->Address2 ?? $request->input('address_2'),
                'address_3' => $validatedAddress->Address3 ?? $request->input('address_3'),

                'contact' => $request->input('shipping_contact1'),
                'contact_2' => $request->input('shipping_contact2'),
                'phone_1' => $request->input('shipping_phone1'),
                'phone_2' => $request->input('shipping_phone2'),
                'email_1' => $request->input('shipping_email1'),
                'email_2' => $request->input('shipping_email2'),

                'country_code' => $request->input('country_code'),
                'state' => $validatedAddress->State ?? $request->input('state'),
                'city' => $validatedAddress->City ?? $request->input('city'),
                'zip_code' => $validatedAddress->ZipCode ?? $request->input('zip_code'),
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
