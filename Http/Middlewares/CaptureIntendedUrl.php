<?php

namespace Amplify\Frontend\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureIntendedUrl
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            !$request->is('/admin/*') &&
            $request->isMethod('GET') &&
            $request->route() &&
            !$request->routeIs('frontend.login*') &&
            !$request->expectsJson()
        ) {
            session(['url.intended' => $request->fullUrl()]);
        }

        return $next($request);
    }
}
