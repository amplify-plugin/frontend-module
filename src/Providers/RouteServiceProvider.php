<?php

namespace Amplify\Frontend\Providers;

use Amplify\Frontend\Http\Middlewares\CaptureIntendedUrl;
use Amplify\Frontend\Http\Middlewares\ContactForceShippingAddressSelection;
use Amplify\Frontend\Http\Middlewares\FrontendDisabled;
use Amplify\System\Backend\Http\Middlewares\AdminForcePasswordReset;
use Amplify\System\Backend\Http\Middlewares\CustomerAuthCheck;
use Amplify\System\Backend\Http\Middlewares\Language;
use Amplify\System\Backend\Http\Middlewares\ProductSessionClear;
use Amplify\System\Backend\Http\Middlewares\TeamsPermission;
use Amplify\System\Cms\Models\Form;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $request = $this->app->make(Request::class);

        if (!$request?->is('admin/*')) {
            $this->bind('form_code', fn(string $value) => Form::whereCode($value)->firstOrFail());

        }

        $this->pushMiddlewareToGroup('frontend', ProtectAgainstSpam::class);
        $this->pushMiddlewareToGroup('frontend', ContactForceShippingAddressSelection::class);
        $this->pushMiddlewareToGroup('frontend', CaptureIntendedUrl::class);
        $this->pushMiddlewareToGroup('frontend', FrontendDisabled::class);

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->name('api.')
                ->group(__DIR__ . '/../../routes/api.php');

            Route::middleware(['web', 'frontend'])
                ->name('frontend.')
                ->group(__DIR__ . '/../../routes/web.php');
        });
    }
}
