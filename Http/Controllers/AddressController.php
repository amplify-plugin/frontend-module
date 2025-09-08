<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\ErpApi\Jobs\CustomerAddressSyncJob;
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
        if (! customer(true)->can('ship-to-addresses.list')) {
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

        if (! customer(true)->can('ship-to-addresses.add')) {
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
        try {
            $address = CustomerAddress::create($request->validated());

            if (config('amplify.erp.auto_create_ship_to')) {
                CustomerAddressSyncJob::dispatch($address->toArray());
            }

            Session::flash('success', 'Addresses Added Successfully');

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
        if (! customer(true)->can('ship-to-addresses.view')) {
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

        if (! customer(true)->can('ship-to-addresses.update')) {
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
        if (! customer(true)->can('ship-to-addresses.remove')) {
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
