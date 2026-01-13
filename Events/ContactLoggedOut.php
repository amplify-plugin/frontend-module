<?php

namespace Amplify\Frontend\Events;

use Amplify\System\Backend\Models\Contact;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactLoggedOut
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Contact $contact;

    /**
     * Create a new event instance.
     */
    public function __construct(Contact $contact, ?string $token = null)
    {
        $this->contact = $contact;

        $customer_number = $contact->customer?->erp_id ?? config('amplify.frontend.guest_default');
        @cache()->forget("getCustomerDetails-{$customer_number}");
        @cache()->forget("getCustomerShippingLocationList-{$customer_number}");

        @cache()->forget("{$token}-customer-model");
        @cache()->forget("{$token}-mobile-menu");
        @cache()->forget("{$token}-primary-menu");
        @cache()->forget("{$token}-account-menu");
        @cache()->forget("{$token}-account-sidebar");

        // Clear the ship-to address from session if it exists
        if (session()->has('ship_to_address')) {
            session()->forget('ship_to_address');
        }
    }
}
