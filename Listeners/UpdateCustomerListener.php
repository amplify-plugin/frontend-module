<?php

namespace Amplify\Frontend\Listeners;

use Amplify\ErpApi\Jobs\CustomerProfileSyncJob;
use Amplify\Frontend\Events\ContactLoggedIn;

class UpdateCustomerListener
{
    /**
     * Handle the event.
     */
    public function handle(ContactLoggedIn $event): void
    {
        CustomerProfileSyncJob::dispatch(['customer_id' => $event->contact->customer->id]);
    }
}
