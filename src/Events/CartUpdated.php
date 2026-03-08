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
     * @param Cart $cartModel
     * @param OrderTotal|null $orderTotal
     */
    public function __construct(Cart $cart, ?OrderTotal $orderTotal = null)
    {
        $cartModel = Cart::find($cart->getKey());

        $cartModel->sub_total = $orderTotal?->TotalLineAmount ?? $cartModel->cartItems->sum('subtotal');
        $cartModel->tax_amount = $orderTotal?->SalesTaxAmount ?? null;
        $cartModel->ship_charge = $orderTotal?->FreightAmount ?? null;
        $cartModel->total = $orderTotal?->TotalOrderValue ?? $cartModel->sub_total;

        $cartModel->save();

        $this->cart = $cartModel;

//        if ($orderTotal == null & $this->cart->total > 0) {
//            CartPricingSyncJob::dispatch($cart->getKey(), session('ship_to_address.ShipToNumber', session('ship_to_address.address_code', ErpApi::getCustomerDetail()->DefaultShipTo)));
//        }
    }
}
