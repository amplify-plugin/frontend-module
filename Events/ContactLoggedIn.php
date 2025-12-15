<?php

namespace Amplify\Frontend\Events;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\System\Backend\Http\Controllers\CartController;
use Amplify\System\Backend\Models\Cart;
use Amplify\System\Backend\Models\Contact;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactLoggedIn
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Contact $contact;

    public ?Cart $guestCart;

    /**
     * Create a new event instance.
     */
    public function __construct(Contact $contact, ?Cart $guestCart = null)
    {
        $this->contact = $contact;
        $this->guestCart = $guestCart;
        customer_permissions();
    }
}
