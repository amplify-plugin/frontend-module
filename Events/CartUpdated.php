<?php

namespace Amplify\Frontend\Events;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Jobs\CartPricingSyncJob;
use Amplify\System\Backend\Models\Cart;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Cart $cart;

    /**
     * Create a new event instance.
     */
    public function __construct(Cart $cart)
    {

        $cart->sub_total = $cart->cartItems->sum('subtotal');
        $cart->tax_amount = null;
        $cart->ship_charge = null;
        $cart->total = $cart->sub_total;
        $cart->save();
        $this->cart = $cart;

        CartPricingSyncJob::dispatch($cart->getKey(), session('ship_to_address.ShipToNumber', session('ship_to_address.address_code', ErpApi::getCustomerDetail()->DefaultShipTo)));
    }
}
