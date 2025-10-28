<?php

namespace Amplify\Frontend\Http\Controllers\Auth;

use Amplify\Frontend\Events\ContactLoggedIn;
use Amplify\Frontend\Events\ContactLoggedOut;
use Amplify\Frontend\Helpers\CustomerHelper;
use Amplify\Frontend\Http\Requests\Auth\LoginRequest;
use Amplify\Frontend\Traits\HasDynamicPage;
use Amplify\System\Backend\Models\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    use HasDynamicPage;

    /**
     * Display the login view.
     *
     * @throws \ErrorException
     */
    public function login(): string
    {
        $this->loadPageByType('login');

        return $this->render();

    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function attempt(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $account = Contact::where([
            'email' => $request->input('email'),
            'enabled' => true,
        ])->first();

        $guestCart = getCart()?->load('cartItems') ?? null;

        if (!$account) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if (!$account->isApproved()) {
            throw ValidationException::withMessages([
                'email' => 'The account has not been approved yet. Please contact Admin',
            ]);
        }

        if (!Auth::guard(Contact::AUTH_GUARD)->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        event(new ContactLoggedIn($account, $guestCart));

        $request->clearRateLimiter();

        $session_token = \Illuminate\Support\Facades\Session::token();
        @Cache::forget("{$session_token}-customer-model");
        @Cache::forget("{$session_token}-mobile-menu");
        @Cache::forget("{$session_token}-primary-menu");
        @Cache::forget("{$session_token}-account-menu");

        $request->session()->regenerate();

        return redirect()->to(CustomerHelper::afterLoggedRedirectTo($request->all()));
    }

    /**
     * Destroy an authenticated session.
     *
     * @return Application|ResponseFactory|\Illuminate\Foundation\Application|JsonResponse|Response
     */
    public function logout(Request $request)
    {
        try {
            $customer_number = customer()->customer_code;
            $session_token = \Illuminate\Support\Facades\Session::token();
            @Cache::forget("getCustomerDetails-{$customer_number}");
            @Cache::forget("getCustomerShippingLocationList-{$customer_number}");
            @Cache::forget("{$session_token}-customer-model");
            @Cache::forget("{$session_token}-mobile-menu");
            @Cache::forget("{$session_token}-primary-menu");
            @Cache::forget("{$session_token}-account-menu");

            $account = Auth::guard(Contact::AUTH_GUARD)->user();

            event(new ContactLoggedOut($account));

            // Clear the ship-to address from session if it exists
            if (session()->has('ship_to_address')) {
                session()->forget('ship_to_address');
            }

            Auth::guard(Contact::AUTH_GUARD)->logout();

            $request->session()->regenerateToken();

            return response([
                'message' => 'Session Logout successfully',
            ], 200);

        } catch (\Exception $exception) {
            return response([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
