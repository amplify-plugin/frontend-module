<?php

namespace Amplify\Frontend\Listeners;

use Amplify\Frontend\Events\ContactLoggedIn;
use Amplify\Frontend\Events\ContactLoggedOut;
use Amplify\System\Backend\Models\ContactLogin;

class UpdateContactLoginListener
{
    /**
     * Handle the event.
     */
    public function handle(ContactLoggedIn|ContactLoggedOut $event): void
    {
        if ($event instanceof ContactLoggedIn) {
            ContactLogin::startSession($event->contact, $event->initiatedBy);

            return;
        }

        ContactLogin::endSession();
    }
}
