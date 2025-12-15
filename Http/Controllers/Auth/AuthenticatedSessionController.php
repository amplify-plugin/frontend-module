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
                'email' => __('The account has not been approved yet. Please contact Admin'),
            ]);
        }

        $guestCustomerNumber = config('amplify.frontend.guest_default');

        if ($guestCustomerNumber) {
            @cache()->forget("getCustomerDetails-{$guestCustomerNumber}");
            @cache()->forget("getCustomerShippingLocationList-{$guestCustomerNumber}");
        }

        $guestSessionToken = \Illuminate\Support\Facades\Session::token();
        @cache()->forget("{$guestSessionToken}-customer-model");
        @cache()->forget("{$guestSessionToken}-mobile-menu");
        @cache()->forget("{$guestSessionToken}-primary-menu");
        @cache()->forget("{$guestSessionToken}-account-menu");
        @cache()->forget("{$guestSessionToken}-account-sidebar");

        if (!Auth::guard(Contact::AUTH_GUARD)->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        event(new ContactLoggedIn($account, $guestCart));

        $request->clearRateLimiter();

        $request->session()->regenerate();

        return redirect()
            ->to(CustomerHelper::afterLoggedRedirectTo($request->all()))
            ->with('success', __("Welcome back! :name", ['name' => $account->name]));
    }

    /**
     * Destroy an authenticated session.
     *
     * @return RedirectResponse
     */
    public function logout(Request $request)
    {
        try {

            $account = Auth::guard(Contact::AUTH_GUARD)->user();

            $token = \Illuminate\Support\Facades\Session::token();

            event(new ContactLoggedOut($account, $token));

            Auth::guard(Contact::AUTH_GUARD)->logout();

            $request->session()->regenerateToken();

            return redirect()
                ->to(route('frontend.index'))
                ->with('success', __('Session Logout successfully'));

        } catch (\Exception $exception) {
            logger()->debug($exception);
            return back()
                ->with('error', $exception->getMessage());
        }
    }
}
