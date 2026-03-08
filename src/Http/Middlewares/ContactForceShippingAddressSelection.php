<?php

namespace Amplify\Frontend\Http\Middlewares;

use Amplify\System\Backend\Models\Contact;
use Amplify\System\Cms\Models\Page;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactForceShippingAddressSelection
{
    /**
     * Handle an incoming request.
     * If the logged-in contact belongs to customer 100810
     * and hasn't set a shipto_id in session, force
     * them to /address (except when they're already there).
     */
    public function handle(Request $request, Closure $next)
    {
        $ignoreRouteNames = [
            'frontend.ship-to-address.store',
            'frontend.force-reset-password',
            'frontend.force-reset-password-attempt',
            'frontend.logout',
        ];
        if (in_array($request->route()->getName(), $ignoreRouteNames)) {
            return $next($request);
        }

        $selectedCustomerId = config('amplify.frontend.force_shipping_address_customer_id', null);
        // If no customer ID is set, skip the middleware
        if (empty($selectedCustomerId)) {
            return $next($request);
        }

        // Check the 'contact' guard
        if (! Auth::guard(Contact::AUTH_GUARD)->check()) {
            return $next($request);
        }

        $shippingPage = Page::wherePageType('shipping_address')->published()->first();

        if (empty($shippingPage)) {
            return $next($request);
        }

        $contact = Auth::guard(Contact::AUTH_GUARD)->user();
        $mustSelect = $contact->customer->customer_code == $selectedCustomerId;
        $noSession = ! session()->has('ship_to_address');

        if ($mustSelect && $noSession && ! $request->is($shippingPage->slug)) {
            // allow the form itself, but everything else redirects
            return redirect($shippingPage->slug);

        }

        return $next($request);
    }
}
