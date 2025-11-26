<?php

namespace Amplify\Frontend\Providers;

use Amplify\Frontend\Events\ContactLoggedIn;
use Amplify\Frontend\Events\ContactLoggedOut;
use Amplify\Frontend\Listeners\UpdateContactLoginDataListener;
use Amplify\Frontend\Listeners\UpdateCustomerDataListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Validator;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ContactLoggedIn::class => [
            UpdateCustomerDataListener::class,
            UpdateContactLoginDataListener::class,
        ],
        ContactLoggedOut::class => [
            UpdateContactLoginDataListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
