<?php

namespace Amplify\Frontend\Http\Middlewares;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Backend\Models\ContactLogin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerDefaultValues
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('ship_to_address')) {

            $contact = $request->user('customer');

            $customer = $contact->customer;

            $addresses = $customer->addresses;

            $defaultAddress = $addresses->firstWhere('address_code', '=', $customer->shipto_address_code);
            if ($defaultAddress) {
                $contactLogin = ContactLogin::where(['customer_id' => $customer->getkey(), 'contact_id' => $contact->getKey(), 'active' => true])->first();
                $contactLogin->customer_address_id = $defaultAddress->getKey();
                $contactLogin->ship_to_name = $defaultAddress->address_code;
                $contactLogin->save();

                $attr = $defaultAddress->toArray();
                $attr['allow_backorder'] = $customer->allow_backorder ?? false;
                $attr['business_contact'] = $customer->business_contact ?? null;
                $attr['customer_po_required'] = $customer->customer_po_required ?? false;
                $attr['carrier_code'] = $customer->carrier_code ?? null;

                $request->session()->put('ship_to_address', ErpApi::init('default')->adapter()->renderSingleCustomerShippingLocation($attr)->toArray());
            }
        }

        return $next($request);
    }
}
