<?php

namespace Amplify\Frontend\Http\Controllers;

use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SwitchAccountController extends Controller
{
    use HasDynamicPage;

    public function index()
    {
        abort_unless(customer(true)->can('switch-account.switch-account'), 403);
        $this->loadPageByType('switch_account');

        return $this->render();
    }

    public function update(Request $request)
    {
        $request->validate([
            'active_customer_id' => 'required|integer',
        ]);

        $contact = customer(true);
        $contact->update([
            'active_customer_id' => $request->input('active_customer_id'),
        ]);

        Auth::guard('customer')->logout();

        if (Auth::guard('customer')->login($contact)) {
            return redirect()->route('frontend.dashboard');
        }

        return back();
    }
}
