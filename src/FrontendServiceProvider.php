<?php

namespace Amplify\Frontend;

use Amplify\ErpApi\Commands\Apprise\TokenRefreshCommand as AppriseTokenRefreshCommand;
use Amplify\ErpApi\Commands\Csd\TokenRefreshCommand as CsdTokenRefreshCommand;
use Amplify\ErpApi\Commands\PriceSyncCommand;
use Amplify\Frontend\Commands\CleanCartCommand;
use Amplify\Frontend\Http\Middlewares\CaptureIntendedUrl;
use Amplify\Frontend\Http\Middlewares\ContactForceShippingAddressSelection;
use Amplify\Frontend\Http\Middlewares\FrontendDisabled;
use Amplify\Frontend\Providers\EventServiceProvider;
use Amplify\Frontend\Providers\ValidationServiceProvider;
use Amplify\Frontend\Providers\WidgetServiceProvider;
use Amplify\Frontend\Store\AnalyticsBus;
use Amplify\System\Cms\Models\Form;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Cookie\Middleware\EncryptCookies;
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
        $this->app->singleton('analytics', fn() => AnalyticsBus::init());
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

        $this->registerScheduler();

        $this->registerMediaQueryMacros();

        $this->app->booted(function () {
            $types = config('amplify.cms.page_types', []);
            $types[] = [
                'code' => 'recently_viewed',
                'label' => 'Recently Viewed',
                'description' => 'Recently viewed products page',
                'middleware' => [],
                'reserved' => true,
                'url' => [
                    'type' => 'route',
                    'name' => 'frontend.recently-viewed.index',
                    'params' => '',
                ],
            ];

            config(['amplify.cms.page_types' => $types]);
        });
    }


    private function registerMediaQueryMacros(): void
    {
        $this->app->afterResolving(EncryptCookies::class, function ($middleware) {
            $middleware->disableFor(['mw', 'mh']);
        });

        Request::macro('screen', function () {

            $breakpoints = [
                'wide' => 1440, //xxl
                'desktop' => 1200, //xl
                'laptop' => 991, //lg
                'tablet' => 768, //md
                'mobile' => 567, //sm
            ];

            $width = (int)$this->cookie('mw', 0);

            foreach ($breakpoints as $name => $minWidth) {
                if ($width >= $minWidth) {
                    return $name;
                }
            }

            return 'mobile';
        });

        Request::macro('isDesktop', function () {
            return $this->screen() === 'desktop';
        });

        Request::macro('isLaptop', function () {
            return $this->screen() === 'laptop';
        });

        Request::macro('isTablet', function () {
            return $this->screen() === 'tablet';
        });

        Request::macro('isMobile', function () {
            return $this->screen() === 'mobile';
        });
    }

    private function registerScheduler()
    {
        $this->app->booted(function () {
            /**
             * @var \Illuminate\Console\Scheduling\Schedule $schedule
             */
            $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);

            $schedule->command(CleanCartCommand::class)
                ->dailyAt('03:00')
                ->withoutOverlapping()
                ->onOneServer();
        });
    }
}
