<?php

namespace Amplify\Frontend\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FrontendDisabled
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('amplify.frontend.ui_disabled', false)) {
            abort(Response::HTTP_SERVICE_UNAVAILABLE, __('Customer Portal is disabled.'));
        }

        return $next($request);
    }
}
