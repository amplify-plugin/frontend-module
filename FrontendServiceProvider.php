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

        if (! $this->app->runningInConsole()) {

            $request = $this->app->make(Request::class);

            if (! $request?->is('admin/*')) {
                Route::bind('form_code', fn (string $value) => Form::whereCode($value)->firstOrFail());

                $this->linkAssetPaths();
            }
        }
    }

    private function linkAssetPaths(): void
    {
        $template = template();

        $target_path = public_path('frontend'.DIRECTORY_SEPARATOR.$template->asset_folder);

        $asset_path = base_path('templates'.DIRECTORY_SEPARATOR.$template->component_folder.DIRECTORY_SEPARATOR.'public');

        if (! file_exists($asset_path)) {
            mkdir($asset_path, 0666, true);
        }

        if (! is_dir($target_path)) {
            if (PHP_OS_FAMILY === 'Windows') {
                exec('mklink /J '.escapeshellarg($target_path).' '.escapeshellarg($asset_path));
            } else {
                symlink($asset_path, $target_path);
            }
        }
    }
}
