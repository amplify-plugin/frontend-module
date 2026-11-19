<?php

namespace Amplify\Frontend\Http\Middlewares;

use Amplify\System\Backend\Models\Contact;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CustomerAuthCheck
{
    private array $excluded_routes = [
        'force-reset-password',
        'login',
    ];

    /**
     * Answer to unauthorized access request.
     *
     * @param  Request  $request
     * @return JsonResponse|RedirectResponse
     */
    private function respondToUnauthorizedRequest($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'You need to be logged in to access this feature.',
            ], 401);
        } else {
            return redirect()->route('frontend.login')->with('error', 'Please sign in with your credentials to view this page.');
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse|JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (customer_check()) {

            if (session('impersonate', false) === true) {
                return $next($request);
            }

            if (! $request->is($this->excluded_routes) && $request->user(Contact::AUTH_GUARD)->password_reset_required) {
                return ($request->ajax() || $request->wantsJson())
                    ? response()->json([
                        'message' => 'Required password reset.',
                    ], 500)
                    : redirect()->route('frontend.force-reset-password');
            }

            return $next($request);
        }

        return $this->respondToUnauthorizedRequest($request);
    }
}
