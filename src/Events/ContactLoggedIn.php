<?php

namespace Amplify\Frontend\Events;

use Amplify\System\Backend\Models\Cart;
use Amplify\System\Backend\Models\Contact;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactLoggedIn
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Contact $contact;

    public ?Cart $guestCart;

    public ?Model $initiatedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Contact $contact, ?Cart $guestCart = null, ?Model $initiatedBy = null)
    {
        $this->contact = $contact;
        $this->guestCart = $guestCart;
        $this->initiatedBy = $initiatedBy;
        customer_permissions();
    }
}
