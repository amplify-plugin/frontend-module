<?php

namespace Amplify\Frontend\Events;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\ErpApi\Wrappers\OrderTotal;
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
     * @param Cart $cart
     * @param OrderTotal|null $orderTotal
     */
    public function __construct(Cart $cart, ?OrderTotal $orderTotal = null)
    {
        $cart->sub_total = $orderTotal?->TotalLineAmount ?? $cart->cartItems->sum('subtotal');
        $cart->tax_amount = $orderTotal?->SalesTaxAmount ?? null;
        $cart->ship_charge = $orderTotal?->FreightAmount ?? null;
        $cart->total = $orderTotal?->TotalOrderValue ?? $cart->sub_total;

        $cart->save();

        $this->cart = $cart;

        if ($orderTotal == null & $this->cart->total > 0) {
            CartPricingSyncJob::dispatch($cart->getKey(), session('ship_to_address.ShipToNumber', session('ship_to_address.address_code', ErpApi::getCustomerDetail()->DefaultShipTo)));
        }
    }
}
