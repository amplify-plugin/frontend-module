<?php

namespace Amplify\Frontend\Providers;

use Amplify\Frontend\Events\CartUpdated;
use Amplify\Frontend\Events\ContactLoggedIn;
use Amplify\Frontend\Events\ContactLoggedOut;
use Amplify\Frontend\Listeners\MergeContactCartListener;
use Amplify\Frontend\Listeners\UpdateContactLoginListener;
use Amplify\Frontend\Listeners\UpdateCustomerListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ContactLoggedIn::class => [
            UpdateCustomerListener::class,
            UpdateContactLoginListener::class,
            MergeContactCartListener::class
        ],
        ContactLoggedOut::class => [
            UpdateContactLoginListener::class,
        ],
        CartUpdated::class => [

        ]
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
