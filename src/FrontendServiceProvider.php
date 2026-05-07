<?php

namespace Amplify\Frontend;

use Amplify\Frontend\Http\Middlewares\CaptureIntendedUrl;
use Amplify\Frontend\Http\Middlewares\ContactForceShippingAddressSelection;
use Amplify\Frontend\Http\Middlewares\FrontendDisabled;
use Amplify\Frontend\Providers\EventServiceProvider;
use Amplify\Frontend\Providers\ValidationServiceProvider;
use Amplify\Frontend\Providers\WidgetServiceProvider;
use Amplify\System\Cms\Models\Form;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\Honeypot\ProtectAgainstSpam;

class FrontendServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/frontend.php',
            'amplify.frontend'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../config/widget.php',
            'amplify.widget'
        );

        $this->app->register(EventServiceProvider::class);
        $this->app->register(ValidationServiceProvider::class);
        $this->app->register(WidgetServiceProvider::class);
    }

    /**
     * Bootstrap services.
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        if (! $this->app->runningInConsole()) {

            $request = $this->app->make(Request::class);

            if (! $request?->is('admin/*')) {
                Route::bind('form_code', fn (string $value) => Form::whereCode($value)->firstOrFail());

            }

            $router = $this->app->make(\Illuminate\Routing\Router::class);

            $router->middlewareGroup('frontend', [
                ProtectAgainstSpam::class,
                ContactForceShippingAddressSelection::class,
                CaptureIntendedUrl::class,
                FrontendDisabled::class
            ]);
        }
    }
}
