<?php

namespace Amplify\Frontend\Http\Controllers\Auth;

use Amplify\Frontend\Http\Requests\Auth\ForceResetPasswordRequest;
use Amplify\Frontend\Traits\HasDynamicPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ForceResetPasswordController extends Controller
{
    use HasDynamicPage;

    /**
     * Handle the incoming request.
     *
     * @throws \ErrorException
     */
    public function __invoke(Request $request): string
    {
        if (! customer_check()) {
            return redirect()->route('frontend.login');
        }

        if (! customer(true)?->password_reset_required) {
            return redirect()->intended(route('frontend.dashboard'));
        }

        $this->loadPageByType('force_password_reset');

        return $this->render();
    }

    public function attempt(ForceResetPasswordRequest $request): RedirectResponse
    {
        customer(true)->update([
            'password' => $request->input('password'),
            'password_reset_required' => false,
        ]);
        Session::flash('success', 'Your Password has updated Successfully');

        return redirect()->intended(route('frontend.dashboard'));
    }
}
