<?php

namespace Amplify\Frontend;

use Amplify\Frontend\Providers\EventServiceProvider;
use Amplify\System\Cms\Models\Form;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FrontendServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/Config/frontend.php',
            'amplify.frontend'
        );

        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Bootstrap services.
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');

        $this->publishes([
            __DIR__.'/resources' => public_path('vendor/frontend')
        ], 'frontend-asset');

        if (! $this->app->runningInConsole()) {

            $request = $this->app->make(Request::class);

            if (! $request?->is('admin/*')) {
                Route::bind('form_code', fn (string $value) => Form::whereCode($value)->firstOrFail());

            }
        }
    }
}
