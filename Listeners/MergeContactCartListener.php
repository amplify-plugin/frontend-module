<?php

namespace Amplify\Frontend\Listeners;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\Frontend\Events\ContactLoggedIn;
use Amplify\System\Backend\Http\Controllers\CartController;
use Amplify\System\Backend\Models\Cart;
use Amplify\System\Jobs\CartPricingSyncJob;

class MergeContactCartListener
{
    /**
     * Handle the event.
     */
    public function handle(ContactLoggedIn $event): void
    {

        return;
        /**
         * @var  Cart $cart
         */
        $cart = Cart::where('contact_id', $event->contact->id)
            ->where('status', true)
            ->first();

        //no logged in cart
        if (!$cart) {
            $event->guestCart->update([
                'contact_id' => $event->contact->getKey(),
                'session_id' => null,
            ]);
            CartPricingSyncJob::dispatch($event->guestCart->getKey(), $event->contact->customer->shipto_address_code);
            return;
        }

        $guestCartItems = $event->guestCart?->cartItems?->toArray() ?? [];

        if (!empty($guestCartItems)) {
            $event->guestCart->cartItems()->delete();
        }

        $event->guestCart->delete();

        $contactCartItems = $cart->cartItems;

        //update quantity of existing cart items
        foreach ($contactCartItems as $cartItem) {
            foreach ($guestCartItems as $guestIndex => $guestCartItem) {
                if ($guestCartItem['product_id'] == $cartItem->product_id) {
                    $cartItem->quantity = $cartItem->quantity + ($guestCartItem['quantity'] ?? 0);
                    $cartItem->save();
                    unset($guestCartItems[$guestIndex]);
                    break;
                }
            }
        }

        //add remaining items to cart
        $cart->cartItems()->createMany($guestCartItems);

        CartPricingSyncJob::dispatch($cart->getKey(), $event->contact->customer->shipto_address_code);
    }
}
