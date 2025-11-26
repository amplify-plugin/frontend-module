<?php
namespace Amplify\Frontend\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('clean_string', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[\pL\pN\s\'\-\+\(\)]+$/u', $value);
        });

        Validator::replacer('clean_string', function ($message, $attribute, $rule, $parameters) {
            return "The {$attribute} may only contain letters, numbers and spaces.";
        });
    }
}
