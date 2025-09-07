<?php

namespace Amplify\Frontend\Events;

use Amplify\ErpApi\Facades\ErpApi;
use App\Http\Controllers\CartController;
use App\Models\Cart;
use App\Models\Contact;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactLoggedIn
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Contact $contact;

    private ?Cart $guestCart;

    private Cart $cart;

    /**
     * Create a new event instance.
     */
    public function __construct(Contact $contact, ?Cart $guestCart = null)
    {
        $this->contact = $contact;
        $this->guestCart = $guestCart;
        $this->cart = Cart::firstOrCreate(['contact_id' => $this->contact->id, 'status' => true])->load('cartItems');

        $this->mergeProductsToCart();
        customer_permissions();
    }

    private function mergeProductsToCart()
    {
        $guestCartItems = $this->guestCart?->cartItems ?? [];
        $cartItems = $this->cart?->cartItems ?? [];

        foreach ($guestCartItems as $cartItem) {
            if ($cartItems->where('product_id', $cartItem->product_id)->count() > 0) {
                continue;
            }

            if ($cartItem->source_type == 'QUOTE') {
                $quotation = ErpApi::getQuotationDetail(['quote_number' => $cartItem->source]);
                $quotationItem = $quotation->QuoteDetail->where('ItemNumber', $cartItem->product_code)->first();
                $cartItem->update([
                    'cart_id' => $this->cart->id,
                    'unitprice' => $quotationItem?->ActualSellPrice,
                    'expiry_date' => $quotation->ExpirationDate,
                    'additional_info' => [
                        'minimum_quantity' => $quotationItem?->QuantityOrdered,
                    ],
                ]);
            } else {
                $erpProduct = CartController::getERPInfo($cartItem->product_code, $cartItem->quantity,
                    $cartItem->product_warehouse_code)->first();
                $product_price = $erpProduct?->Price ?? 0;

                for ($i = 1; $i <= 6; $i++) {
                    if (isset($erpProduct["QtyBreak_{$i}"]) && $erpProduct["QtyBreak_{$i}"] <= $cartItem->quantity) {
                        $product_price = $erpProduct["QtyPrice_{$i}"];

                        continue;
                    }

                    break;
                }

                $cartItem->update([
                    'cart_id' => $this->cart->id,
                    'quantity' => $cartItem->quantity,
                    'unitprice' => $product_price,
                ]);
            }
        }

        if ($this->guestCart) {
            $this->guestCart->refresh();

            $this->guestCart->update([
                'status' => false,
            ]);
        }
    }
}
