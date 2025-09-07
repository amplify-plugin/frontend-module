<?php

namespace Amplify\Frontend\Listeners;

use Amplify\Frontend\Events\ContactLoggedIn;
use Amplify\Frontend\Events\ContactLoggedOut;
use App\Models\ContactLogin;
use Illuminate\Support\Facades\Schema;

class UpdateContactLoginDataListener
{
    /**
     * Handle the event.
     */
    public function handle(ContactLoggedIn|ContactLoggedOut $event): void
    {
        if (Schema::hasTable('contact_logins')) {
            $contact_id = $event->contact->id;
            $customer_id = $event->contact->customer_id;

            if (config('amplify.basic.enable_multi_customer_manage', false) && $event->contact->active_customer_id != null) {
                $customer_id = $event->contact->active_customer_id;
            }

            $login = ContactLogin::where([
                'contact_id' => $contact_id,
                'customer_id' => $customer_id,
            ])->first();

            if ($login) {
                if (empty($login->warehouse_id)) {
                    $login->warehouse_id = $event->contact->customer->warehouse_id;
                }

                if ($event instanceof ContactLoggedIn) {
                    $login->last_logged_in = now();
                    $login->save();
                }

                if ($event instanceof ContactLoggedOut) {
                    $login->last_logged_out = now();
                    $login->save();
                }
            }
        }
    }
}
